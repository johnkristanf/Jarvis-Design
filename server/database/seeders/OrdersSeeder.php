<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class OrdersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $orderId = 'ORD-'.now()->timestamp.'-'.str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        \App\Models\Orders::firstOrCreate([
            'order_id' => $orderId,
            'paid_amount' => 999.99,
            'quantity' => 5,
            'option' => 'delivery',

            'color_id' => 1, // assumes color with ID 1 exists
            'size_id' => 1, // assumes size with ID 1 exists
            'type_id' => 1, // assumes order_type with ID 1 exists
            'design_id' => 1, // assumes design with ID 1 exists
            'status_id' => 1, // assumes order_status with ID 1 exists
            'user_id' => 2, // assumes user with ID 1 exists
        ]);
    }
}
