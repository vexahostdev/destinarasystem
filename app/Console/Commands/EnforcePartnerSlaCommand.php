<?php

namespace App\Console\Commands;

use App\Domain\Booking\Services\BookingService;
use App\Domain\Notification\Services\NotificationDispatcher;
use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Console\Command;

class EnforcePartnerSlaCommand extends Command
{
    protected $signature = 'bookings:partner-sla';

    protected $description = 'Enforce partner confirmation SLA: send reminders and auto-cancel passed deadlines (SPEC §12)';

    public function handle(
        BookingService $bookingService,
        NotificationDispatcher $notifier
    ): int {
        $now = now();

        // 1. Auto-cancel bookings that passed confirmation deadline
        $overdueBookings = Booking::where('status', BookingStatus::DIBAYAR)
            ->whereNotNull('partner_confirm_deadline')
            ->where('partner_confirm_deadline', '<=', $now)
            ->get();

        $cancelledCount = 0;
        foreach ($overdueBookings as $booking) {
            // Cancel booking via BookingService (source 'sla', 100% refund, release slots)
            $bookingService->cancel(
                booking: $booking,
                actor: $booking->user, // fallback actor
                reason: 'Batas waktu konfirmasi mitra (SLA) telah terlampaui.',
                source: 'sla',
                overridePercent: 100
            );

            $notifier->send('booking.auto_cancelled', [
                'booking' => $booking,
                'user' => $booking->user,
                'destination_name' => $booking->destination->name,
                'booking_code' => $booking->booking_code,
                'refund_amount' => 'Rp '.number_format($booking->total_amount, 0, ',', '.'),
            ], ['buyer', 'partner', 'admin']);

            $cancelledCount++;
        }

        // 2. Remind partners when deadline is approaching (within 2 hours)
        $reminderThreshold = $now->copy()->addHours(2);
        $approachingBookings = Booking::where('status', BookingStatus::DIBAYAR)
            ->whereNotNull('partner_confirm_deadline')
            ->whereNull('partner_sla_reminded_at')
            ->where('partner_confirm_deadline', '>', $now)
            ->where('partner_confirm_deadline', '<=', $reminderThreshold)
            ->get();

        $remindedCount = 0;
        foreach ($approachingBookings as $booking) {
            $hoursLeft = max(1, (int) round($now->diffInMinutes($booking->partner_confirm_deadline) / 60));
            $notifier->send('partner.confirm_reminder', [
                'booking' => $booking,
                'destination_name' => $booking->destination->name,
                'booking_code' => $booking->booking_code,
                'institution_name' => $booking->institution?->institution_name ?? $booking->user?->name ?? 'Institusi Pemesan',
                'organization_name' => $booking->destination?->partner?->partnerProfile?->organization_name ?? $booking->destination?->partner?->name ?? 'Mitra Pengelola',
                'deadline' => $booking->partner_confirm_deadline->format('d/m/Y H:i').' WIB',
                'hours_left' => (string) $hoursLeft,
                'partner_url' => route('mitra.bookings'),
                'partner_booking_url' => route('mitra.bookings'),
            ], ['partner', 'admin']);

            $booking->update(['partner_sla_reminded_at' => $now]);
            $remindedCount++;
        }

        $this->info("SLA diproses: {$cancelledCount} booking dibatalkan otomatis, {$remindedCount} pengingat dikirim ke mitra.");

        return Command::SUCCESS;
    }
}
