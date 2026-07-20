<?php

namespace App\Models;

use Database\Factories\UserAchievementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $achievement_id
 * @property Carbon $unlocked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Achievement $achievement
 */
#[Fillable(['user_id', 'achievement_id', 'unlocked_at'])]
class UserAchievement extends Model
{
    /** @use HasFactory<UserAchievementFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unlocked_at' => 'datetime',
        ];
    }

    /**
     * Get the student who unlocked the achievement.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the achievement that was unlocked.
     *
     * @return BelongsTo<Achievement, $this>
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }
}
