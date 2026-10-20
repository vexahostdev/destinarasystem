<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isInternal() && $user->can('documents.view');
    }

    public function view(User $user, Document $document): bool
    {
        if ($user->isInternal() && $user->can('documents.view')) {
            return true;
        }

        // Buyer who owns the booking
        if ($user->isBuyer() && $document->booking && $document->booking->user_id === $user->id) {
            return true;
        }

        // Partner who owns the destination of the booking
        if ($user->isPartner() && $document->booking && $document->booking->destination->partner_id === $user->id) {
            return true;
        }

        // Partner who owns the payout
        if ($user->isPartner() && $document->payout && $document->payout->partner_id === $user->id) {
            return true;
        }

        return false;
    }

    public function regenerate(User $user, Document $document): bool
    {
        return $user->isInternal() && $user->can('documents.regenerate');
    }
}
