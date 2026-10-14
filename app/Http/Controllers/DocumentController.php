<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /**
     * Download secure document with DocumentPolicy authorization (SPEC §11).
     */
    public function download(Document $document): StreamedResponse
    {
        $this->authorize('view', $document);

        if (! Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'Berkas dokumen fisik tidak ditemukan di penyimpanan server.');
        }

        $safeNum = str_replace(['/', '\\'], '_', $document->document_number);
        $fileName = "{$document->type->value}_{$safeNum}.pdf";

        return Storage::disk('local')->download($document->file_path, $fileName);
    }
}
