<?php

namespace Database\Seeders;

use App\Models\Sizes;
use Illuminate\Database\Seeder;

class SizesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sizes = [
            'XXS',
            'XS',
            'S',
            'M',
            'L',
            'XL',
            'XXL',
            'XXL',
        ];

        foreach ($sizes as $size) {
            Sizes::firstOrCreate([
                'name' => $size,
            ]);
        }
    }
}
