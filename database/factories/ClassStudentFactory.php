<?php

namespace Database\Factories;

use App\Models\ClassStudent;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassStudentFactory extends Factory
{
    protected $model = ClassStudent::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'whatsapp_primary' => '08123456789',
            'whatsapp_secondary' => '08123456789',
            'rate_per_meeting' => 30000,
            'status' => 'active',
            'notes' => fake()->sentence(),
        ];
    }
}