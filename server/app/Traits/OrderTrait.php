<?php

namespace App\Traits;

trait OrderTrait
{
    public function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());
    }
}
