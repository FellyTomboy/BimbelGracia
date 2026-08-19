<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class AttendanceFineService
{
    private const CACHE_TTL = 3600; // 1 hour

    public const KEY_ATTENDANCE_PENALTY = 'fine.attendance_penalty_enabled';
    public const KEY_LATE_PENALTY = 'fine.late_penalty_enabled';

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
        Cache::forget('settings.' . self::KEY_ATTENDANCE_PENALTY);
        Cache::forget('settings.' . self::KEY_LATE_PENALTY);
    }

    private function getBool(string $key): bool
    {
        return Cache::rememberForever("settings.{$key}", function () use ($key) {
            return \DB::table('settings')->where('key', $key)->value('value') === 'true';
        });
    }
}
