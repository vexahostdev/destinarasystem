<?php

namespace App\Console\Commands;

use App\Domain\Booking\Services\AvailabilityService;
use App\Domain\Booking\StateMachine\BookingStateMachine;
use App\Domain\Notification\Services\NotificationDispatcher;
use App\Domain\Payment\Services\PaymentService;
use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireUnpaidBookingsCommand extends Command
{
    protected $signature = 'bookings:expire-unpaid';
    protected $description = 'Expire unpaid bookings that have passed their payment deadline (SPEC §12)';

    public function handle(
        AvailabilityService $availabilityService,
        BookingStateMachine $stateMachine,
        PaymentService $paymentService,
        NotificationDispatcher $notifier
    ): int {
        $expiredBookings = Booking::where('status', BookingStatus::MENUNGGU_PEMBAYARAN)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        $count = 0;

        foreach ($expiredBookings as $booking) {
            DB::transaction(function () use ($booking, $availabilityService, $stateMachine, $paymentService, $notifier, &$count) {
                $locked = Booking::where('id', $booking->id)->lockForUpdate()->first();

                if ($locked->status !== BookingStatus::MENUNGGU_PEMBAYARAN) {
                    return;
                }

                // 1. Transition to KEDALUWARSA
                $stateMachine->transition(
                    booking: $locked,
                    to: BookingStatus::KEDALUWARSA,
                    actor: null,
                    note: 'Batas waktu pembayaran pesanan telah habis (otomatis kedaluwarsa oleh sistem).'
                );

                // 2. Release held slots
                $availabilityService->releaseSlots($locked);

                // 3. Expire pending payments on gateway
                foreach ($locked->payments()->where('status', 'pending')->get() as $payment) {
                    $paymentService->processPaymentExpiry($payment);
                }

                // 4. Dispatch notification
                $notifier->send('booking.expired', [
                    'booking' => $locked,
                    'user' => $locked->user,
                    'destination_name' => $locked->destination->name,
                    'booking_code' => $locked->booking_code,
                ], ['buyer']);

                $count++;
            });
        }

        $this->info("Berhasil memproses {$count} booking kedaluwarsa.");

        return Command::SUCCESS;
    }
}
