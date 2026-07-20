<?php

namespace App\Models;

use App\Enums\CheckInStatus;
use Database\Factories\CheckInFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A student's seat in a class.
 *
 * There is exactly one row per (class, student) pair, enforced by a unique
 * index: the database itself rejects a duplicate check-in. Cancelling and
 * checking in again flips this row's status; the trail lives in audit_logs.
 *
 * @property int $id
 * @property int $class_session_id
 * @property int $user_id
 * @property CheckInStatus $status
 * @property Carbon $checked_in_at
 * @property Carbon|null $cancelled_at
 * @property int|null $attendance_marked_by
 * @property Carbon|null $attendance_marked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ClassSession $classSession
 * @property-read User $user
 */
#[Fillable(['class_session_id', 'user_id', 'status', 'checked_in_at', 'cancelled_at', 'attendance_marked_by', 'attendance_marked_at'])]
class CheckIn extends Model
{
    /** @use HasFactory<CheckInFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CheckInStatus::class,
            'checked_in_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'attendance_marked_at' => 'datetime',
        ];
    }

    /**
     * Get the class the seat belongs to.
     *
     * @return BelongsTo<ClassSession, $this>
     */
    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }

    /**
     * Get the student holding the seat.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the staff member who marked attendance.
     *
     * @return BelongsTo<User, $this>
     */
    public function attendanceMarkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attendance_marked_by');
    }

    /**
     * Scope the query to rows that still hold a seat.
     *
     * @param  Builder<CheckIn>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', '!=', CheckInStatus::Cancelled);
    }

    /**
     * Scope the query to rows that spend one of the student's weekly check-ins.
     *
     * @param  Builder<CheckIn>  $query
     */
    public function scopeConsumingQuota(Builder $query): void
    {
        $query->whereNotIn('status', [CheckInStatus::Cancelled, CheckInStatus::JustifiedAbsence]);
    }
}
