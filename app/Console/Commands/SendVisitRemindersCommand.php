<?php

namespace App\Console\Commands;

use App\Domain\Notification\Services\NotificationDispatcher;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\NotificationLog;
use Illuminate\Console\Command;

class SendVisitRemindersCommand extends Command
{
    protected $signature = 'bookings:visit-reminders';

    protected $description = 'Send upcoming visit reminder to buyers and partners H-N before visit date (SPEC §12)';

    public function handle(NotificationDispatcher $notifier): int
    {
        $daysBefore = (int) setting('visit_reminder_days', 3);
        $targetDate = now()->addDays($daysBefore)->toDateString();

        $bookings = Booking::where('status', BookingStatus::DIKONFIRMASI)
            ->whereDate('planned_date_start', $targetDate)
            ->get();

        $count = 0;

        foreach ($bookings as $booking) {
            $alreadyReminded = NotificationLog::where('event_key', 'visit.reminder')
                ->where('body', 'like', "%{$booking->booking_code}%")
                ->exists();

            if ($alreadyReminded) {
                continue;
            }

            $notifier->send('visit.reminder', [
                'booking' => $booking,
                'user' => $booking->user,
                'destination_name' => $booking->destination->name,
                'booking_code' => $booking->booking_code,
                'visit_date' => $booking->planned_date_start->format('d/m/Y'),
                'planned_date_start' => $booking->planned_date_start->format('d/m/Y'),
                'days_left' => (string) $daysBefore,
                'pax' => $booking->participant_count,
                'participant_count' => $booking->participant_count,
                'institution_name' => $booking->institution?->institution_name ?? $booking->user?->name ?? 'Institusi Pemesan',
                'pic_name' => $booking->institution?->pic_name ?? $booking->user?->name ?? 'Pengguna',
                'organization_name' => $booking->destination?->partner?->partnerProfile?->organization_name ?? $booking->destination?->partner?->name ?? 'Mitra Pengelola',
            ], ['buyer', 'partner']);

            $count++;
        }

        $this->info("Berhasil mengirim {$count} pengingat kunjungan (H-{$daysBefore}).");

        return Command::SUCCESS;
    }
}
