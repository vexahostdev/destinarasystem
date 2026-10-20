<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isInternal() || $user->isBuyer() || $user->isPartner();
    }

    public function view(User $user, Booking $booking): bool
    {
        if ($user->isInternal() && $user->can('bookings.view')) {
            return true;
        }

        if ($user->isBuyer() && $booking->user_id === $user->id) {
            return true;
        }

        if ($user->isPartner() && $booking->destination->partner_id === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isBuyer();
    }

    public function update(User $user, Booking $booking): bool
    {
        return $user->isInternal() && $user->can('bookings.update');
    }

    public function cancel(User $user, Booking $booking): bool
    {
        if ($user->isInternal() && $user->can('bookings.cancel')) {
            return true;
        }

        // Partner can reject booking waiting for confirmation
        if ($user->isPartner() && $booking->destination->partner_id === $user->id) {
            return $booking->status === BookingStatus::DIBAYAR;
        }

        // Buyer can cancel if waiting payment, or if confirmed and allow_buyer_cancel is true
        if ($user->isBuyer() && $booking->user_id === $user->id) {
            if ($booking->status === BookingStatus::MENUNGGU_PEMBAYARAN) {
                return true;
            }

            if (in_array($booking->status, [BookingStatus::DIBAYAR, BookingStatus::DIKONFIRMASI])) {
                return (bool) setting('allow_buyer_cancel', true);
            }
        }

        return false;
    }

    public function confirm(User $user, Booking $booking): bool
    {
        if ($user->isInternal() && $user->can('bookings.force_confirm')) {
            return true;
        }

        if ($user->isPartner() && $booking->destination->partner_id === $user->id) {
            return $booking->status === BookingStatus::DIBAYAR && (bool) $user->partnerProfile?->isVerified();
        }

        return false;
    }

    public function pay(User $user, Booking $booking): bool
    {
        return $user->isBuyer() && $booking->user_id === $user->id && $booking->status === BookingStatus::MENUNGGU_PEMBAYARAN;
    }

    public function checkIn(User $user, Booking $booking): bool
    {
        return $user->isPartner() && $booking->destination->partner_id === $user->id && $booking->status === BookingStatus::DIKONFIRMASI;
    }

    public function uploadPermit(User $user, Booking $booking): bool
    {
        if (! $user->isPartner() || $booking->destination->partner_id !== $user->id) {
            return false;
        }

        if ($booking->status !== BookingStatus::DIKONFIRMASI) {
            return false;
        }

        return $booking->documents()
            ->where('type', \App\Enums\DocumentType::PERMIT_DRAFT)
            ->where('status', '!=', \App\Enums\DocumentStatus::VOID)
            ->exists();
    }

    public function review(User $user, Booking $booking): bool
    {
        return $user->isBuyer() && $booking->user_id === $user->id && $booking->status === BookingStatus::SELESAI && ! $booking->review;
    }

    public function revealContact(User $user, Booking $booking): bool
    {
        if ($user->isInternal()) {
            return true;
        }

        if ($user->isPartner() && $booking->destination->partner_id === $user->id) {
            return true;
        }

        if ($user->isBuyer() && $booking->user_id === $user->id) {
            return in_array($booking->status, BookingStatus::contactRevealStatuses(), true);
        }

        return false;
    }
}
