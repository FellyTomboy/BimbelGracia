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

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin
        $admin = User::create([
            'name' => 'Admin Bimbel',
            'email' => 'admin@bimbelgracia.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
            'phone' => '6281234567890',
        ]);

        // 2. Co-Founders (teachers with is_founder=true)
        $founder1 = Teacher::create([
            'user_id' => null,
            'name' => 'Dr. Sarah Amelia',
            'whatsapp' => '6281111111111',
            'whatsapp_number' => '6281111111111',
            'major' => 'S.Pd., M.Pd. / Pendidikan Matematika',
            'subjects' => 'Matematika, Fisika',
            'bank_name' => 'BCA',
            'bank_account' => '1234567890',
            'bank_owner' => 'Sarah Amelia',
            'class_rate' => 75000,
            'status' => 'active',
            'is_founder' => true,
            'founder_description' => 'Mendirikan Bimbel Gracia dengan visi memberikan pendidikan berkualitas yang terjangkau.',
        ]);

        $founder2 = Teacher::create([
            'user_id' => null,
            'name' => 'Ahmad Rizki',
            'whatsapp' => '6282222222222',
            'whatsapp_number' => '6282222222222',
            'major' => 'S.Si. / Kimia Industri',
            'subjects' => 'Kimia, Biologi, IPA',
            'bank_name' => 'Mandiri',
            'bank_account' => '9876543210',
            'bank_owner' => 'Ahmad Rizki',
            'class_rate' => 70000,
            'status' => 'active',
            'is_founder' => true,
            'founder_description' => 'Berpengalaman 10 tahun mengajar sains dengan metode interaktif dan menyenangkan.',
        ]);

        // 3. Regular teachers
        $teacher1 = Teacher::create([
            'user_id' => null,
            'name' => 'Dina Wijaya',
            'whatsapp' => '6283333333333',
            'whatsapp_number' => '6283333333333',
            'major' => 'S.Pd. / Pendidikan Bahasa Inggris',
            'subjects' => 'Bahasa Inggris',
            'bank_name' => 'BNI',
            'bank_account' => '5555555555',
            'bank_owner' => 'Dina Wijaya',
            'class_rate' => 65000,
            'status' => 'active',
            'is_founder' => false,
        ]);

        $teacher2 = Teacher::create([
            'user_id' => null,
            'name' => 'Budi Hartono',
            'whatsapp' => '6284444444444',
            'whatsapp_number' => '6284444444444',
            'major' => 'S.Pd. / Pendidikan Matematika',
            'subjects' => 'Matematika',
            'bank_name' => 'BRI',
            'bank_account' => '6666666666',
            'bank_owner' => 'Budi Hartono',
            'class_rate' => 70000,
            'status' => 'active',
            'is_founder' => false,
        ]);

        // 4. Programs with divisions
        $programs = [
            ['Privat Matematika SD', 'SD', 'Privat', 'Matematika', 100000, 60000],
            ['Privat IPA SD', 'SD', 'Privat', 'IPA', 100000, 60000],
            ['Privat Matematika SMP', 'SMP', 'Privat', 'Matematika', 120000, 70000],
            ['Privat IPA SMP', 'SMP', 'Privat', 'IPA', 120000, 70000],
            ['Privat Matematika SMA', 'SMA', 'Privat', 'Matematika', 150000, 80000],
            ['Privat Fisika SMA', 'SMA', 'Privat', 'Fisika', 150000, 80000],
            ['Privat Kimia SMA', 'SMA', 'Privat', 'Kimia', 150000, 80000],
            ['Privat Bahasa Inggris', 'SMA', 'Privat', 'Bahasa Inggris', 130000, 70000],
            ['Privat UTBK - Matematika', 'UTBK', 'Privat', 'Matematika', 175000, 90000],
            ['Privat UTBK - Fisika', 'UTBK', 'Privat', 'Fisika', 175000, 90000],
            ['Kelas Matematika SD', 'SD', 'Kelas', 'Matematika', 80000, 50000],
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

        // 5. Parent user & parent record
        $parentUser = User::create([
            'name' => 'Ibu Rina Sari',
            'email' => 'rina@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Parent,
            'phone' => '6285555555555',
        ]);

        $parent = ParentModel::create([
            'user_id' => $parentUser->id,
            'name' => 'Ibu Rina Sari',
        ]);

        // 6. Students
        $student1 = Student::create([
            'parent_id' => $parent->id,
            'name' => 'Aulia Putri',
            'address' => 'Jl. Merdeka No. 10, Jakarta',
            'status' => 'active',
        ]);

        $student2 = Student::create([
            'parent_id' => $parent->id,
            'name' => 'Bima Sakti',
            'address' => 'Jl. Merdeka No. 10, Jakarta',
            'status' => 'active',
        ]);

        // Create another parent for Citra
        $parentUser2 = User::create([
            'name' => 'Bapak Adi Lestari',
            'email' => 'adi@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Parent,
            'phone' => '6286666666666',
        ]);

        $parent2 = ParentModel::create([
            'user_id' => $parentUser2->id,
            'name' => 'Bapak Adi Lestari',
        ]);

        $student3 = Student::create([
            'parent_id' => $parent2->id,
            'name' => 'Citra Lestari',
            'address' => 'Jl. Sudirman No. 25, Jakarta',
            'status' => 'active',
        ]);

        // 7. Enrollments
        $enrollment1 = Enrollment::create([
            'program_id' => $programModels[2]->id, // Privat Matematika SMP
            'teacher_id' => $teacher2->id, // Budi Hartono
            'parent_rate' => 120000,
            'teacher_rate' => 70000,
            'pricing_tiers' => [
                'parent_rate' => ['1' => 150000, '2' => 120000],
                'teacher_rate' => ['1' => 80000, '2' => 70000],
            ],
            'agreed_sessions_per_month' => 4,
            'validation_status' => 1,
            'status' => 'active',
        ]);

        $enrollment2 = Enrollment::create([
            'program_id' => $programModels[3]->id, // Privat IPA SMP
            'teacher_id' => $teacher1->id, // Dina Wijaya (IPA)
            'parent_rate' => 120000,
            'teacher_rate' => 70000,
            'agreed_sessions_per_month' => 4,
            'validation_status' => 1,
            'status' => 'active',
        ]);

        $enrollment3 = Enrollment::create([
            'program_id' => $programModels[4]->id, // Privat Matematika SMA
            'teacher_id' => $teacher2->id,
            'parent_rate' => 150000,
            'teacher_rate' => 80000,
            'agreed_sessions_per_month' => 4,
            'validation_status' => 1,
            'status' => 'active',
        ]);

        // Attach students to enrollments
        $enrollment1->students()->attach([$student1->id, $student2->id]);
        $enrollment2->students()->attach([$student1->id]);
        $enrollment3->students()->attach([$student3->id]);

        // 8. Monthly attendance records (July 2026)
        $month = 7;
        $year = 2026;

        $attendanceData = [
            // === Enrollment 1 (Matematika SMP - Budi) - Aulia & Bima ===
            // Pertemuan 1: Aulia hadir, Bima hadir (2 siswa, rate normal)
            ['enrollment_id' => $enrollment1->id, 'lesson_date' => '2026-07-06', 'student_ids' => [$student1->id, $student2->id], 'present' => [1, 1], 'status' => 'terima'],
            // Pertemuan 2: Aulia hadir, Bima hadir (2 siswa, rate normal)
            ['enrollment_id' => $enrollment1->id, 'lesson_date' => '2026-07-13', 'student_ids' => [$student1->id, $student2->id], 'present' => [1, 1], 'status' => 'terima'],
            // Pertemuan 3: Aulia hadir, Bima TIDAK hadir (1 siswa, rate berbeda)
            ['enrollment_id' => $enrollment1->id, 'lesson_date' => '2026-07-20', 'student_ids' => [$student1->id, $student2->id], 'present' => [1, 0], 'status' => 'terima'],
            // Pertemuan 4: Aulia hadir, Bima hadir (2 siswa, rate normal) - GURU TERLAMBAT
            ['enrollment_id' => $enrollment1->id, 'lesson_date' => '2026-07-27', 'student_ids' => [$student1->id, $student2->id], 'present' => [1, 1], 'status' => 'terlambat'],

            // === Enrollment 2 (IPA SMP - Dina) - Aulia only ===
            // Aulia hadir semua 4x (100% kehadiran)
            ['enrollment_id' => $enrollment2->id, 'lesson_date' => '2026-07-07', 'student_ids' => [$student1->id], 'present' => [1], 'status' => 'terima'],
            ['enrollment_id' => $enrollment2->id, 'lesson_date' => '2026-07-14', 'student_ids' => [$student1->id], 'present' => [1], 'status' => 'terima'],
            ['enrollment_id' => $enrollment2->id, 'lesson_date' => '2026-07-21', 'student_ids' => [$student1->id], 'present' => [1], 'status' => 'terima'],
            ['enrollment_id' => $enrollment2->id, 'lesson_date' => '2026-07-28', 'student_ids' => [$student1->id], 'present' => [1], 'status' => 'terima'],

            // === Enrollment 3 (Matematika SMA - Budi) - Citra only ===
            // Citra hanya hadir 1x dari 4 pertemuan (< 50%) → kena denda tambahan biaya
            ['enrollment_id' => $enrollment3->id, 'lesson_date' => '2026-07-08', 'student_ids' => [$student3->id], 'present' => [1], 'status' => 'terima'],
            ['enrollment_id' => $enrollment3->id, 'lesson_date' => '2026-07-15', 'student_ids' => [$student3->id], 'present' => [0], 'status' => 'terima'],
            ['enrollment_id' => $enrollment3->id, 'lesson_date' => '2026-07-22', 'student_ids' => [$student3->id], 'present' => [0], 'status' => 'terima'],
            ['enrollment_id' => $enrollment3->id, 'lesson_date' => '2026-07-29', 'student_ids' => [$student3->id], 'present' => [0], 'status' => 'terima'],
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

        // 9. Bank accounts
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

        $this->command->info('Demo data seeded successfully!');
        $this->command->info('Admin login: admin@bimbelgracia.com / password');
        $this->command->info('Month: July 2026 is ready for invoice/salary generation.');
    }
}