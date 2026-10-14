<?php

namespace App\Console\Commands;

use App\Domain\Notification\Clients\VexaHostClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WhatsAppHealthCheckCommand extends Command
{
    protected $signature = 'whatsapp:health-check';
    protected $description = 'Periksa status kesehatan gateway WhatsApp VexaHost';

    public function handle(VexaHostClient $client): int
    {
        $this->info('Memeriksa kesehatan gateway WhatsApp...');

        $health = $client->checkHealth();

        Cache::put('system_whatsapp_health', $health, now()->addMinutes(30));

        if ($health['status'] === 'connected') {
            $this->info("WhatsApp Gateway terhubung. Status: {$health['status']}");
            return self::SUCCESS;
        }

        $this->warn("WhatsApp Gateway bermasalah: Status [{$health['status']}], Code [{$health['statusCode']}]");
        return self::SUCCESS;
    }
}
