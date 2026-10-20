<?php

namespace App\Events;

use App\Models\PartnerPayout;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PayoutCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PartnerPayout $payout
    ) {}
}
