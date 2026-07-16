<?php

namespace Database\Factories;

use App\Models\ClassGroup;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassGroupFactory extends Factory
{
    protected $model = ClassGroup::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'subject' => fake()->randomElement(['Matematika', 'Bahasa Inggris', 'IPA', 'Kimia', 'Fisika']),
            'teacher_id' => Teacher::factory(),
            'notes' => fake()->sentence(),
        ];
    }
}