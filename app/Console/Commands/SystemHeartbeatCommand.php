<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SystemHeartbeatCommand extends Command
{
    protected $signature = 'system:heartbeat';
    protected $description = 'Rekam heartbeat operasional sistem berkala';

    public function handle(): int
    {
        $timestamp = now()->toIso8601String();
        Cache::put('system_last_heartbeat_at', $timestamp, now()->addMinutes(10));

        $this->info("System heartbeat updated at {$timestamp}");
        return self::SUCCESS;
    }
}
