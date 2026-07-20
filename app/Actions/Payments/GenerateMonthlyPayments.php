<?php

namespace App\Actions\Payments;

use App\Enums\PaymentStatus;
use App\Enums\PlanType;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\StudentPlan;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;

class GenerateMonthlyPayments
{
    /**
     * Generate missing fees for enrollments that were active at the turn of the month.
     */
    public function handle(?CarbonInterface $month = null): int
    {
        $period = Date::parse(($month ?? Date::today())->toDateString())->startOfMonth();
        $periodEnd = $period->copy()->endOfMonth();
        $created = 0;

        StudentPlan::query()
            ->select(['id', 'user_id', 'plan_id', 'started_at', 'ended_at', 'payment_due_day'])
            ->with('plan:id,type,price,payment_due_day')
            ->whereDate('started_at', '<', $period)
            ->where(function (Builder $query) use ($period): void {
                $query->whereNull('ended_at')
                    ->orWhereDate('ended_at', '>=', $period);
            })
            ->whereHas('plan', fn (Builder $query) => $query->where('type', PlanType::Weekly))
            ->chunkById(200, function ($enrollments) use ($period, $periodEnd, &$created): void {
                foreach ($enrollments as $enrollment) {
                    $wasCreated = DB::transaction(function () use ($enrollment, $period, $periodEnd): bool {
                        $alreadyHasFeeForMonth = Payment::query()
                            ->where('user_id', $enrollment->user_id)
                            ->whereBetween('due_date', [$period, $periodEnd])
                            ->exists();

                        if ($alreadyHasFeeForMonth) {
                            return false;
                        }

                        $payment = Payment::query()->firstOrCreate(
                            [
                                'user_id' => $enrollment->user_id,
                                'billing_period' => $period->toDateString(),
                            ],
                            [
                                'student_plan_id' => $enrollment->id,
                                'amount' => $enrollment->plan->price,
                                'due_date' => $period->copy()->day($enrollment->paymentDueDay()),
                                'status' => PaymentStatus::Pending,
                            ],
                        );

                        if (! $payment->wasRecentlyCreated) {
                            return false;
                        }

                        AuditLog::create([
                            'auditable_type' => $payment->getMorphClass(),
                            'auditable_id' => $payment->id,
                            'user_id' => null,
                            'action' => 'payment.generated',
                            'old_values' => null,
                            'new_values' => [
                                'amount' => $payment->amount,
                                'billing_period' => $payment->billing_period->toDateString(),
                                'due_date' => $payment->due_date->toDateString(),
                                'status' => $payment->status->value,
                            ],
                        ]);

                        return true;
                    });

                    if ($wasCreated) {
                        $created++;
                    }
                }
            });

        return $created;
    }
}
