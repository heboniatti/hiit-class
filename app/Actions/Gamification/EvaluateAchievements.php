<?php

namespace App\Actions\Gamification;

use App\Enums\CheckInStatus;
use App\Models\Achievement;
use App\Models\CheckIn;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Support\Facades\Date;

class EvaluateAchievements
{
    /**
     * How long away from the gym counts as having been away.
     */
    private const COMEBACK_DAYS = 21;

    public function __construct(private CalculateStreak $streak) {}

    /**
     * Unlock whatever the student has just earned.
     *
     * Unlocks are persisted rather than derived so that "most recent
     * achievement" and the celebration fire exactly once. Every achievement
     * measures the student against their own history — never against anyone
     * else's.
     *
     * @return array<int, Achievement> the achievements unlocked by this call
     */
    public function handle(User $student): array
    {
        $attended = $this->attendedCount($student);
        $unlocked = [];

        foreach ($this->earnedCodes($student, $attended) as $code) {
            $achievement = Achievement::query()
                ->where('code', $code)
                ->where('is_active', true)
                ->first();

            if (! $achievement) {
                continue;
            }

            $already = UserAchievement::query()
                ->where('user_id', $student->id)
                ->where('achievement_id', $achievement->id)
                ->exists();

            if ($already) {
                continue;
            }

            UserAchievement::create([
                'user_id' => $student->id,
                'achievement_id' => $achievement->id,
                'unlocked_at' => now(),
            ]);

            $unlocked[] = $achievement;
        }

        return $unlocked;
    }

    /**
     * Work out which achievements the student now qualifies for.
     *
     * @return array<int, string>
     */
    private function earnedCodes(User $student, int $attended): array
    {
        $codes = [];

        foreach (['first_class' => 1, 'five_classes' => 5, 'ten_classes' => 10] as $code => $threshold) {
            if ($attended >= $threshold) {
                $codes[] = $code;
            }
        }

        if ($this->streak->handle($student) >= 4) {
            $codes[] = 'four_week_streak';
        }

        if ($this->hasComeBack($student)) {
            $codes[] = 'comeback';
        }

        return $codes;
    }

    /**
     * Determine whether the student is back after a long gap.
     *
     * Looks at the gap between the last two classes they attended, so it fires
     * on the return itself rather than on every class afterwards.
     */
    private function hasComeBack(User $student): bool
    {
        $dates = CheckIn::query()
            ->where('user_id', $student->id)
            ->where('check_ins.status', CheckInStatus::Present)
            ->join('class_sessions', 'class_sessions.id', '=', 'check_ins.class_session_id')
            ->orderByDesc('class_sessions.date')
            ->limit(2)
            ->pluck('class_sessions.date')
            ->map(fn (mixed $date) => Date::parse((string) $date))
            ->all();

        if (count($dates) < 2) {
            return false;
        }

        return $dates[1]->diffInDays($dates[0]) >= self::COMEBACK_DAYS;
    }

    /**
     * Count the classes the student has attended.
     */
    private function attendedCount(User $student): int
    {
        return CheckIn::query()
            ->where('user_id', $student->id)
            ->where('check_ins.status', CheckInStatus::Present)
            ->count();
    }
}
