<?php

namespace App\Domain\Catalog\Services;

use App\Models\AvailabilitySlot;
use App\Models\Destination;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AvailabilityCalendarService
{
    /**
     * Bulk generate availability slots for a date range.
     *
     * @param Destination $destination
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $daysOfWeek (e.g. [1, 2, 3, 4, 5] for Mon-Fri, or 0-6)
     * @param int $capacity
     * @return int Number of slots created or updated
     */
    public function bulkGenerateSlots(
        Destination $destination,
        Carbon $startDate,
        Carbon $endDate,
        array $daysOfWeek,
        int $capacity
    ): int {
        if ($capacity < 1) {
            throw new InvalidArgumentException('Kapasitas harian minimal 1 peserta.');
        }

        $period = CarbonPeriod::create($startDate, $endDate);
        $count = 0;

        DB::transaction(function () use ($destination, $period, $daysOfWeek, $capacity, &$count) {
            foreach ($period as $date) {
                // Carbon dayOfWeek: 0 (Sunday) to 6 (Saturday)
                if (! in_array($date->dayOfWeek, $daysOfWeek)) {
                    continue;
                }

                $slot = AvailabilitySlot::firstOrNew([
                    'destination_id' => $destination->id,
                    'date' => $date->format('Y-m-d'),
                ]);

                if ($slot->exists) {
                    // Cannot lower capacity below already booked and held slots
                    $minRequired = $slot->booked_count + $slot->held_count;
                    $slot->capacity = max($capacity, $minRequired);
                } else {
                    $slot->capacity = $capacity;
                    $slot->booked_count = 0;
                    $slot->held_count = 0;
                    $slot->status = 'open';
                }

                $slot->save();
                $count++;
            }
        });

        return $count;
    }

    /**
     * Update single slot capacity.
     */
    public function updateSlotCapacity(AvailabilitySlot $slot, int $newCapacity): AvailabilitySlot
    {
        $minRequired = $slot->booked_count + $slot->held_count;

        if ($newCapacity < $minRequired) {
            throw new InvalidArgumentException(
                "Kapasitas tidak boleh lebih kecil dari jumlah slot yang sudah dipesan / ditahan ({$minRequired} pax)."
            );
        }

        $slot->capacity = $newCapacity;
        $slot->save();

        return $slot;
    }

    /**
     * Toggle slot status between open and closed.
     */
    public function toggleSlotStatus(AvailabilitySlot $slot): AvailabilitySlot
    {
        $slot->status = $slot->status === 'open' ? 'closed' : 'open';
        $slot->save();

        return $slot;
    }

    /**
     * Get monthly slots for destination calendar.
     */
    public function getMonthlySlots(Destination $destination, int $year, int $month): Collection
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = Carbon::create($year, $month, 1)->endOfMonth();

        return AvailabilitySlot::where('destination_id', $destination->id)
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->orderBy('date')
            ->get();
    }
}
