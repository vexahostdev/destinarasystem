<?php

namespace App\Domain\Document\Services;

use App\Domain\Audit\AuditLogger;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Document;
use App\Models\DocumentTemplate;
use App\Models\PartnerPayout;
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

            // Prepare E-Voucher check-in QR if needed
            $checkinQrBase64 = '';
            if ($type === DocumentType::E_VOUCHER) {
                $checkinUrl = URL::signedRoute('mitra.checkin.show', ['code' => $booking->booking_code]);
                $checkinQrSvg = QrCode::format('svg')->size(160)->generate($checkinUrl);
                $checkinQrBase64 = base64_encode($checkinQrSvg);
            }

            // Replace Placeholders
            $bodyHtml = $this->replaceBookingPlaceholders(
                $bodyHtml,
                $booking,
                $type,
                $docNumber,
                $hash,
                $qrVerificationBase64,
                $checkinQrBase64
            );

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

            $latestVersion = Document::where('booking_id', $booking->id)
                ->where('type', $type)
                ->max('version') ?? 0;

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
                'version' => $latestVersion + 1,
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
        PartnerPayout $payout,
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
                'document_number' => $docNumber,
                'payout.number' => $payout->payout_number,
                'payout_number' => $payout->payout_number,
                'transferred_at' => $payout->transferred_at ? $payout->transferred_at->translatedFormat('d F Y H:i') : now()->translatedFormat('d F Y H:i'),
                'organization_name' => $profile?->organization_name ?? $partner?->name ?? 'Mitra Pengelola',
                'partner.name' => $partner?->name ?? '-',
                'partner.organization' => $profile?->organization_name ?? $partner?->name ?? '-',
                'bank_name' => $payout->bank_name ?? '-',
                'account_number' => $payout->bank_account_number ?? '-',
                'account_holder' => $payout->bank_account_holder ?? '-',
                'payout.bank_name' => $payout->bank_name ?? '-',
                'payout.bank_account_number' => $payout->bank_account_number ?? '-',
                'payout.bank_account_holder' => $payout->bank_account_holder ?? '-',
                'payout.transferred_at' => $payout->transferred_at ? $payout->transferred_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i'),
                'booking_code' => $payout->booking ? $payout->booking->booking_code : '-',
                'booking.code' => $payout->booking ? $payout->booking->booking_code : '-',
                'destination_name' => $payout->booking?->destination?->name ?? 'Destinasi Tapak',
                'gross_amount' => number_format($payout->gross_amount, 0, ',', '.'),
                'commission_amount' => number_format($payout->commission_amount, 0, ',', '.'),
                'net_amount' => number_format($payout->net_amount, 0, ',', '.'),
                'payout.gross_amount' => 'Rp '.number_format($payout->gross_amount, 0, ',', '.'),
                'payout.commission_amount' => 'Rp '.number_format($payout->commission_amount, 0, ',', '.'),
                'payout.net_amount' => 'Rp '.number_format($payout->net_amount, 0, ',', '.'),
            ];

            $bodyHtml = preg_replace_callback('/\{\{?\s*([a-zA-Z0-9_\.]+)\s*\}?\}/', function ($matches) use ($replacements) {
                $key = $matches[1];
                if (array_key_exists($key, $replacements)) {
                    return (string) $replacements[$key];
                }

                return '';
            }, $bodyHtml);

            $bodyHtml = preg_replace('/\{\{?[a-zA-Z0-9_\.]+\}?\}|\{[a-zA-Z0-9_\.]+\}/', '', $bodyHtml);

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
    protected function replaceBookingPlaceholders(
        string $content,
        Booking $booking,
        DocumentType $type,
        string $docNumber,
        string $hash,
        string $qrVerificationBase64,
        string $checkinQrBase64
    ): string {
        $institution = $booking->institution;
        $destination = $booking->destination;
        $package = $booking->package;
        $partner = $destination?->partner;
        $partnerProfile = $partner?->partnerProfile;
        $payment = $booking->payments()->whereIn('status', [PaymentStatus::PAID, 'paid'])->latest()->first()
            ?? $booking->payments()->latest()->first();

        // Render booking items table rows
        $itemsRows = '';
        $no = 1;
        if ($booking->items->isNotEmpty()) {
            foreach ($booking->items as $item) {
                $itemsRows .= '<tr>'
                    .'<td style="text-align: center;">'.$no++.'</td>'
                    .'<td>'.htmlspecialchars($item->name).'</td>'
                    .'<td style="text-align: center;">'.$item->quantity.'</td>'
                    .'<td style="text-align: right;">Rp '.number_format($item->unit_price, 0, ',', '.').'</td>'
                    .'<td style="text-align: right;">Rp '.number_format($item->total, 0, ',', '.').'</td>'
                    .'</tr>';
            }
        } elseif ($package) {
            $itemsRows .= '<tr>'
                .'<td style="text-align: center;">1</td>'
                .'<td>'.htmlspecialchars($package->name).'</td>'
                .'<td style="text-align: center;">'.$booking->participant_count.'</td>'
                .'<td style="text-align: right;">Rp '.number_format($package->price_per_pax, 0, ',', '.').'</td>'
                .'<td style="text-align: right;">Rp '.number_format($booking->subtotal_amount, 0, ',', '.').'</td>'
                .'</tr>';
        }

        // Full items table
        $itemsTable = '<table class="items-table data-table">'
            .'<thead><tr><th style="text-align: center; width: 40px;">No</th><th>Uraian Layanan</th><th style="text-align: center; width: 80px;">Kuantitas</th><th style="text-align: right; width: 120px;">Harga Satuan</th><th style="text-align: right; width: 140px;">Subtotal</th></tr></thead>'
            .'<tbody>'.$itemsRows.'</tbody>'
            .'<tfoot><tr style="font-weight:bold; background:#F7FAFC;"><td colspan="4" style="text-align: right;">Total Tagihan:</td><td style="text-align: right;">Rp '.number_format($booking->total_amount, 0, ',', '.').'</td></tr></tfoot>'
            .'</table>';

        // Checkin box for e-voucher
        $checkinBox = '<div class="text-center mt-4 mb-4" style="border: 2px dashed #8C5151; padding: 15px; border-radius: 8px; background: #FFF5F5;">'
            .'<h3 style="color: #8C5151; margin-top:0;">QR CHECK-IN ROMBONGAN</h3>'
            .'<img src="data:image/svg+xml;base64,'.$checkinQrBase64.'" width="150" height="150" alt="QR Check-in">'
            .'<p style="font-size: 9pt; color: #4A5568; margin-bottom: 0;">Tunjukkan QR Code ini kepada pengelola destinasi saat tiba di lokasi untuk proses check-in rombongan.</p>'
            .'</div>';

        $provinceName = is_string($destination?->province) ? $destination->province : ($destination?->province?->name ?? 'DI Yogyakarta');

        $replacements = [
            'document_number' => $docNumber,
            'document.number' => $docNumber,
            'booking_code' => $booking->booking_code,
            'booking.code' => $booking->booking_code,
            'issue_date' => now()->translatedFormat('d F Y'),
            'date_issued' => now()->translatedFormat('d F Y'),
            'created_at' => $booking->created_at->format('d/m/Y'),
            'booking.created_at' => $booking->created_at->format('d/m/Y'),
            'paid_at' => $payment?->paid_at ? $payment->paid_at->translatedFormat('d F Y H:i') : ($booking->paid_at ? $booking->paid_at->translatedFormat('d F Y H:i') : now()->translatedFormat('d F Y H:i')),
            'confirmed_at' => $booking->confirmed_at ? $booking->confirmed_at->translatedFormat('d F Y H:i') : now()->translatedFormat('d F Y H:i'),
            'expires_at' => $booking->expires_at ? $booking->expires_at->translatedFormat('d F Y H:i') : now()->addHours(24)->translatedFormat('d F Y H:i'),
            'institution_name' => $institution?->institution_name ?? $booking->user?->name ?? 'Institusi Pemesan',
            'institution.name' => $institution?->institution_name ?? $booking->user?->name ?? 'Institusi Pemesan',
            'pic_name' => $institution?->pic_name ?? $booking->user?->name ?? '-',
            'institution.pic' => $institution?->pic_name ?? $booking->user?->name ?? '-',
            'pic_phone' => $institution?->pic_phone ?? $booking->user?->phone ?? '-',
            'institution.phone' => $institution?->pic_phone ?? $booking->user?->phone ?? '-',
            'pic_email' => $institution?->pic_email ?? $booking->user?->email ?? '-',
            'destination_name' => $destination?->name ?? 'Destinasi Edukasi',
            'destination.name' => $destination?->name ?? 'Destinasi Edukasi',
            'destination_city' => $destination?->city ?? '-',
            'destination.city' => $destination?->city ?? '-',
            'destination_province' => $provinceName,
            'destination.province' => $provinceName,
            'destination_address' => $destination?->address ?? '',
            'destination.address' => $destination?->address ?? '',
            'contact_person_name' => $destination?->contact_person_name ?? $partnerProfile?->pic_name ?? $partner?->name ?? 'Pengelola Tapak',
            'contact_person_phone' => $destination?->contact_person_phone ?? $partnerProfile?->pic_phone ?? $partner?->phone ?? '-',
            'organization_name' => $partnerProfile?->organization_name ?? $partner?->name ?? 'Mitra Pengelola',
            'partner.name' => $partner?->name ?? '-',
            'partner.organization' => $partnerProfile?->organization_name ?? $partner?->name ?? '-',
            'planned_date_start' => $booking->planned_date_start ? $booking->planned_date_start->format('d/m/Y') : '-',
            'planned_date_end' => $booking->planned_date_end ? $booking->planned_date_end->format('d/m/Y') : '-',
            'visit_date' => ($booking->planned_date_start?->format('d/m/Y') ?? '-').' s.d. '.($booking->planned_date_end?->format('d/m/Y') ?? '-'),
            'booking.visit_date' => ($booking->planned_date_start?->format('d/m/Y') ?? '-').' s.d. '.($booking->planned_date_end?->format('d/m/Y') ?? '-'),
            'participant_count' => (string) $booking->participant_count,
            'booking.pax' => (string) $booking->participant_count,
            'companion_count' => (string) ($booking->companion_count ?? 0),
            'package_name' => $package?->name ?? 'Paket Edukasi',
            'package.name' => $package?->name ?? 'Paket Edukasi',
            'package_duration' => $package ? "{$package->duration_days} Hari" : '-',
            'package.duration' => $package ? "{$package->duration_days} Hari" : '-',
            'inquiry_type_label' => $booking->inquiry_type ? $booking->inquiry_type->label() : 'Study Tour',
            'payment_method' => $payment?->payment_type ? ucfirst(str_replace('_', ' ', $payment->payment_type)) : ($booking->payment_gateway ?? 'Transfer Manual'),
            'payment_channel' => $payment?->payment_channel ?? $payment?->gateway ?? 'Bank Transfer',
            'total_amount' => number_format($booking->total_amount, 0, ',', '.'),
            'booking.total_amount' => 'Rp '.number_format($booking->total_amount, 0, ',', '.'),
            'subtotal_amount' => number_format($booking->subtotal_amount, 0, ',', '.'),
            'discount_amount' => number_format($booking->discount_amount ?? 0, 0, ',', '.'),
            'insurance_amount' => number_format($booking->insurance_amount ?? 0, 0, ',', '.'),
            'platform_fee_amount' => number_format($booking->platform_fee_amount ?? 0, 0, ',', '.'),
            'amount_words' => $this->terbilang((int) $booking->total_amount),
            'permit_title' => $booking->permit_title ?? ('Pengajuan Izin Kegiatan Edukasi di '.($destination?->name ?? 'Tapak')),
            'permit_supervisor' => $booking->permit_supervisor ?? ($institution?->pic_name ?? $booking->user?->name ?? 'Dosen / Penanggung Jawab'),
            'items_table_rows' => $itemsRows,
            'items_table' => $itemsTable,
            'checkin_qr_svg_base64' => 'data:image/svg+xml;base64,'.$checkinQrBase64,
            'qr_checkin' => $checkinBox,
        ];

        // Replace using callback so {{key}} is substituted reliably
        $replaced = preg_replace_callback('/\{\{?\s*([a-zA-Z0-9_\.]+)\s*\}?\}/', function ($matches) use ($replacements) {
            $key = $matches[1];
            if (array_key_exists($key, $replacements)) {
                return (string) $replacements[$key];
            }

            return '';
        }, $content);

        // If E-Voucher and checkin QR was not in template body, append it
        if ($type === DocumentType::E_VOUCHER && ! str_contains($content, '{{checkin_qr_svg_base64}}') && ! str_contains($content, '{{qr_checkin}}')) {
            $replaced .= $checkinBox;
        }

        // Strip any remaining unreplaced placeholders
        return preg_replace('/\{\{?[a-zA-Z0-9_\.]+\}?\}|\{[a-zA-Z0-9_\.]+\}/', '', $replaced);
    }

    /**
     * Indonesian number to words (terbilang).
     */
    protected function terbilang(int $number): string
    {
        if ($number <= 0) {
            return 'nol';
        }

        return trim(ucwords($this->numberToWords($number)));
    }

    private function numberToWords(int $number): string
    {
        $words = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

        if ($number < 12) {
            return $words[$number];
        }
        if ($number < 20) {
            return $this->numberToWords($number - 10).' belas';
        }
        if ($number < 100) {
            return $this->numberToWords((int) ($number / 10)).' puluh'.($number % 10 ? ' '.$this->numberToWords($number % 10) : '');
        }
        if ($number < 200) {
            return 'seratus'.($number % 100 ? ' '.$this->numberToWords($number % 100) : '');
        }
        if ($number < 1000) {
            return $this->numberToWords((int) ($number / 100)).' ratus'.($number % 100 ? ' '.$this->numberToWords($number % 100) : '');
        }
        if ($number < 2000) {
            return 'seribu'.($number % 1000 ? ' '.$this->numberToWords($number % 1000) : '');
        }
        if ($number < 1000000) {
            return $this->numberToWords((int) ($number / 1000)).' ribu'.($number % 1000 ? ' '.$this->numberToWords($number % 1000) : '');
        }
        if ($number < 1000000000) {
            return $this->numberToWords((int) ($number / 1000000)).' juta'.($number % 1000000 ? ' '.$this->numberToWords($number % 1000000) : '');
        }
        if ($number < 1000000000000) {
            return $this->numberToWords((int) ($number / 1000000000)).' milyar'.($number % 1000000000 ? ' '.$this->numberToWords($number % 1000000000) : '');
        }

        return (string) $number;
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
