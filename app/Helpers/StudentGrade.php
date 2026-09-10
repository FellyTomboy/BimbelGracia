<?php

declare(strict_types=1);

namespace App\Helpers;

class StudentGrade
{
    public const PRESCHOOL = 'Preschool';
    public const TK_A = 'TK A';
    public const TK_B = 'TK B';

    public const LEVELS = [
        self::PRESCHOOL,
        self::TK_A,
        self::TK_B,
        '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12',
    ];

    /**
     * Return the next grade level for a given current level.
     * Returns null if the student is at the top level (12) — caller should mark as lulus.
     * Returns null if the current level is unknown/null — caller should skip.
     */
    public static function nextLevel(?string $current): ?string
    {
        if ($current === null || $current === '') {
            return null;
        }

        $index = array_search($current, self::LEVELS, true);

        if ($index === false || $index === count(self::LEVELS) - 1) {
            return null;
        }

        return self::LEVELS[$index + 1];
    }

    /**
     * Options array for select dropdowns: ['' => '— Pilih kelas —', ...LEVELS].
     */
    public static function options(): array
    {
        return ['' => '— Pilih kelas —'] + array_combine(self::LEVELS, self::LEVELS);
    }
}
