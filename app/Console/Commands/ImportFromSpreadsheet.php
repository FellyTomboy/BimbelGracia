<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\ClassStudent;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImportFromSpreadsheet extends Command
{
    protected $signature = 'import:spreadsheet 
        {--spreadsheet-id=1UsxOchz2rVE5NAakDhocRj6kdREJF7jbW4eM4pm64hs : Google Spreadsheet ID}
        {--default-password=pw12345678 : Default password for all users}';

    protected $description = 'Import data from Google Spreadsheet (Data Guru, Data Murid, Data Les, Data Kelas) into the database';

    private string $defaultPassword;

    private array $teacherMap = [];      // teacher name => Teacher model
    private array $studentMap = [];      // student name => Student model
    private array $classStudentMap = []; // class student name => ClassStudent model
    private array $programMap = [];      // "teacherName::subject" => Program model
    private array $enrollmentMap = [];   // "teacherName::subject" => Enrollment model

    public function handle(): int
    {
        $this->defaultPassword = $this->option('default-password');
        $spreadsheetId = $this->option('spreadsheet-id');

        $this->info('Starting import from Google Spreadsheet...');
        $this->newLine();

        // ── Step 1: Import Teachers ──
        $this->info('Step 1/5: Importing teachers from "Data Guru"...');
        $teachersData = $this->fetchSheet($spreadsheetId, 'Data Guru');
        $this->importTeachers($teachersData);

        // ── Step 2: Import Private Students ──
        $this->info('Step 2/5: Importing private students from "Data Murid"...');
        $studentsData = $this->fetchSheet($spreadsheetId, 'Data Murid');
        $this->importStudents($studentsData);

        // ── Step 3: Import Class Students ──
        $this->info('Step 3/5: Importing class students from "Data Kelas"...');
        $classStudentsData = $this->fetchSheet($spreadsheetId, 'Data Kelas');
        $this->importClassStudents($classStudentsData);

        // ── Step 4: Create Programs & Enrollments ──
        $this->info('Step 4/5: Creating programs and enrollments from "Data Les"...');
        $lesData = $this->fetchSheet($spreadsheetId, 'Data Les');
        $this->createProgramsAndEnrollments($lesData);

        // ── Step 5: Assign students to enrollments ──
        $this->info('Step 5/5: Assigning students to enrollments...');
        $this->assignStudentsToEnrollments($lesData);

        $this->newLine();
        $this->info('✓ Import completed successfully!');
        $this->table(
            ['Type', 'Count'],
            [
                ['Teachers', count($this->teacherMap)],
                ['Private Students', count($this->studentMap)],
                ['Class Students', count($this->classStudentMap)],
                ['Programs', count($this->programMap)],
                ['Enrollments', count($this->enrollmentMap)],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Fetch a sheet from Google Spreadsheet as CSV and parse it.
     */
    private function fetchSheet(string $spreadsheetId, string $sheetName): array
    {
        $url = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/gviz/tq?tqx=out:csv&sheet=" . urlencode($sheetName);

        $this->line("  Fetching sheet: {$sheetName}...");

        $response = Http::timeout(30)->get($url);

        if (!$response->successful()) {
            $this->warn("  Warning: Could not fetch sheet '{$sheetName}'. HTTP {$response->status()}");
            return [];
        }

        $csvContent = $response->body();

        // Handle BOM
        $csvContent = preg_replace('/^\xEF\xBB\xBF/', '', $csvContent);

        // Parse CSV
        $rows = array_map('str_getcsv', explode("\n", $csvContent));
        $rows = array_filter($rows, fn($row) => !empty(array_filter($row)));

        if (empty($rows)) {
            $this->warn("  Warning: Sheet '{$sheetName}' is empty.");
            return [];
        }

        // First row is header
        $headers = array_shift($rows);
        $headers = array_map('trim', $headers);

        $result = [];
        foreach ($rows as $row) {
            // Pad row to match header count
            while (count($row) < count($headers)) {
                $row[] = '';
            }
            $row = array_slice($row, 0, count($headers));
            $row = array_map('trim', $row);

            // Skip empty rows
            if (empty(implode('', $row))) {
                continue;
            }

            $result[] = array_combine($headers, $row);
        }

        $this->line("  Found " . count($result) . " rows.");

        return $result;
    }

    /**
     * Import teachers from the Data Guru sheet.
     * Expected columns: NAMA GURU, NO HP, JURUSAN, MAPEL, ALAMAT, NAMA BANK, NOMOR REKENING, NAMA PEMILIK REKENING
     */
    private function importTeachers(array $data): void
    {
        $count = 0;

        foreach ($data as $row) {
            $name = $this->cleanName($row['NAMA GURU'] ?? '');
            $phone = $this->cleanPhone($row['NO HP'] ?? '');
            $major = $row['JURUSAN'] ?? '';
            $subjects = $row['MAPEL'] ?? '';
            $address = $row['ALAMAT'] ?? '';
            $bankName = $row['NAMA BANK'] ?? '';
            $bankAccount = $row['NOMOR REKENING'] ?? '';
            $bankOwner = $row['NAMA PEMILIK REKENING'] ?? '';

            if (empty($name) || $name === 'NAMA GURU' || $name === 'TOTAL') {
                continue;
            }

            // Skip if already imported
            if (isset($this->teacherMap[$name])) {
                continue;
            }

            $phone08 = $this->cleanPhone08($phone);

            $user = User::query()->firstOrCreate(
                ['phone' => $phone08],
                [
                    'name' => $name,
                    'phone' => $phone08,
                    'role' => UserRole::Guru,
                    'password' => Hash::make($this->defaultPassword),
                    'must_change_password' => true,
                ]
            );

            $teacher = Teacher::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $name,
                    'whatsapp' => $phone08,
                    'whatsapp_number' => $phone08,
                    'major' => $major,
                    'subjects' => $subjects,
                    'address' => $address ?: null,
                    'bank_name' => $bankName ?: null,
                    'bank_account' => $bankAccount ?: null,
                    'bank_owner' => $bankOwner ?: $bankOwner,
                    'class_rate' => 0,
                    'status' => 'active',
                ]
            );

            $this->teacherMap[$name] = $teacher;
            $count++;
        }

        $this->line("  ✓ Imported {$count} teachers.");
    }

    /**
     * Import private students from the Data Murid sheet.
     * Expected columns: Nama Murid, Nama Ortu/Wali, No HP, Alamat
     */
    private function importStudents(array $data): void
    {
        $count = 0;

        foreach ($data as $row) {
            $keys = array_keys($row);
            $name = $this->cleanName($row[$keys[0]] ?? '');
            $phone = $this->cleanPhone($row[$keys[2] ?? ''] ?? '');
            $address = $row[$keys[3] ?? ''] ?? '';

            if (empty($name)) {
                continue;
            }

            // Skip header-like rows
            if (str_contains($name, 'murid') || str_contains($name, 'NAMA')) {
                continue;
            }

            // Skip if already imported
            $normalizedName = $this->normalizeStudentName($name);
            if (isset($this->studentMap[$normalizedName])) {
                continue;
            }

            $phone08 = $this->cleanPhone08($phone);

            $user = User::query()->firstOrCreate(
                ['phone' => $phone08],
                [
                    'name' => $name,
                    'phone' => $phone08,
                    'role' => UserRole::Murid,
                    'password' => Hash::make($this->defaultPassword),
                    'must_change_password' => true,
                ]
            );

            $student = Student::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $name,
                    'whatsapp' => $phone08,
                    'whatsapp_primary' => $phone08,
                    'whatsapp_secondary' => $phone08,
                    'address' => $address ?: null,
                    'status' => 'active',
                ]
            );

            $this->studentMap[$normalizedName] = $student;
            $count++;
        }

        $this->line("  ✓ Imported {$count} private students.");
    }

    /**
     * Import class students from the Data Kelas sheet.
     * Expected columns: NAMA MURID, NO HP
     */
    private function importClassStudents(array $data): void
    {
        $count = 0;

        foreach ($data as $row) {
            $keys = array_keys($row);
            $name = $this->cleanName($row[$keys[0]] ?? '');
            $phone = $this->cleanPhone($row[$keys[1] ?? ''] ?? '');

            if (empty($name)) {
                continue;
            }

            // Skip header-like rows
            if (str_contains($name, 'murid') || str_contains($name, 'NAMA') || $name === 'TOTAL') {
                continue;
            }

            // Skip if already imported
            $normalizedName = $this->normalizeStudentName($name);
            if (isset($this->classStudentMap[$normalizedName])) {
                continue;
            }

            $phone08 = $this->cleanPhone08($phone);

            $classStudent = ClassStudent::query()->updateOrCreate(
                ['name' => $name],
                [
                    'whatsapp_primary' => $phone08,
                    'whatsapp_secondary' => $phone08,
                    'rate_per_meeting' => 0,
                    'status' => 'active',
                    'notes' => 'Diimpor dari spreadsheet Data Kelas',
                ]
            );

            $this->classStudentMap[$normalizedName] = $classStudent;
            $count++;
        }

        $this->line("  ✓ Imported {$count} class students.");
    }

    /**
     * Create programs and enrollments from the Data Les sheet.
     * Expected columns: NAMA PENGAJAR, NAMA SISWA, BIAYA ORTU PER/PERTEMUAN, GAJI GURU PER PERT
     */
    private function createProgramsAndEnrollments(array $data): void
    {
        $programCount = 0;
        $enrollmentCount = 0;

        foreach ($data as $row) {
            $teacherName = $this->cleanName($row['NAMA PENGAJAR'] ?? '');
            $studentName = $this->cleanName($row['NAMA SISWA'] ?? '');
            $parentRate = $this->parseRate($row['BIAYA ORTU PER/PERTEMUAN'] ?? '');
            $teacherRate = $this->parseRate($row['GAJI GURU PER PERT'] ?? '');

            if (empty($teacherName) || empty($studentName)) {
                continue;
            }

            // Find or create teacher
            $teacher = $this->findOrCreateTeacher($teacherName);
            if (!$teacher) {
                continue;
            }

            // Determine subject from teacher's subjects or use a default
            $subject = $this->determineSubject($teacher, $studentName);

            // Create program if not exists
            $programKey = $teacherName . '::' . $subject;
            if (!isset($this->programMap[$programKey])) {
                $program = Program::query()->updateOrCreate(
                    ['name' => "Privat {$subject} - {$teacherName}"],
                    [
                        'type' => 'privat',
                        'subject' => $subject,
                        'description' => "Les privat {$subject} dengan {$teacherName}",
                        'default_parent_rate' => $parentRate,
                        'default_teacher_rate' => $teacherRate,
                        'status' => 'active',
                    ]
                );
                $this->programMap[$programKey] = $program;
                $programCount++;
            }

            $program = $this->programMap[$programKey];

            // Create enrollment if not exists
            if (!isset($this->enrollmentMap[$programKey])) {
                $enrollment = Enrollment::query()->updateOrCreate(
                    [
                        'program_id' => $program->id,
                        'teacher_id' => $teacher->id,
                    ],
                    [
                        'parent_rate' => $parentRate,
                        'teacher_rate' => $teacherRate,
                        'validation_status' => 1,
                        'status' => 'active',
                    ]
                );
                $this->enrollmentMap[$programKey] = $enrollment;
                $enrollmentCount++;
            } else {
                // Update rates if this row has higher values
                $enrollment = $this->enrollmentMap[$programKey];
                if ($parentRate > $enrollment->parent_rate) {
                    $enrollment->update(['parent_rate' => $parentRate]);
                }
                if ($teacherRate > $enrollment->teacher_rate) {
                    $enrollment->update(['teacher_rate' => $teacherRate]);
                }
            }
        }

        $this->line("  ✓ Created {$programCount} programs and {$enrollmentCount} enrollments.");
    }

    /**
     * Assign students to enrollments based on les data.
     */
    private function assignStudentsToEnrollments(array $data): void
    {
        $assignmentCount = 0;

        foreach ($data as $row) {
            $teacherName = $this->cleanName($row['NAMA PENGAJAR'] ?? '');
            $studentName = $this->cleanName($row['NAMA SISWA'] ?? '');

            if (empty($teacherName) || empty($studentName)) {
                continue;
            }

            $teacher = $this->teacherMap[$teacherName] ?? null;
            if (!$teacher) {
                continue;
            }

            $subject = $this->determineSubject($teacher, $studentName);
            $programKey = $teacherName . '::' . $subject;
            $enrollment = $this->enrollmentMap[$programKey] ?? null;
            if (!$enrollment) {
                continue;
            }

            // Find student - try multiple name variations
            $student = $this->findStudent($studentName);
            if (!$student) {
                $this->warn("  Warning: Student '{$studentName}' not found in database. Creating...");
                $student = $this->createStudentFromLesData($studentName);
                if (!$student) {
                    continue;
                }
            }

            // Link teacher to student
            $teacher->students()->syncWithoutDetaching([$student->id]);

            // Link student to enrollment
            $enrollment->students()->syncWithoutDetaching([$student->id]);

            $assignmentCount++;
        }

        $this->line("  ✓ Assigned {$assignmentCount} student-enrollment relationships.");
    }

    /**
     * Find a student by name with various matching strategies.
     */
    private function findStudent(string $name): ?Student
    {
        $normalizedName = $this->normalizeStudentName($name);

        // Direct match
        if (isset($this->studentMap[$normalizedName])) {
            return $this->studentMap[$normalizedName];
        }

        // Try partial match
        foreach ($this->studentMap as $key => $student) {
            $similarity = 0;
            similar_text($normalizedName, $key, $similarity);
            if ($similarity > 80) {
                return $student;
            }

            // Check if one contains the other
            if (str_contains($normalizedName, $key) || str_contains($key, $normalizedName)) {
                return $student;
            }
        }

        return null;
    }

    /**
     * Create a student from les data if not found in murid sheet.
     */
    private function createStudentFromLesData(string $name): ?Student
    {
        $normalizedName = $this->normalizeStudentName($name);

        if (isset($this->studentMap[$normalizedName])) {
            return $this->studentMap[$normalizedName];
        }

        $phone08 = '08' . rand(1000000000, 9999999999);

        $user = User::query()->firstOrCreate(
            ['phone' => $phone08],
            [
                'name' => $name,
                'phone' => $phone08,
                'role' => UserRole::Murid,
                'password' => Hash::make($this->defaultPassword),
                'must_change_password' => true,
            ]
        );

        $student = Student::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $name,
                'status' => 'active',
            ]
        );

        $this->studentMap[$normalizedName] = $student;

        return $student;
    }

    /**
     * Find or create a teacher by name.
     */
    private function findOrCreateTeacher(string $name): ?Teacher
    {
        if (isset($this->teacherMap[$name])) {
            return $this->teacherMap[$name];
        }

        // Try to find existing teacher
        $teacher = Teacher::query()->where('name', $name)->first();
        if ($teacher) {
            $this->teacherMap[$name] = $teacher;
            return $teacher;
        }

        // Create new teacher
        $phone08 = '08' . rand(1000000000, 9999999999);

        $user = User::query()->firstOrCreate(
            ['phone' => $phone08],
            [
                'name' => $name,
                'phone' => $phone08,
                'role' => UserRole::Guru,
                'password' => Hash::make($this->defaultPassword),
                'must_change_password' => true,
            ]
        );

        $teacher = Teacher::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $name,
                'status' => 'active',
            ]
        );

        $this->teacherMap[$name] = $teacher;

        return $teacher;
    }

    /**
     * Determine subject for a teacher-student pair.
     */
    private function determineSubject(Teacher $teacher, string $studentName): string
    {
        // Check if student name contains subject hints
        $subjectHints = [
            'bhs inggris' => 'Bahasa Inggris',
            'inggris' => 'Bahasa Inggris',
            'mandarin' => 'Bahasa Mandarin',
            'mat' => 'Matematika',
            'matematika' => 'Matematika',
            'fisika' => 'Fisika',
            'kimia' => 'Kimia',
            'biologi' => 'Biologi',
            'ipa' => 'IPA',
            'ips' => 'IPS',
            'geografi' => 'Geografi',
            'sejarah' => 'Sejarah',
            'gambar' => 'Seni Rupa',
            'seni' => 'Seni Rupa',
            'jepang' => 'Bahasa Jepang',
            'mengaji' => 'Mengaji',
            'abk' => 'ABK',
            'sd' => 'SD',
            'tk' => 'TK',
            'smp' => 'SMP',
            'smk' => 'SMK',
            'sma' => 'SMA',
            'utbk' => 'UTBK',
        ];

        $lowerStudent = strtolower($studentName);
        foreach ($subjectHints as $hint => $subject) {
            if (str_contains($lowerStudent, $hint)) {
                return $subject;
            }
        }

        // Use teacher's subjects if available
        if ($teacher->subjects) {
            $subjects = explode(',', $teacher->subjects);
            $firstSubject = trim($subjects[0]);
            if (!empty($firstSubject)) {
                return $firstSubject;
            }
        }

        // Use teacher's major
        if ($teacher->major) {
            return $teacher->major;
        }

        return 'Umum';
    }

    /**
     * Clean and normalize a name.
     */
    private function cleanName(string $name): string
    {
        $name = trim($name);
        // Remove #N/A
        $name = str_replace('#N/A', '', $name);
        // Remove multiple spaces
        $name = preg_replace('/\s+/', ' ', $name);
        // Remove leading/trailing special chars
        $name = trim($name, " \t\n\r\0\x0B.,;:-");

        return $name;
    }

    /**
     * Clean and normalize a phone number (returns 08 format for storage).
     */
    private function cleanPhone08(string $phone): string
    {
        $phone = trim($phone);
        $phone = str_replace([' ', '-', '(', ')', '+'], '', $phone);

        // Ensure it starts with 0
        if (str_starts_with($phone, '62')) {
            $phone = '0' . substr($phone, 2);
        } elseif (!str_starts_with($phone, '0')) {
            $phone = '0' . $phone;
        }

        return $phone;
    }

    /**
     * Parse a rate value that may contain "Rp" prefix, dots, etc.
     */
    private function parseRate(string $rate): int
    {
        $rate = trim($rate);
        $rate = str_replace(['Rp', 'rp', 'RP', '.', ',', ' ', '-'], '', $rate);

        // Handle ranges like "50000-70000" - take the higher value
        if (str_contains($rate, '-')) {
            $parts = explode('-', $rate);
            $rate = end($parts);
        }

        // Handle #N/A
        if ($rate === '#N/A' || empty($rate)) {
            return 0;
        }

        return (int) $rate;
    }

    /**
     * Generate a unique email from a name.
     */
    private function generateEmail(string $name, string $type): string
    {
        $slug = Str::slug($name);
        $email = "{$slug}.{$type}@bimbelgracia.test";

        // Ensure uniqueness
        $counter = 1;
        while (User::query()->where('email', $email)->exists()) {
            $email = "{$slug}.{$type}{$counter}@bimbelgracia.test";
            $counter++;
        }

        return $email;
    }

    /**
     * Normalize student name for comparison.
     */
    private function normalizeStudentName(string $name): string
    {
        $name = $this->cleanName($name);
        return strtolower($name);
    }
}