<?php

namespace App\Domain\Booking\Exceptions;

use App\Enums\BookingStatus;
use DomainException;

class InvalidBookingTransitionException extends DomainException
{
    public function __construct(?BookingStatus $from, BookingStatus $to)
    {
        $fromName = $from ? $from->label() : 'Baru';
        $toName = $to->label();

        parent::__construct(
            "Transisi status booking tidak valid dari [{$fromName}] ke [{$toName}]. (SPEC §7.1)"
        );
    }
}
