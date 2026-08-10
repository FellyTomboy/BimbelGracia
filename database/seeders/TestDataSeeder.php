<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\BankAccount;
use App\Models\Enrollment;
use App\Models\MonthlyAttendance;
use App\Models\ParentModel;
use App\Models\Program;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // 1. ADMIN
        // ============================================================
        $admin = User::create([
            'name' => 'Admin Bimbel',
            'email' => 'admin@bimbelgracia.test',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
            'phone' => '6281234567890',
        ]);

        // ============================================================
        // 2. TEACHERS
        // ============================================================
        // Teacher H - mengajar privat (guru les twins F&G dan juga privat sendiri-sendiri)
        $teacherH = Teacher::create([
            'user_id' => null,
            'name' => 'Hendra Saputra',
            'whatsapp' => '6287711111111',
            'whatsapp_number' => '6287711111111',
            'major' => 'S.Pd. / Pendidikan Matematika',
            'subjects' => 'Matematika, Fisika',
            'bank_name' => 'BCA',
            'bank_account' => '7711111111',
            'bank_owner' => 'Hendra Saputra',
            'class_rate' => 75000,
            'status' => 'active',
            'is_founder' => false,
        ]);

        // Teacher I - mengajar privat dan juga kelas
        $teacherI = Teacher::create([
            'user_id' => null,
            'name' => 'Indah Permata Sari',
            'whatsapp' => '6287722222222',
            'whatsapp_number' => '6287722222222',
            'major' => 'S.Si. / Matematika Murni',
            'subjects' => 'Matematika, Olimpiade Matematika',
            'bank_name' => 'Mandiri',
            'bank_account' => '7722222222',
            'bank_owner' => 'Indah Permata Sari',
            'class_rate' => 80000,
            'status' => 'active',
            'is_founder' => false,
        ]);

        // Teacher J - sering terlambat presensi
        $teacherJ = Teacher::create([
            'user_id' => null,
            'name' => 'Joko Widodo (Guru)',
            'whatsapp' => '6287733333333',
            'whatsapp_number' => '6287733333333',
            'major' => 'S.Pd. / Pendidikan Bahasa Inggris',
            'subjects' => 'Bahasa Inggris',
            'bank_name' => 'BNI',
            'bank_account' => '7733333333',
            'bank_owner' => 'Joko Widodo',
            'class_rate' => 65000,
            'status' => 'active',
            'is_founder' => false,
        ]);

        // ============================================================
        // 3. PROGRAMS
        // ============================================================
        $programs = [
            // Privat programs
            ['Privat Matematika SD', 'SD', 'Privat', 'Matematika', 100000, 60000],
            ['Privat Matematika SMP', 'SMP', 'Privat', 'Matematika', 120000, 70000],
            ['Privat IPA SMP', 'SMP', 'Privat', 'IPA', 120000, 70000],
            ['Privat Matematika SMA', 'SMA', 'Privat', 'Matematika', 150000, 80000],
            ['Privat Bahasa Inggris SMP', 'SMP', 'Privat', 'Bahasa Inggris', 110000, 65000],
            ['Privat Bahasa Inggris SMA', 'SMA', 'Privat', 'Bahasa Inggris', 130000, 70000],
            // Class programs
            ['Kelas Olimpiade Matematika SD', 'SD', 'Kelas', 'Matematika', 90000, 55000],
            ['Kelas Matematika SMP', 'SMP', 'Kelas', 'Matematika', 90000, 55000],
        ];

        $programModels = [];
        foreach ($programs as [$name, $division, $type, $subject, $parentRate, $teacherRate]) {
            $programModels[] = Program::create([
                'name' => $name,
                'division' => $division,
                'type' => $type,
                'subject' => $subject,
                'default_parent_rate' => $parentRate,
                'default_teacher_rate' => $teacherRate,
                'description' => "Program $name untuk divisi $division",
                'status' => 'active',
            ]);
        }

        // Map program indices for readability
        $PRIVAT_MAT_SD = $programModels[0];
        $PRIVAT_MAT_SMP = $programModels[1];
        $PRIVAT_IPA_SMP = $programModels[2];
        $PRIVAT_MAT_SMA = $programModels[3];
        $PRIVAT_BING_SMP = $programModels[4];
        $PRIVAT_BING_SMA = $programModels[5];
        $KELAS_OLIM_SD = $programModels[6];
        $KELAS_MAT_SMP = $programModels[7];

        // ============================================================
        // 4. PARENTS & STUDENTS
        // ============================================================

        // --- PARENT A: 1 anak ---
        $userA = User::create([
            'name' => 'Bapak Ahmad Fauzi',
            'email' => 'ahmad.fauzi@example.test',
            'password' => Hash::make('password'),
            'role' => UserRole::Parent,
            'phone' => '6281111111111',
        ]);
        $parentA = ParentModel::create([
            'user_id' => $userA->id,
            'name' => 'Bapak Ahmad Fauzi',
        ]);
        $studentA1 = Student::create([
            'parent_id' => $parentA->id,
            'name' => 'Anak A1 - Rina Fauzi',
            'address' => 'Jl. Merpati No. 1, Jakarta',
            'status' => 'active',
        ]);

        // --- PARENT B: 1 anak, kena denda minimal les (hadir 2 dari 8) ---
        $userB = User::create([
            'name' => 'Ibu Budi Lestari',
            'email' => 'budi.lestari@example.test',
            'password' => Hash::make('password'),
            'role' => UserRole::Parent,
            'phone' => '6281222222222',
        ]);
        $parentB = ParentModel::create([
            'user_id' => $userB->id,
            'name' => 'Ibu Budi Lestari',
        ]);
        $studentB1 = Student::create([
            'parent_id' => $parentB->id,
            'name' => 'Anak B1 - Dedi Lestari',
            'address' => 'Jl. Kenari No. 2, Jakarta',
            'status' => 'active',
        ]);

        // --- PARENT C: 1 anak ---
        $userC = User::create([
            'name' => 'Bapak Cahyo Nugroho',
            'email' => 'cahyo.nugroho@example.test',
            'password' => Hash::make('password'),
            'role' => UserRole::Parent,
            'phone' => '6281333333333',
        ]);
        $parentC = ParentModel::create([
            'user_id' => $userC->id,
            'name' => 'Bapak Cahyo Nugroho',
        ]);
        $studentC1 = Student::create([
            'parent_id' => $parentC->id,
            'name' => 'Anak C1 - Sari Nugroho',
            'address' => 'Jl. Cendrawasih No. 3, Jakarta',
            'status' => 'active',
        ]);

        // --- PARENT D: 2 anak (kembar F & G) ---
        $userD = User::create([
            'name' => 'Ibu Dewi Sartika',
            'email' => 'dewi.sartika@example.test',
            'password' => Hash::make('password'),
            'role' => UserRole::Parent,
            'phone' => '6281444444444',
        ]);
        $parentD = ParentModel::create([
            'user_id' => $userD->id,
            'name' => 'Ibu Dewi Sartika',
        ]);
        $studentF = Student::create([
            'parent_id' => $parentD->id,
            'name' => 'Anak F (Kembar) - Farhan',
            'address' => 'Jl. Flamboyan No. 4, Jakarta',
            'status' => 'active',
        ]);
        $studentG = Student::create([
            'parent_id' => $parentD->id,
            'name' => 'Anak G (Kembar) - Galih',
            'address' => 'Jl. Flamboyan No. 4, Jakarta',
            'status' => 'active',
        ]);

        // --- PARENT E: 3 anak ---
        $userE = User::create([
            'name' => 'Bapak Eko Prasetyo',
            'email' => 'eko.prasetyo@example.test',
            'password' => Hash::make('password'),
            'role' => UserRole::Parent,
            'phone' => '6281555555555',
        ]);
        $parentE = ParentModel::create([
            'user_id' => $userE->id,
            'name' => 'Bapak Eko Prasetyo',
        ]);
        $studentE1 = Student::create([
            'parent_id' => $parentE->id,
            'name' => 'Anak E1 - Ayu Prasetyo',
            'address' => 'Jl. Dahlia No. 5, Jakarta',
            'status' => 'active',
        ]);
        $studentE2 = Student::create([
            'parent_id' => $parentE->id,
            'name' => 'Anak E2 - Bambang Prasetyo',
            'address' => 'Jl. Dahlia No. 5, Jakarta',
            'status' => 'active',
        ]);
        $studentE3 = Student::create([
            'parent_id' => $parentE->id,
            'name' => 'Anak E3 - Cici Prasetyo',
            'address' => 'Jl. Dahlia No. 5, Jakarta',
            'status' => 'active',
        ]);

        // ============================================================
        // 5. ENROLLMENTS
        // ============================================================

        // --- ENROLLMENT 1: Parent A - Rina les Matematika SD privat dengan teacher H ---
        $enrollA1 = Enrollment::create([
            'program_id' => $PRIVAT_MAT_SD->id,
            'teacher_id' => $teacherH->id,
            'parent_rate' => 100000,
            'teacher_rate' => 60000,
            'pricing_tiers' => [
                'parent_rate' => ['1' => 100000],
                'teacher_rate' => ['1' => 60000],
            ],
            'agreed_sessions_per_month' => 4,
            'validation_status' => 1,
            'status' => 'active',
        ]);
        $enrollA1->students()->attach([$studentA1->id]);

        // --- ENROLLMENT 2: Parent B - Dedi les Matematika SMP privat dengan teacher H (8 sesi, hadir cuma 2) ---
        $enrollB1 = Enrollment::create([
            'program_id' => $PRIVAT_MAT_SMP->id,
            'teacher_id' => $teacherH->id,
            'parent_rate' => 120000,
            'teacher_rate' => 70000,
            'pricing_tiers' => [
                'parent_rate' => ['1' => 120000],
                'teacher_rate' => ['1' => 70000],
            ],
            'agreed_sessions_per_month' => 8,
            'validation_status' => 1,
            'status' => 'active',
        ]);
        $enrollB1->students()->attach([$studentB1->id]);

        // --- ENROLLMENT 3: Parent C - Sari les Bahasa Inggris SMP privat dengan teacher J ---
        $enrollC1 = Enrollment::create([
            'program_id' => $PRIVAT_BING_SMP->id,
            'teacher_id' => $teacherJ->id,
            'parent_rate' => 110000,
            'teacher_rate' => 65000,
            'pricing_tiers' => [
                'parent_rate' => ['1' => 110000],
                'teacher_rate' => ['1' => 65000],
            ],
            'agreed_sessions_per_month' => 4,
            'validation_status' => 1,
            'status' => 'active',
        ]);
        $enrollC1->students()->attach([$studentC1->id]);

        // --- ENROLLMENT 4: Parent D - Farhan & Galih les privat Matematika SD bersama teacher H (berdua) ---
        // Pricing tiers: 2 siswa = 150rb parent, 1 siswa = 100rb parent; guru: 2 siswa = 80rb, 1 siswa = 60rb
        $enrollD1 = Enrollment::create([
            'program_id' => $PRIVAT_MAT_SD->id,
            'teacher_id' => $teacherH->id,
            'parent_rate' => 150000,
            'teacher_rate' => 80000,
            'pricing_tiers' => [
                'parent_rate' => ['1' => 100000, '2' => 150000],
                'teacher_rate' => ['1' => 60000, '2' => 80000],
            ],
            'agreed_sessions_per_month' => 4,
            'validation_status' => 1,
            'status' => 'active',
        ]);
        $enrollD1->students()->attach([$studentF->id, $studentG->id]);

        // --- ENROLLMENT 5: Parent D - Farhan les tambahan Olimpiade (Kelas) dengan teacher I ---
        $enrollD2 = Enrollment::create([
            'program_id' => $KELAS_OLIM_SD->id,
            'teacher_id' => $teacherI->id,
            'parent_rate' => 90000,
            'teacher_rate' => 55000,
            'pricing_tiers' => [
                'parent_rate' => ['1' => 90000],
                'teacher_rate' => ['1' => 55000],
            ],
            'agreed_sessions_per_month' => 4,
            'validation_status' => 1,
            'status' => 'active',
        ]);
        $enrollD2->students()->attach([$studentF->id]);

        // --- ENROLLMENT 6: Parent E - Ayu les Matematika SD privat dengan teacher I ---
        $enrollE1 = Enrollment::create([
            'program_id' => $PRIVAT_MAT_SD->id,
            'teacher_id' => $teacherI->id,
            'parent_rate' => 100000,
            'teacher_rate' => 60000,
            'pricing_tiers' => [
                'parent_rate' => ['1' => 100000],
                'teacher_rate' => ['1' => 60000],
            ],
            'agreed_sessions_per_month' => 4,
            'validation_status' => 1,
            'status' => 'active',
        ]);
        $enrollE1->students()->attach([$studentE1->id]);

        // --- ENROLLMENT 7: Parent E - Bambang & Cici les Matematika SD privat bersama teacher I (berdua) ---
        $enrollE2 = Enrollment::create([
            'program_id' => $PRIVAT_MAT_SD->id,
            'teacher_id' => $teacherI->id,
            'parent_rate' => 150000,
            'teacher_rate' => 80000,
            'pricing_tiers' => [
                'parent_rate' => ['1' => 100000, '2' => 150000],
                'teacher_rate' => ['1' => 60000, '2' => 80000],
            ],
            'agreed_sessions_per_month' => 4,
            'validation_status' => 1,
            'status' => 'active',
        ]);
        $enrollE2->students()->attach([$studentE2->id, $studentE3->id]);

        // --- ENROLLMENT 8: Parent E - Bambang les privat Bahasa Inggris SMA dengan teacher J ---
        $enrollE3 = Enrollment::create([
            'program_id' => $PRIVAT_BING_SMA->id,
            'teacher_id' => $teacherJ->id,
            'parent_rate' => 130000,
            'teacher_rate' => 70000,
            'pricing_tiers' => [
                'parent_rate' => ['1' => 130000],
                'teacher_rate' => ['1' => 70000],
            ],
            'agreed_sessions_per_month' => 4,
            'validation_status' => 1,
            'status' => 'active',
        ]);
        $enrollE3->students()->attach([$studentE2->id]);

        // --- ENROLLMENT 9: Teacher I juga ngajar kelas Matematika SMP ---
        $enrollKelas = Enrollment::create([
            'program_id' => $KELAS_MAT_SMP->id,
            'teacher_id' => $teacherI->id,
            'parent_rate' => 90000,
            'teacher_rate' => 55000,
            'pricing_tiers' => [
                'parent_rate' => ['1' => 90000],
                'teacher_rate' => ['1' => 55000],
            ],
            'agreed_sessions_per_month' => 4,
            'validation_status' => 1,
            'status' => 'active',
        ]);
        // Kelas diikuti oleh beberapa siswa dari berbagai parent
        $enrollKelas->students()->attach([$studentA1->id, $studentC1->id, $studentE1->id]);

        // ============================================================
        // 6. MONTHLY ATTENDANCE RECORDS (August 2026)
        // ============================================================
        $month = 8;
        $year = 2026;

        $attendanceData = [
            // ======== ENROLLMENT 1: Rina (Parent A) - Privat Matematika SD with teacher H ========
            // Hadir semua 4x (normal)
            ['enrollment_id' => $enrollA1->id, 'lesson_date' => '2026-08-04', 'student_ids' => [$studentA1->id], 'present' => [1], 'status' => 'terima'],
            ['enrollment_id' => $enrollA1->id, 'lesson_date' => '2026-08-11', 'student_ids' => [$studentA1->id], 'present' => [1], 'status' => 'terima'],
            ['enrollment_id' => $enrollA1->id, 'lesson_date' => '2026-08-18', 'student_ids' => [$studentA1->id], 'present' => [1], 'status' => 'terima'],
            ['enrollment_id' => $enrollA1->id, 'lesson_date' => '2026-08-25', 'student_ids' => [$studentA1->id], 'present' => [1], 'status' => 'terima'],

            // ======== ENROLLMENT 2: Dedi (Parent B) - Privat Matematika SMP with teacher H (8 sesi, hadir cuma 2) ========
            // Agreed 8 sessions, only attends 2 → kena denda minimal les
            ['enrollment_id' => $enrollB1->id, 'lesson_date' => '2026-08-03', 'student_ids' => [$studentB1->id], 'present' => [1], 'status' => 'terima'],
            ['enrollment_id' => $enrollB1->id, 'lesson_date' => '2026-08-05', 'student_ids' => [$studentB1->id], 'present' => [1], 'status' => 'terima'],
            ['enrollment_id' => $enrollB1->id, 'lesson_date' => '2026-08-07', 'student_ids' => [$studentB1->id], 'present' => [0], 'status' => 'terima'],
            ['enrollment_id' => $enrollB1->id, 'lesson_date' => '2026-08-10', 'student_ids' => [$studentB1->id], 'present' => [0], 'status' => 'terima'],
            ['enrollment_id' => $enrollB1->id, 'lesson_date' => '2026-08-12', 'student_ids' => [$studentB1->id], 'present' => [0], 'status' => 'terima'],
            ['enrollment_id' => $enrollB1->id, 'lesson_date' => '2026-08-14', 'student_ids' => [$studentB1->id], 'present' => [0], 'status' => 'terima'],
            ['enrollment_id' => $enrollB1->id, 'lesson_date' => '2026-08-17', 'student_ids' => [$studentB1->id], 'present' => [0], 'status' => 'terima'],
            ['enrollment_id' => $enrollB1->id, 'lesson_date' => '2026-08-19', 'student_ids' => [$studentB1->id], 'present' => [0], 'status' => 'terima'],

            // ======== ENROLLMENT 3: Sari (Parent C) - Privat Bahasa Inggris SMP with teacher J ========
            // Teacher J terlambat presensi di pertemuan ke-2 dan ke-4
            ['enrollment_id' => $enrollC1->id, 'lesson_date' => '2026-08-05', 'student_ids' => [$studentC1->id], 'present' => [1], 'status' => 'terima'],
            ['enrollment_id' => $enrollC1->id, 'lesson_date' => '2026-08-12', 'student_ids' => [$studentC1->id], 'present' => [1], 'status' => 'terlambat'],
            ['enrollment_id' => $enrollC1->id, 'lesson_date' => '2026-08-19', 'student_ids' => [$studentC1->id], 'present' => [1], 'status' => 'terima'],
            ['enrollment_id' => $enrollC1->id, 'lesson_date' => '2026-08-26', 'student_ids' => [$studentC1->id], 'present' => [1], 'status' => 'terlambat'],

            // ======== ENROLLMENT 4: Farhan & Galih (Parent D) - Privat Matematika SD bersama teacher H ========
            // Pertemuan 1: Farhan hadir, Galih hadir (berdua - rate 2 siswa)
            ['enrollment_id' => $enrollD1->id, 'lesson_date' => '2026-08-04', 'student_ids' => [$studentF->id, $studentG->id], 'present' => [1, 1], 'status' => 'terima'],
            // Pertemuan 2: Farhan hadir, Galih TIDAK hadir (Farhan sendiri - rate 1 siswa)
            ['enrollment_id' => $enrollD1->id, 'lesson_date' => '2026-08-11', 'student_ids' => [$studentF->id, $studentG->id], 'present' => [1, 0], 'status' => 'terima'],
            // Pertemuan 3: Farhan TIDAK hadir, Galih hadir (Galih sendiri - rate 1 siswa)
            ['enrollment_id' => $enrollD1->id, 'lesson_date' => '2026-08-18', 'student_ids' => [$studentF->id, $studentG->id], 'present' => [0, 1], 'status' => 'terima'],
            // Pertemuan 4: Farhan hadir, Galih hadir (berdua - rate 2 siswa)
            ['enrollment_id' => $enrollD1->id, 'lesson_date' => '2026-08-25', 'student_ids' => [$studentF->id, $studentG->id], 'present' => [1, 1], 'status' => 'terima'],

            // ======== ENROLLMENT 5: Farhan (Parent D) - Kelas Olimpiade Matematika SD with teacher I ========
            // Farhan ikut kelas olimpiade (karena dia pintar)
            ['enrollment_id' => $enrollD2->id, 'lesson_date' => '2026-08-06', 'student_ids' => [$studentF->id], 'present' => [1], 'status' => 'terima'],
            ['enrollment_id' => $enrollD2->id, 'lesson_date' => '2026-08-13', 'student_ids' => [$studentF->id], 'present' => [1], 'status' => 'terima'],
            ['enrollment_id' => $enrollD2->id, 'lesson_date' => '2026-08-20', 'student_ids' => [$studentF->id], 'present' => [1], 'status' => 'terima'],
            ['enrollment_id' => $enrollD2->id, 'lesson_date' => '2026-08-27', 'student_ids' => [$studentF->id], 'present' => [1], 'status' => 'terima'],

            // ======== ENROLLMENT 6: Ayu (Parent E) - Privat Matematika SD with teacher I ========
            ['enrollment_id' => $enrollE1->id, 'lesson_date' => '2026-08-05', 'student_ids' => [$studentE1->id], 'present' => [1], 'status' => 'terima'],
            ['enrollment_id' => $enrollE1->id, 'lesson_date' => '2026-08-12', 'student_ids' => [$studentE1->id], 'present' => [1], 'status' => 'terima'],
            ['enrollment_id' => $enrollE1->id, 'lesson_date' => '2026-08-19', 'student_ids' => [$studentE1->id], 'present' => [1], 'status' => 'terima'],
            ['enrollment_id' => $enrollE1->id, 'lesson_date' => '2026-08-26', 'student_ids' => [$studentE1->id], 'present' => [1], 'status' => 'terima'],

            // ======== ENROLLMENT 7: Bambang & Cici (Parent E) - Privat Matematika SD bersama teacher I ========
            // Pertemuan 1: Bambang hadir, Cici hadir (berdua)
            ['enrollment_id' => $enrollE2->id, 'lesson_date' => '2026-08-06', 'student_ids' => [$studentE2->id, $studentE3->id], 'present' => [1, 1], 'status' => 'terima'],
            // Pertemuan 2: Bambang hadir, Cici TIDAK hadir
            ['enrollment_id' => $enrollE2->id, 'lesson_date' => '2026-08-13', 'student_ids' => [$studentE2->id, $studentE3->id], 'present' => [1, 0], 'status' => 'terima'],
            // Pertemuan 3: Bambang TIDAK hadir, Cici hadir
            ['enrollment_id' => $enrollE2->id, 'lesson_date' => '2026-08-20', 'student_ids' => [$studentE2->id, $studentE3->id], 'present' => [0, 1], 'status' => 'terima'],
            // Pertemuan 4: Bambang hadir, Cici hadir (berdua)
            ['enrollment_id' => $enrollE2->id, 'lesson_date' => '2026-08-27', 'student_ids' => [$studentE2->id, $studentE3->id], 'present' => [1, 1], 'status' => 'terima'],

            // ======== ENROLLMENT 8: Bambang (Parent E) - Privat Bahasa Inggris SMA with teacher J ========
            // Teacher J terlambat di pertemuan 3
            ['enrollment_id' => $enrollE3->id, 'lesson_date' => '2026-08-07', 'student_ids' => [$studentE2->id], 'present' => [1], 'status' => 'terima'],
            ['enrollment_id' => $enrollE3->id, 'lesson_date' => '2026-08-14', 'student_ids' => [$studentE2->id], 'present' => [1], 'status' => 'terima'],
            ['enrollment_id' => $enrollE3->id, 'lesson_date' => '2026-08-21', 'student_ids' => [$studentE2->id], 'present' => [1], 'status' => 'terlambat'],
            ['enrollment_id' => $enrollE3->id, 'lesson_date' => '2026-08-28', 'student_ids' => [$studentE2->id], 'present' => [1], 'status' => 'terima'],

            // ======== ENROLLMENT 9: Kelas Matematika SMP with teacher I ========
            // Diikuti oleh Rina (A1), Sari (C1), Ayu (E1)
            ['enrollment_id' => $enrollKelas->id, 'lesson_date' => '2026-08-03', 'student_ids' => [$studentA1->id, $studentC1->id, $studentE1->id], 'present' => [1, 1, 1], 'status' => 'terima'],
            ['enrollment_id' => $enrollKelas->id, 'lesson_date' => '2026-08-10', 'student_ids' => [$studentA1->id, $studentC1->id, $studentE1->id], 'present' => [1, 1, 1], 'status' => 'terima'],
            ['enrollment_id' => $enrollKelas->id, 'lesson_date' => '2026-08-17', 'student_ids' => [$studentA1->id, $studentC1->id, $studentE1->id], 'present' => [1, 0, 1], 'status' => 'terima'],
            ['enrollment_id' => $enrollKelas->id, 'lesson_date' => '2026-08-24', 'student_ids' => [$studentA1->id, $studentC1->id, $studentE1->id], 'present' => [1, 1, 1], 'status' => 'terima'],
        ];

        // Pre-load enrollments with rates
        $enrollments = Enrollment::with('program', 'teacher')->get()->keyBy('id');

        foreach ($attendanceData as $data) {
            $enrollment = $enrollments[$data['enrollment_id']];

            // Calculate present count for this attendance
            $presentCount = count(array_filter($data['present'], fn($p) => $p > 0));

            // Get rate based on pricing tiers if available
            $parentRate = $enrollment->getParentRateForCount($presentCount);
            $teacherRate = $enrollment->getTeacherRateForCount($presentCount);

            $attendance = MonthlyAttendance::create([
                'enrollment_id' => $data['enrollment_id'],
                'lesson_date' => $data['lesson_date'],
                'month' => $month,
                'year' => $year,
                'status_validation' => $data['status'],
                'parent_payment_status' => 'unpaid',
                'teacher_payment_status' => 'unpaid',
                'parent_rate' => $parentRate,
                'teacher_rate' => $teacherRate,
                'created_by' => $admin->id,
                'validated_by' => $admin->id,
                'validated_at' => now(),
                'parent_review_status' => 'accepted',
            ]);

            // Attach students with present count
            foreach ($data['student_ids'] as $i => $studentId) {
                $attendance->students()->attach($studentId, [
                    'total_present' => $data['present'][$i],
                ]);
            }
        }

        // ============================================================
        // 7. BANK ACCOUNTS
        // ============================================================
        BankAccount::create([
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'Bimbel Gracia',
            'status' => 'active',
        ]);

        BankAccount::create([
            'bank_name' => 'Mandiri',
            'account_number' => '9876543210',
            'account_holder' => 'Bimbel Gracia',
            'status' => 'active',
        ]);

        BankAccount::create([
            'bank_name' => 'DANA',
            'account_number' => '08123456789',
            'account_holder' => 'Bimbel Gracia',
            'status' => 'active',
        ]);

        $this->command->info('========================================');
        $this->command->info('Test Data Seeded Successfully!');
        $this->command->info('========================================');
        $this->command->info('Month: August 2026');
        $this->command->info('');
        $this->command->info('=== PARENTS ===');
        $this->command->info('A: Bapak Ahmad Fauzi - 1 anak (Rina)');
        $this->command->info('B: Ibu Budi Lestari - 1 anak (Dedi) - Kena denda minimal les (2/8)');
        $this->command->info('C: Bapak Cahyo Nugroho - 1 anak (Sari)');
        $this->command->info('D: Ibu Dewi Sartika - 2 anak kembar (Farhan & Galih)');
        $this->command->info('   Farhan les tambahan Olimpiade (Kelas)');
        $this->command->info('E: Bapak Eko Prasetyo - 3 anak (Ayu, Bambang, Cici)');
        $this->command->info('');
        $this->command->info('=== TEACHERS ===');
        $this->command->info('H: Hendra Saputra - Privat (Rina, Dedi, Farhan&Galih)');
        $this->command->info('I: Indah Permata Sari - Privat (Ayu, Bambang&Cici) + Kelas Olimpiade + Kelas Matematika SMP');
        $this->command->info('J: Joko Widodo - Privat (Sari, Bambang) - Terlambat presensi');
        $this->command->info('');
        $this->command->info('Admin login: admin@bimbelgracia.test / password');
        $this->command->info('========================================');
    }
}