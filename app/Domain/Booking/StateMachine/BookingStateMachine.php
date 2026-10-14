<?php

namespace App\Domain\Booking\StateMachine;

use App\Domain\Audit\AuditLogger;
use App\Domain\Booking\Exceptions\InvalidBookingTransitionException;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\User;

class BookingStateMachine
{
    /**
     * State transition table according to SPEC §7.1.
     *
     * @var array<string, list<string>>
     */
    protected const ALLOWED_TRANSITIONS = [
        'null' => ['menunggu_pembayaran'],
        'menunggu_pembayaran' => ['dibayar', 'kedaluwarsa', 'batal'],
        'dibayar' => ['dikonfirmasi', 'batal'],
        'dikonfirmasi' => ['berlangsung', 'batal', 'selesai'],
        'berlangsung' => ['selesai'],
        'selesai' => [],
        'batal' => [],
        'kedaluwarsa' => [],
    ];

    /**
     * Check if transition is allowed.
     */
    public function canTransition(?BookingStatus $from, BookingStatus $to): bool
    {
        $fromKey = $from ? $from->value : 'null';
        $allowed = self::ALLOWED_TRANSITIONS[$fromKey] ?? [];

        return in_array($to->value, $allowed, true);
    }

    /**
     * Execute status transition on booking.
     * Must be called within DB::transaction.
     */
    public function transition(
        Booking $booking,
        BookingStatus $to,
        ?User $actor = null,
        ?string $note = null
    ): Booking {
        $from = $booking->status;

        if (! $this->canTransition($from, $to)) {
            throw new InvalidBookingTransitionException($from, $to);
        }

        // Apply timestamp attributes according to SPEC §7.1
        $now = now();
        match ($to) {
            BookingStatus::MENUNGGU_PEMBAYARAN => null,
            BookingStatus::DIBAYAR => $booking->paid_at = $now,
            BookingStatus::DIKONFIRMASI => $booking->confirmed_at = $now,
            BookingStatus::BERLANGSUNG => $booking->checked_in_at = $now,
            BookingStatus::SELESAI => $booking->completed_at = $now,
            BookingStatus::BATAL => $booking->cancelled_at = $now,
            BookingStatus::KEDALUWARSA => null,
        };

        if ($to === BookingStatus::BATAL && $actor) {
            $booking->cancelled_by = $actor->id;
        }

        $booking->status = $to;
        $booking->save();

        // Write status timeline history
        BookingStatusHistory::create([
            'booking_id' => $booking->id,
            'from_status' => $from,
            'to_status' => $to,
            'actor_id' => $actor?->id,
            'note' => $note,
            'created_at' => $now,
        ]);

        $actorDesc = $actor ? "oleh {$actor->name}" : 'oleh sistem';
        $fromLabel = $from ? $from->label() : 'Awal';
        AuditLogger::log(
            action: 'booking_transition',
            target: $booking,
            description: "Status booking {$booking->booking_code} berpindah dari [{$fromLabel}] ke [{$to->label()}] {$actorDesc}. {$note}",
            userId: $actor?->id
        );

        return $booking;
    }
}
