<?php

namespace Database\Seeders;

use App\Models\DesignCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DesignCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Mugs'
            ],

            [
                'name' => 'T-shirt'
            ],

            [
                'name' => 'Tarpaulin'
            ],
        ];


        foreach ($categories as $category) {
            DesignCategory::firstOrCreate($category);
        }
    }
}
