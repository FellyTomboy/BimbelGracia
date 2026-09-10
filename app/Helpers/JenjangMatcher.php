<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Student;

class JenjangMatcher
{
    public const JENJANGS = ['TK', 'SD', 'SMP', 'SMA'];

    /**
     * Map student.grade → jenjang (TK/SD/SMP/SMA).
     * Returns null for Preschool, null kelas, or unknown values.
     */
    public static function fromGrade(?string $kelas): ?string
    {
        if ($kelas === null || $kelas === '') {
            return null;
        }

        if (in_array($kelas, ['TK A', 'TK B'], true)) {
            return 'TK';
        }

        // Preschool has no jenjang mapping — skip (will be flagged as mismatch).
        if (strcasecmp($kelas, 'Preschool') === 0) {
            return null;
        }

        if (ctype_digit($kelas)) {
            $n = (int) $kelas;
            return match (true) {
                $n >= 1 && $n <= 6  => 'SD',
                $n >= 7 && $n <= 9  => 'SMP',
                $n >= 10 && $n <= 12 => 'SMA',
                default => null,
            };
        }

        return null;
    }

    /**
     * Detect online variant from program name (substring "online").
     */
    public static function isOnline(Program $program): bool
    {
        return str_contains(strtolower((string) $program->name), 'online');
    }

    /**
     * Detect special programs (e.g., Privat Mengaji) that are independent of jenjang.
     */
    public static function isSpecialProgram(?Program $program): bool
    {
        if (! $program) {
            return false;
        }
        $name = strtolower((string) $program->name);
        return str_contains($name, 'mengaji');
    }

    /**
     * Generate expected program name from type + jenjang + online flag.
     */
    public static function expectedProgramName(string $type, string $jenjang, bool $online): string
    {
        if ($online) {
            return "Privat Online {$jenjang}";
        }
        return $type === 'kelas' ? "Kelas {$jenjang}" : "Privat {$jenjang}";
    }

    /**
     * Find the program an enrollment should be on based on its students' grades.
     * Returns null if:
     *   - students have mixed jenjang
     *   - any student has null/preschool/unknown kelas
     *   - program is "special" (mengaji)
     *   - target program not found in DB
     */
    public static function findTargetProgram(Enrollment $enrollment): ?Program
    {
        $current = $enrollment->program;
        if (! $current || self::isSpecialProgram($current)) {
            return null;
        }

        $jenjangs = $enrollment->students
            ->map(fn (Student $s) => self::fromGrade($s->kelas))
            ->unique()
            ->values()
            ->all();

        if (count($jenjangs) !== 1) {
            return null;
        }

        $jenjang = $jenjangs[0];
        if ($jenjang === null) {
            return null;
        }

        $expectedName = self::expectedProgramName(
            (string) ($enrollment->type ?? 'privat'),
            $jenjang,
            self::isOnline($current),
        );

        return Program::where('name', $expectedName)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Determine if an enrollment's program is mismatched with its students' grades.
     * An enrollment is mismatched if the current program is NOT the target program
     * (e.g., after a promotion crosses jenjang boundary, or for mixed jenjang groups).
     */
    public static function isMismatch(Enrollment $enrollment): bool
    {
        $current = $enrollment->program;
        if (! $current) {
            return true;
        }

        if (self::isSpecialProgram($current)) {
            return false;
        }

        $target = self::findTargetProgram($enrollment);

        // If target can't be determined (mixed/unknown), still consider it mismatch
        // unless current matches the only jenjang present.
        if ($target === null) {
            $jenjangs = $enrollment->students
                ->map(fn (Student $s) => self::fromGrade($s->kelas))
                ->filter()
                ->unique()
                ->values()
                ->all();
            // Single jenjang with null target means program with that jenjang doesn't exist
            if (count($jenjangs) === 1) {
                $expectedName = self::expectedProgramName(
                    (string) ($enrollment->type ?? 'privat'),
                    $jenjangs[0],
                    self::isOnline($current),
                );
                return $current->name !== $expectedName;
            }
            // Mixed or null grades → flag as mismatch for review
            return true;
        }

        return $target->id !== $current->id;
    }
}
