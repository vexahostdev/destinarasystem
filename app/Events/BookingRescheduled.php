<?php

namespace App\Events;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingRescheduled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $oldDates,
        public ?User $actor = null
    ) {}
}
