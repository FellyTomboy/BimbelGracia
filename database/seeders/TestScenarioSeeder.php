<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\MonthlyAttendance;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestScenarioSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the seeder.
     * This seeder builds on top of existing data (WebsiteDatasetSeeder).
     * It activates existing students, creates new enrollments with proper types,
     * and generates attendance records for August 2026.
     */
    public function run(): void
    {
        // ──────────────────────────────────────────────────────────
        // STEP 1: Restore & activate all existing students
        // ──────────────────────────────────────────────────────────
        Student::withTrashed()->restore();
        Student::query()->update(['status' => 'active']);

        // ──────────────────────────────────────────────────────────
        // STEP 2: Get references
        // ──────────────────────────────────────────────────────────
        $teacherHendra = Teacher::withTrashed()->find(1); // Matematika
        $teacherIndah  = Teacher::withTrashed()->find(2); // IPA/Bahasa Inggris
        $teacherJoko   = Teacher::withTrashed()->find(3); // Bahasa Inggris

        $rina  = Student::withTrashed()->find(1); // Parent 1 (Ahmad Fauzi)
        $dedi  = Student::withTrashed()->find(2); // Parent 2 (Budi Lestari)
        $sari  = Student::withTrashed()->find(3); // Parent 3 (Cahyo Nugroho)
        $farhan= Student::withTrashed()->find(4); // Parent 4 (Dewi Sartika) - kembar
        $galih = Student::withTrashed()->find(5); // Parent 4 (Dewi Sartika) - kembar
        $ayu   = Student::withTrashed()->find(6); // Parent 5 (Eko Prasetyo)
        $bambang=Student::withTrashed()->find(7); // Parent 5 (Eko Prasetyo)
        $cici  = Student::withTrashed()->find(8); // Parent 5 (Eko Prasetyo)

        $month = 8;
        $year = 2026;

        // ──────────────────────────────────────────────────────────
        // STEP 3: Deactivate old enrollments & create new ones
        // ──────────────────────────────────────────────────────────
        Enrollment::query()->update(['status' => 'hibernasi']);
        MonthlyAttendance::query()->delete();

        // ─── SCENARIO A: Privat 1-on-1, full attendance ───
        // Rina (SD) les Privat Matematika SD with Hendra, 4x/month, full attendance
        $eA = Enrollment::create([
            'program_id' => 1, // Privat Matematika SD
            'type' => 'privat',
            'teacher_id' => $teacherHendra->id,
            'parent_rate' => 100000,
            'teacher_rate' => 60000,
            'agreed_sessions_per_month' => 4,
            'validation_status' => 1,
            'status' => 'active',
        ]);
        $eA->students()->sync([$rina->id]);
        $this->createAttendance($eA, $rina, $teacherHendra, $month, $year, [3, 6, 10, 13], 'terima');

        // ─── SCENARIO B: Privat 1-on-1, attendance PENALTY (< 50%) ───
        // Dedi (SMP) les Privat Matematika SMP with Hendra, 8x/month agreed, only 3x attended
        $eB = Enrollment::create([
            'program_id' => 2, // Privat Matematika SMP
            'type' => 'privat',
            'teacher_id' => $teacherHendra->id,
            'parent_rate' => 120000,
            'teacher_rate' => 70000,
            'agreed_sessions_per_month' => 8,
            'validation_status' => 1,
            'status' => 'active',
        ]);
        $eB->students()->sync([$dedi->id]);
        $this->createAttendance($eB, $dedi, $teacherHendra, $month, $year, [3, 10, 17], 'terima');
        // Only 3 out of 8 sessions → 37.5% ≤ 50% → PENALTY Rp5.000/sesi

        // ─── SCENARIO C: Privat 1-on-1, LATE attendance for teacher ───
        // Sari (SMP) les Privat Bahasa Inggris SMP with Joko, 4x/month, 2 terlambat
        $eC = Enrollment::create([
            'program_id' => 5, // Privat Bahasa Inggris SMP
            'type' => 'privat',
            'teacher_id' => $teacherJoko->id,
            'parent_rate' => 110000,
            'teacher_rate' => 65000,
            'agreed_sessions_per_month' => 4,
            'validation_status' => 1,
            'status' => 'active',
        ]);
        $eC->students()->sync([$sari->id]);
        $this->createAttendance($eC, $sari, $teacherJoko, $month, $year, [4], 'terima');
        $this->createAttendance($eC, $sari, $teacherJoko, $month, $year, [11], 'terlambat');
        $this->createAttendance($eC, $sari, $teacherJoko, $month, $year, [18], 'terima');
        $this->createAttendance($eC, $sari, $teacherJoko, $month, $year, [25], 'terlambat');
        // 2 terlambat → 10% penalty per late session for teacher

        // ─── SCENARIO D: Privat MULTI-STUDENT (2 siblings) ───
        // Farhan & Galih (kembar, SMA) les Privat Matematika SMA with Hendra
        // Pricing tiers PER-STUDENT:
        //   Parent: 1 student = 45000, 2 students = 35000 (total 70rb utk 2)
        //   Guru:   1 student = 35000, 2 students = 30000 (total 60rb utk 2)
        $eD = Enrollment::create([
            'program_id' => 4, // Privat Matematika SMA
            'type' => 'privat',
            'teacher_id' => $teacherHendra->id,
            'parent_rate' => 45000,
            'teacher_rate' => 35000,
            'pricing_tiers' => [
                'parent_rate' => ['1' => 45000, '2' => 35000],
                'teacher_rate' => ['1' => 35000, '2' => 30000],
            ],
            'agreed_sessions_per_month' => 4,
            'validation_status' => 1,
            'status' => 'active',
        ]);
        $eD->students()->sync([$farhan->id, $galih->id]);
        // Session 1: both attend (2 students → rate 35000/30000 per student)
        $this->createMultiAttendance($eD, [$farhan, $galih], $teacherHendra, $month, $year, [4], 'terima', 35000, 30000);
        // Session 2: only Farhan (1 student → rate 45000/35000)
        $this->createMultiAttendance($eD, [$farhan], $teacherHendra, $month, $year, [11], 'terima', 45000, 35000);
        // Session 3: only Galih (1 student → rate 45000/35000)
        $this->createMultiAttendance($eD, [$galih], $teacherHendra, $month, $year, [18], 'terima', 45000, 35000);
        // Session 4: both attend (2 students → rate 35000/30000 per student)
        $this->createMultiAttendance($eD, [$farhan, $galih], $teacherHendra, $month, $year, [25], 'terima', 35000, 30000);

        // ─── SCENARIO E: KELAS (package billing) ───
        // Farhan (SD) ikut Kelas Olimpiade Matematika SD, 4x/month agreed
        // parent_rate = 90000 (paket), teacher_rate = 55000 (per-sesi)
        // Hadir 1 dari 4 sesi → ≤50% → bayar 50% × 90000 = 45000
        $eE = Enrollment::create([
            'program_id' => 7, // Kelas Olimpiade Matematika SD
            'type' => 'kelas',
            'teacher_id' => null, // kelas: no fixed teacher
            'parent_rate' => 90000,
            'teacher_rate' => 55000,
            'agreed_sessions_per_month' => 4,
            'validation_status' => 1,
            'status' => 'active',
        ]);
        $eE->students()->sync([$farhan->id]);
        // Session 1: taught by Indah
        $this->createKelasAttendance($eE, [$farhan], $teacherIndah, $month, $year, [5], 'terima');
        // Session 2: taught by Hendra (different teacher!)
        $this->createKelasAttendance($eE, [$farhan], $teacherHendra, $month, $year, [12], 'terlambat');
        // Session 3 & 4: no attendance (Farhan only came 2x)
        // 2 out of 4 = 50% → ≤50% → bayar 50% × 90000 = 45000

        // ─── SCENARIO F: KELAS with FULL attendance ───
        // Rina, Sari, Ayu ikut Kelas Matematika SMP, 4x/month
        // parent_rate = 90000 (paket), teacher_rate = 55000 (per-sesi)
        // All 3 students attend all 4 sessions → >50% → bayar full 90000 each
        $eF = Enrollment::create([
            'program_id' => 8, // Kelas Matematika SMP
            'type' => 'kelas',
            'teacher_id' => null,
            'parent_rate' => 90000,
            'teacher_rate' => 55000,
            'agreed_sessions_per_month' => 4,
            'validation_status' => 1,
            'status' => 'active',
        ]);
        $eF->students()->sync([$rina->id, $sari->id, $ayu->id]);
        // All 4 sessions taught by Indah
        $this->createKelasAttendance($eF, [$rina, $sari, $ayu], $teacherIndah, $month, $year, [3], 'terima');
        $this->createKelasAttendance($eF, [$rina, $sari, $ayu], $teacherIndah, $month, $year, [10], 'terima');
        $this->createKelasAttendance($eF, [$rina, $sari, $ayu], $teacherIndah, $month, $year, [17], 'terima');
        $this->createKelasAttendance($eF, [$rina, $sari, $ayu], $teacherIndah, $month, $year, [24], 'terima');
        // 4/4 = 100% > 50% → bayar full 90000 each

        // ─── SCENARIO G: Privat 1-on-1 with Joko, full attendance ───
        // Bambang (SMA) les Privat Bahasa Inggris SMA with Joko, 4x/month
        $eG = Enrollment::create([
            'program_id' => 6, // Privat Bahasa Inggris SMA
            'type' => 'privat',
            'teacher_id' => $teacherJoko->id,
            'parent_rate' => 130000,
            'teacher_rate' => 70000,
            'agreed_sessions_per_month' => 4,
            'validation_status' => 1,
            'status' => 'active',
        ]);
        $eG->students()->sync([$bambang->id]);
        $this->createAttendance($eG, $bambang, $teacherJoko, $month, $year, [6, 13, 20, 27], 'terima');

        // ─── SCENARIO H: Privat MULTI-STUDENT (2 siblings) with Indah ───
        // Bambang & Cici les Privat Matematika SMA with Indah
        // Pricing tiers PER-STUDENT (sama seperti D):
        //   Parent: 1 = 45000, 2 = 35000
        //   Guru:   1 = 35000, 2 = 30000
        $eH = Enrollment::create([
            'program_id' => 4, // Privat Matematika SMA
            'type' => 'privat',
            'teacher_id' => $teacherIndah->id,
            'parent_rate' => 45000,
            'teacher_rate' => 35000,
            'pricing_tiers' => [
                'parent_rate' => ['1' => 45000, '2' => 35000],
                'teacher_rate' => ['1' => 35000, '2' => 30000],
            ],
            'agreed_sessions_per_month' => 4,
            'validation_status' => 1,
            'status' => 'active',
        ]);
        $eH->students()->sync([$bambang->id, $cici->id]);
        // Session 1: both attend (2 → rate 35000/30000 per student)
        $this->createMultiAttendance($eH, [$bambang, $cici], $teacherIndah, $month, $year, [7], 'terima', 35000, 30000);
        // Session 2: only Bambang (1 → rate 45000/35000)
        $this->createMultiAttendance($eH, [$bambang], $teacherIndah, $month, $year, [14], 'terima', 45000, 35000);
        // Session 3: only Cici (1 → rate 45000/35000) - TERLAMBAT
        $this->createMultiAttendance($eH, [$cici], $teacherIndah, $month, $year, [21], 'terlambat', 45000, 35000);
        // Session 4: both attend (2 → rate 35000/30000 per student)
        $this->createMultiAttendance($eH, [$bambang, $cici], $teacherIndah, $month, $year, [28], 'terima', 35000, 30000);

        // ─── SCENARIO I: Privat 1-on-1 with Indah, PENALTY attendance ───
        // Ayu (SD) les Privat Matematika SD with Indah, 4x/month, only 1x attended
        $eI = Enrollment::create([
            'program_id' => 1, // Privat Matematika SD
            'type' => 'privat',
            'teacher_id' => $teacherIndah->id,
            'parent_rate' => 100000,
            'teacher_rate' => 60000,
            'agreed_sessions_per_month' => 4,
            'validation_status' => 1,
            'status' => 'active',
        ]);
        $eI->students()->sync([$ayu->id]);
        $this->createAttendance($eI, $ayu, $teacherIndah, $month, $year, [5], 'terima');
        // Only 1 out of 4 → 25% ≤ 50% → PENALTY Rp5.000/sesi

        // ─── SCENARIO J: KELAS with LOW attendance ───
        // Cici ikut Kelas Olimpiade Matematika SD, 4x/month, only 1x attended
        $eJ = Enrollment::create([
            'program_id' => 7, // Kelas Olimpiade Matematika SD
            'type' => 'kelas',
            'teacher_id' => null,
            'parent_rate' => 90000,
            'teacher_rate' => 55000,
            'agreed_sessions_per_month' => 4,
            'validation_status' => 1,
            'status' => 'active',
        ]);
        $eJ->students()->sync([$cici->id]);
        // Only 1 session attended
        $this->createKelasAttendance($eJ, [$cici], $teacherJoko, $month, $year, [8], 'terima');
        // 1/4 = 25% ≤ 50% → bayar 50% × 90000 = 45000

        $this->command->info('Test scenarios seeded successfully!');
        $this->command->info('');
        $this->command->info('=== SCENARIO SUMMARY ===');
        $this->command->info('A: Rina - Privat SD with Hendra - 4/4 sessions - FULL PRICE');
        $this->command->info('B: Dedi - Privat SMP with Hendra - 3/8 sessions - PENALTY Rp5k/sesi');
        $this->command->info('C: Sari - Privat Inggris with Joko - 4/4 sessions - 2 LATE (10% potongan)');
        $this->command->info('D: Farhan+Galih - Privat SMA with Hendra - MULTI-STUDENT tiers');
        $this->command->info('E: Farhan - KELAS Olimpiade SD - 2/4 sessions - BAYAR 50% PAKET');
        $this->command->info('F: Rina+Sari+Ayu - KELAS Matematika SMP - 4/4 sessions - BAYAR FULL');
        $this->command->info('G: Bambang - Privat Inggris with Joko - 4/4 sessions - FULL PRICE');
        $this->command->info('H: Bambang+Cici - Privat SMA with Indah - MULTI-STUDENT tiers');
        $this->command->info('I: Ayu - Privat SD with Indah - 1/4 sessions - PENALTY Rp5k/sesi');
        $this->command->info('J: Cici - KELAS Olimpiade SD - 1/4 sessions - BAYAR 50% PAKET');
        $this->command->info('');
        $this->command->info('=== TEACHER SALARY SUMMARY ===');
        $this->command->info('Hendra: A(4x60k) + B(3x70k) + D(4 sesi multi) + E(1x55k kelas)');
        $this->command->info('Indah:  F(4x55k kelas) + H(4 sesi multi) + I(1x60k)');
        $this->command->info('Joko:   C(4x65k - 2 late) + G(4x70k) + J(1x55k kelas)');
    }

    /**
     * Create attendance for a single-student privat enrollment.
     */
    private function createAttendance(
        Enrollment $enrollment,
        Student $student,
        Teacher $teacher,
        int $month,
        int $year,
        array $days,
        string $status,
    ): void {
        foreach ($days as $day) {
            $date = Carbon::create($year, $month, $day);
            $attendance = MonthlyAttendance::create([
                'enrollment_id' => $enrollment->id,
                'lesson_date' => $date,
                'month' => $month,
                'year' => $year,
                'status_validation' => $status,
                'parent_rate' => $enrollment->parent_rate,
                'teacher_rate' => $enrollment->teacher_rate,
                'created_by' => 1,
            ]);
            $attendance->students()->attach($student->id, ['total_present' => 1]);
        }
    }

    /**
     * Create attendance for a multi-student privat enrollment.
     * Rates are passed explicitly because pricing_tiers determine them.
     */
    private function createMultiAttendance(
        Enrollment $enrollment,
        array $students,
        Teacher $teacher,
        int $month,
        int $year,
        array $days,
        string $status,
        int $parentRate,
        int $teacherRate,
    ): void {
        foreach ($days as $day) {
            $date = Carbon::create($year, $month, $day);
            $attendance = MonthlyAttendance::create([
                'enrollment_id' => $enrollment->id,
                'lesson_date' => $date,
                'month' => $month,
                'year' => $year,
                'status_validation' => $status,
                'parent_rate' => $parentRate,
                'teacher_rate' => $teacherRate,
                'created_by' => 1,
            ]);
            foreach ($students as $student) {
                $attendance->students()->attach($student->id, ['total_present' => 1]);
            }
        }
    }

    /**
     * Create attendance for a kelas enrollment (with session_teacher_id).
     */
    private function createKelasAttendance(
        Enrollment $enrollment,
        array $students,
        Teacher $teacher,
        int $month,
        int $year,
        array $days,
        string $status,
    ): void {
        foreach ($days as $day) {
            $date = Carbon::create($year, $month, $day);
            $attendance = MonthlyAttendance::create([
                'enrollment_id' => $enrollment->id,
                'session_teacher_id' => $teacher->id,
                'lesson_date' => $date,
                'month' => $month,
                'year' => $year,
                'status_validation' => $status,
                'parent_rate' => $enrollment->parent_rate,
                'teacher_rate' => $enrollment->teacher_rate,
                'created_by' => 1,
            ]);
            foreach ($students as $student) {
                $attendance->students()->attach($student->id, ['total_present' => 1]);
            }
        }
    }
}