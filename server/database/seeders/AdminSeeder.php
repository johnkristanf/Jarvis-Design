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

            User::firstOrCreate([
            'name' => 'Administrator',
            'username' => 'admin@admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('admin123'),
            'role_id' => $adminRoleID,
        ]);

        User::firstOrCreate([
            'name' => 'John Kristan Torremocha',
            'username' => 'jake',
            'email' => 'johnkristan01@gmail.com',
            'password' => Hash::make('jkgwapo123'),
            'role_id' => $userRoleID,
        ]);
    }
}
