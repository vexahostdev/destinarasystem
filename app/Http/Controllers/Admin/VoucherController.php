<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        $query = Voucher::withCount('redemptions');

        if ($request->filled('search')) {
            $s = $request->query('search');
            $query->where(function ($q) use ($s) {
                $q->where('code', 'like', "%{$s}%")
                  ->orWhere('name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->query('is_active') === '1');
        }

        $vouchers = $query->latest('id')->paginate(15)->withQueryString();
        $destinations = Destination::orderBy('name')->get(['id', 'name']);

        return view('admin.vouchers.index', compact('vouchers', 'destinations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|integer|min:1',
            'max_discount' => 'nullable|integer|min:0',
            'min_transaction' => 'nullable|integer|min:0',
            'quota' => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'destination_ids' => 'nullable|array',
            'destination_ids.*' => 'integer|exists:destinations,id',
            'is_active' => 'boolean',
        ]);

        $validated['code'] = strtoupper($validated['code']);
        $validated['is_active'] = $request->boolean('is_active', true);

        $voucher = Voucher::create($validated);

        AuditLogger::log(
            action: 'create_voucher',
            target: $voucher,
            description: "Voucher diskon {$voucher->code} ({$voucher->name}) dibuat."
        );

        return back()->with('success', "Voucher '{$voucher->code}' berhasil dibuat.");
    }

    public function update(Request $request, Voucher $voucher)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('vouchers', 'code')->ignore($voucher->id)],
            'name' => 'required|string|max:255',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|integer|min:1',
            'max_discount' => 'nullable|integer|min:0',
            'min_transaction' => 'nullable|integer|min:0',
            'quota' => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'destination_ids' => 'nullable|array',
            'destination_ids.*' => 'integer|exists:destinations,id',
            'is_active' => 'boolean',
        ]);

        $validated['code'] = strtoupper($validated['code']);
        $validated['is_active'] = $request->boolean('is_active');

        $voucher->update($validated);

        AuditLogger::log(
            action: 'update_voucher',
            target: $voucher,
            description: "Data voucher diskon {$voucher->code} diperbarui."
        );

        return back()->with('success', "Voucher '{$voucher->code}' berhasil diperbarui.");
    }

    public function destroy(Voucher $voucher)
    {
        $code = $voucher->code;
        $voucher->delete();

        AuditLogger::log(
            action: 'delete_voucher',
            target: $voucher,
            description: "Voucher diskon {$code} dihapus."
        );

        return back()->with('success', "Voucher '{$code}' berhasil dihapus.");
    }
}
