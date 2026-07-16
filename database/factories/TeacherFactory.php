<?php

namespace Database\Factories;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'whatsapp' => '08123456789',
            'whatsapp_number' => '08123456789',
            'major' => fake()->randomElement(['Matematika', 'Bahasa Indonesia', 'IPA']),
            'subjects' => fake()->randomElement(['Matematika, Fisika', 'Bahasa Indonesia, Bahasa Inggris', 'IPA, Kimia']),
            'address' => fake()->address(),
            'bank_name' => 'BCA',
            'bank_account' => (string) fake()->numberBetween(10000000, 99999999),
            'bank_owner' => fake()->name(),
            'class_rate' => 50000,
            'status' => 'active',
        ];
    }
}