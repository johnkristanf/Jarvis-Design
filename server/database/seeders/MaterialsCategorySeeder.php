<?php

namespace Database\Seeders;

use App\Models\MaterialsCategory;
use Illuminate\Database\Seeder;

class MaterialsCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $catergories = [
            'Printing & Sublimation Equipment',
            'Printable Blanks / Products',
            'Paper & Consumables',
            'Clothing & Fabric Supplies',
            'Tools & Accessories',
            'Packaging Materials',
        ];

        foreach ($catergories as $category) {
            MaterialsCategory::firstOrCreate([
                'name' => $category,
            ]);
        }
    }
}
