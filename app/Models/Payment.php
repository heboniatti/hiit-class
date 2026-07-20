<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $student_plan_id
 * @property Carbon|null $billing_period
 * @property string $amount
 * @property Carbon $due_date
 * @property Carbon|null $paid_at
 * @property PaymentMethod|null $method
 * @property PaymentStatus $status
 * @property string|null $notes
 * @property string|null $receipt_path
 * @property string|null $receipt_original_name
 * @property Carbon|null $receipt_uploaded_at
 * @property int|null $recorded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable(['user_id', 'student_plan_id', 'billing_period', 'amount', 'due_date', 'paid_at', 'method', 'status', 'notes', 'receipt_path', 'receipt_original_name', 'receipt_uploaded_at', 'recorded_by'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'billing_period' => 'date',
            'due_date' => 'date',
            'paid_at' => 'date',
            'receipt_uploaded_at' => 'datetime',
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
        ];
    }

    /**
     * Get the student who owes the payment.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the enrollment the payment belongs to.
     *
     * @return BelongsTo<StudentPlan, $this>
     */
    public function studentPlan(): BelongsTo
    {
        return $this->belongsTo(StudentPlan::class);
    }

    /**
     * Get the staff member who last recorded the payment.
     *
     * @return BelongsTo<User, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Scope the query to payments that are past their due date.
     *
     * "Vencido" is derived rather than stored, so there is a single source of
     * truth and no daily job that can drift.
     *
     * @param  Builder<Payment>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->where('status', PaymentStatus::Pending)
            ->whereDate('due_date', '<', Carbon::today());
    }

    /**
     * Determine whether the payment is past its due date.
     */
    public function isOverdue(): bool
    {
        return $this->status === PaymentStatus::Pending
            && $this->due_date->isBefore(Carbon::today());
    }

    /**
     * Get the label shown to staff, expanding the derived overdue state.
     */
    public function statusLabel(): string
    {
        return $this->isOverdue() ? 'Vencido' : $this->status->label();
    }
}
