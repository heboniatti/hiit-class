<?php

namespace App\Models;

use App\Enums\GoalStatus;
use App\Enums\GoalType;
use Database\Factories\GoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * An individual goal (user_id set) or a collective one (user_id null).
 *
 * @property int $id
 * @property int|null $user_id
 * @property int $created_by
 * @property string $title
 * @property string|null $description
 * @property GoalType $type
 * @property int $target_value
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 * @property GoalStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'created_by', 'title', 'description', 'type', 'target_value', 'starts_on', 'ends_on', 'status'])]
class Goal extends Model
{
    /** @use HasFactory<GoalFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => GoalType::class,
            'target_value' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'status' => GoalStatus::class,
        ];
    }

    /**
     * Get the student the goal belongs to, when it is individual.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the staff member who created the goal.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the students a collective goal is scoped to.
     *
     * An empty list means every active student takes part.
     *
     * @return BelongsToMany<User, $this>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Scope the query to collective goals.
     *
     * @param  Builder<Goal>  $query
     */
    public function scopeCollective(Builder $query): void
    {
        $query->whereNull('user_id');
    }

    /**
     * Scope the query to individual goals.
     *
     * @param  Builder<Goal>  $query
     */
    public function scopeIndividual(Builder $query): void
    {
        $query->whereNotNull('user_id');
    }

    /**
     * Determine whether the goal is shared by the group.
     */
    public function isCollective(): bool
    {
        return $this->user_id === null;
    }
}
