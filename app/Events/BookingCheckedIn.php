<?php

namespace App\Events;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingCheckedIn
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public int $actualPax,
        public ?User $actor = null
    ) {}
}
