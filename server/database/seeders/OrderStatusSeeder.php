<?php

namespace Database\Seeders;

use App\Models\OrderStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orderStatus = [
            ['name' => 'in_progress'],
            ['name' => 'pick_up'],
            ['name' => 'delivery'],
            ['name' => 'completed'],
        ];

        foreach ($orderStatus as $status) {
            OrderStatus::firstOrCreate($status);
        }
    }
}
