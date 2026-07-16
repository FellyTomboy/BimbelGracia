<?php

namespace Database\Factories;

use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'type' => fake()->randomElement(['privat', 'kelas', 'online']),
            'subject' => fake()->randomElement(['Matematika', 'Bahasa Inggris', 'IPA', 'Kimia', 'Fisika']),
            'description' => fake()->sentence(),
            'default_parent_rate' => 200000,
            'default_teacher_rate' => 100000,
            'status' => 'active',
        ];
    }
}