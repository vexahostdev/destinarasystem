<?php

namespace App\Domain\Booking\Services;

use App\Domain\Audit\AuditLogger;
use App\Domain\Booking\StateMachine\BookingStateMachine;
use App\Domain\Notification\Services\NotificationDispatcher;
use App\Enums\BookingStatus;
use App\Events\BookingCheckedIn;
use App\Models\Booking;
use App\Models\CheckIn;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class CheckInService
{
    public function __construct(
        protected BookingStateMachine $stateMachine,
        protected NotificationDispatcher $notifier
    ) {}

    /**
     * Process QR scan check-in for a confirmed booking (SPEC §7.1, §8.8, §11).
     */
    public function processCheckIn(
        Booking $booking,
        User $scanner,
        int $actualPax,
        ?string $note = null
    ): CheckIn {
        return DB::transaction(function () use ($booking, $scanner, $actualPax, $note) {
            $locked = Booking::where('id', $booking->id)->lockForUpdate()->first();

            if ($locked->status !== BookingStatus::DIKONFIRMASI) {
                throw new InvalidArgumentException(
                    "Check-in hanya dapat dilakukan untuk booking yang berstatus dikonfirmasi (status saat ini: {$locked->status->label()})."
                );
            }

            // Verify destination ownership for partner scanner
            if ($scanner->isPartner() && $locked->destination && $locked->destination->partner_id !== $scanner->id) {
                throw new InvalidArgumentException('Anda bukan pengelola destinasi dari pesanan ini.');
            }

            // Date validation: visit range with tolerance (H-1 start to end + 1 day) (SPEC §11)
            $now = Carbon::now()->startOfDay();
            $earliestAllowed = Carbon::parse($locked->planned_date_start)->subDay()->startOfDay();
            $latestAllowed = Carbon::parse($locked->planned_date_end)->addDay()->endOfDay();

            if ($now->lt($earliestAllowed) || $now->gt($latestAllowed)) {
                throw new InvalidArgumentException(
                    'Check-in hanya dapat dilakukan pada rentang jadwal kunjungan ('.
                    Carbon::parse($locked->planned_date_start)->format('d/m/Y').' s.d. '.
                    Carbon::parse($locked->planned_date_end)->format('d/m/Y').').'
                );
            }

            if (CheckIn::where('booking_id', $locked->id)->exists()) {
                throw new RuntimeException("Rombongan booking {$locked->booking_code} sudah melakukan check-in sebelumnya.");
            }

            // Create check-in record
            $checkIn = CheckIn::create([
                'booking_id' => $locked->id,
                'scanned_by' => $scanner->id,
                'scanned_at' => now(),
                'actual_participant_count' => $actualPax,
                'note' => $note,
            ]);

            // Transition status to BERLANGSUNG
            $this->stateMachine->transition(
                booking: $locked,
                to: BookingStatus::BERLANGSUNG,
                actor: $scanner,
                note: "Rombongan tiba di lokasi dan check-in ({$actualPax} peserta aktual)."
            );

            // Dispatch domain event (SPEC §10.2)
            BookingCheckedIn::dispatch($locked, $actualPax, $scanner);

            AuditLogger::log(
                action: 'booking_checkin',
                target: $locked,
                description: "Check-in rombongan {$locked->booking_code} diverifikasi oleh {$scanner->name} ({$actualPax} peserta aktual).",
                userId: $scanner->id
            );

            return $checkIn;
        });
    }

    /**
     * Auto-complete bookings that finished their visit (SPEC §7.1, §12).
     */
    public function autoCompleteFinishedBookings(): int
    {
        $afterDays = (int) setting('auto_complete_after_days', 1);
        $threshold = now()->subDays($afterDays)->endOfDay();

        // 1. Bookings in 'berlangsung' that passed visit end date -> auto-complete
        $ongoingBookings = Booking::where('status', BookingStatus::BERLANGSUNG)
            ->whereDate('planned_date_end', '<=', $threshold)
            ->get();

        $bookingService = app(BookingService::class);
        $count = 0;

        foreach ($ongoingBookings as $booking) {
            $bookingService->complete(
                booking: $booking,
                actor: null,
                note: 'Kunjungan otomatis ditandai selesai oleh sistem setelah melewati batas jadwal kunjungan.'
            );

            $this->notifier->send('booking.completed', [
                'booking' => $booking,
                'user' => $booking->user,
                'destination_name' => $booking->destination->name,
                'booking_code' => $booking->booking_code,
            ], ['buyer']);

            $count++;
        }

        // 2. Bookings in 'dikonfirmasi' without check-in that passed visit end date -> mark needs_attention (SPEC §12)
        Booking::where('status', BookingStatus::DIKONFIRMASI)
            ->whereDate('planned_date_end', '<=', $threshold)
            ->whereNull('internal_notes')
            ->update([
                'internal_notes' => '[needs_attention] Jadwal kunjungan telah lewat tanpa proses scan check-in oleh pengelola tapak. Perlu investigasi admin.',
            ]);

        return $count;
    }
}
