<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentAmountMismatch
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Payment $payment,
        public int $expectedAmount,
        public int $receivedAmount,
        public ?string $externalId = null
    ) {}
}
