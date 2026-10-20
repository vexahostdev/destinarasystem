<?php

namespace App\Domain\Booking\Services;

use App\Models\AvailabilitySlot;
use App\Models\Booking;
use App\Models\BookingSlotAllocation;
use App\Models\Destination;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AvailabilityService
{
    /**
     * Check if a destination has enough open capacity for all dates in range.
     */
    public function checkDateRangeAvailable(Destination $destination, Carbon $start, Carbon $end, int $pax): bool
    {
        $period = CarbonPeriod::create($start, $end);

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $slot = AvailabilitySlot::where('destination_id', $destination->id)
                ->whereDate('date', $dateStr)
                ->first();

            if (! $slot) {
                if ($destination->default_daily_capacity === null) {
                    return false;
                }
                if ($destination->default_daily_capacity < $pax) {
                    return false;
                }
            } else {
                if ($slot->status !== 'open' || $slot->remaining_capacity < $pax) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Hold slots for a booking across its date range.
     * Must be called inside a DB transaction with lockForUpdate.
     */
    public function holdSlots(Booking $booking): void
    {
        $destination = $booking->destination;
        $pax = $booking->participant_count;
        $period = CarbonPeriod::create($booking->planned_date_start, $booking->planned_date_end);

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');

            // Find or create slot with row lock
            $slot = AvailabilitySlot::where('destination_id', $destination->id)
                ->whereDate('date', $dateStr)
                ->lockForUpdate()
                ->first();

            if (! $slot) {
                if ($destination->default_daily_capacity === null) {
                    throw new RuntimeException("Tanggal {$dateStr} tidak dibuka untuk pemesanan.");
                }

                $slot = AvailabilitySlot::create([
                    'destination_id' => $destination->id,
                    'date' => $dateStr,
                    'capacity' => $destination->default_daily_capacity,
                    'booked_count' => 0,
                    'held_count' => 0,
                    'status' => 'open',
                ]);

                // Re-lock
                $slot = AvailabilitySlot::where('id', $slot->id)->lockForUpdate()->first();
            }

            if ($slot->status !== 'open') {
                throw new RuntimeException("Slot pada tanggal {$dateStr} sedang ditutup oleh pengelola.");
            }

            if ($slot->remaining_capacity < $pax) {
                throw new RuntimeException(
                    "Kapasitas pada tanggal {$dateStr} tidak mencukupi (sisa {$slot->remaining_capacity} pax, dibutuhkan {$pax} pax)."
                );
            }

            // Hold slot
            $slot->held_count += $pax;
            $slot->save();

            BookingSlotAllocation::create([
                'booking_id' => $booking->id,
                'availability_slot_id' => $slot->id,
                'pax' => $pax,
                'type' => 'held',
            ]);
        }
    }

    /**
     * Promote held slots to booked when payment is completed.
     */
    public function promoteHeldToBooked(Booking $booking): void
    {
        $allocations = BookingSlotAllocation::where('booking_id', $booking->id)
            ->where('type', 'held')
            ->get();

        foreach ($allocations as $allocation) {
            $slot = AvailabilitySlot::where('id', $allocation->availability_slot_id)
                ->lockForUpdate()
                ->first();

            if ($slot) {
                $slot->held_count = max(0, $slot->held_count - $allocation->pax);
                $slot->booked_count += $allocation->pax;
                $slot->save();
            }

            $allocation->type = 'booked';
            $allocation->save();
        }
    }

    /**
     * Release held or booked slots back to capacity (when cancelled or expired).
     */
    public function releaseSlots(Booking $booking): void
    {
        $allocations = BookingSlotAllocation::where('booking_id', $booking->id)->get();

        foreach ($allocations as $allocation) {
            $slot = AvailabilitySlot::where('id', $allocation->availability_slot_id)
                ->lockForUpdate()
                ->first();

            if ($slot) {
                if ($allocation->type === 'held') {
                    $slot->held_count = max(0, $slot->held_count - $allocation->pax);
                } elseif ($allocation->type === 'booked') {
                    $slot->booked_count = max(0, $slot->booked_count - $allocation->pax);
                }
                $slot->save();
            }

            $allocation->delete();
        }
    }
}
