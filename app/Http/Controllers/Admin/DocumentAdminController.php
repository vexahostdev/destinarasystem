<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditLogger;
use App\Domain\Document\Services\DocumentService;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentAdminController extends Controller
{
    public function __construct(
        protected DocumentService $documentService
    ) {}

    public function index(Request $request)
    {
        $query = Document::with(['booking.user.institution', 'generator']);

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $s = $request->query('search');
            $query->where(function ($q) use ($s) {
                $q->where('document_number', 'like', "%{$s}%")
                    ->orWhere('title', 'like', "%{$s}%")
                    ->orWhereHas('booking', function ($bq) use ($s) {
                        $bq->where('booking_code', 'like', "%{$s}%");
                    });
            });
        }

        $documents = $query->latest('id')->paginate(15)->withQueryString();
        $types = DocumentType::cases();

        return view('admin.documents.index', compact('documents', 'types'));
    }

    public function preview(Document $document)
    {
        if (! Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'Berkas dokumen fisik tidak ditemukan di server.');
        }

        return response()->file(Storage::disk('local')->path($document->file_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($document->file_path).'"',
        ]);
    }

    public function regenerate(Document $document)
    {
        if ($document->status === DocumentStatus::VOID) {
            return back()->with('error', 'Dokumen yang telah berstatus void tidak dapat di-regenerate.');
        }

        $newDoc = $this->documentService->regenerate($document, Auth::user());

        return back()->with('success', "Dokumen {$document->document_number} berhasil di-regenerate ke versi {$newDoc->version}.");
    }

    public function void(Document $document)
    {
        $document->status = DocumentStatus::VOID;
        $document->save();

        AuditLogger::log(
            action: 'document_voided',
            target: $document,
            description: "Dokumen {$document->document_number} dibatalkan (void) oleh admin ".Auth::user()->name
        );

        return back()->with('success', "Dokumen {$document->document_number} telah ditandai void.");
    }

    public function templates()
    {
        $templates = DocumentTemplate::all();

        return view('admin.documents.templates', compact('templates'));
    }

    public function updateTemplate(Request $request, DocumentTemplate $template)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $template->update($validated);

        AuditLogger::log(
            action: 'update_document_template',
            target: $template,
            description: "Template dokumen {$template->type} diperbarui."
        );

        return back()->with('success', "Template '{$template->title}' berhasil diperbarui.");
    }

    public function previewTemplate(DocumentTemplate $template)
    {
        $sampleData = [
            '{{booking.code}}' => 'BK/202610/00001',
            '{{booking.created_at}}' => now()->format('d/m/Y'),
            '{{booking.visit_date}}' => now()->addDays(14)->format('d/m/Y').' s.d. '.now()->addDays(16)->format('d/m/Y'),
            '{{booking.pax}}' => '45',
            '{{booking.total_amount}}' => 'Rp 12.500.000',
            '{{destination.name}}' => 'Desa Edukasi Candi Kedulan',
            '{{destination.city}}' => 'Sleman',
            '{{destination.province}}' => 'DI Yogyakarta',
            '{{destination.address}}' => 'Jl. Kalasan - Berbah Km. 3, Sleman',
            '{{institution.name}}' => 'SMA Negeri 1 Teladan',
            '{{institution.pic}}' => 'Drs. Budi Santoso',
            '{{institution.phone}}' => '628123456789',
            '{{package.name}}' => 'Paket Edukasi Budaya & Arkeologi (3 Hari)',
            '{{package.duration}}' => '3 Hari',
            '{{items_table}}' => '<table class="data-table"><thead><tr><th>Uraian Layanan</th><th class="text-center">Kuantitas</th><th class="text-right">Harga Satuan</th><th class="text-right">Subtotal</th></tr></thead><tbody><tr><td>Paket Edukasi Budaya</td><td class="text-center">45</td><td class="text-right">Rp 250.000</td><td class="text-right">Rp 11.250.000</td></tr><tr style="font-weight:bold; background:#F7FAFC;"><td colspan="3" class="text-right">Total Tagihan:</td><td class="text-right">Rp 11.250.000</td></tr></tbody></table>',
            '{{qr_checkin}}' => '<div style="border: 2px dashed #8C5151; padding: 15px; text-align: center; border-radius: 8px;">[QR CODE CHECK-IN CONTOH]</div>',
        ];

        $bodyHtml = str_replace(array_keys($sampleData), array_values($sampleData), $template->body);

        $pdfView = view('pdf.layout', [
            'title' => $template->title,
            'documentNumber' => 'CONTOH/PRV/2026/001',
            'dateIssued' => now()->translatedFormat('d F Y'),
            'statusLabel' => 'CONTOH PREVIEW',
            'isDraft' => false,
            'qrVerification' => null,
            'body' => $bodyHtml,
        ])->render();

        $pdf = Pdf::loadHTML($pdfView)->setPaper('a4', 'portrait');

        return $pdf->stream('preview-template-'.$template->type.'.pdf');
    }
}
