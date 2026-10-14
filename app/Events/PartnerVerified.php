<?php

namespace App\Events;

use App\Models\PartnerProfile;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PartnerVerified
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PartnerProfile $partnerProfile,
        public ?User $actor = null
    ) {}
}
