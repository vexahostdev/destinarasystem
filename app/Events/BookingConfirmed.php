<?php

namespace App\Events;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingConfirmed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public ?User $actor = null
    ) {}
}
