<?php

namespace Database\Seeders;

use App\Models\DiningTable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DiningTableSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $code = 'T'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);

            DiningTable::firstOrCreate(
                ['code' => $code],
                [
                    'name' => 'Meja '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                    'capacity' => $i <= 4 ? 2 : ($i <= 8 ? 4 : 6),
                    'qr_token' => (string) Str::uuid(),
                    'is_active' => true,
                ],
            );
        }
    }
}
