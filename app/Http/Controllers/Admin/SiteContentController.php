<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use Illuminate\Http\Request;

class SiteContentController extends Controller
{
    public function index(Request $request)
    {
        $defaultKeys = [
            'hero' => 'Bagian Hero Beranda',
            'cara_kerja' => 'Cara Kerja & Alur Reservasi',
            'faq' => 'Pertanyaan Umum (FAQ)',
            'snk' => 'Syarat & Ketentuan Layanan',
        ];

        // Ensure default records exist
        foreach ($defaultKeys as $key => $title) {
            SiteContent::firstOrCreate(
                ['key' => $key],
                ['title' => $title, 'body' => '', 'meta' => []]
            );
        }

        $activeKey = $request->query('key', 'hero');
        $contents = SiteContent::all()->keyBy('key');
        $current = $contents[$activeKey] ?? $contents->first();

        return view('admin.site-contents.index', compact('contents', 'current', 'activeKey', 'defaultKeys'));
    }

    public function update(Request $request, SiteContent $siteContent)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'meta' => 'nullable|array',
        ]);

        $siteContent->update($validated);

        AuditLogger::log(
            action: 'update_site_content',
            target: $siteContent,
            description: "Konten halaman '{$siteContent->title}' ({$siteContent->key}) diperbarui."
        );

        return back()->with('success', "Konten '{$siteContent->title}' berhasil disimpan.");
    }
}
