<?php

namespace App\Events;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentProofRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Payment $payment,
        public Booking $booking,
        public string $reason
    ) {}
}
