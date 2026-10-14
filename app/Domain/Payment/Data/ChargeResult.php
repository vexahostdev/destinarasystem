<?php

namespace App\Domain\Payment\Data;

use Carbon\Carbon;

class ChargeResult
{
    public function __construct(
        public string $checkoutUrl,
        public ?string $gatewayReference,
        public ?Carbon $expiresAt = null,
        public array $raw = []
    ) {}
}
