<?php

namespace App\Models;

use App\Enums\CheckInStatus;
use App\Enums\ClassSessionStatus;
use Database\Factories\ClassSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $class_schedule_id
 * @property string $name
 * @property Carbon $date
 * @property string $start_time
 * @property string $end_time
 * @property int $max_students
 * @property int $teacher_id
 * @property ClassSessionStatus $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $teacher
 * @property-read int|null $taken_count Seats taken; loaded by withCount() on the teacher's dashboard.
 * @property-read int|null $pending_count Students still unmarked; loaded by withCount() on the teacher's dashboard.
 * @property-read int|null $present_count Confirmed presences; loaded by withCount() in the reports.
 */
#[Fillable(['class_schedule_id', 'name', 'date', 'start_time', 'end_time', 'max_students', 'teacher_id', 'status', 'notes'])]
class ClassSession extends Model
{
    /** @use HasFactory<ClassSessionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'max_students' => 'integer',
            'status' => ClassSessionStatus::class,
        ];
    }

    /**
     * Get the recurrence template this class was materialised from.
     *
     * @return BelongsTo<ClassSchedule, $this>
     */
    public function classSchedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class);
    }

    /**
     * Get the teacher running the class.
     *
     * @return BelongsTo<User, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get every check-in row for the class, cancellations included.
     *
     * @return HasMany<CheckIn, $this>
     */
    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    /**
     * Get the check-ins that currently hold a seat.
     *
     * @return HasMany<CheckIn, $this>
     */
    public function activeCheckIns(): HasMany
    {
        return $this->checkIns()->where('status', '!=', CheckInStatus::Cancelled);
    }

    /**
     * Scope the query to classes that have not happened yet.
     *
     * @param  Builder<ClassSession>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->whereDate('date', '>=', Carbon::today())
            ->orderBy('date')
            ->orderBy('start_time');
    }

    /**
     * Get the moment the class starts, in the application timezone.
     */
    public function startsAt(): Carbon
    {
        return Carbon::parse($this->date->toDateString().' '.$this->start_time);
    }

    /**
     * Get the moment the class ends, in the application timezone.
     */
    public function endsAt(): Carbon
    {
        return Carbon::parse($this->date->toDateString().' '.$this->end_time);
    }

    /**
     * Count the seats already taken.
     */
    public function takenSpots(): int
    {
        return $this->activeCheckIns()->count();
    }

    /**
     * Count the seats still available.
     */
    public function availableSpots(): int
    {
        return max(0, $this->max_students - $this->takenSpots());
    }

    /**
     * Determine whether the class has no seats left.
     */
    public function isFull(): bool
    {
        return $this->availableSpots() === 0;
    }
}
