<?php

namespace App\Events;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InstitutionRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Institution $institution,
        public ?string $reason = null,
        public ?User $actor = null
    ) {}
}
