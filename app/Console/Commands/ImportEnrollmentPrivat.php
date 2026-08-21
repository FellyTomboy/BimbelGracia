<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportEnrollmentPrivat extends Command
{
    protected $signature = 'import:enrollment-privat
        {--spreadsheet-id=1zhNdJkE8gc0V3DxbKF2P7BvZcNY7GO0s7ukTVRCpiD0 : Google Spreadsheet ID}
        {--sheet-name=Enrollment Privat : Sheet name to import}';

    protected $description = 'Import enrollment data from Google Spreadsheet "Enrollment Privat" sheet into the database';

    public function handle(): int
    {
        $spreadsheetId = $this->option('spreadsheet-id');
        $sheetName    = $this->option('sheet-name');

        $this->info("Starting import from Google Spreadsheet...");
        $this->info("Spreadsheet ID : {$spreadsheetId}");
        $this->info("Sheet Name     : {$sheetName}");
        $this->newLine();

        $rawRows = $this->fetchSheet($spreadsheetId, $sheetName);

        if (empty($rawRows)) {
            $this->error('No data found in the sheet. Aborting.');
            return Command::FAILURE;
        }

        $this->line('Parsed ' . count($rawRows) . ' rows from sheet.');
        $this->newLine();

        // ── Build lookup maps (case-insensitive) ──
        $existingPrograms = Program::withTrashed()
            ->get()
            ->keyBy(fn($p) => strtolower(trim($p->name)));

        $existingTeachers = Teacher::withTrashed()
            ->get()
            ->keyBy(fn($t) => strtolower(trim($t->name)));

        $existingStudents = Student::withTrashed()
            ->get()
            ->keyBy(fn($s) => strtolower(trim($s->nickname ?: $s->full_name ?: '')));

        // ── Enrollment result tracking ──
        $results = [
            'imported'  => [],
            'updated'   => [],
            'skipped'   => [],
        ];

        $bar = $this->output->createProgressBar(count($rawRows));
        $bar->start();

        foreach ($rawRows as $row) {
            $bar->advance();

            // Parse positional columns
            $studentName = $this->cleanName($row[0] ?? '');
            $programRaw  = trim($row[1] ?? '');
            $frequency   = $this->parseFrequency($row[2] ?? '');
            $parentRate  = $this->parseMoney($row[3] ?? '');
            $teacherName = $this->cleanName($row[4] ?? '');
            $teacherRate = $this->parseMoney($row[5] ?? '');

            // Skip empty or header rows
            if ($this->isSkippable($studentName, $programRaw, $teacherName)) {
                $results['skipped'][] = [
                    'student'  => $studentName ?: '(empty)',
                    'program'  => $programRaw   ?: '(empty)',
                    'teacher'  => $teacherName  ?: '(empty)',
                    'reason'   => 'Empty or header row',
                ];
                continue;
            }

            $dbProgramName = $this->mapProgramToDbName($programRaw);
            $programKey    = strtolower($dbProgramName);
            $teacherKey = strtolower($teacherName);
            $studentKey = strtolower($studentName);

            // ── Validate: Program ──
            if (!isset($existingPrograms[$programKey])) {
                $results['skipped'][] = [
                    'student'  => $studentName,
                    'program'  => $programRaw,
                    'teacher'  => $teacherName,
                    'reason'   => "Program '{$dbProgramName}' not found in database (spreadsheet: '{$programRaw}')",
                ];
                continue;
            }
            $program = $existingPrograms[$programKey];

            // ── Validate: Teacher ──
            if (!isset($existingTeachers[$teacherKey])) {
                $results['skipped'][] = [
                    'student'  => $studentName,
                    'program'  => $programRaw,
                    'teacher'  => $teacherName,
                    'reason'   => "Guru '{$teacherName}' not found in database",
                ];
                continue;
            }
            $teacher = $existingTeachers[$teacherKey];

            // ── Validate: Student ──
            if (!isset($existingStudents[$studentKey])) {
                $results['skipped'][] = [
                    'student'  => $studentName,
                    'program'  => $programRaw,
                    'teacher'  => $teacherName,
                    'reason'   => "Murid '{$studentName}' not found in database",
                ];
                continue;
            }
            $student = $existingStudents[$studentKey];

            // ── Enrollment (no duplicates) ──
            // Guard: soft-delete any existing ACTIVE enrollment with the same key
            // to prevent duplicates from prior runs of this command.
            Enrollment::where('student_id', $student->id)
                ->where('program_id', $program->id)
                ->where('teacher_id', $teacher->id)
                ->delete();

            // Now look for a soft-deleted one to restore/update, or create fresh.
            $enrollment = Enrollment::withTrashed()
                ->where('student_id', $student->id)
                ->where('program_id', $program->id)
                ->where('teacher_id', $teacher->id)
                ->first();

            $enrollmentData = [
                'student_id'               => $student->id,
                'program_id'               => $program->id,
                'teacher_id'              => $teacher->id,
                'type'                     => 'privat',
                'parent_rate'              => $parentRate,
                'teacher_rate'             => $teacherRate,
                'agreed_sessions_per_month'=> $frequency,
                'status'                   => 'active',
                'deleted_at'               => null, // restore if was soft-deleted
            ];

            if ($enrollment) {
                $enrollment->update($enrollmentData);
                $results['updated'][] = "{$studentName} | {$programRaw} | {$teacherName}";
            } else {
                $enrollment = Enrollment::create($enrollmentData);
                $results['created'][] = "{$studentName} | {$programRaw} | {$teacherName}";
            }

            // Attach student to pivot (use the $enrollment from either path)
            $enrollment->students()->syncWithoutDetaching([$student->id]);
        }

        $bar->finish();
        $this->newLine(2);

        // ── Summary ──
        $this->info('=== Import Summary ===');
        $this->line('  Programs in DB  : ' . $existingPrograms->count());
        $this->line('  Teachers in DB  : ' . $existingTeachers->count());
        $this->line('  Students in DB  : ' . $existingStudents->count());
        $this->newLine();
        $this->info('  Imported  : ' . count($results['created']));
        $this->info('  Updated   : ' . count($results['updated']));
        $this->info('  Skipped   : ' . count($results['skipped']));

        // ── Detail: Imported ──
        if (!empty($results['created'])) {
            $this->newLine();
            $this->info('=== Newly Imported ===');
            foreach ($results['created'] as $line) {
                $this->line("  ✓ {$line}");
            }
        }

        // ── Detail: Updated ──
        if (!empty($results['updated'])) {
            $this->newLine();
            $this->info('=== Updated (already existed) ===');
            foreach ($results['updated'] as $line) {
                $this->line("  ~ {$line}");
            }
        }

        // ── Detail: Skipped ──
        if (!empty($results['skipped'])) {
            $this->newLine();
            $this->error('=== Skipped (NOT imported) ===');

            $table = [];
            foreach ($results['skipped'] as $s) {
                $table[] = [
                    $s['student'],
                    $s['program'],
                    $s['teacher'],
                    $s['reason'],
                ];
            }

            $this->table(
                ['Murid', 'Program', 'Guru', 'Alasan Tidak Diimport'],
                $table
            );

            $this->warn('  Fix the issues above in the spreadsheet or database, then run the command again.');
        } else {
            $this->newLine();
            $this->info('All rows imported successfully!');
        }

        $this->newLine();

        return $results['skipped'] ? Command::SUCCESS : Command::SUCCESS;
    }

    /**
     * Fetch a sheet from Google Spreadsheet as CSV and parse it.
     */
    private function fetchSheet(string $spreadsheetId, string $sheetName): array
    {
        $url = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/gviz/tq?tqx=out:csv&sheet=" . urlencode($sheetName);

        $this->line("Fetching sheet: {$sheetName}...");

        $response = Http::timeout(60)->get($url);

        if (!$response->successful()) {
            $this->warn("  HTTP {$response->status()} — {$response->body()}");
            return [];
        }

        $csvContent = $response->body();
        $csvContent = preg_replace('/^\xEF\xBB\xBF/', '', $csvContent);

        $rows = array_map('str_getcsv', explode("\n", $csvContent));
        $rows = array_filter($rows, fn($row) => !empty(array_filter($row)));
        $rows = array_values($rows);

        // Positional columns:
        // [0]=MURID, [1]=Program, [2]=Berapa kali seminggu,
        // [3]=BIAYA LES PER PERT, [4]=GURU, [5]=GAJI GURU per pert
        return $rows;
    }

    /**
     * Map a short program name from the spreadsheet to the full name in the database.
     */
    private function mapProgramToDbName(string $raw): string
    {
        $raw = strtoupper(trim($raw));

        return match ($raw) {
            'TK'      => 'Privat TK',
            'SD'      => 'Privat SD',
            'SMP'     => 'Privat SMP',
            'SMA'     => 'Privat SMA',
            'MENGAJI' => 'Privat Mengaji',
            default   => 'Privat ' . $raw,
        };
    }

    /**
     * Parse weekly frequency — "2X", "2x", "1x", "3X" → int
     */
    private function parseFrequency(string $value): int
    {
        $value = strtoupper(trim($value));
        $value = str_replace(['X', ' '], '', $value);
        $int   = (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
        return max(1, $int);
    }

    /**
     * Parse money string like "45.000", "50.000", "Rp 65.000" → int
     */
    private function parseMoney(string $value): int
    {
        $value = preg_replace('/[Rp\s,]/', '', trim($value));
        $value = str_replace('.', '', $value); // Indonesian thousand separator
        return (int) $value;
    }

    /**
     * Clean and normalize a name string.
     */
    private function cleanName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/^\xEF\xBB\xBF/', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        $name = trim($name, " \t\n\r\0\x0B.,;:-/");

        return $name;
    }

    /**
     * Determine if a row should be skipped (empty or header).
     */
    private function isSkippable(string $studentName, string $programRaw, string $teacherName): bool
    {
        if (empty(trim($studentName)) && empty(trim($programRaw)) && empty(trim($teacherName))) {
            return true;
        }

        $lowerStudent = strtolower($studentName);
        $headerKeywords = ['murid', 'nama', 'total', 'no'];

        foreach ($headerKeywords as $keyword) {
            if (str_contains($lowerStudent, $keyword)) {
                return true;
            }
        }

        $lowerProgram = strtolower(trim($programRaw));
        if (in_array($lowerProgram, ['program', 'jenjang', 'kelas', 'tingkat'])) {
            return true;
        }

        return false;
    }
}
