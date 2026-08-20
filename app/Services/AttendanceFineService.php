<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class AttendanceFineService
{
    public const KEY_ATTENDANCE_PENALTY = 'fine.attendance_penalty_enabled';
    public const KEY_LATE_PENALTY = 'fine.late_penalty_enabled';

    private function cacheKey(string $key): string
    {
        return "settings.{$key}";
    }

    public function isAttendancePenaltyEnabled(): bool
    {
        return $this->getBool(self::KEY_ATTENDANCE_PENALTY);
    }

    public function isLatePenaltyEnabled(): bool
    {
        return $this->getBool(self::KEY_LATE_PENALTY);
    }

    public function invalidateCache(): void
    {
        Cache::forget($this->cacheKey(self::KEY_ATTENDANCE_PENALTY));
        Cache::forget($this->cacheKey(self::KEY_LATE_PENALTY));
    }

    private function getBool(string $key): bool
    {
        $cacheKey = $this->cacheKey($key);
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        $value = \DB::table('settings')->where('key', $key)->value('value') === 'true';
        Cache::put($cacheKey, $value, now()->addDays(30));

        return $value;
    }
}
