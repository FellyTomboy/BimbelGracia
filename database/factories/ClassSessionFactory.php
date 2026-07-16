<?php

namespace Database\Factories;

use App\Models\ClassSession;
use App\Models\ClassGroup;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassSessionFactory extends Factory
{
    protected $model = ClassSession::class;

    public function definition(): array
    {
        return [
            'class_group_id' => ClassGroup::factory(),
            'teacher_id' => Teacher::factory(),
            'subject' => fake()->randomElement(['Matematika', 'Bahasa Inggris', 'IPA']),
            'session_date' => fake()->date(),
            'session_time' => '15:00:00',
            'notes' => fake()->sentence(),
        ];
    }
}