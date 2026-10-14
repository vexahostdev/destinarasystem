<?php

namespace App\Events;

use App\Models\PartnerProfile;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PartnerRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PartnerProfile $partnerProfile,
        public ?string $reason = null,
        public ?User $actor = null
    ) {}
}
