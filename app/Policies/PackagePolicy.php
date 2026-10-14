<?php

namespace App\Policies;

use App\Models\Destination;
use App\Models\Package;
use App\Models\User;

class PackagePolicy
{
    public function manage(User $user, Package|Destination $target): bool
    {
        if ($user->isInternal() && $user->can('packages.manage')) {
            return true;
        }

        $destination = $target instanceof Package ? $target->destination : $target;

        if ($user->isPartner() && $destination && $destination->partner_id === $user->id) {
            return true;
        }

        return false;
    }
}
