<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@rasanusantara.id'],
            [
                'name' => 'Admin Rasa Nusantara',
                'password' => 'password',
                'role' => 'admin',
            ],
        );
    }
}
