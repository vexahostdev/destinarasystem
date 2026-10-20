<?php

namespace App\Livewire\Buyer;

use App\Domain\Booking\Services\AvailabilityService;
use App\Domain\Booking\Services\BookingService;
use App\Domain\Booking\Services\PricingService;
use App\Domain\Payment\Services\PaymentGatewayManager;
use App\Models\AvailabilitySlot;
use App\Models\Destination;
use App\Models\Package;
use App\Models\Voucher;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class BookingForm extends Component
{
    public Destination $destination;

    public bool $isPaymentAvailable = true;

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
    public int $packagePricePerPax = 0;

    public int $packageTotal = 0;

    public int $subtotal = 0;

    public int $addonsTotal = 0;

    public int $insuranceAmount = 0;

    public int $insuranceTotal = 0;

    public int $platformFeeAmount = 0;

    public int $platformFee = 0;

    public int $discountAmount = 0;

    public int $discount = 0;

    public int $grandTotal = 0;

    public array $pricingItems = [];

    public array $availableInquiryTypes = [];

    public string $minDate = '';

    public string $maxDate = '';

    public bool $isSlotAvailable = true;

    public string $availabilityMessage = '';

    public ?string $voucherError = null;

    public function mount(Destination $destination, ?string $preselectedPackage = null): void
    {
        $this->destination = $destination;
        $activePackages = $this->destination->packages;

        $minLeadDays = (int) setting('min_lead_days', 7);
        $maxAdvanceDays = (int) setting('max_advance_days', 180);
        $this->minDate = now()->addDays($minLeadDays)->format('Y-m-d');
        $this->maxDate = now()->addDays($maxAdvanceDays)->format('Y-m-d');
        $this->plannedDateStart = now()->addDays($minLeadDays + 1)->format('Y-m-d');

        if ($activePackages->isNotEmpty()) {
            $defaultPackage = $preselectedPackage
                ? $activePackages->firstWhere('slug', $preselectedPackage) ?? $activePackages->first()
                : $activePackages->first();

            $this->packageId = $defaultPackage->id;
            $this->participantCount = $defaultPackage->min_pax;
            $this->updateAvailableInquiryTypes($defaultPackage);
        } else {
            $this->updateAvailableInquiryTypes(null);
        }

        $this->recalculate();
    }

    protected function updateAvailableInquiryTypes(?Package $package): void
    {
        if (! $package) {
            $this->availableInquiryTypes = ['study_tour'];

            return;
        }

        $packageInquiryTypes = $package->inquiry_types ?? [];
        $suitableFor = $this->destination->suitable_for ?? [];

        $intersection = array_values(array_intersect($packageInquiryTypes, $suitableFor));
        if (empty($intersection)) {
            $intersection = ! empty($packageInquiryTypes) ? $packageInquiryTypes : ['study_tour'];
        }

        $this->availableInquiryTypes = $intersection;

        if (! in_array($this->inquiryType, $this->availableInquiryTypes)) {
            $this->inquiryType = $this->availableInquiryTypes[0] ?? 'study_tour';
        }
    }

    public function updatedPackageId(): void
    {
        $package = Package::find($this->packageId);
        if ($package) {
            $this->updateAvailableInquiryTypes($package);
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
        $this->isPaymentAvailable = ! empty(app(PaymentGatewayManager::class)->getAvailableGateways());

        $package = Package::find($this->packageId);
        if (! $package) {
            return;
        }

        $pax = (int) $this->participantCount;
        $isPaxInvalid = ($pax < $package->min_pax || $pax > $package->max_pax);

        if ($isPaxInvalid) {
            $this->addError('participantCount', "Paket ini untuk {$package->min_pax}–{$package->max_pax} peserta");

            $this->packagePricePerPax = $package->price_per_pax ?? 0;
            $this->packageTotal = 0;
            $this->subtotal = 0;
            $this->addonsTotal = 0;
            $this->insuranceAmount = 0;
            $this->insuranceTotal = 0;
            $this->platformFeeAmount = 0;
            $this->platformFee = 0;
            $this->discountAmount = 0;
            $this->discount = 0;
            $this->grandTotal = 0;
            $this->pricingItems = [];

            // V7-3: jika jumlah peserta di luar min/max paket, jangan jalankan cek slot dan jangan tampilkan pesan slot
            $this->isSlotAvailable = false;
            $this->availabilityMessage = '';

            return;
        } else {
            $this->resetErrorBag('participantCount');

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

            $pricingService = app(PricingService::class);
            $pricing = $pricingService->calculate(
                package: $package,
                pax: $pax,
                selectedAddonIds: $this->selectedAddonIds,
                voucher: $voucher,
                userId: Auth::id()
            );

            $this->packagePricePerPax = $pricing->packagePricePerPax;
            $this->packageTotal = $pricing->packageTotal;
            $this->subtotal = $pricing->subtotal;
            $this->addonsTotal = $pricing->addonsTotal;
            $this->insuranceAmount = $pricing->insuranceAmount;
            $this->insuranceTotal = $pricing->insuranceAmount;
            $this->platformFeeAmount = $pricing->platformFeeAmount;
            $this->platformFee = $pricing->platformFeeAmount;
            $this->discountAmount = $pricing->discountAmount;
            $this->discount = $pricing->discountAmount;
            $this->grandTotal = $pricing->totalAmount;
            $this->pricingItems = $pricing->items;
        }

        // Check availability strictly for slot state
        $this->checkSlotAvailability($package);
    }

    protected function checkSlotAvailability(Package $package): void
    {
        if (! $this->plannedDateStart) {
            $this->isSlotAvailable = false;
            $this->availabilityMessage = 'Pilih tanggal kunjungan terlebih dahulu.';

            return;
        }

        try {
            $minLeadDays = (int) setting('min_lead_days', 7);
            $maxAdvanceDays = (int) setting('max_advance_days', 180);
            $startDate = Carbon::parse($this->plannedDateStart)->startOfDay();
            $duration = max(1, $package->duration_days ?? 1);
            $endDate = $startDate->copy()->addDays($duration - 1)->endOfDay();

            $minAllowed = now()->addDays($minLeadDays)->startOfDay();
            $maxAllowed = now()->addDays($maxAdvanceDays)->endOfDay();

            if ($startDate->lt($minAllowed) || $startDate->gt($maxAllowed)) {
                $this->isSlotAvailable = false;
                $this->availabilityMessage = "Tanggal di luar rentang pemesanan (minimal H-{$minLeadDays} dan maksimal {$maxAdvanceDays} hari ke depan).";

                return;
            }

            $paxToCheck = max(1, (int) $this->participantCount);
            $period = CarbonPeriod::create($startDate, $endDate);

            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');
                $slot = AvailabilitySlot::where('destination_id', $this->destination->id)
                    ->whereDate('date', $dateStr)
                    ->first();

                if ($slot) {
                    if ($slot->status === 'closed' || $slot->status !== 'open') {
                        $this->isSlotAvailable = false;
                        $this->availabilityMessage = 'Slot ditutup oleh pengelola tapak pada tanggal ini.';

                        return;
                    }
                    if ($slot->remaining_capacity < $paxToCheck) {
                        $this->isSlotAvailable = false;
                        $this->availabilityMessage = 'Slot penuh untuk tanggal kunjungan yang dipilih.';

                        return;
                    }
                } else {
                    $defCap = $this->destination->default_daily_capacity;
                    if ($defCap === null) {
                        $this->isSlotAvailable = false;
                        $this->availabilityMessage = 'Tanggal ini belum dibuka oleh pengelola.';

                        return;
                    }
                    if ($defCap < $paxToCheck) {
                        $this->isSlotAvailable = false;
                        $this->availabilityMessage = 'Slot penuh untuk tanggal kunjungan yang dipilih.';

                        return;
                    }
                }
            }

            $this->isSlotAvailable = true;
            $this->availabilityMessage = 'Slot ketersediaan terbuka untuk tanggal ini.';
        } catch (\Throwable $e) {
            $this->isSlotAvailable = false;
            $this->availabilityMessage = $e->getMessage();
        }
    }

    public function submit(BookingService $bookingService)
    {
        $this->isPaymentAvailable = ! empty(app(PaymentGatewayManager::class)->getAvailableGateways());

        if (! $this->isPaymentAvailable) {
            $this->addError('form', 'Pembayaran belum tersedia, hubungi admin.');

            return;
        }

        $package = Package::findOrFail($this->packageId);

        if ($this->getErrorBag()->has('participantCount')) {
            return;
        }

        $this->validate([
            'packageId' => 'required|exists:packages,id',
            'inquiryType' => ['required', 'string', Rule::in($this->availableInquiryTypes)],
            'plannedDateStart' => "required|date|after_or_equal:{$this->minDate}|before_or_equal:{$this->maxDate}",
            'participantCount' => "required|integer|min:{$package->min_pax}|max:{$package->max_pax}",
            'companionCount' => 'nullable|integer|min:0|max:50',
            'purposeNotes' => 'nullable|string|max:1000',
            'permitDetails' => 'nullable|string|max:1000',
        ], [
            'participantCount.min' => "Paket ini untuk {$package->min_pax}–{$package->max_pax} peserta",
            'participantCount.max' => "Paket ini untuk {$package->min_pax}–{$package->max_pax} peserta",
            'plannedDateStart.after_or_equal' => 'Tanggal di luar rentang pemesanan.',
            'plannedDateStart.before_or_equal' => 'Tanggal di luar rentang pemesanan.',
        ]);

        if (! $this->isSlotAvailable) {
            $this->addError('plannedDateStart', $this->availabilityMessage ?: 'Kapasitas slot tapak tidak mencukupi untuk tanggal dan kuota yang dipilih.');

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
        $this->isPaymentAvailable = ! empty(app(PaymentGatewayManager::class)->getAvailableGateways());

        return view('livewire.buyer.booking-form', [
            'packages' => $this->destination->packages,
            'addons' => $this->destination->addons,
        ]);
    }
}
