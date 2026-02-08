<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AttendanceWindow;
use App\Models\AuditLog;
use App\Models\ClassStudent;
use App\Models\ClassStudentSession;
use App\Models\Enrollment;
use App\Models\LessonOffer;
use App\Models\MonthlyAttendance;
use App\Models\Program;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $faker = fake();
        $defaultPassword = config('bimbel.default_password', '12345678');
        $defaultWhatsapp = '085706512155';

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@bimbelgracia.test'],
            [
                'name' => 'Admin',
                'role' => UserRole::Admin,
                'password' => Hash::make($defaultPassword),
                'must_change_password' => true,
            ]
        );

        $teacherUsers = collect(range(1, 3))->map(function (int $index) use ($defaultPassword, $faker) {
            return User::query()->firstOrCreate(
                ['email' => "guru{$index}@bimbelgracia.test"],
                [
                    'name' => $faker->name(),
                    'role' => UserRole::Guru,
                    'password' => Hash::make($defaultPassword),
                    'must_change_password' => true,
                ]
            );
        });

        $teachers = $teacherUsers->map(function (User $user) use ($faker, $defaultWhatsapp) {
            return Teacher::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $user->name,
                    'whatsapp' => $defaultWhatsapp,
                    'whatsapp_number' => $defaultWhatsapp,
                    'major' => $faker->randomElement(['Matematika', 'Fisika', 'Bahasa Inggris']),
                    'subjects' => $faker->randomElement([
                        'Matematika, IPA',
                        'Bahasa Inggris, Bahasa Indonesia',
                        'Fisika, Kimia',
                    ]),
                    'address' => $faker->address,
                    'bank_name' => $faker->randomElement(['BCA', 'BRI', 'Mandiri', 'BNI']),
                    'bank_account' => (string) $faker->numberBetween(10000000, 99999999),
                    'bank_owner' => $user->name,
                    'class_rate' => $faker->randomElement([40000, 45000, 50000]),
                    'status' => 'active',
                ]
            );
        });

        $studentUsers = collect(range(1, 8))->map(function (int $index) use ($defaultPassword, $faker) {
            return User::query()->firstOrCreate(
                ['email' => "murid{$index}@bimbelgracia.test"],
                [
                    'name' => $faker->name(),
                    'role' => UserRole::Murid,
                    'password' => Hash::make($defaultPassword),
                    'must_change_password' => true,
                ]
            );
        });

        $students = $studentUsers->map(function (User $user, int $index) use ($defaultWhatsapp, $faker) {
            $sharedParent = $index <= 3;

            return Student::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $user->name,
                    'whatsapp' => $defaultWhatsapp,
                    'whatsapp_primary' => $sharedParent ? $defaultWhatsapp : $defaultWhatsapp,
                    'whatsapp_secondary' => $sharedParent ? $defaultWhatsapp : $defaultWhatsapp,
                    'address' => $faker->address,
                    'status' => 'active',
                ]
            );
        });

        $classStudents = collect(range(1, 6))->map(function () use ($defaultWhatsapp, $faker) {
            return ClassStudent::query()->create([
                'name' => $faker->name(),
                'whatsapp_primary' => $defaultWhatsapp,
                'whatsapp_secondary' => $defaultWhatsapp,
                'rate_per_meeting' => $faker->randomElement([25000, 30000, 35000]),
                'status' => 'active',
                'notes' => 'Murid kelas bersama.',
            ]);
        });

        $programs = collect([
            [
                'name' => 'Privat Intensif',
                'type' => 'privat',
                'subject' => 'Matematika',
                'description' => 'Sesi privat dengan target akademik khusus.',
                'default_parent_rate' => 250000,
                'default_teacher_rate' => 150000,
                'status' => 'active',
            ],
            [
                'name' => 'Privat Reguler',
                'type' => 'privat',
                'subject' => 'Bahasa Inggris',
                'description' => 'Program rutin mingguan dengan laporan progres.',
                'default_parent_rate' => 180000,
                'default_teacher_rate' => 110000,
                'status' => 'active',
            ],
            [
                'name' => 'Kelas Bersama',
                'type' => 'kelas',
                'subject' => 'IPA Terpadu',
                'description' => 'Kelas kecil dengan diskusi intensif.',
                'default_parent_rate' => 140000,
                'default_teacher_rate' => 90000,
                'status' => 'active',
            ],
        ])->map(function (array $data) {
            return Program::query()->firstOrCreate(['name' => $data['name']], $data);
        });

        $enrollments = collect();
        foreach ($teachers as $teacher) {
            $programs->random(2)->each(function (Program $program) use ($teacher, $enrollments) {
                $enrollments->push(Enrollment::query()->create([
                    'program_id' => $program->id,
                    'teacher_id' => $teacher->id,
                    'parent_rate' => $program->default_parent_rate,
                    'teacher_rate' => $program->default_teacher_rate,
                    'validation_status' => 1,
                    'status' => 'active',
                ]));
            });
        }

        $teachers->each(function (Teacher $teacher) use ($students) {
            $teacherStudentIds = $students->random(min(4, $students->count()))->pluck('id')->all();
            $teacher->students()->syncWithoutDetaching($teacherStudentIds);
        });

        $enrollments->each(function (Enrollment $enrollment) use ($students) {
            $enrollmentStudentIds = $students->random(min(3, $students->count()))->pluck('id')->all();
            $enrollment->students()->syncWithoutDetaching($enrollmentStudentIds);
        });


        $classStudents->each(function (ClassStudent $student) use ($faker) {
            collect(range(1, 3))->each(function (int $index) use ($student, $faker) {
                ClassStudentSession::query()->create([
                    'class_student_id' => $student->id,
                    'session_date' => now()->addDays($index),
                    'start_time' => '15:00',
                    'end_time' => '16:00',
                    'notes' => $faker->sentence(4),
                ]);
            });
        });

        $currentMonth = (int) now()->format('n');
        $currentYear = (int) now()->format('Y');
        AttendanceWindow::query()->firstOrCreate(
            ['month' => $currentMonth, 'year' => $currentYear],
            [
                'is_open' => true,
                'opened_by' => $admin->id,
                'opened_at' => now(),
            ]
        );

        $enrollments->each(function (Enrollment $enrollment) use ($admin, $students, $currentMonth, $currentYear) {
            $attendance = MonthlyAttendance::query()->create([
                'enrollment_id' => $enrollment->id,
                'month' => $currentMonth,
                'year' => $currentYear,
                'dates' => [1, 8, 15, 22],
                'notes' => 'Hadir rutin, progres stabil.',
                'total_lessons' => 4,
                'status_validation' => 'valid',
                'parent_payment_status' => 'paid',
                'teacher_payment_status' => 'paid',
                'validated_at' => now(),
                'validated_by' => $admin->id,
                'created_by' => $admin->id,
            ]);

            $attendanceStudentIds = $students->random(min(3, $students->count()))->pluck('id')->all();
            $pivotData = collect($attendanceStudentIds)->mapWithKeys(function (int $studentId) {
                return [$studentId => ['total_present' => random_int(2, 4)]];
            })->all();
            $attendance->students()->syncWithoutDetaching($pivotData);
        });

        $students->random(min(3, $students->count()))->each(function (Student $student) use ($admin, $defaultWhatsapp) {
            LessonOffer::query()->create([
                'code' => Str::upper(Str::random(8)),
                'student_id' => $student->id,
                'subject' => 'Matematika',
                'schedule_day' => 'Sabtu',
                'schedule_time' => '15:00',
                'note' => 'Permintaan kelas tambahan untuk persiapan ujian.',
                'status' => 'open',
                'contact_whatsapp' => $defaultWhatsapp,
                'created_by' => $admin->id,
            ]);
        });

        AuditLog::query()->create([
            'user_id' => $admin->id,
            'action' => 'seeded',
            'auditable_type' => User::class,
            'auditable_id' => $admin->id,
            'before' => [],
            'after' => ['seeded' => true],
        ]);
    }
}
