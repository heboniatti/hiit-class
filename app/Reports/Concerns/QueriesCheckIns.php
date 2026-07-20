<?php

namespace App\Reports\Concerns;

use App\Enums\UserRole;
use App\Models\CheckIn;
use App\Models\StudentPlan;
use App\Models\User;
use App\Reports\ReportFilters;
use Illuminate\Database\Eloquent\Builder;

trait QueriesCheckIns
{
    /**
     * Start a check-in query already narrowed by the report's filters.
     *
     * Joined to class_sessions rather than using whereHas, because every report
     * filters or orders by the class's date, teacher or recurrence. Columns are
     * qualified throughout: both tables carry a "status".
     *
     * @return Builder<CheckIn>
     */
    protected function checkInsQuery(ReportFilters $filters): Builder
    {
        return CheckIn::query()
            ->join('class_sessions', 'class_sessions.id', '=', 'check_ins.class_session_id')
            ->whereDate('class_sessions.date', '>=', $filters->from)
            ->whereDate('class_sessions.date', '<=', $filters->to)
            ->when($filters->studentId, fn (Builder $query, int $id) => $query
                ->where('check_ins.user_id', $id))
            ->when($filters->teacherId, fn (Builder $query, int $id) => $query
                ->where('class_sessions.teacher_id', $id))
            ->when($filters->classScheduleId, fn (Builder $query, int $id) => $query
                ->where('class_sessions.class_schedule_id', $id))
            ->when($filters->planId, fn (Builder $query, int $id) => $query
                ->whereIn('check_ins.user_id', $this->studentIdsOnPlan($id)))
            ->select('check_ins.*');
    }

    /**
     * Start a student query narrowed by the report's filters.
     *
     * @return Builder<User>
     */
    protected function studentsQuery(ReportFilters $filters): Builder
    {
        return User::query()
            ->where('role', UserRole::Student)
            ->when($filters->studentId, fn (Builder $query, int $id) => $query->whereKey($id))
            ->when($filters->planId, fn (Builder $query, int $id) => $query
                ->whereIn('id', $this->studentIdsOnPlan($id)))
            ->orderBy('name');
    }

    /**
     * Get the students currently on a plan.
     *
     * @return array<int, int>
     */
    protected function studentIdsOnPlan(int $planId): array
    {
        return StudentPlan::query()
            ->where('plan_id', $planId)
            ->whereNull('ended_at')
            ->pluck('user_id')
            ->all();
    }

    /**
     * Turn a count into a whole percentage, guarding the empty case.
     */
    protected function percentage(int $part, int $whole): int
    {
        return $whole === 0 ? 0 : (int) round($part / $whole * 100);
    }
}
