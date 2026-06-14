<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@appsibiti.com'],
            [
                'full_name' => 'System Admin',
                'password'  => Hash::make('password'),
                'role'      => 'ADMIN',
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'pharmacist@appsibiti.com'],
            [
                'full_name' => 'Demo Pharmacist',
                'password'  => Hash::make('password'),
                'role'      => 'PHARMACIST',
                'email_verified_at' => now(),
            ]
        );

        $this->call(DrugDictionarySeeder::class);
    }
}
