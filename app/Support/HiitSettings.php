<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Typed access to the gym's runtime settings.
 *
 * Every reader goes through here for two reasons: PHPStan level 7 rejects
 * arithmetic on the mixed that config() and the JSON column hand back, and this
 * is the single seam where the "Configurações gerais" screen plugs in without
 * touching a single call site.
 */
class HiitSettings
{
    public const CHECKIN_OPENS_HOURS_BEFORE = 'checkin_opens_hours_before';

    public const CHECKIN_CLOSES_MINUTES_BEFORE = 'checkin_closes_minutes_before';

    public const CANCELLATION_DEADLINE_MINUTES = 'cancellation_deadline_minutes';

    public const SESSION_HORIZON_WEEKS = 'session_horizon_weeks';

    public const LOW_FREQUENCY_THRESHOLD = 'low_frequency_threshold';

    /**
     * The fallback used until a setting is stored.
     *
     * @var array<string, int>
     */
    private const DEFAULTS = [
        self::CHECKIN_OPENS_HOURS_BEFORE => 48,
        self::CHECKIN_CLOSES_MINUTES_BEFORE => 0,
        self::CANCELLATION_DEADLINE_MINUTES => 120,
        self::SESSION_HORIZON_WEEKS => 8,
        self::LOW_FREQUENCY_THRESHOLD => 2,
    ];

    /**
     * How many hours before a class the check-in window opens.
     */
    public function checkInOpensHoursBefore(): int
    {
        return $this->integer(self::CHECKIN_OPENS_HOURS_BEFORE);
    }

    /**
     * How many minutes before a class starts the check-in window closes.
     */
    public function checkInClosesMinutesBefore(): int
    {
        return $this->integer(self::CHECKIN_CLOSES_MINUTES_BEFORE);
    }

    /**
     * How many minutes before a class a student may still cancel.
     */
    public function cancellationDeadlineMinutes(): int
    {
        return $this->integer(self::CANCELLATION_DEADLINE_MINUTES);
    }

    /**
     * How many weeks ahead recurring classes are materialised.
     */
    public function sessionHorizonWeeks(): int
    {
        return $this->integer(self::SESSION_HORIZON_WEEKS);
    }

    /**
     * Weekly attendance below which a student is flagged to the teacher.
     */
    public function lowFrequencyThreshold(): int
    {
        return $this->integer(self::LOW_FREQUENCY_THRESHOLD);
    }

    /**
     * Store a setting and drop the cached copy.
     */
    public function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget($this->cacheKey($key));
    }

    /**
     * Get every setting with its current value.
     *
     * @return array<string, int>
     */
    public function all(): array
    {
        $values = [];

        foreach (array_keys(self::DEFAULTS) as $key) {
            $values[$key] = $this->integer($key);
        }

        return $values;
    }

    /**
     * Read a setting as an integer, falling back to its default.
     */
    private function integer(string $key): int
    {
        $value = Cache::rememberForever(
            $this->cacheKey($key),
            fn (): mixed => Setting::query()->where('key', $key)->value('value'),
        );

        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return self::DEFAULTS[$key];
    }

    /**
     * Get the cache key for a setting.
     */
    private function cacheKey(string $key): string
    {
        return "hiit.settings.{$key}";
    }
}
