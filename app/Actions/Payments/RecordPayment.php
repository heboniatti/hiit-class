<?php

namespace App\Actions\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class RecordPayment
{
    /**
     * Raise a new fee for a student.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $staff, array $attributes): Payment
    {
        return DB::transaction(function () use ($staff, $attributes): Payment {
            $payment = Payment::create([
                ...$attributes,
                'recorded_by' => $staff->id,
            ]);

            $this->log($staff, $payment, 'payment.created', null, $payment->only([
                'amount', 'due_date', 'status',
            ]));

            return $payment;
        });
    }

    /**
     * Change a fee, keeping a note of who did it.
     *
     * "Toda alteração de pagamento deve registrar quem realizou a alteração":
     * recorded_by carries the last hand to touch it, and audit_logs carries
     * every hand before that.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $staff, Payment $payment, array $attributes): Payment
    {
        return DB::transaction(function () use ($staff, $payment, $attributes): Payment {
            $before = $payment->only(['amount', 'due_date', 'paid_at', 'method', 'status']);

            $payment->update([
                ...$attributes,
                'recorded_by' => $staff->id,
            ]);

            $this->log($staff, $payment, 'payment.updated', $before, $payment->only([
                'amount', 'due_date', 'paid_at', 'method', 'status',
            ]));

            return $payment;
        });
    }

    /**
     * Settle a fee.
     *
     * This is the action that unblocks a student's check-ins, so it is the one
     * most worth being able to trace later.
     */
    public function markAsPaid(User $staff, Payment $payment, PaymentMethod $method): Payment
    {
        return $this->update($staff, $payment, [
            'status' => PaymentStatus::Paid,
            'paid_at' => Date::today(),
            'method' => $method,
        ]);
    }

    /**
     * Write the trail entry.
     *
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    private function log(User $staff, Payment $payment, string $action, ?array $old, ?array $new): void
    {
        AuditLog::create([
            'auditable_type' => $payment->getMorphClass(),
            'auditable_id' => $payment->id,
            'user_id' => $staff->id,
            'action' => $action,
            'old_values' => $this->stringify($old),
            'new_values' => $this->stringify($new),
        ]);
    }

    /**
     * Reduce values to strings so the trail stays readable as plain JSON.
     *
     * @param  array<string, mixed>|null  $values
     * @return array<string, string|null>|null
     */
    private function stringify(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        return array_map(
            fn (mixed $value): ?string => match (true) {
                $value === null => null,
                $value instanceof PaymentStatus, $value instanceof PaymentMethod => $value->value,
                // Dates as plain YYYY-MM-DD. json_encode would wrap a Carbon in
                // escaped quotes and a full timestamp, which nobody can read
                // back later.
                $value instanceof CarbonInterface => $value->toDateString(),
                is_scalar($value) => (string) $value,
                default => (string) json_encode($value),
            },
            $values,
        );
    }
}
