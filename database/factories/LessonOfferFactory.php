<?php

namespace Database\Factories;

use App\Models\LessonOffer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonOfferFactory extends Factory
{
    protected $model = LessonOffer::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('????????')),
            'education_level' => fake()->randomElement(['SD', 'SMP', 'SMA']),
            'subject' => fake()->randomElement(['Matematika', 'Fisika', 'Kimia', 'Bahasa Inggris']),
            'schedules' => [['day' => fake()->dayOfWeek(), 'time' => '15:00']],
            'note' => fake()->sentence(),
            'status' => 'open',
            'contact_whatsapp' => '08123456789',
            'created_by' => User::factory(),
        ];
    }
}