<?php

namespace Database\Factories;

use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'bank_name' => fake()->randomElement(['BCA', 'BRI', 'Mandiri', 'BNI']),
            'account_number' => fake()->unique()->numerify('##########'),
            'account_holder' => fake()->name(),
            'status' => 'active',
        ];
    }
}