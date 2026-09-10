<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $map = [
                'Privat TK'         => 'TK',
                'Privat SD'         => 'SD',
                'Privat SMP'        => 'SMP',
                'Privat SMA'        => 'SMA',
                'Privat Online SD'  => 'SD',
                'Privat Online SMP' => 'SMP',
                'Privat Online SMA' => 'SMA',
                'Privat Mengaji'    => 'mengaji',
                'Kelas TK'          => 'TK',
                'Kelas SD'          => 'SD',
                'Kelas SMP'         => 'SMP',
                'Kelas SMA'         => 'SMA',
            ];

            foreach ($map as $name => $division) {
                DB::table('programs')
                    ->where('name', $name)
                    ->update(['division' => $division]);
            }
        });
    }

    public function down(): void
    {
        DB::table('programs')->update(['division' => null]);
    }
};
