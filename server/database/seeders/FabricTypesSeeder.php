<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FabricTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('fabric_types')->insert([
            ['name' => 'Cooltech Fabric', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Semistepline Fabric', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Mixed Fabric', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cooltech and Micrositeline', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Warmer, Cooltech Fabric or Semistepline', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
