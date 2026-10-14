<?php

namespace App\Domain\Document\Services;

use App\Domain\Audit\AuditLogger;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Booking;
use App\Models\Document;
use App\Models\DocumentTemplate;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DocumentService
{
    public function __construct(
        protected DocumentNumberGenerator $numberGenerator
    ) {}

    /**
     * Generate PDF document for a booking.
     */
    public function generateForBooking(
        Booking $booking,
        DocumentType $type,
        ?User $actor = null
    ): Document {
        return DB::transaction(function () use ($booking, $type, $actor) {
            $docNumber = $this->numberGenerator->generate($type);
            $hash = bin2hex(random_bytes(16));
            $isDraft = ($type === DocumentType::PERMIT_DRAFT);
            $status = $isDraft ? DocumentStatus::DRAFT : DocumentStatus::FINAL;

            // 1. Prepare Verification QR
            $verifyUrl = route('verification.document', ['hash' => $hash]);
            $qrSvg = QrCode::format('svg')->size(75)->generate($verifyUrl);
            $qrVerificationBase64 = base64_encode($qrSvg);

            // 2. Prepare Template & Body
            $template = DocumentTemplate::where('type', $type->value)
                ->where('is_active', true)
                ->first();

            $bodyHtml = $template ? $template->body : '<p>Dokumen resmi Destinara.</p>';
            $title = $template ? $template->title : $type->label();

            // Replace Placeholders
            $bodyHtml = $this->replaceBookingPlaceholders($bodyHtml, $booking);

            // If E-Voucher, generate signed check-in QR
            if ($type === DocumentType::E_VOUCHER) {
                $checkinUrl = URL::signedRoute('mitra.checkin.show', ['code' => $booking->booking_code]);
                $checkinQrSvg = QrCode::format('svg')->size(160)->generate($checkinUrl);
                $checkinQrBase64 = base64_encode($checkinQrSvg);

                $checkinBox = '<div class="text-center mt-4 mb-4" style="border: 2px dashed #8C5151; padding: 15px; border-radius: 8px; background: #FFF5F5;">'
                    .'<h3 style="color: #8C5151; margin-top:0;">QR PASUKAN MASUK TAPAK (CHECK-IN)</h3>'
                    .'<img src="data:image/svg+xml;base64,'.$checkinQrBase64.'" width="150" height="150" alt="QR Check-in">'
                    .'<p style="font-size: 9pt; color: #4A5568; margin-bottom: 0;">Tunjukkan QR Code ini kepada pengelola destinasi saat tiba di lokasi untuk proses check-in rombongan.</p>'
                    .'</div>';

                if (str_contains($bodyHtml, '{{qr_checkin}}')) {
                    $bodyHtml = str_replace('{{qr_checkin}}', $checkinBox, $bodyHtml);
                } else {
                    $bodyHtml .= $checkinBox;
                }
            }

            // 3. Render DomPDF
            $pdfView = view('pdf.layout', [
                'title' => $title,
                'documentNumber' => $docNumber,
                'dateIssued' => now()->translatedFormat('d F Y'),
                'statusLabel' => $type->label(),
                'isDraft' => $isDraft,
                'qrVerification' => $qrVerificationBase64,
                'body' => $bodyHtml,
            ])->render();

            $pdf = Pdf::loadHTML($pdfView)->setPaper('a4', 'portrait');

            // 4. Save to private storage (disk local already roots to storage/app/private)
            $yearMonth = now()->format('Y/m');
            $dir = "documents/{$yearMonth}";
            $safeDocNum = str_replace(['/', '\\'], '_', $docNumber);
            $fileName = "{$type->value}_{$safeDocNum}_{$hash}.pdf";
            $filePath = "{$dir}/{$fileName}";

            Storage::disk('local')->put($filePath, $pdf->output());

            // 5. Create Document Record
            $document = Document::create([
                'booking_id' => $booking->id,
                'type' => $type,
                'document_number' => $docNumber,
                'title' => $title,
                'file_path' => $filePath,
                'verification_hash' => $hash,
                'status' => $status,
                'generated_at' => now(),
                'generated_by' => $actor?->id,
                'version' => 1,
            ]);

            AuditLogger::log(
                action: 'document_generated',
                target: $document,
                description: "Dokumen {$type->label()} nomor {$docNumber} berhasil di-generate untuk booking {$booking->booking_code}.",
                userId: $actor?->id
            );

            return $document;
        });
    }

    /**
     * Generate PDF payout receipt document for a partner payout (SPEC §11).
     */
    public function generateForPayout(
        \App\Models\PartnerPayout $payout,
        ?User $actor = null
    ): Document {
        return DB::transaction(function () use ($payout, $actor) {
            $docNumber = $this->numberGenerator->generate(DocumentType::PAYOUT_RECEIPT);
            $hash = bin2hex(random_bytes(16));
            $type = DocumentType::PAYOUT_RECEIPT;

            // 1. Prepare Verification QR
            $verifyUrl = route('verification.document', ['hash' => $hash]);
            $qrSvg = QrCode::format('svg')->size(75)->generate($verifyUrl);
            $qrVerificationBase64 = base64_encode($qrSvg);

            // 2. Prepare Template & Body
            $template = DocumentTemplate::where('type', $type->value)
                ->where('is_active', true)
                ->first();

            $bodyHtml = $template ? $template->body : '<p>Bukti pencairan dana kemitraan Destinara.</p>';
            $title = $template ? $template->title : $type->label();

            $partner = $payout->partner;
            $profile = $partner?->partnerProfile;

            $replacements = [
                '{{payout.number}}' => $payout->payout_number,
                '{{payout.gross_amount}}' => 'Rp ' . number_format($payout->gross_amount, 0, ',', '.'),
                '{{payout.commission_amount}}' => 'Rp ' . number_format($payout->commission_amount, 0, ',', '.'),
                '{{payout.net_amount}}' => 'Rp ' . number_format($payout->net_amount, 0, ',', '.'),
                '{{payout.bank_name}}' => $payout->bank_name ?? '-',
                '{{payout.bank_account_number}}' => $payout->bank_account_number ?? '-',
                '{{payout.bank_account_holder}}' => $payout->bank_account_holder ?? '-',
                '{{payout.transferred_at}}' => $payout->transferred_at ? $payout->transferred_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i'),
                '{{partner.name}}' => $partner->name,
                '{{partner.organization}}' => $profile?->organization_name ?? $partner->name,
                '{{booking.code}}' => $payout->booking ? $payout->booking->booking_code : '-',
            ];

            $bodyHtml = str_replace(array_keys($replacements), array_values($replacements), $bodyHtml);

            // 3. Render DomPDF
            $pdfView = view('pdf.layout', [
                'title' => $title,
                'documentNumber' => $docNumber,
                'dateIssued' => now()->translatedFormat('d F Y'),
                'statusLabel' => $type->label(),
                'isDraft' => false,
                'qrVerification' => $qrVerificationBase64,
                'body' => $bodyHtml,
            ])->render();

            $pdf = Pdf::loadHTML($pdfView)->setPaper('a4', 'portrait');

            // 4. Save to private storage
            $yearMonth = now()->format('Y/m');
            $dir = "documents/{$yearMonth}";
            $safeDocNum = str_replace(['/', '\\'], '_', $docNumber);
            $fileName = "{$type->value}_{$safeDocNum}_{$hash}.pdf";
            $filePath = "{$dir}/{$fileName}";

            Storage::disk('local')->put($filePath, $pdf->output());

            // 5. Create Document Record
            $document = Document::create([
                'booking_id' => $payout->booking_id,
                'payout_id' => $payout->id,
                'type' => $type,
                'document_number' => $docNumber,
                'title' => $title,
                'file_path' => $filePath,
                'verification_hash' => $hash,
                'status' => DocumentStatus::FINAL,
                'generated_at' => now(),
                'generated_by' => $actor?->id,
                'version' => 1,
            ]);

            AuditLogger::log(
                action: 'document_generated',
                target: $document,
                description: "Bukti transfer payout nomor {$docNumber} berhasil di-generate untuk {$payout->payout_number}.",
                userId: $actor?->id
            );

            return $document;
        });
    }

    /**
     * Replace template placeholders with booking data.
     */
    protected function replaceBookingPlaceholders(string $content, Booking $booking): string
    {
        $institution = $booking->institution;
        $destination = $booking->destination;
        $package = $booking->package;

        // Render booking items table
        $itemsTable = '<table class="data-table"><thead><tr><th>Uraian Layanan</th><th class="text-center">Kuantitas</th><th class="text-right">Harga Satuan</th><th class="text-right">Subtotal</th></tr></thead><tbody>';
        foreach ($booking->items as $item) {
            $itemsTable .= '<tr>'
                .'<td>'.htmlspecialchars($item->name).'</td>'
                .'<td class="text-center">'.$item->quantity.'</td>'
                .'<td class="text-right">Rp '.number_format($item->unit_price, 0, ',', '.').'</td>'
                .'<td class="text-right">Rp '.number_format($item->total, 0, ',', '.').'</td>'
                .'</tr>';
        }
        $itemsTable .= '<tr style="font-weight:bold; background:#F7FAFC;"><td colspan="3" class="text-right">Total Tagihan:</td><td class="text-right">Rp '.number_format($booking->total_amount, 0, ',', '.').'</td></tr>';
        $itemsTable .= '</tbody></table>';

        $replacements = [
            '{{booking.code}}' => $booking->booking_code,
            '{{booking.created_at}}' => $booking->created_at->format('d/m/Y'),
            '{{booking.visit_date}}' => $booking->planned_date_start->format('d/m/Y').' s.d. '.$booking->planned_date_end->format('d/m/Y'),
            '{{booking.pax}}' => (string) $booking->participant_count,
            '{{booking.total_amount}}' => 'Rp '.number_format($booking->total_amount, 0, ',', '.'),
            '{{destination.name}}' => $destination->name,
            '{{destination.city}}' => $destination->city,
            '{{destination.province}}' => $destination->province?->name ?? 'DI Yogyakarta',
            '{{destination.address}}' => $destination->address ?? '',
            '{{institution.name}}' => $institution?->institution_name ?? 'Institusi Pemesan',
            '{{institution.pic}}' => $institution?->pic_name ?? '-',
            '{{institution.phone}}' => $institution?->pic_phone ?? '-',
            '{{package.name}}' => $package->name,
            '{{package.duration}}' => "{$package->duration_days} Hari",
            '{{items_table}}' => $itemsTable,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Regenerate document to create a new version and void the old version (Admin).
     */
    public function regenerate(Document $oldDocument, User $admin): Document
    {
        return DB::transaction(function () use ($oldDocument, $admin) {
            $lockedOld = Document::where('id', $oldDocument->id)->lockForUpdate()->first();
            $lockedOld->status = DocumentStatus::VOID;
            $lockedOld->save();

            $newDoc = $this->generateForBooking($lockedOld->booking, $lockedOld->type, $admin);
            $newDoc->version = $lockedOld->version + 1;
            $newDoc->save();

            AuditLogger::log(
                action: 'document_regenerated',
                target: $newDoc,
                description: "Dokumen {$lockedOld->document_number} di-regenerate menjadi versi {$newDoc->version} oleh {$admin->name}. Versi sebelumnya ditandai void.",
                userId: $admin->id
            );

            return $newDoc;
        });
    }
}
