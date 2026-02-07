<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $defaultPassword = config('bimbel.default_password', '12345678');

        User::query()->firstOrCreate(
            ['email' => 'admin@bimbelgracia.test'],
            [
                'name' => 'Admin',
                'role' => UserRole::Admin,
                'password' => Hash::make($defaultPassword),
                'must_change_password' => true,
            ]
        );
    }
}
