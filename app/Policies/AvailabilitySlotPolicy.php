<?php

namespace App\Policies;

use App\Models\AvailabilitySlot;
use App\Models\Destination;
use App\Models\User;

class AvailabilitySlotPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isInternal() || $user->isPartner();
    }

    public function manage(User $user, AvailabilitySlot|Destination $target): bool
    {
        if ($user->isInternal() && $user->can('availability.manage')) {
            return true;
        }

        $destination = $target instanceof AvailabilitySlot ? $target->destination : $target;

        if ($user->isPartner() && $destination && $destination->partner_id === $user->id) {
            return true;
        }

        return false;
    }
}
