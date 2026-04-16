<?php

namespace App\Interfaces;

use App\Models\Booking;

interface PaymentGatewayInterface
{
    public function createPaymentUrl(Booking $booking): string;
}
