<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@tawreedhub.test'],
            [
                'name' => 'TawreedHub Admin',
                'phone' => '01000000000',
                'password' => 'password',
                'role' => UserRole::Admin,
                'locale' => 'ar',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ],
        );
    }
}
