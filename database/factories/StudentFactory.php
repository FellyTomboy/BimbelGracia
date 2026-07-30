<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => [fake()->name()],
            'whatsapp' => '08123456789',
            'whatsapp_primary' => '08123456789',
            'whatsapp_secondary' => '08123456789',
            'address' => fake()->address(),
            'status' => 'active',
        ];
    }
}