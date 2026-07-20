<?php

namespace App\Http\Controllers\Management;

use App\Actions\CheckIns\CalculateAvailableCheckIns;
use App\Enums\CheckInStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\StudentRequest;
use App\Models\CheckIn;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\StudentPlan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function __construct(private CalculateAvailableCheckIns $availableCheckIns) {}

    /**
     * List the students, newest sign-ups first.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        // Resolved once, so the list stays a single pass and Payment::scopeOverdue()
        // remains the only definition of "vencido".
        $overdueIds = Payment::query()->overdue()->distinct()->pluck('user_id')->all();

        $students = User::query()
            ->where('role', UserRole::Student)
            ->when($search !== '', fn ($query) => $query
                ->where(fn ($inner) => $inner
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', '%'.User::normalizePhone($search).'%'),
                ),
            )
            ->with('currentStudentPlan.plan')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (User $student): array => [
                'id' => $student->id,
                'name' => $student->name,
                'phone' => $student->phone,
                'status' => $student->status->label(),
                'is_active' => $student->status === UserStatus::Active,
                'plan_name' => $student->currentStudentPlan?->plan->name,
                'has_overdue' => in_array($student->id, $overdueIds, true),
                'registered_at' => $student->created_at?->format('d/m/Y') ?? '',
            ])
            ->values();

        return Inertia::render('management/students/index', [
            'students' => $students,
            'search' => $search,
        ]);
    }

    /**
     * Show the form to add a student by hand.
     *
     * Students can sign themselves up, but a teacher still needs this for
     * someone standing at the desk without a phone.
     */
    public function create(): Response
    {
        return Inertia::render('management/students/create', [
            'plans' => $this->planOptions(),
        ]);
    }

    /**
     * Store a student created by staff.
     */
    public function store(StudentRequest $request): RedirectResponse
    {
        $student = DB::transaction(function () use ($request): User {
            $student = User::create([
                ...$request->studentAttributes(),
                'password' => $request->validated('password'),
                'role' => UserRole::Student,
            ]);

            $this->syncPlan($student, $request->integer('plan_id') ?: null);

            return $student;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Aluno cadastrado.')]);

        return to_route('management.students.show', $student);
    }

    /**
     * Show one student's profile.
     */
    public function show(User $student): Response
    {
        abort_unless($student->role === UserRole::Student, 404);

        $student->load('currentStudentPlan.plan')->makeVisible('notes');
        $studentPlan = $student->currentStudentPlan;

        $history = $student->checkIns()
            ->with('classSession')
            ->whereNot('check_ins.status', CheckInStatus::CheckedIn)
            ->join('class_sessions', 'class_sessions.id', '=', 'check_ins.class_session_id')
            ->orderByDesc('class_sessions.date')
            ->select('check_ins.*')
            ->limit(20)
            ->get()
            ->map(fn (CheckIn $checkIn): array => [
                'id' => $checkIn->id,
                'name' => $checkIn->classSession->name,
                'date' => $checkIn->classSession->date->format('d/m/Y'),
                'status' => $checkIn->status->label(),
            ])
            ->values();

        return Inertia::render('management/students/show', [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'phone' => $student->phone,
                'email' => $student->email,
                'status' => $student->status->value,
                'is_active' => $student->status === UserStatus::Active,
                'notes' => $student->notes,
                'emergency_contact_name' => $student->emergency_contact_name,
                'emergency_contact_phone' => $student->emergency_contact_phone,
                'registered_at' => $student->created_at?->format('d/m/Y') ?? '',
                'plan_id' => $studentPlan?->plan_id,
                'plan_name' => $studentPlan?->plan->name,
                'plan_started_at' => $studentPlan?->started_at->format('d/m/Y'),
                'available_check_ins' => $studentPlan
                    ? $this->availableCheckIns->forWeek($studentPlan)
                    : null,
                'has_plan' => $studentPlan !== null,
                'attended_last_month' => $student->checkIns()
                    ->where('check_ins.status', CheckInStatus::Present)
                    ->whereHas('classSession', fn ($query) => $query
                        ->whereDate('date', '>=', Date::today()->subWeeks(4)),
                    )
                    ->count(),
            ],
            'history' => $history,
            'plans' => $this->planOptions($studentPlan?->plan_id),
        ]);
    }

    /**
     * Update a student's details and plan.
     */
    public function update(StudentRequest $request, User $student): RedirectResponse
    {
        abort_unless($student->role === UserRole::Student, 404);

        DB::transaction(function () use ($request, $student): void {
            $student->update($request->studentAttributes());

            $this->syncPlan($student, $request->integer('plan_id') ?: null);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Aluno atualizado.')]);

        return back();
    }

    /**
     * Get the plans a student can be put on.
     *
     * @return array<int, array{value: int, label: string, disabled: bool}>
     */
    private function planOptions(?int $currentPlanId = null): array
    {
        return Plan::query()
            ->where('is_active', true)
            ->withCount(['studentPlans as active_students_count' => fn ($query) => $query
                ->whereNull('ended_at'),
            ])
            ->orderBy('name')
            ->get()
            ->map(function (Plan $plan) use ($currentPlanId): array {
                $activeStudents = (int) $plan->active_students_count;
                $capacity = $plan->max_students === null
                    ? 'ilimitado'
                    : "{$activeStudents}/{$plan->max_students} alunos";

                return [
                    'value' => $plan->id,
                    'label' => $plan->name.' — '.$plan->allowanceLabel()." — {$capacity}",
                    'disabled' => $plan->id !== $currentPlanId
                        && $plan->isAtCapacity($activeStudents),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Move the student onto a plan, closing the previous enrollment.
     *
     * The old enrollment is ended rather than deleted so that plan history and
     * the trial's lifetime check-in keep their anchor.
     */
    private function syncPlan(User $student, ?int $planId): void
    {
        $current = $student->currentStudentPlan()->first();

        if ($planId === null || $current?->plan_id === $planId) {
            return;
        }

        $plan = Plan::query()
            ->whereKey($planId)
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();

        if ($plan === null || $plan->isAtCapacity()) {
            throw ValidationException::withMessages([
                'plan_id' => 'Este plano atingiu o limite de alunos.',
            ]);
        }

        $current?->update(['ended_at' => Date::today()]);

        StudentPlan::create([
            'user_id' => $student->id,
            'plan_id' => $planId,
            'started_at' => Date::today(),
        ]);
    }
}
