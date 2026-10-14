<?php

namespace App\Console\Commands;

use App\Domain\Notification\Services\NotificationDispatcher;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\NotificationLog;
use Illuminate\Console\Command;

class SendPaymentRemindersCommand extends Command
{
    protected $signature = 'payments:send-reminders';

    protected $description = 'Send payment deadline reminders to buyers before expiry (SPEC §12)';

    public function handle(NotificationDispatcher $notifier): int
    {
        $reminderHours = (int) setting('payment_reminder_hours', 2);
        $threshold = now()->addHours($reminderHours);

        $bookings = Booking::where('status', BookingStatus::MENUNGGU_PEMBAYARAN)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', $threshold)
            ->get();

        $count = 0;

        foreach ($bookings as $booking) {
            // Check if reminder already sent for this booking
            $alreadyReminded = NotificationLog::where('event_key', 'payment.reminder')
                ->where('recipient', $booking->user->email)
                ->where('body', 'like', "%{$booking->booking_code}%")
                ->exists();

            if ($alreadyReminded) {
                continue;
            }

            $payment = $booking->latestPayment;
            $paymentUrl = $payment?->checkout_url ?? route('buyer.booking.show', $booking->booking_code);

            $notifier->send('payment.reminder', [
                'booking' => $booking,
                'user' => $booking->user,
                'destination_name' => $booking->destination->name,
                'booking_code' => $booking->booking_code,
                'total_amount' => 'Rp '.number_format($booking->total_amount, 0, ',', '.'),
                'payment_url' => $paymentUrl,
                'expires_at' => $booking->expires_at->format('d/m/Y H:i').' WIB',
            ], ['buyer']);

            $count++;
        }

        $this->info("Berhasil mengirim {$count} pengingat pembayaran.");

        return Command::SUCCESS;
    }
}
