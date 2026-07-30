<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WebsiteDatasetSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // ──────────────────────────────────────────────
        // 1. ADMIN
        // ──────────────────────────────────────────────
        User::query()->firstOrCreate(
            ['phone' => '0817-0302-7942'],
            [
                'name' => 'Admin Bimbel',
                'email' => 'mybimbelgracia@gmail.com',
                'role' => UserRole::Admin,
                'password' => Hash::make('bimbelgracia2026'),
                'must_change_password' => true,
            ]
        );
    }
}