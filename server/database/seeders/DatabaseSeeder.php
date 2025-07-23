<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            AdminSeeder::class,
            ColorsSeeder::class,
            SizesSeeder::class,
            OrderTypeSeeder::class,
            OrderStatusSeeder::class,
            MaterialsCategorySeeder::class,
            FabricTypesSeeder::class,
            DesignCategorySeeder::class

            // FOR TESTING USE CASE ONLY: 
            // MaterialsSeeder::class,
            // DesignsSeeder::class,
            // DesignsMaterialsSeeder::class,

            // OrdersSeeder::class,

        ]);
    }
}
