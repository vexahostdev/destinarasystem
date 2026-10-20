<?php

namespace App\Events;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $reason,
        public ?User $actor = null
    ) {}
}
