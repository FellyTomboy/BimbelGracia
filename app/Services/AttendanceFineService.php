<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class AttendanceFineService
{
    public const KEY_ATTENDANCE_PENALTY = 'fine.attendance_penalty_enabled';
    public const KEY_LATE_PENALTY = 'fine.late_penalty_enabled';
    public const KEY_BILLING_MODE = 'billing.mode';
    public const KEY_LATE_PENALTY_TYPE = 'fine.late_penalty_type';
    public const KEY_LATE_PENALTY_VALUE = 'fine.late_penalty_value';
    public const KEY_ATTENDANCE_PENALTY_TYPE = 'fine.attendance_penalty_type';
    public const KEY_ATTENDANCE_PENALTY_VALUE = 'fine.attendance_penalty_value';

    private function cacheKey(string $key): string
    {
        return "settings.{$key}";
    }

    public function isAttendancePenaltyEnabled(): bool
    {
        return $this->getValue(self::KEY_ATTENDANCE_PENALTY) === 'true';
    }

    public function isLatePenaltyEnabled(): bool
    {
        return $this->getValue(self::KEY_LATE_PENALTY) === 'true';
    }

    public function isBillingModeDaily(): bool
    {
        return $this->getValue(self::KEY_BILLING_MODE) === 'daily';
    }

    public function isBillingModeMonthly(): bool
    {
        return $this->getValue(self::KEY_BILLING_MODE) === 'monthly';
    }

    public function getBillingMode(): string
    {
        return $this->getValue(self::KEY_BILLING_MODE) ?: 'monthly';
    }

    public function getLatePenaltyType(): string
    {
        return $this->getValue(self::KEY_LATE_PENALTY_TYPE) ?: 'percent';
    }

    public function getLatePenaltyValue(): float
    {
        return (float) ($this->getValue(self::KEY_LATE_PENALTY_VALUE) ?: '10');
    }

    public function getAttendancePenaltyType(): string
    {
        return $this->getValue(self::KEY_ATTENDANCE_PENALTY_TYPE) ?: 'fixed';
    }

    public function getAttendancePenaltyValue(): float
    {
        return (float) ($this->getValue(self::KEY_ATTENDANCE_PENALTY_VALUE) ?: '5000');
    }

    /**
     * Calculate late penalty amount in rupiah.
     * $rate = teacher_rate per session, $lateCount = number of late sessions.
     */
    public function getLatePenaltyAmount(float $rate, int $lateCount): int
    {
        if ($lateCount <= 0 || ! $this->isLatePenaltyEnabled()) {
            return 0;
        }
        $type = $this->getLatePenaltyType();
        $value = $this->getLatePenaltyValue();
        if ($type === 'percent') {
            return (int) ($lateCount * $rate * $value / 100);
        }

        return (int) ($lateCount * $value);
    }

    /**
     * Calculate attendance penalty per attended session in rupiah.
     * $rate = parent rate per student per session (used when type is percent).
     */
    public function getAttendancePenaltyPerSession(float $rate = 0): int
    {
        $type = $this->getAttendancePenaltyType();
        $value = $this->getAttendancePenaltyValue();
        if ($type === 'percent') {
            return (int) ($rate * $value / 100);
        }

        return (int) $value;
    }

    /**
     * Get human-readable label for late penalty, e.g. "10%" or "Rp 2.000".
     */
    public function getLatePenaltyDisplayLabel(): string
    {
        $type = $this->getLatePenaltyType();
        $value = $this->getLatePenaltyValue();
        if ($type === 'percent') {
            return $value . '%';
        }

        return 'Rp ' . number_format($value);
    }

    /**
     * Get human-readable label for attendance penalty, e.g. "Rp 5.000" or "5%".
     */
    public function getAttendancePenaltyDisplayLabel(): string
    {
        $type = $this->getAttendancePenaltyType();
        $value = $this->getAttendancePenaltyValue();
        if ($type === 'percent') {
            return $value . '%';
        }

        return 'Rp ' . number_format($value);
    }

    public function invalidateCache(): void
    {
        Cache::forget($this->cacheKey(self::KEY_ATTENDANCE_PENALTY));
        Cache::forget($this->cacheKey(self::KEY_LATE_PENALTY));
        Cache::forget($this->cacheKey(self::KEY_BILLING_MODE));
        Cache::forget($this->cacheKey(self::KEY_LATE_PENALTY_TYPE));
        Cache::forget($this->cacheKey(self::KEY_LATE_PENALTY_VALUE));
        Cache::forget($this->cacheKey(self::KEY_ATTENDANCE_PENALTY_TYPE));
        Cache::forget($this->cacheKey(self::KEY_ATTENDANCE_PENALTY_VALUE));
    }

    private function getValue(string $key): ?string
    {
        $cacheKey = $this->cacheKey($key);
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        $value = \DB::table('settings')->where('key', $key)->value('value');
        Cache::put($cacheKey, $value, now()->addDays(30));
        return $value;
    }
}
