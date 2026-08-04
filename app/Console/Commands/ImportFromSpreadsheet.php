<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\ParentModel;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class ImportFromSpreadsheet extends Command
{
    protected $signature = 'import:spreadsheet 
        {--spreadsheet-id=1UsxOchz2rVE5NAakDhocRj6kdREJF7jbW4eM4pm64hs : Google Spreadsheet ID}
        {--default-password=pw12345678 : Default password for all users}';

    protected $description = 'Import teachers and students from Google Spreadsheet (Data Guru, Data Murid, Data Kelas)';

    private string $defaultPassword;

    public function handle(): int
    {
        $this->defaultPassword = $this->option('default-password');
        $spreadsheetId = $this->option('spreadsheet-id');

        $this->info('Starting import from Google Spreadsheet...');
        $this->newLine();

        // ── Step 1: Import Teachers ──
        $this->info('Step 1/2: Importing teachers from "Data Guru"...');
        $teachersData = $this->fetchSheet($spreadsheetId, 'Data Guru');
        $teacherCount = $this->importTeachers($teachersData);
        $this->line("  ✓ Imported {$teacherCount} teachers.");

        // ── Step 2: Import Students (Privat + Kelas) ──
        $this->info('Step 2/2: Importing students from "Data Murid" and "Data Kelas"...');
        $muridData = $this->fetchSheet($spreadsheetId, 'Data Murid');
        $kelasData = $this->fetchSheet($spreadsheetId, 'Data Kelas');
        $studentCount = $this->importStudents($muridData, $kelasData);
        $this->line("  ✓ Imported {$studentCount} student records.");

        $this->newLine();
        $this->info('✓ Import completed successfully!');

        return Command::SUCCESS;
    }

    /**
     * Fetch a sheet from Google Spreadsheet as CSV and parse it.
     */
    private function fetchSheet(string $spreadsheetId, string $sheetName): array
    {
        $url = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/gviz/tq?tqx=out:csv&sheet=" . urlencode($sheetName);

        $this->line("  Fetching sheet: {$sheetName}...");

        $response = Http::timeout(60)->get($url);

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

        $rows = array_values($rows);

        // Data Murid uses positional format (no headers)
        if ($sheetName === 'Data Murid') {
            $startIdx = 0;
            $firstCell = trim($rows[0][0] ?? '');
            $firstCellUpper = strtoupper($firstCell);
            if ($firstCellUpper === 'TOTAL' || str_contains($firstCellUpper, 'NAMA')) {
                $startIdx = 1;
            }

            $result = [];
            for ($i = $startIdx; $i < count($rows); $i++) {
                $row = array_map('trim', $rows[$i]);
                $row = array_pad($row, 10, '');
                $row = array_slice($row, 0, 10);
                if (empty(implode('', $row))) {
                    continue;
                }
                $result[] = $row;
            }

            $this->line("  Found " . count($result) . " rows (positional format).");
            return $result;
        }

        // Data Guru and Data Kelas: first row is header
        $headers = array_shift($rows);
        $headers = array_map('trim', $headers);

        $result = [];
        foreach ($rows as $row) {
            while (count($row) < count($headers)) {
                $row[] = '';
            }
            $row = array_slice($row, 0, count($headers));
            $row = array_map('trim', $row);

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
    private function importTeachers(array $data): int
    {
        $count = 0;

        foreach ($data as $row) {
            $name = $this->cleanName($row['NAMA GURU'] ?? '');
            $phone = $this->cleanPhone08($row['NO HP'] ?? '');
            $major = $row['JURUSAN'] ?? '';
            $subjects = $row['MAPEL'] ?? '';
            $address = $row['ALAMAT'] ?? '';
            $bankName = $row['NAMA BANK'] ?? '';
            $bankAccount = $row['NOMOR REKENING'] ?? '';
            $bankOwner = $row['NAMA PEMILIK REKENING'] ?? '';

            if (empty($name) || $name === 'NAMA GURU' || $name === 'TOTAL') {
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

            Teacher::query()->updateOrCreate(
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

            $count++;
        }

        return $count;
    }

    /**
     * Import students from both Data Murid and Data Kelas sheets.
     * 
     * Data Murid format: [0]=Nama, [1]=Ortu, [2]=No HP, [3]=Alamat
     * Data Kelas format: NAMA MURID, NO HP (header-based)
     * 
     * Alur:
     * 1. Kumpulkan semua nomor HP unik dari kedua sheet
     * 2. Buat User + Parent record untuk setiap nomor HP
     * 3. Buat Student record untuk setiap nama, hubungkan ke parent berdasarkan nomor HP
     */
    private function importStudents(array $muridData, array $kelasData): int
    {
        // ── Step 1: Collect all student entries ──
        // Each entry: [phone, name, address]
        $entries = [];

        // From Data Murid (positional: [0]=Nama, [1]=Ortu, [2]=No HP, [3]=Alamat)
        foreach ($muridData as $row) {
            $name = $this->cleanName($row[0] ?? '');
            $phone = $this->cleanPhone08($row[2] ?? '');
            $address = $row[3] ?? '';

            if (empty($name)) {
                continue;
            }
            if (str_contains($name, 'murid') || str_contains($name, 'NAMA') || $name === 'TOTAL') {
                continue;
            }

            // Parse names: split by comma, "dan", "and", "/"
            $parsedNames = $this->parseNameList($name);

            foreach ($parsedNames as $parsedName) {
                $parsedName = trim($parsedName);
                if (!empty($parsedName)) {
                    $entries[] = [
                        'phone' => $phone,
                        'name' => $parsedName,
                        'address' => $address,
                    ];
                }
            }
        }

        // From Data Kelas (header-based: NAMA MURID, NO HP)
        foreach ($kelasData as $row) {
            $keys = array_keys($row);
            $name = $this->cleanName($row[$keys[0]] ?? '');
            $phone = $this->cleanPhone08($row[$keys[1] ?? ''] ?? '');

            if (empty($name)) {
                continue;
            }
            if (str_contains($name, 'murid') || str_contains($name, 'NAMA') || $name === 'TOTAL') {
                continue;
            }

            $parsedNames = $this->parseNameList($name);
            foreach ($parsedNames as $parsedName) {
                $parsedName = trim($parsedName);
                if (!empty($parsedName)) {
                    $entries[] = [
                        'phone' => $phone,
                        'name' => $parsedName,
                        'address' => '',
                    ];
                }
            }
        }

        if (empty($entries)) {
            $this->warn('  No student entries found.');
            return 0;
        }

        $this->line('  Total individual student names: ' . count($entries));

        // ── Step 2: Group by phone number to identify parents ──
        $grouped = [];
        foreach ($entries as $entry) {
            $phone = $entry['phone'];
            if (!isset($grouped[$phone])) {
                $grouped[$phone] = [
                    'names' => [],
                    'addresses' => [],
                ];
            }
            $grouped[$phone]['names'][] = $entry['name'];
            if (!empty($entry['address'])) {
                $grouped[$phone]['addresses'][] = $entry['address'];
            }
        }

        $this->line('  Unique parent phone numbers: ' . count($grouped));

        // ── Step 3: Create parent records and student records ──
        $studentCount = 0;

        foreach ($grouped as $phone08 => $data) {
            // Remove duplicate names
            $names = array_unique($data['names']);
            $names = array_values(array_filter($names, fn($n) => !empty($n)));

            if (empty($names)) {
                continue;
            }

            // Pick the first address
            $address = !empty($data['addresses']) ? $data['addresses'][0] : '';

            // Create or find user (parent)
            $user = User::query()->firstOrCreate(
                ['phone' => $phone08],
                [
                    'name' => $names[0],
                    'phone' => $phone08,
                    'role' => UserRole::Parent,
                    'password' => Hash::make($this->defaultPassword),
                    'must_change_password' => true,
                ]
            );

            // Create parent record
            $parent = ParentModel::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['name' => $names[0]]
            );

            // Check if a student already exists for this parent
            $existingStudent = Student::where('parent_id', $parent->id)->first();

            if ($existingStudent) {
                // Update existing student's name to include all names
                $existingNames = array_map('trim', explode(',', $existingStudent->name));
                $allNames = array_unique(array_merge($existingNames, $names));
                $existingStudent->update([
                    'name' => implode(', ', $allNames),
                    'address' => $address ?: $existingStudent->address,
                    'status' => 'active',
                ]);
            } else {
                // Create new student record
                Student::create([
                    'parent_id' => $parent->id,
                    'name' => implode(', ', $names),
                    'address' => $address ?: null,
                    'status' => 'active',
                ]);
            }

            $studentCount++;
        }

        return $studentCount;
    }

    /**
     * Parse a name field that may contain multiple names separated by comma, "dan", "and", "/", or spaces.
     */
    private function parseNameList(string $input): array
    {
        $input = trim($input);
        if (empty($input)) {
            return [];
        }

        // Replace common separators with comma
        $input = preg_replace('/\s+(dan|and)\s+/i', ',', $input);
        $input = str_replace(['/', '&'], ',', $input);

        // Split by comma
        $parts = explode(',', $input);
        $names = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if (!empty($part)) {
                $names[] = $part;
            }
        }

        // If only one name and it has multiple words (e.g. "Alea Amelia Nazwa Rara"),
        // treat each word as a separate name
        if (count($names) === 1) {
            $words = preg_split('/\s+/', $names[0]);
            if (count($words) > 2) {
                return $words;
            }
        }

        return $names;
    }

    /**
     * Clean and normalize a name.
     */
    private function cleanName(string $name): string
    {
        $name = trim($name);
        $name = str_replace('#N/A', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);
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

        if (str_starts_with($phone, '62')) {
            $phone = '0' . substr($phone, 2);
        } elseif (!str_starts_with($phone, '0')) {
            $phone = '0' . $phone;
        }

        return $phone;
    }
}