<?php

namespace App\Models;

use App\Enums\PlanType;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property PlanType $type
 * @property int|null $checkins_per_week
 * @property int|null $max_students
 * @property string $price
 * @property int $payment_due_day
 * @property bool $allows_rollover
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $active_students_count Students currently on the plan; loaded by withCount().
 */
#[Fillable(['name', 'description', 'type', 'checkins_per_week', 'max_students', 'price', 'payment_due_day', 'allows_rollover', 'is_active'])]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PlanType::class,
            'checkins_per_week' => 'integer',
            'max_students' => 'integer',
            'price' => 'decimal:2',
            'payment_due_day' => 'integer',
            'allows_rollover' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the enrollments on this plan.
     *
     * @return HasMany<StudentPlan, $this>
     */
    public function studentPlans(): HasMany
    {
        return $this->hasMany(StudentPlan::class);
    }

    /**
     * Determine whether the plan grants an unbounded number of check-ins.
     */
    public function hasUnlimitedCheckIns(): bool
    {
        return $this->type === PlanType::Weekly && $this->checkins_per_week === null;
    }

    /**
     * Determine whether the plan accepts an unlimited number of students.
     */
    public function hasUnlimitedStudents(): bool
    {
        return $this->max_students === null;
    }

    /**
     * Determine whether every student place on the plan is already occupied.
     */
    public function isAtCapacity(?int $activeStudents = null): bool
    {
        if ($this->hasUnlimitedStudents()) {
            return false;
        }

        $activeStudents ??= $this->studentPlans()->whereNull('ended_at')->count();

        return $activeStudents >= $this->max_students;
    }

    /**
     * Describe the plan's allowance in words for the student.
     */
    public function allowanceLabel(): string
    {
        if ($this->type === PlanType::Trial) {
            return '1 check-in experimental';
        }

        if ($this->hasUnlimitedCheckIns()) {
            return 'Check-ins ilimitados';
        }

        return $this->checkins_per_week === 1
            ? '1 check-in por semana'
            : "{$this->checkins_per_week} check-ins por semana";
    }
}
