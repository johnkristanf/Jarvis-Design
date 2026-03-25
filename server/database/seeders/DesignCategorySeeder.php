<?php

namespace Database\Seeders;

use App\Models\DesignCategory;
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
                'name' => 'Basketball Apparel',
                'is_fixed_priced' => false,
                'fixed_price' => null,
                'styles' => [
                    ['name' => 'V-Neck', 'panel' => 'front'],
                    ['name' => 'Roundneck', 'panel' => 'front'],
                    ['name' => 'NBA Cut', 'panel' => 'back'],
                    ['name' => 'Regular Cut', 'panel' => 'back'],
                ],
            ],
            [
                'name' => 'Volleyball Apparel',
                'is_fixed_priced' => false,
                'fixed_price' => null,
            ],
            [
                'name' => 'T-shirts',
                'is_fixed_priced' => false,
                'fixed_price' => null,
                'styles' => [
                    ['name' => 'Round Neck'],
                    ['name' => 'V-Neck'],
                    ['name' => 'Tshirt - Hooddie'],
                    ['name' => 'Longsleeve Neck'],
                ],
            ],
            [
                'name' => 'Polo Shirts',
                'is_fixed_priced' => false,
                'fixed_price' => null,
                'styles' => [
                    ['name' => 'Regular Collar', 'attributes' => 'ZIPPER / BUTTONS'],
                    ['name' => 'Collar - Vneck', 'attributes' => 'ZIPPER / BUTTONS'],
                    ['name' => 'Chinese Collar', 'attributes' => 'ZIPPER / BUTTONS'],
                    ['name' => 'Turtle Neck', 'attributes' => 'ZIPPER / BUTTONS'],
                    ['name' => 'Regular Arm Sleeve'],
                    ['name' => 'Longer Arm Length', 'attributes' => 'ADD 0.75 INCHES'],
                ],
            ],
            [
                'name' => 'Varsity Jackets',
                'is_fixed_priced' => false,
                'fixed_price' => null,
            ],
            [
                'name' => 'Longsleeve Shirt',
                'is_fixed_priced' => false,
                'fixed_price' => null,
                'styles' => [
                    ['name' => 'Regular Collar', 'attributes' => 'ZIPPER / BUTTONS'],
                    ['name' => 'Collar - Vneck', 'attributes' => 'ZIPPER / BUTTONS'],
                    ['name' => 'Chinese Collar', 'attributes' => 'ZIPPER / BUTTONS'],
                    ['name' => 'Turtle Neck', 'attributes' => 'ZIPPER / BUTTONS'],
                    ['name' => 'Without Arm Cuffs'],
                    ['name' => 'With Arm Cuffs'],
                ],
            ],
            [
                'name' => 'Mugs',
                'is_fixed_priced' => false, // because it has two price variants
                'fixed_price' => null,
            ],
            [
                'name' => 'Tarpaulin',
                'is_fixed_priced' => true,
                'fixed_price' => 15.00, // per sq.ft
            ],
            [
                'name' => 'ID Card',
                'is_fixed_priced' => true,
                'fixed_price' => 60.00,
            ],
            [
                'name' => 'Banner',
                'is_fixed_priced' => true,
                'fixed_price' => 1000.00,
            ],
            [
                'name' => 'Mask',
                'is_fixed_priced' => true,
                'fixed_price' => 150.00,
            ],
            [
                'name' => 'Lanyard',
                'is_fixed_priced' => true,
                'fixed_price' => 500.00,
            ],
        ];

        foreach ($categories as $category) {
            $styles = $category['styles'] ?? [];
            unset($category['styles']);

            $createdCategory = DesignCategory::firstOrCreate(
                ['name' => $category['name']],
                $category
            );

            foreach ($styles as $style) {
                $style['panel'] = $style['panel'] ?? null;
                $style['attributes'] = $style['attributes'] ?? null;
                $createdCategory->productStyles()->firstOrCreate(
                    ['name' => $style['name'], 'panel' => $style['panel']],
                    $style
                );
            }
        }
    }
}
