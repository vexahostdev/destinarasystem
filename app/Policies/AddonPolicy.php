<?php

namespace App\Policies;

use App\Models\Addon;
use App\Models\Destination;
use App\Models\User;

class AddonPolicy
{
    public function manage(User $user, Addon|Destination $target): bool
    {
        if ($user->isInternal() && $user->can('addons.manage')) {
            return true;
        }

        $destination = $target instanceof Addon ? $target->destination : $target;

        if ($user->isPartner() && $destination && $destination->partner_id === $user->id) {
            return true;
        }

        return false;
    }
}
