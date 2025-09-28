<?php

namespace App\Traits;

trait OrderTrait
{
    public function generateOrderNumber(): string
    {
        return 'ORD-'.date('Ymd').'-'.strtoupper(uniqid());
    }


    public function generatePaymentNumber(): string
    {
        return 'PAY-'.date('Ymd').'-'.strtoupper(uniqid());
    }
}
