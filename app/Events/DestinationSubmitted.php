<?php

namespace App\Events;

use App\Models\Destination;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DestinationSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Destination $destination,
        public ?User $actor = null
    ) {}
}
