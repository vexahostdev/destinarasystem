<?php

namespace App\Livewire\Buyer;

use App\Domain\Booking\Services\AvailabilityService;
use App\Domain\Booking\Services\BookingService;
use App\Domain\Booking\Services\PricingService;
use App\Models\Destination;
use App\Models\Package;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BookingForm extends Component
{
    public Destination $destination;

    public ?int $packageId = null;

    public string $inquiryType = 'study_tour';

    public ?string $plannedDateStart = null;

    public int $participantCount = 10;

    public int $companionCount = 2;

    public array $selectedAddonIds = [];

    public ?string $voucherCode = '';

    public ?string $purposeNotes = '';

    public bool $needsPermitLetter = false;

    public ?string $permitDetails = '';

    // Calculation results
    public int $subtotal = 0;

    public int $addonsTotal = 0;

    public int $insuranceTotal = 0;

    public int $platformFee = 0;

    public int $discount = 0;

    public int $grandTotal = 0;

    public bool $isSlotAvailable = true;

    public string $availabilityMessage = '';

    public ?string $voucherError = null;

    public function mount(Destination $destination, ?string $preselectedPackage = null): void
    {
        $this->destination = $destination;
        $activePackages = $this->destination->packages;

        if ($activePackages->isNotEmpty()) {
            $defaultPackage = $preselectedPackage
                ? $activePackages->firstWhere('slug', $preselectedPackage) ?? $activePackages->first()
                : $activePackages->first();

            $this->packageId = $defaultPackage->id;
            $this->participantCount = $defaultPackage->min_pax;
            $this->inquiryType = $defaultPackage->inquiry_types[0] ?? 'study_tour';
        }

        $minLeadDays = (int) setting('min_lead_days', 7);
        $this->plannedDateStart = now()->addDays($minLeadDays + 1)->format('Y-m-d');

        $this->recalculate();
    }

    public function updatedPackageId(): void
    {
        $package = Package::find($this->packageId);
        if ($package) {
            if ($this->participantCount < $package->min_pax) {
                $this->participantCount = $package->min_pax;
            } elseif ($this->participantCount > $package->max_pax) {
                $this->participantCount = $package->max_pax;
            }
            if (! in_array($this->inquiryType, $package->inquiry_types ?? [])) {
                $this->inquiryType = $package->inquiry_types[0] ?? 'study_tour';
            }
        }
        $this->recalculate();
    }

    public function updatedParticipantCount(): void
    {
        $this->recalculate();
    }

    public function updatedSelectedAddonIds(): void
    {
        $this->recalculate();
    }

    public function updatedPlannedDateStart(): void
    {
        $this->recalculate();
    }

    public function updatedVoucherCode(): void
    {
        $this->recalculate();
    }

    public function recalculate(): void
    {
        $package = Package::find($this->packageId);
        if (! $package) {
            return;
        }

        $voucher = null;
        $this->voucherError = null;
        if (! empty($this->voucherCode)) {
            $voucher = Voucher::where('code', strtoupper(trim($this->voucherCode)))->first();
            $reason = null;
            if (! $voucher || ! $voucher->isValid(destinationId: $this->destination->id, userId: Auth::id(), reason: $reason)) {
                $this->voucherError = $reason ?? 'Kode voucher tidak valid atau masa berlaku telah habis.';
                $voucher = null;
            }
        }

        try {
            $pricingService = app(PricingService::class);
            $pricing = $pricingService->calculate(
                package: $package,
                pax: (int) $this->participantCount,
                selectedAddonIds: $this->selectedAddonIds,
                voucher: $voucher,
                userId: Auth::id()
            );

            $this->subtotal = $pricing->subtotal;
            $this->addonsTotal = $pricing->addonsTotal;
            $this->insuranceTotal = $pricing->insuranceTotal;
            $this->platformFee = $pricing->platformFee;
            $this->discount = $pricing->discountAmount;
            $this->grandTotal = $pricing->grandTotal;
        } catch (\Throwable $e) {
            // Keep current values on calculation error
        }

        // Check availability
        if ($this->plannedDateStart) {
            try {
                $startDate = Carbon::parse($this->plannedDateStart);
                $endDate = $startDate->copy()->addDays($package->duration_days - 1);
                $availService = app(AvailabilityService::class);
                $this->isSlotAvailable = $availService->checkDateRangeAvailable(
                    $this->destination,
                    $startDate,
                    $endDate,
                    (int) $this->participantCount
                );
                $this->availabilityMessage = $this->isSlotAvailable
                    ? 'Slot ketersediaan terbuka untuk tanggal ini.'
                    : 'Kapasitas penuh atau slot ditutup oleh pengelola pada tanggal ini.';
            } catch (\Throwable $e) {
                $this->isSlotAvailable = false;
                $this->availabilityMessage = $e->getMessage();
            }
        }
    }

    public function submit(BookingService $bookingService)
    {
        $package = Package::findOrFail($this->packageId);

        $this->validate([
            'packageId' => 'required|exists:packages,id',
            'inquiryType' => 'required|string',
            'plannedDateStart' => 'required|date',
            'participantCount' => "required|integer|min:{$package->min_pax}|max:{$package->max_pax}",
            'companionCount' => 'nullable|integer|min:0|max:50',
            'purposeNotes' => 'nullable|string|max:1000',
            'permitDetails' => 'nullable|string|max:1000',
        ]);

        if (! $this->isSlotAvailable) {
            $this->addError('plannedDateStart', 'Kapasitas slot tapak tidak mencukupi untuk tanggal dan kuota yang dipilih.');

            return;
        }

        try {
            $booking = $bookingService->create([
                'destination_id' => $this->destination->id,
                'package_id' => $package->id,
                'inquiry_type' => $this->inquiryType,
                'planned_date_start' => $this->plannedDateStart,
                'participant_count' => (int) $this->participantCount,
                'companion_count' => (int) $this->companionCount,
                'addon_ids' => $this->selectedAddonIds,
                'voucher_code' => $this->voucherCode,
                'purpose_notes' => $this->purposeNotes,
                'needs_permit_letter' => $this->needsPermitLetter,
                'permit_details' => $this->permitDetails,
            ], Auth::user());

            return redirect()->route('buyer.booking.payment', $booking->booking_code)
                ->with('success', 'Reservasi berhasil dibuat! Silakan selesaikan pembayaran sesuai tagihan resmi.');
        } catch (\Throwable $e) {
            $this->addError('form', $e->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.buyer.booking-form', [
            'packages' => $this->destination->packages,
            'addons' => $this->destination->addons,
        ]);
    }
}
