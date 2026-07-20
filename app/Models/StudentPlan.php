<?php

namespace App\Models;

use Database\Factories\StudentPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A student's enrollment on a plan.
 *
 * Kept as its own table rather than a column on the user so that plan history
 * survives, payments have a stable thing to point at, and a trial plan has an
 * anchor for its lifetime check-in.
 *
 * @property int $id
 * @property int $user_id
 * @property int $plan_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property int|null $payment_due_day
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Plan $plan
 */
#[Fillable(['user_id', 'plan_id', 'started_at', 'ended_at', 'payment_due_day'])]
class StudentPlan extends Model
{
    /** @use HasFactory<StudentPlanFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'ended_at' => 'date',
            'payment_due_day' => 'integer',
        ];
    }

    /**
     * Get the student who owns the enrollment.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the plan being followed.
     *
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the payments raised for this enrollment.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Scope the query to enrollments that have not ended.
     *
     * @param  Builder<StudentPlan>  $query
     */
    public function scopeCurrent(Builder $query): void
    {
        $query->whereNull('ended_at');
    }

    /**
     * Determine whether this is the student's current enrollment.
     */
    public function isCurrent(): bool
    {
        return $this->ended_at === null;
    }

    /**
     * Get the day of the month the student's fee is due.
     */
    public function paymentDueDay(): int
    {
        return $this->payment_due_day ?? $this->plan->payment_due_day;
    }
}
