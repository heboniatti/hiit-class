<?php

namespace App\Models;

use Database\Factories\ClassScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * The recurrence template for a repeating class.
 *
 * @property int $id
 * @property string $name
 * @property int $teacher_id
 * @property array<int, int> $days_of_week
 * @property string $start_time
 * @property string $end_time
 * @property int $max_students
 * @property Carbon $starts_on
 * @property Carbon|null $ends_on
 * @property string|null $notes
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'teacher_id', 'days_of_week', 'start_time', 'end_time', 'max_students', 'starts_on', 'ends_on', 'notes', 'is_active'])]
class ClassSchedule extends Model
{
    /** @use HasFactory<ClassScheduleFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'days_of_week' => 'array',
            'max_students' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the teacher running the recurring class.
     *
     * @return BelongsTo<User, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the concrete classes materialised from this template.
     *
     * @return HasMany<ClassSession, $this>
     */
    public function classSessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }
}
