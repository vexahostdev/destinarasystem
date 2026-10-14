<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\CancellationPolicy;
use App\Models\DestinationCategory;
use App\Models\Facility;
use App\Models\InstitutionType;
use App\Models\OrganizationType;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MasterDataController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'categories');

        $categories = DestinationCategory::withCount('destinations')->orderBy('sort_order')->get();
        $facilities = Facility::orderBy('sort_order')->get();
        $banks = Bank::orderBy('name')->get();
        $provinces = Province::orderBy('name')->get();
        $institutionTypes = InstitutionType::all();
        $organizationTypes = OrganizationType::all();
        $cancellationPolicies = CancellationPolicy::all();

        return view('admin.master.index', compact(
            'tab',
            'categories',
            'facilities',
            'banks',
            'provinces',
            'institutionTypes',
            'organizationTypes',
            'cancellationPolicies'
        ));
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
        ]);

        $cat = DestinationCategory::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'icon' => $request->icon ?? 'nature',
            'description' => $request->description,
            'is_active' => true,
        ]);

        AuditLogger::log('master_data_created', $cat, "Menambahkan kategori destinasi: {$cat->name}");

        return back()->with('success', "Kategori {$cat->name} berhasil ditambahkan!");
    }

    public function toggleCategory($id)
    {
        $cat = DestinationCategory::findOrFail($id);
        $cat->is_active = ! $cat->is_active;
        $cat->save();

        AuditLogger::log('master_data_toggled', $cat, "Mengubah status aktif kategori {$cat->name} menjadi: " . ($cat->is_active ? 'aktif' : 'nonaktif'));

        return back()->with('success', "Status kategori {$cat->name} berhasil diperbarui.");
    }

    public function storeFacility(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'icon' => 'nullable|string|max:50',
        ]);

        $fac = Facility::create([
            'name' => $request->name,
            'icon' => $request->icon ?? 'check_circle',
            'is_active' => true,
        ]);

        AuditLogger::log('master_data_created', $fac, "Menambahkan fasilitas tapak: {$fac->name}");

        return back()->with('success', "Fasilitas {$fac->name} berhasil ditambahkan!");
    }

    public function toggleFacility($id)
    {
        $fac = Facility::findOrFail($id);
        $fac->is_active = ! $fac->is_active;
        $fac->save();

        return back()->with('success', "Status fasilitas {$fac->name} diperbarui.");
    }

    public function storeBank(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:banks,code',
        ]);

        $bank = Bank::create([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'is_active' => true,
        ]);

        AuditLogger::log('master_data_created', $bank, "Menambahkan bank: {$bank->name} ({$bank->code})");

        return back()->with('success', "Bank {$bank->name} berhasil ditambahkan!");
    }

    public function toggleBank($id)
    {
        $bank = Bank::findOrFail($id);
        $bank->is_active = ! $bank->is_active;
        $bank->save();

        return back()->with('success', "Status bank {$bank->name} diperbarui.");
    }

    public function store(Request $request, $type)
    {
        return match ($type) {
            'categories' => $this->storeCategory($request),
            'facilities' => $this->storeFacility($request),
            'banks' => $this->storeBank($request),
            'cancellation-policies' => $this->storeCancellationPolicy($request),
            default => back()->with('error', "Tipe master data [{$type}] tidak dikenali."),
        };
    }

    public function update(Request $request, $type, $id)
    {
        return match ($type) {
            'categories' => $this->toggleCategory($id),
            'facilities' => $this->toggleFacility($id),
            'banks' => $this->toggleBank($id),
            default => back()->with('error', "Tipe master data [{$type}] tidak dikenali."),
        };
    }

    public function destroy(Request $request, $type, $id)
    {
        return match ($type) {
            'categories' => $this->toggleCategory($id),
            'facilities' => $this->toggleFacility($id),
            'banks' => $this->toggleBank($id),
            default => back()->with('error', "Tipe master data [{$type}] tidak dikenali."),
        };
    }
}

