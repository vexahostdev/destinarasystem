<?php

namespace App\Listeners;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Events\BookingConfirmed;
use App\Events\BookingCreated;
use App\Events\BookingPaid;
use App\Events\BookingRescheduled;
use App\Events\PayoutTransferred;
use App\Jobs\GenerateDocumentJob;
use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateBookingDocumentsListener
{
    public bool $afterCommit = true;

    public string $queue = 'documents';

    /**
     * Handle document generation upon domain events (SPEC §11).
     */
    public function handle(mixed $event): void
    {
        if ($event instanceof BookingCreated) {
            GenerateDocumentJob::dispatch($event->booking, DocumentType::INVOICE)->afterCommit();
        } elseif ($event instanceof BookingPaid) {
            GenerateDocumentJob::dispatch($event->booking, DocumentType::RECEIPT)->afterCommit();
        } elseif ($event instanceof BookingConfirmed) {
            GenerateDocumentJob::dispatch($event->booking, DocumentType::CONFIRMATION_LETTER, $event->actor)->afterCommit();
            GenerateDocumentJob::dispatch($event->booking, DocumentType::E_VOUCHER, $event->actor)->afterCommit();

            if ($event->booking->destination?->has_permit_document) {
                GenerateDocumentJob::dispatch($event->booking, DocumentType::PERMIT_DRAFT, $event->actor)->afterCommit();
            }
        } elseif ($event instanceof BookingRescheduled) {
            // Void previous versions
            Document::where('booking_id', $event->booking->id)
                ->whereIn('type', [DocumentType::CONFIRMATION_LETTER, DocumentType::E_VOUCHER])
                ->where('status', '!=', DocumentStatus::VOID)
                ->update(['status' => DocumentStatus::VOID]);

            // Regenerate updated confirmation letter & e-voucher
            GenerateDocumentJob::dispatch($event->booking, DocumentType::CONFIRMATION_LETTER, $event->actor)->afterCommit();
            GenerateDocumentJob::dispatch($event->booking, DocumentType::E_VOUCHER, $event->actor)->afterCommit();
        } elseif ($event instanceof PayoutTransferred) {
            GenerateDocumentJob::dispatch($event->payout, DocumentType::PAYOUT_RECEIPT, $event->actor)->afterCommit();
        }
    }
}
