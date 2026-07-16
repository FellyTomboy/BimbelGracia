<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'teacher_id' => Teacher::factory(),
            'parent_rate' => 200000,
            'teacher_rate' => 100000,
            'validation_status' => 0,
            'status' => 'active',
        ];
    }
}