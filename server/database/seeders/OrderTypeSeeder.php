<?php

namespace Database\Seeders;

use App\Models\OrderType;
use Illuminate\Database\Seeder;

class OrderTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orderTypes = [
            ['name' => 'uploaded'],
            ['name' => 'pre-made'],
        ];

        foreach ($orderTypes as $type) {
            OrderType::firstOrCreate($type);
        }
    }
}
