<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $phone = env('ADMIN_SEED_PHONE', '01000000000');
        $password = env('ADMIN_SEED_PASSWORD', 'password');

        User::query()->updateOrCreate(
            ['phone' => $phone],
            [
                'name' => env('ADMIN_SEED_NAME', 'مدير النظام'),
                'password' => $password,
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
                'subscription_expires_at' => null,
                'activation_code' => null,
            ]
        );
    }
}
