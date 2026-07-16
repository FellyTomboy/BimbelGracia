<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AttendanceWindow;
use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\ClassGroup;
use App\Models\ClassSession;
use App\Models\ClassStudent;
use App\Models\ClassStudentDiscount;
use App\Models\ClassStudentSession;
use App\Models\Enrollment;
use App\Models\EnrollmentStudentDiscount;
use App\Models\LessonOffer;
use App\Models\MonthlyAttendance;
use App\Models\Program;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\MonthlySnapshotSyncService;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WebsiteDatasetSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $defaultPassword = config('bimbel.default_password', '12345678');
        $defaultWhatsapp = '085706512155';
        $now = Carbon::now(config('app.timezone', 'Asia/Jakarta'));

        // ──────────────────────────────────────────────
        // 1. ADMIN
        // ──────────────────────────────────────────────
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@bimbelgracia.test'],
            [
                'name' => 'Admin Bimbel',
                'role' => UserRole::Admin,
                'password' => Hash::make($defaultPassword),
                'must_change_password' => true,
            ]
        );

        // ──────────────────────────────────────────────
        // 2. TEACHERS
        // ──────────────────────────────────────────────
        $teacherData = [
            ['email' => 'andi.pratama@bimbelgracia.test', 'name' => 'Andi Pratama', 'major' => 'Matematika', 'subjects' => 'Matematika, Fisika', 'class_rate' => 50000],
            ['email' => 'siti.rahma@bimbelgracia.test', 'name' => 'Siti Rahma', 'major' => 'Bahasa Indonesia', 'subjects' => 'Bahasa Indonesia, Bahasa Inggris', 'class_rate' => 45000],
            ['email' => 'budi.santoso@bimbelgracia.test', 'name' => 'Budi Santoso', 'major' => 'IPA', 'subjects' => 'IPA, Kimia', 'class_rate' => 47500],
        ];

        $teachers = collect($teacherData)->map(function (array $data) use ($defaultPassword, $defaultWhatsapp) {
            $user = User::query()->firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'role' => UserRole::Guru,
                    'password' => Hash::make($defaultPassword),
                    'must_change_password' => true,
                ]
            );

            return Teacher::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $data['name'],
                    'whatsapp' => $defaultWhatsapp,
                    'whatsapp_number' => $defaultWhatsapp,
                    'major' => $data['major'],
                    'subjects' => $data['subjects'],
                    'address' => fake()->address(),
                    'bank_name' => 'BCA',
                    'bank_account' => (string) fake()->numberBetween(10000000, 99999999),
                    'bank_owner' => $data['name'],
                    'class_rate' => $data['class_rate'],
                    'status' => 'active',
                ]
            );
        });

        // ──────────────────────────────────────────────
        // 3. STUDENTS (private)
        // ──────────────────────────────────────────────
        $studentData = [
            ['email' => 'alya.putri@bimbelgracia.test', 'name' => 'Alya Putri'],
            ['email' => 'bagas.mahendra@bimbelgracia.test', 'name' => 'Bagas Mahendra'],
            ['email' => 'citra.ayu@bimbelgracia.test', 'name' => 'Citra Ayu'],
            ['email' => 'dimas.prakoso@bimbelgracia.test', 'name' => 'Dimas Prakoso'],
            ['email' => 'elsa.nur@bimbelgracia.test', 'name' => 'Elsa Nur'],
            ['email' => 'fajar.hidayat@bimbelgracia.test', 'name' => 'Fajar Hidayat'],
            ['email' => 'gita.larasati@bimbelgracia.test', 'name' => 'Gita Larasati'],
            ['email' => 'hana.safitri@bimbelgracia.test', 'name' => 'Hana Safitri'],
        ];

        $students = collect($studentData)->map(function (array $data) use ($defaultPassword, $defaultWhatsapp) {
            $user = User::query()->firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'role' => UserRole::Murid,
                    'password' => Hash::make($defaultPassword),
                    'must_change_password' => true,
                ]
            );

            return Student::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $data['name'],
                    'whatsapp' => $defaultWhatsapp,
                    'whatsapp_primary' => $defaultWhatsapp,
                    'whatsapp_secondary' => $defaultWhatsapp,
                    'address' => fake()->address(),
                    'status' => 'active',
                ]
            );
        });

        // ──────────────────────────────────────────────
        // 4. CLASS STUDENTS (kelas bersama)
        // ──────────────────────────────────────────────
        $classStudents = collect([
            ['name' => 'Raka Saputra', 'rate' => 30000],
            ['name' => 'Nadia Puspita', 'rate' => 30000],
            ['name' => 'Jihan Maulida', 'rate' => 35000],
            ['name' => 'Kevin Wijaya', 'rate' => 35000],
            ['name' => 'Laila Ramadhani', 'rate' => 30000],
            ['name' => 'Naufal Hakim', 'rate' => 32500],
        ])->map(function (array $data) use ($defaultWhatsapp) {
            return ClassStudent::query()->updateOrCreate(
                ['name' => $data['name']],
                [
                    'whatsapp_primary' => $defaultWhatsapp,
                    'whatsapp_secondary' => $defaultWhatsapp,
                    'rate_per_meeting' => $data['rate'],
                    'status' => 'active',
                    'notes' => 'Data demo untuk kelas bersama.',
                ]
            );
        });

        // ──────────────────────────────────────────────
        // 5. BANK ACCOUNTS
        // ──────────────────────────────────────────────
        collect([
            ['bank_name' => 'BCA', 'account_number' => '1234567890', 'account_holder' => 'Bimbel Gracia', 'status' => 'active'],
            ['bank_name' => 'BRI', 'account_number' => '9876543210', 'account_holder' => 'Bimbel Gracia', 'status' => 'active'],
            ['bank_name' => 'Mandiri', 'account_number' => '1122334455', 'account_holder' => 'Bimbel Gracia', 'status' => 'active'],
        ])->each(function (array $data) {
            BankAccount::query()->updateOrCreate(
                ['account_number' => $data['account_number']],
                $data
            );
        });

        // ──────────────────────────────────────────────
        // 6. PROGRAMS
        // ──────────────────────────────────────────────
        $programs = collect([
            ['name' => 'Privat Intensif Matematika', 'type' => 'privat', 'subject' => 'Matematika', 'description' => 'Belajar fokus untuk nilai sekolah dan ujian.', 'default_parent_rate' => 250000, 'default_teacher_rate' => 150000],
            ['name' => 'Privat Bahasa Inggris', 'type' => 'privat', 'subject' => 'Bahasa Inggris', 'description' => 'Latihan komunikasi, grammar, dan reading.', 'default_parent_rate' => 220000, 'default_teacher_rate' => 130000],
            ['name' => 'Kelas IPA Kecil', 'type' => 'kelas', 'subject' => 'IPA Terpadu', 'description' => 'Kelas kecil untuk diskusi dan latihan soal.', 'default_parent_rate' => 180000, 'default_teacher_rate' => 100000],
        ])->map(function (array $data) {
            return Program::query()->updateOrCreate(
                ['name' => $data['name']],
                $data + ['status' => 'active']
            );
        });

        // ──────────────────────────────────────────────
        // 7. ENROLLMENTS (teacher → program)
        // ──────────────────────────────────────────────
        $enrollments = collect();
        foreach ($teachers as $index => $teacher) {
            $primaryProgram = $programs[$index % $programs->count()];
            $secondaryProgram = $programs[($index + 1) % $programs->count()];

            foreach ([$primaryProgram, $secondaryProgram] as $program) {
                $enrollments->push(
                    Enrollment::query()->updateOrCreate(
                        ['program_id' => $program->id, 'teacher_id' => $teacher->id],
                        [
                            'parent_rate' => $program->default_parent_rate,
                            'teacher_rate' => $program->default_teacher_rate,
                            'validation_status' => 1,
                            'status' => 'active',
                        ]
                    )
                );
            }
        }

        // ──────────────────────────────────────────────
        // 8. CLASS GROUPS
        // ──────────────────────────────────────────────
        $classGroups = collect([
            ['name' => 'Kelas Matematika Senin', 'subject' => 'Matematika', 'teacher' => $teachers[0], 'notes' => 'Persiapan ujian tengah semester.'],
            ['name' => 'Kelas Bahasa Inggris Rabu', 'subject' => 'Bahasa Inggris', 'teacher' => $teachers[1], 'notes' => 'Fokus percakapan dan grammar.'],
            ['name' => 'Kelas IPA Jumat', 'subject' => 'IPA', 'teacher' => $teachers[2], 'notes' => 'Latihan konsep dan eksperimen sederhana.'],
        ])->map(function (array $data) {
            return ClassGroup::query()->updateOrCreate(
                ['name' => $data['name']],
                [
                    'subject' => $data['subject'],
                    'teacher_id' => $data['teacher']->id,
                    'notes' => $data['notes'],
                ]
            );
        });

        // ──────────────────────────────────────────────
        // 9. RELATIONSHIPS: teacher ↔ student, enrollment ↔ student, classGroup ↔ student
        // ──────────────────────────────────────────────
        $teachers->each(function (Teacher $teacher) use ($students) {
            $teacherStudentIds = $students->random(min(4, $students->count()))->pluck('id')->all();
            $teacher->students()->syncWithoutDetaching($teacherStudentIds);
        });

        $enrollments->each(function (Enrollment $enrollment) use ($students) {
            $enrollmentStudentIds = $students->random(min(3, $students->count()))->pluck('id')->all();
            $enrollment->students()->syncWithoutDetaching($enrollmentStudentIds);
        });

        $classGroups->each(function (ClassGroup $classGroup) use ($students, $classStudents) {
            $studentIds = $students->random(min(5, $students->count()))->pluck('id')->all();
            $classGroup->students()->syncWithoutDetaching($studentIds);

            $classStudentIds = $classStudents->random(min(4, $classStudents->count()))->pluck('id')->all();
            $classGroup->students()->syncWithoutDetaching($classStudentIds);
        });

        // ──────────────────────────────────────────────
        // 10. MONTHLY DATA (6 bulan terakhir)
        // ──────────────────────────────────────────────
        collect(range(0, 5))->each(function (int $offset) use ($admin, $enrollments, $students, $classGroups, $classStudents, $now) {
            $period = $now->copy()->subMonthsNoOverflow($offset)->startOfMonth();
            $month = $period->month;
            $year = $period->year;

            // ── 10a. Attendance Window ──
            AttendanceWindow::query()->updateOrCreate(
                ['month' => $month, 'year' => $year],
                [
                    'is_open' => $offset === 0,
                    'opened_by' => $admin->id,
                    'opened_at' => $period->copy()->addDay(),
                    'closed_at' => $offset === 0 ? null : $period->copy()->endOfMonth(),
                ]
            );

            // ── 10b. Monthly Attendance (per enrollment) ──
            foreach ($enrollments as $enrollmentIndex => $enrollment) {
                $statusValidation = $offset === 5 && $enrollmentIndex === 0
                    ? 'ditolak'
                    : (($enrollmentIndex + $offset) % 4 === 1 ? 'terlambat' : 'terima');

                // Bervariasi: setiap enrollment punya lesson_date berbeda dalam bulan yang sama
                $lessonDay = 5 + ($enrollmentIndex * 3);
                $lessonDate = $period->copy()->addDays(min($lessonDay, 25));

                $attendance = MonthlyAttendance::query()->updateOrCreate(
                    ['enrollment_id' => $enrollment->id, 'month' => $month, 'year' => $year],
                    [
                        'notes' => "Rekap demo {$period->format('F Y')}",
                        'lesson_date' => $lessonDate,
                        'status_validation' => $statusValidation,
                        'parent_payment_status' => $statusValidation === 'ditolak' ? 'pending' : 'paid',
                        'teacher_payment_status' => $statusValidation === 'ditolak' ? 'pending' : 'paid',
                        'validated_at' => $statusValidation === 'ditolak' ? null : $period->copy()->addDays(24),
                        'validated_by' => $statusValidation === 'ditolak' ? null : $admin->id,
                        'created_by' => $admin->id,
                        'parent_rate' => $enrollment->parent_rate,
                        'teacher_rate' => $enrollment->teacher_rate,
                    ]
                );

                // total_present: 1-4 pertemuan per bulan (realistis)
                $attendanceStudentIds = $students->random(min(3, $students->count()))->pluck('id')->all();
                $pivotData = collect($attendanceStudentIds)->mapWithKeys(function (int $studentId, int $index) {
                    return [$studentId => ['total_present' => 1 + ($index % 4)]];
                })->all();

                $attendance->students()->syncWithoutDetaching($pivotData);
            }

            // ── 10c. Class Sessions (per class group) ──
            foreach ($classGroups as $groupIndex => $classGroup) {
                $sessionDate = $period->copy()->addDays(6 + ($groupIndex * 2));
                $session = ClassSession::query()->updateOrCreate(
                    [
                        'class_group_id' => $classGroup->id,
                        'teacher_id' => $classGroup->teacher_id,
                        'session_date' => $sessionDate->toDateString(),
                        'session_time' => '15:00:00',
                    ],
                    [
                        'subject' => $classGroup->subject,
                        'notes' => "Sesi kelas demo {$classGroup->name} {$period->format('F Y')}",
                    ]
                );

                $groupStudentIds = $students->random(min(4, $students->count()))->pluck('id')->all();
                $session->students()->syncWithoutDetaching(array_fill_keys($groupStudentIds, ['is_present' => true]));
            }

            // ── 10d. Class Student Sessions (kelas bersama) ──
            collect(range(1, 2))->each(function (int $sessionOffset) use ($period, $classStudents) {
                $sessionDate = $period->copy()->addDays(10 + ($sessionOffset * 7));
                $classStudentSession = ClassStudentSession::query()->updateOrCreate(
                    [
                        'session_date' => $sessionDate->toDateString(),
                        'start_time' => '16:00:00',
                    ],
                    [
                        'end_time' => '17:00:00',
                        'notes' => "Kelas bersama demo {$period->format('F Y')} #{$sessionOffset}",
                    ]
                );

                $selectedIds = $classStudents->random(min(3, $classStudents->count()))->pluck('id')->all();
                $classStudentSession->students()->syncWithoutDetaching(array_fill_keys($selectedIds, []));
            });

            // ── 10e. Discounts untuk enrollment privat ──
            // Beri diskon untuk 1-2 enrollment per bulan (random)
            $enrollmentsForDiscount = $enrollments->random(min(2, $enrollments->count()));
            foreach ($enrollmentsForDiscount as $enrollment) {
                $discountStudents = $students->random(min(2, $students->count()));
                foreach ($discountStudents as $student) {
                    EnrollmentStudentDiscount::query()->updateOrCreate(
                        [
                            'enrollment_id' => $enrollment->id,
                            'student_id' => $student->id,
                            'month' => $month,
                            'year' => $year,
                        ],
                        [
                            'discount_type' => 'persentase',
                            'discount_value' => 10, // 10%
                        ]
                    );
                }
            }

            // ── 10f. Discounts untuk class students ──
            $classStudentsForDiscount = $classStudents->random(min(2, $classStudents->count()));
            foreach ($classStudentsForDiscount as $classStudent) {
                ClassStudentDiscount::query()->updateOrCreate(
                    [
                        'class_student_id' => $classStudent->id,
                        'month' => $month,
                        'year' => $year,
                    ],
                    [
                        'discount_type' => 'nominal',
                        'discount_value' => 5000, // Rp 5.000
                    ]
                );
            }

            // ── 10g. Sync snapshots (mengisi monthly_student_snapshots,
            //         monthly_teacher_snapshots, dan class_student_monthly_attendances) ──
            app(MonthlySnapshotSyncService::class)->syncForPeriod($month, $year);
        });

        // ──────────────────────────────────────────────
        // 11. LESSON OFFERS
        // ──────────────────────────────────────────────
        $students->random(min(4, $students->count()))->each(function (Student $student) use ($admin, $defaultWhatsapp) {
            LessonOffer::query()->updateOrCreate(
                ['code' => strtoupper(substr(md5($student->id . $student->name), 0, 8))],
                [
                    'education_level' => 'SMA',
                    'subject' => 'Matematika',
                    'schedules' => [['day' => 'Sabtu', 'time' => '15:00']],
                    'note' => 'Permintaan kelas tambahan untuk persiapan ujian.',
                    'status' => 'open',
                    'contact_whatsapp' => $defaultWhatsapp,
                    'created_by' => $admin->id,
                ]
            );
        });

        // ──────────────────────────────────────────────
        // 12. AUDIT LOG
        // ──────────────────────────────────────────────
        AuditLog::query()->updateOrCreate(
            [
                'user_id' => $admin->id,
                'action' => 'seeded',
                'auditable_type' => User::class,
                'auditable_id' => $admin->id,
            ],
            [
                'before' => [],
                'after' => ['seeded' => true],
            ]
        );
    }
}