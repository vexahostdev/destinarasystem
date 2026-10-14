<?php

namespace App\Domain\Notification\Exceptions;

use RuntimeException;

class RateLimitedNotificationException extends RuntimeException
{
    public function __construct(
        public int $retryAfter = 60,
        string $message = 'Rate limit exceeded'
    ) {
        parent::__construct($message);
    }
}
