<?php

namespace App\Events;

use App\Models\Destination;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DestinationModerated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Destination $destination,
        public string $action,
        public ?string $note = null,
        public ?User $actor = null
    ) {}
}
