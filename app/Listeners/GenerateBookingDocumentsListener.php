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
            // Only regenerate document types that have ALREADY been published for this booking (V7-1)
            $existingTypes = Document::where('booking_id', $event->booking->id)
                ->whereIn('type', [DocumentType::CONFIRMATION_LETTER, DocumentType::E_VOUCHER, DocumentType::PERMIT_DRAFT])
                ->where('status', '!=', DocumentStatus::VOID)
                ->pluck('type')
                ->unique()
                ->values();

            if ($existingTypes->isNotEmpty()) {
                // Void previous versions
                Document::where('booking_id', $event->booking->id)
                    ->whereIn('type', $existingTypes)
                    ->where('status', '!=', DocumentStatus::VOID)
                    ->update(['status' => DocumentStatus::VOID]);

                // Regenerate only the types that were previously published
                foreach ($existingTypes as $type) {
                    $docType = $type instanceof DocumentType ? $type : DocumentType::from($type);
                    GenerateDocumentJob::dispatch($event->booking, $docType, $event->actor)->afterCommit();
                }
            }
        } elseif ($event instanceof PayoutTransferred) {
            GenerateDocumentJob::dispatch($event->payout, DocumentType::PAYOUT_RECEIPT, $event->actor)->afterCommit();
        }
    }
}
