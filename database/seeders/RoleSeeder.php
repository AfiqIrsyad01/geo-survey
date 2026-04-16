<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@geosurvey.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Create HOD
        User::create([
            'name' => 'HOD User',
            'email' => 'hod@geosurvey.com',
            'password' => Hash::make('password'),
            'role' => 'hod',
        ]);

        // Create Staff
        User::create([
            'name' => 'Staff User',
            'email' => 'staff@geosurvey.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
        ]);
    }
}
