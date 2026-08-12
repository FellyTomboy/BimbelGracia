<?php

namespace Database\Factories;

use App\Models\ParentModel;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'parent_id' => ParentModel::factory(),
            'nickname' => fake()->firstName(),
            'full_name' => fake()->name(),
            'address' => fake()->address(),
            'status' => 'active',
        ];
    }
}