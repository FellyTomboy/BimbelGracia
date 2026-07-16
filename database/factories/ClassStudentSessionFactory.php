<?php

namespace Database\Factories;

use App\Models\ClassStudentSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassStudentSessionFactory extends Factory
{
    protected $model = ClassStudentSession::class;

    public function definition(): array
    {
        return [
            'session_date' => fake()->date(),
            'start_time' => '16:00:00',
            'end_time' => '17:00:00',
            'notes' => fake()->sentence(),
        ];
    }
}