<?php

namespace App\Events;

use App\Models\PartnerPayout;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PayoutTransferred
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PartnerPayout $payout,
        public ?User $actor = null
    ) {}
}
