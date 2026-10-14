<?php

namespace App\Domain\Payment\Data;

use App\Enums\PaymentStatus;
use Carbon\Carbon;

class WebhookResult
{
    public function __construct(
        public string $eventId,
        public string $externalId,
        public PaymentStatus $status,
        public ?int $amount = null,
        public ?Carbon $paidAt = null,
        public ?string $paymentMethod = null,
        public ?string $paymentChannel = null,
        public ?string $gatewayReference = null,
        public array $raw = []
    ) {}
}
