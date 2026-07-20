<?php

namespace App\Models;

use Database\Factories\AchievementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $description
 * @property string $icon
 * @property int|null $threshold
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['code', 'name', 'description', 'icon', 'threshold', 'is_active'])]
class Achievement extends Model
{
    /** @use HasFactory<AchievementFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'threshold' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the unlocks of this achievement.
     *
     * @return HasMany<UserAchievement, $this>
     */
    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }
}
