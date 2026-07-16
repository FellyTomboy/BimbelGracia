<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\MonthlyAttendance;
use Illuminate\Database\Eloquent\Factories\Factory;

class MonthlyAttendanceFactory extends Factory
{
    protected $model = MonthlyAttendance::class;

    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'month' => now()->month,
            'year' => now()->year,
            'lesson_date' => now()->subDays(5),
            'notes' => fake()->sentence(),
            'status_validation' => 'terima',
            'parent_payment_status' => 'paid',
            'teacher_payment_status' => 'paid',
            'validated_at' => now()->subDays(2),
            'validated_by' => null,
            'created_by' => null,
            'parent_rate' => 200000,
            'teacher_rate' => 100000,
        ];
    }
}