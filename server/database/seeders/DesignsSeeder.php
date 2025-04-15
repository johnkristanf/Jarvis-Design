<?php

namespace Database\Seeders;

use App\Models\Designs;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DesignsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // THINK OF THIS SEQUENCE CREATION PROCCESS AS IF THIS IS THE REAL INSERTION

        Designs::create([
            'name' => 'Test Jersey 1',
            'price' => 700,
            'quantity' => 100,
            'image_path' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcREPwgVqXQsSbFZ4u1twX7cXH0w7tsbyZQYaA&s'
        ]);

    }
}
