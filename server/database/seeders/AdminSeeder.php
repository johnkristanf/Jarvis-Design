<?php

namespace Database\Seeders;

use App\Models\Roles;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
        /**
         * Run the database seeds.
         */
        public function run(): void
        {

            $adminRoleID = Roles::where('name', 'admin')->first()->id;
            $userRoleID = Roles::where('name', 'user')->first()->id;

            // Check by email to prevent duplicates
            User::firstOrCreate(
                ['email' => 'jarvisdesigns01@gmail.com'], // Search criteria
                [
                    'name' => 'Jarvis Designs',
                    'username' => 'jarvisdesigns',
                    'password' => Hash::make('admin123'),
                    'role_id' => $adminRoleID,
                ]
            );

            User::firstOrCreate(
                ['email' => 'johnkristan01@gmail.com'], // Search criteria
                [
                    'name' => 'John Kristan Torremocha',
                    'username' => 'jake',
                    'password' => Hash::make('123456789'),
                    'role_id' => $userRoleID,
                ]
            );
    }
}
