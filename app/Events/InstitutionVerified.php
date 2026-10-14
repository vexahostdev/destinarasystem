<?php

namespace App\Events;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InstitutionVerified
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Institution $institution,
        public ?User $actor = null
    ) {}
}
