<?php

namespace Database\Seeders;

use App\Models\Colors;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ColorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $colors = [
            'White',
            'Black',
            'Blue',
            'Green',
            'Gray',
            'Yellow',
        ];

        foreach ($colors as $color) {
            Colors::firstOrCreate([
                'name' => $color
            ]);
        }

    }
}
