<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function moderate(User $user, Review $review): bool
    {
        return $user->isInternal() && $user->can('reviews.moderate');
    }

    public function reply(User $user, Review $review): bool
    {
        return $user->isPartner() &&
            $review->destination->partner_id === $user->id &&
            empty($review->partner_reply);
    }
}
