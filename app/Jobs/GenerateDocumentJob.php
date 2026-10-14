<?php

namespace App\Jobs;

use App\Domain\Document\Services\DocumentService;
use App\Enums\DocumentType;
use App\Models\Booking;
use App\Models\PartnerPayout;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public Booking|PartnerPayout $target,
        public DocumentType $type,
        public ?User $actor = null
    ) {
        $this->onQueue('documents');
    }

    public function handle(DocumentService $documentService): void
    {
        if ($this->target instanceof PartnerPayout) {
            $documentService->generateForPayout($this->target, $this->actor);
        } else {
            $documentService->generateForBooking($this->target, $this->type, $this->actor);
        }
    }
}
