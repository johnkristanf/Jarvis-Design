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
            ],
            [
                'name' => 'Polo Shirts',
                'is_fixed_priced' => false,
                'fixed_price' => null,
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
            DesignCategory::firstOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
