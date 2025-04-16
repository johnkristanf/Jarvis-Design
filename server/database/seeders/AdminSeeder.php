<?php

namespace Database\Seeders;

use App\Models\Roles;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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

        User::firstOrCreate([
            'name' => "Administrator",
            'username' => 'admin@admin',
            'password' => Hash::make("admin123"),
            'role_id'  => $adminRoleID
        ]);
    }
}
