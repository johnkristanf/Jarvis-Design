<?php

namespace Database\Seeders;

use App\Models\Materials;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaterialsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Materials::create(['name' => 'Cotton', 'quantity' => 1000]);
        Materials::create(['name' => 'Polyester', 'quantity' => 500]);
        
    }
}
