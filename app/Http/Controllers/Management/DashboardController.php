<?php

namespace App\Http\Controllers\Management;

use App\Enums\CheckInStatus;
use App\Enums\ClassSessionStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\ClassSession;
use App\Models\Payment;
use App\Models\User;
use App\Support\HiitSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private HiitSettings $settings) {}

    /**
     * Show the teacher what needs attention today.
     */
    public function __invoke(): Response
    {
        return Inertia::render('management/dashboard', [
            'today' => Date::today()->format('d/m/Y'),
            'classesToday' => $this->classesToday(),
            'checkInsToday' => $this->checkInsToday(),
            'awaitingAttendance' => $this->awaitingAttendance(),
            'studentsWithoutPlan' => $this->studentsWithoutPlan(),
            'studentsOverdue' => $this->studentsOverdue(),
            'lowFrequencyStudents' => $this->lowFrequencyStudents(),
        ]);
    }

    /**
     * List today's classes with how full they are.
     *
     * @return array<int, array{id: int, name: string, starts_at: string, ends_at: string, teacher: string, taken: int, max_students: int, available: int, status: string, is_cancelled: bool}>
     */
    private function classesToday(): array
    {
        return ClassSession::query()
            ->with('teacher')
            ->withCount(['checkIns as taken_count' => fn ($query) => $query
                ->where('status', '!=', CheckInStatus::Cancelled),
            ])
            ->whereDate('date', Date::today())
            ->orderBy('start_time')
            ->get()
            ->map(fn (ClassSession $class): array => [
                'id' => $class->id,
                'name' => $class->name,
                'starts_at' => substr($class->start_time, 0, 5),
                'ends_at' => substr($class->end_time, 0, 5),
                'teacher' => $class->teacher->name,
                'taken' => (int) $class->taken_count,
                'max_students' => $class->max_students,
                'available' => max(0, $class->max_students - (int) $class->taken_count),
                'status' => $class->status->label(),
                'is_cancelled' => $class->status === ClassSessionStatus::Cancelled,
            ])
            ->values()
            ->all();
    }

    /**
     * Count the seats taken for classes happening today.
     */
    private function checkInsToday(): int
    {
        return CheckIn::query()
            ->active()
            ->whereHas('classSession', fn ($query) => $query->whereDate('date', Date::today()))
            ->count();
    }

    /**
     * List classes that already finished but still have nobody marked.
     *
     * @return array<int, array{id: int, name: string, date: string, starts_at: string, pending: int}>
     */
    private function awaitingAttendance(): array
    {
        return ClassSession::query()
            ->withCount(['checkIns as pending_count' => fn ($query) => $query
                ->where('status', CheckInStatus::CheckedIn),
            ])
            ->whereNot('status', ClassSessionStatus::Cancelled)
            ->whereDate('date', '<=', Date::today())
            // whereHas rather than having(): SQLite rejects a HAVING clause on
            // a query with no aggregate, and withCount() is a subquery alias.
            ->whereHas('checkIns', fn (Builder $query) => $query
                ->where('status', CheckInStatus::CheckedIn),
            )
            ->orderByDesc('date')
            ->limit(10)
            ->get()
            ->map(fn (ClassSession $class): array => [
                'id' => $class->id,
                'name' => $class->name,
                'date' => $class->date->format('d/m'),
                'starts_at' => substr($class->start_time, 0, 5),
                'pending' => (int) $class->pending_count,
            ])
            ->values()
            ->all();
    }

    /**
     * List active students who still need a recurring plan.
     *
     * Students sign themselves up, so this is the queue the teacher works
     * through after the experimental access introduces them to the gym.
     *
     * @return array<int, array{id: int, name: string, phone: string, registered_at: string}>
     */
    private function studentsWithoutPlan(): array
    {
        return User::query()
            ->where('role', UserRole::Student)
            ->whereDoesntHave('currentStudentPlan')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn (User $student): array => [
                'id' => $student->id,
                'name' => $student->name,
                'phone' => $student->phone,
                'registered_at' => $student->created_at?->format('d/m/Y') ?? '',
            ])
            ->values()
            ->all();
    }

    /**
     * List students blocked from checking in by an unpaid fee.
     *
     * @return array<int, array{id: int, name: string, phone: string, due_date: string, amount: string}>
     */
    private function studentsOverdue(): array
    {
        // Driven from the payments side so that Payment::scopeOverdue() stays
        // the single definition of what "vencido" means.
        return Payment::query()
            ->overdue()
            ->with('user')
            ->orderBy('due_date')
            ->limit(50)
            ->get()
            ->unique('user_id')
            ->take(20)
            ->map(fn (Payment $payment): array => [
                'id' => $payment->user_id,
                'name' => $payment->user->name,
                'phone' => $payment->user->phone,
                'due_date' => $payment->due_date->format('d/m/Y'),
                'amount' => number_format((float) $payment->amount, 2, ',', '.'),
            ])
            ->values()
            ->all();
    }

    /**
     * List students who have been training less than usual lately.
     *
     * This is operational care, not a ranking: it never reaches a student's
     * screen, and it exists so the teacher can reach out.
     *
     * @return array<int, array{id: int, name: string, phone: string, attended: int}>
     */
    private function lowFrequencyStudents(): array
    {
        $since = Date::today()->subWeeks(4);
        $threshold = $this->settings->lowFrequencyThreshold();

        return User::query()
            ->where('role', UserRole::Student)
            ->whereHas('currentStudentPlan')
            ->withCount(['checkIns as attended_count' => fn ($query) => $query
                ->where('check_ins.status', CheckInStatus::Present)
                ->whereHas('classSession', fn ($inner) => $inner->whereDate('date', '>=', $since)),
            ])
            // Ordering by the alias works everywhere; HAVING on it does not on
            // SQLite. Taking the 20 quietest and then dropping anyone at or
            // above the threshold gives the same answer: if more than 20 are
            // below it, the 20 quietest are exactly the ones to show.
            ->orderBy('attended_count')
            ->limit(20)
            ->get()
            ->filter(fn (User $student): bool => (int) $student->attended_count < $threshold)
            ->map(fn (User $student): array => [
                'id' => $student->id,
                'name' => $student->name,
                'phone' => $student->phone,
                'attended' => (int) $student->attended_count,
            ])
            ->values()
            ->all();
    }
}
