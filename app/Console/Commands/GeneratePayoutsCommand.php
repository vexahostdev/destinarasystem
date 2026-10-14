<?php

namespace App\Console\Commands;

use App\Domain\Finance\Services\PayoutService;
use Illuminate\Console\Command;

class GeneratePayoutsCommand extends Command
{
    protected $signature = 'payouts:generate';
    protected $description = 'Generate partner payouts for eligible completed bookings (SPEC §12)';

    public function handle(PayoutService $payoutService): int
    {
        $count = $payoutService->generateEligiblePayouts();

        $this->info("Berhasil men-generate {$count} payout kemitraan baru.");

        return Command::SUCCESS;
    }
}
