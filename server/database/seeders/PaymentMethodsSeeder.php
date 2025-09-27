<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            [
                'code' => 'cash',
                'name' => 'Cash'
            ],

            [
                'code' => 'gcash',
                'name' => 'GCash'
            ]
        ];

        foreach ($methods as $method) {
            PaymentMethod::create($method);
        }
    }
}
