<?php

namespace App\Console\Commands;

use App\Domain\Booking\Services\CheckInService;
use Illuminate\Console\Command;

class AutoCompleteBookingsCommand extends Command
{
    protected $signature = 'bookings:auto-complete';

    protected $description = 'Automatically mark completed visits and flag un-scanned bookings for attention (SPEC §12)';

    public function handle(CheckInService $checkInService): int
    {
        $count = $checkInService->autoCompleteFinishedBookings();

        $this->info("Berhasil memproses penyelesaian otomatis untuk {$count} kunjungan.");

        return Command::SUCCESS;
    }
}
