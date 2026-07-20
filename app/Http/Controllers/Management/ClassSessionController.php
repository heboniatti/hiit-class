<?php

namespace App\Http\Controllers\Management;

use App\Actions\Classes\GenerateClassSessions;
use App\Enums\CheckInStatus;
use App\Enums\ClassSessionStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\ClassSessionRequest;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

class ClassSessionController extends Controller
{
    public function __construct(private GenerateClassSessions $generate) {}

    /**
     * Show the schedule, a week at a time.
     */
    public function index(Request $request): Response
    {
        $weekOffset = (int) $request->integer('week');
        $start = Date::today()->startOfWeek(CarbonInterface::MONDAY)->addWeeks($weekOffset);
        $end = $start->addDays(6);

        $classes = ClassSession::query()
            ->with('teacher')
            ->withCount(['checkIns as taken_count' => fn ($query) => $query
                ->where('status', '!=', CheckInStatus::Cancelled),
            ])
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $end)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (ClassSession $class): array => [
                'id' => $class->id,
                'name' => $class->name,
                'date' => $class->date->format('d/m'),
                'weekday' => $this->weekday($class->date->dayOfWeekIso),
                'starts_at' => substr($class->start_time, 0, 5),
                'ends_at' => substr($class->end_time, 0, 5),
                'teacher' => $class->teacher->name,
                'taken' => (int) $class->taken_count,
                'max_students' => $class->max_students,
                'status' => $class->status->label(),
                'is_cancelled' => $class->status === ClassSessionStatus::Cancelled,
                'is_recurring' => $class->class_schedule_id !== null,
            ])
            ->values();

        return Inertia::render('management/schedule', [
            'classes' => $classes,
            'week' => [
                'offset' => $weekOffset,
                'label' => $start->format('d/m').' a '.$end->format('d/m/Y'),
                'is_current' => $weekOffset === 0,
            ],
        ]);
    }

    /**
     * Show the form to create a class.
     */
    public function create(): Response
    {
        return Inertia::render('management/classes/create', [
            'teachers' => $this->teacherOptions(),
            'today' => Date::today()->toDateString(),
        ]);
    }

    /**
     * Create a one-off class or a whole recurrence.
     */
    public function store(ClassSessionRequest $request): RedirectResponse
    {
        if (! $request->isRecurring()) {
            ClassSession::create([
                'name' => $request->validated('name'),
                'date' => $request->validated('date'),
                'start_time' => $request->validated('start_time'),
                'end_time' => $request->validated('end_time'),
                'max_students' => $request->validated('max_students'),
                'teacher_id' => $request->validated('teacher_id'),
                'notes' => $request->validated('notes'),
            ]);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Aula criada.')]);

            return to_route('management.schedule');
        }

        $schedule = ClassSchedule::create([
            'name' => $request->validated('name'),
            'teacher_id' => $request->validated('teacher_id'),
            'days_of_week' => $request->daysOfWeek(),
            'start_time' => $request->validated('start_time'),
            'end_time' => $request->validated('end_time'),
            'max_students' => $request->validated('max_students'),
            'starts_on' => $request->validated('starts_on'),
            'ends_on' => $request->validated('ends_on'),
            'notes' => $request->validated('notes'),
            // Set explicitly: a database default is not applied to the model
            // instance in memory, and the generator checks this flag.
            'is_active' => true,
        ]);

        $created = $this->generate->handle($schedule);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':count aulas criadas. As próximas são geradas automaticamente.', [
                'count' => $created,
            ]),
        ]);

        return to_route('management.schedule');
    }

    /**
     * Show the form to edit one class.
     */
    public function edit(ClassSession $classSession): Response
    {
        return Inertia::render('management/classes/edit', [
            'classSession' => [
                'id' => $classSession->id,
                'name' => $classSession->name,
                'date' => $classSession->date->toDateString(),
                'start_time' => substr($classSession->start_time, 0, 5),
                'end_time' => substr($classSession->end_time, 0, 5),
                'max_students' => $classSession->max_students,
                'teacher_id' => $classSession->teacher_id,
                'status' => $classSession->status->value,
                'notes' => $classSession->notes,
                'taken' => $classSession->takenSpots(),
                'is_recurring' => $classSession->class_schedule_id !== null,
            ],
            'teachers' => $this->teacherOptions(),
            'statuses' => collect(ClassSessionStatus::cases())
                ->map(fn (ClassSessionStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ])
                ->values(),
        ]);
    }

    /**
     * Update one class.
     *
     * Only this occurrence changes, never the whole recurrence: a teacher
     * moving Friday's class does not mean moving every Friday.
     */
    public function update(ClassSessionRequest $request, ClassSession $classSession): RedirectResponse
    {
        $classSession->update([
            'name' => $request->validated('name'),
            'date' => $request->validated('date'),
            'start_time' => $request->validated('start_time'),
            'end_time' => $request->validated('end_time'),
            'max_students' => $request->validated('max_students'),
            'teacher_id' => $request->validated('teacher_id'),
            'status' => $request->validated('status') ?? $classSession->status,
            'notes' => $request->validated('notes'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Aula atualizada.')]);

        return back();
    }

    /**
     * Call off a class.
     *
     * The row is never deleted: check_ins cascades on delete, so removing a
     * class would erase every student's history of it. Cancelling frees the
     * students' check-ins (a cancelled class stops consuming quota) while the
     * record survives for the reports.
     */
    public function destroy(ClassSession $classSession): RedirectResponse
    {
        $classSession->update(['status' => ClassSessionStatus::Cancelled]);

        $classSession->checkIns()
            ->where('status', CheckInStatus::CheckedIn)
            ->update([
                'status' => CheckInStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Aula cancelada. Os check-ins foram devolvidos aos alunos.'),
        ]);

        return to_route('management.schedule');
    }

    /**
     * Get the staff who can run a class.
     *
     * @return array<int, array{value: int, label: string}>
     */
    private function teacherOptions(): array
    {
        return User::query()
            ->whereIn('role', [UserRole::Teacher, UserRole::Admin])
            ->orderBy('name')
            ->get()
            ->map(fn (User $teacher): array => [
                'value' => $teacher->id,
                'label' => $teacher->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Name the weekday in Portuguese.
     */
    private function weekday(int $isoDay): string
    {
        return match ($isoDay) {
            1 => 'Segunda',
            2 => 'Terça',
            3 => 'Quarta',
            4 => 'Quinta',
            5 => 'Sexta',
            6 => 'Sábado',
            default => 'Domingo',
        };
    }
}
