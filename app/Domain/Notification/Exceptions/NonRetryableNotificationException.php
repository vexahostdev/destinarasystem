<?php

namespace App\Domain\Notification\Exceptions;

use RuntimeException;

class NonRetryableNotificationException extends RuntimeException
{
    // Exception that indicates a 4xx error where retrying will not help (e.g. invalid phone, bad api key)
}
