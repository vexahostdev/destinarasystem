<?php

namespace App\Policies;

use App\Models\PartnerPayout;
use App\Models\User;

class PayoutPolicy
{
    public function viewAny(User $user): bool
    {
        return ($user->isInternal() && $user->can('payouts.view')) || $user->isPartner();
    }

    public function view(User $user, PartnerPayout $payout): bool
    {
        if ($user->isInternal() && $user->can('payouts.view')) {
            return true;
        }

        if ($user->isPartner() && $payout->partner_id === $user->id) {
            return true;
        }

        return false;
    }

    public function process(User $user, PartnerPayout $payout): bool
    {
        return $user->isInternal() && $user->can('payouts.process');
    }
}
