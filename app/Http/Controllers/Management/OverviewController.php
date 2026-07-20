<?php

namespace App\Http\Controllers\Management;

use App\Actions\Gamification\CalculateGoalProgress;
use App\Enums\CheckInStatus;
use App\Enums\ClassSessionStatus;
use App\Enums\GoalStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\ClassSession;
use App\Models\Goal;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

class OverviewController extends Controller
{
    public function __construct(private CalculateGoalProgress $progress) {}

    /**
     * Show the administrator how the gym is doing.
     */
    public function __invoke(): Response
    {
        $since = Date::today()->subDays(30);

        return Inertia::render('management/overview', [
            'period' => $since->format('d/m').' a '.Date::today()->format('d/m/Y'),
            'totals' => [
                'active_students' => User::query()
                    ->where('role', UserRole::Student)
                    ->where('status', UserStatus::Active)
                    ->count(),
                'teachers' => User::query()->where('role', UserRole::Teacher)->count(),
                'classes_held' => ClassSession::query()
                    ->where('status', ClassSessionStatus::Completed)
                    ->orWhere(fn ($query) => $query
                        ->whereDate('date', '<', Date::today())
                        ->whereNot('status', ClassSessionStatus::Cancelled),
                    )
                    ->whereDate('date', '>=', $since)
                    ->count(),
                'check_ins' => CheckIn::query()
                    ->active()
                    ->whereHas('classSession', fn ($query) => $query->whereDate('date', '>=', $since))
                    ->count(),
                'active_plans' => Plan::query()->where('is_active', true)->count(),
                'payments_pending' => Payment::query()->where('status', PaymentStatus::Pending)->count(),
                'payments_overdue' => Payment::query()->overdue()->count(),
                'attendance_rate' => $this->attendanceRate($since),
            ],
            'monthlyAttendance' => $this->monthlyAttendance(),
            'collectiveGoals' => $this->collectiveGoals(),
        ]);
    }

    /**
     * Work out what share of booked seats actually turned up.
     */
    private function attendanceRate(CarbonInterface $since): int
    {
        $marked = CheckIn::query()
            ->whereIn('check_ins.status', [CheckInStatus::Present, CheckInStatus::Absent])
            ->whereHas('classSession', fn ($query) => $query->whereDate('date', '>=', $since))
            ->count();

        if ($marked === 0) {
            return 0;
        }

        $present = CheckIn::query()
            ->where('check_ins.status', CheckInStatus::Present)
            ->whereHas('classSession', fn ($query) => $query->whereDate('date', '>=', $since))
            ->count();

        return (int) round($present / $marked * 100);
    }

    /**
     * Chart attendance over the last six months.
     *
     * @return array<int, array{label: string, value: int}>
     */
    private function monthlyAttendance(): array
    {
        $months = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Date::today()->subMonths($i);

            $months[] = [
                'label' => $month->format('m/y'),
                'value' => CheckIn::query()
                    ->where('check_ins.status', CheckInStatus::Present)
                    ->whereHas('classSession', fn ($query) => $query
                        ->whereDate('date', '>=', $month->startOfMonth())
                        ->whereDate('date', '<=', $month->endOfMonth()),
                    )
                    ->count(),
            ];
        }

        return $months;
    }

    /**
     * Summarise the group goals in flight.
     *
     * Aggregates only, exactly as the students see them.
     *
     * @return array<int, array<string, mixed>>
     */
    private function collectiveGoals(): array
    {
        return Goal::query()
            ->collective()
            ->where('status', GoalStatus::Active)
            ->orderBy('ends_on')
            ->get()
            ->map(fn (Goal $goal): array => [
                'id' => $goal->id,
                'title' => $goal->title,
                'unit' => $goal->type->unit(),
                'ends_on' => $goal->ends_on->format('d/m/Y'),
                ...$this->progress->handle($goal),
            ])
            ->values()
            ->all();
    }
}
