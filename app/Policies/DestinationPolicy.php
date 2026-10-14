<?php

namespace App\Policies;

use App\Enums\DestinationStatus;
use App\Models\Destination;
use App\Models\User;

class DestinationPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Destination $destination): bool
    {
        if ($destination->isPublished()) {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($user->isInternal() && $user->can('destinations.view')) {
            return true;
        }

        if ($user->isPartner() && $destination->partner_id === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($user->isInternal() && $user->can('destinations.create')) {
            return true;
        }

        if ($user->isPartner()) {
            return (bool) $user->partnerProfile?->isVerified();
        }

        return false;
    }

    public function update(User $user, Destination $destination): bool
    {
        if ($user->isInternal() && $user->can('destinations.update')) {
            return true;
        }

        if ($user->isPartner() && $destination->partner_id === $user->id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Destination $destination): bool
    {
        if ($user->isInternal() && $user->can('destinations.delete')) {
            return true;
        }

        if ($user->isPartner() && $destination->partner_id === $user->id) {
            return $destination->status === DestinationStatus::DRAFT;
        }

        return false;
    }

    public function verify(User $user, Destination $destination): bool
    {
        return $user->isInternal() && $user->can('destinations.verify');
    }

    public function feature(User $user, Destination $destination): bool
    {
        return $user->isInternal() && $user->can('destinations.feature');
    }
}
