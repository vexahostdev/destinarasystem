<?php

namespace App\Domain\Booking\Services;

use App\Domain\Audit\AuditLogger;
use App\Domain\Booking\StateMachine\BookingStateMachine;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Enums\RefundStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingStatusHistory;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\Package;
use App\Models\PartnerPayout;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherRedemption;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class BookingService
{
    public function __construct(
        protected PricingService $pricingService,
        protected AvailabilityService $availabilityService,
        protected BookingCodeGenerator $codeGenerator,
        protected BookingStateMachine $stateMachine
    ) {}

    /**
     * Create a new booking according to SPEC §8.1.
     */
    public function create(array $data, User $buyer): Booking
    {
        // 1. Verify Buyer Profile minimum data (SPEC §8.1 Poin 6)
        $institution = $buyer->institution;
        if (! $institution || empty($institution->institution_name) || empty($institution->pic_name) || empty($institution->pic_phone)) {
            throw new InvalidArgumentException('Profil institusi belum lengkap. Harap lengkapi profil terlebih dahulu.');
        }

        // 2. Verify Destination & Partner
        $destination = Destination::findOrFail($data['destination_id']);
        if (! $destination->isPublished()) {
            throw new InvalidArgumentException('Destinasi ini sedang tidak aktif atau belum ditayangkan.');
        }

        $partner = $destination->partner;
        if (! $partner || $partner->isSuspended() || ! $partner->partnerProfile?->isVerified()) {
            throw new InvalidArgumentException('Mitra pengelola destinasi ini belum terverifikasi atau sedang ditangguhkan.');
        }

        // 3. Verify Package
        $package = Package::where('destination_id', $destination->id)
            ->where('is_active', true)
            ->findOrFail($data['package_id']);

        $inquiryType = $data['inquiry_type'] ?? 'study_tour';
        if (! in_array($inquiryType, $package->inquiry_types ?? [])) {
            throw new InvalidArgumentException("Paket ini tidak mendukung jenis inquiry: {$inquiryType}.");
        }

        // 4. Verify Dates & Lead time
        $minLeadDays = (int) setting('min_lead_days', 7);
        $maxAdvanceDays = (int) setting('max_advance_days', 365);
        $startDate = Carbon::parse($data['planned_date_start'])->startOfDay();
        $minAllowedDate = now()->addDays($minLeadDays)->startOfDay();
        $maxAllowedDate = now()->addDays($maxAdvanceDays)->endOfDay();

        if ($startDate->lt($minAllowedDate)) {
            throw new InvalidArgumentException("Pemesanan minimal dilakukan {$minLeadDays} hari sebelum tanggal kunjungan.");
        }
        if ($startDate->gt($maxAllowedDate)) {
            throw new InvalidArgumentException("Pemesanan maksimal dapat dilakukan hingga {$maxAdvanceDays} hari ke depan.");
        }

        // Calculate end date automatically from package duration (SPEC §8.1 Poin 3)
        $endDate = $startDate->copy()->addDays($package->duration_days - 1);

        // 5. Verify Participant count (Pax)
        $pax = (int) $data['participant_count'];
        if ($pax < $package->min_pax || $pax > $package->max_pax) {
            throw new InvalidArgumentException(
                "Jumlah peserta harus antara {$package->min_pax} sampai {$package->max_pax} peserta."
            );
        }

        // 6. Voucher (if provided)
        $voucher = null;
        if (! empty($data['voucher_code'])) {
            $voucher = Voucher::where('code', strtoupper($data['voucher_code']))->first();
        }

        // 7. Atomic DB Transaction with slot lock
        return DB::transaction(function () use (
            $buyer, $institution, $destination, $package, $inquiryType,
            $startDate, $endDate, $pax, $data, $voucher
        ) {
            $pricing = $this->pricingService->calculate(
                package: $package,
                pax: $pax,
                selectedAddonIds: $data['addon_ids'] ?? [],
                voucher: $voucher,
                userId: $buyer->id
            );

            $bookingCode = $this->codeGenerator->generate();
            $expiryHours = (int) setting('payment_expiry_hours', 24);

            $booking = Booking::create([
                'booking_code' => $bookingCode,
                'user_id' => $buyer->id,
                'institution_id' => $institution->id,
                'destination_id' => $destination->id,
                'package_id' => $package->id,
                'inquiry_type' => $inquiryType,
                'planned_date_start' => $startDate->format('Y-m-d'),
                'planned_date_end' => $endDate->format('Y-m-d'),
                'participant_count' => $pax,
                'companion_count' => (int) ($data['companion_count'] ?? 0),
                'purpose_notes' => $data['purpose_notes'] ?? null,
                'needs_permit_letter' => (bool) ($data['needs_permit_letter'] ?? false),
                'permit_details' => $data['permit_details'] ?? null,
                'subtotal_amount' => $pricing->subtotal,
                'addons_amount' => $pricing->addonsTotal,
                'insurance_amount' => $pricing->insuranceAmount,
                'platform_fee_amount' => $pricing->platformFeeAmount,
                'discount_amount' => $pricing->discountAmount,
                'total_amount' => $pricing->totalAmount,
                'commission_percent' => $pricing->commissionPercent,
                'commission_amount' => $pricing->commissionAmount,
                'partner_net_amount' => $pricing->partnerNetAmount,
                'voucher_id' => $pricing->voucherId,
                'status' => BookingStatus::MENUNGGU_PEMBAYARAN,
                'expires_at' => now()->addHours($expiryHours),
            ]);

            // Hold slots across dates (locks availability_slots)
            $this->availabilityService->holdSlots($booking);

            // Create booking snapshot items
            foreach ($pricing->items as $item) {
                BookingItem::create([
                    'booking_id' => $booking->id,
                    'type' => $item['type'],
                    'reference_id' => $item['reference_id'] ?? null,
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
                ]);
            }

            // Voucher redemption record
            if ($voucher && $pricing->discountAmount > 0) {
                $voucher->increment('used_count');
                VoucherRedemption::create([
                    'voucher_id' => $voucher->id,
                    'booking_id' => $booking->id,
                    'user_id' => $buyer->id,
                    'amount' => $pricing->discountAmount,
                ]);
            }

            // Create initial pending payment
            Payment::create([
                'booking_id' => $booking->id,
                'gateway' => setting('default_gateway', 'xendit'),
                'external_id' => "{$bookingCode}-P1",
                'amount' => $booking->total_amount,
                'status' => PaymentStatus::PENDING,
                'expires_at' => $booking->expires_at,
            ]);

            // Record initial status timeline history (null -> menunggu_pembayaran)
            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'from_status' => null,
                'to_status' => BookingStatus::MENUNGGU_PEMBAYARAN,
                'actor_id' => $buyer->id,
                'note' => 'Pemesanan baru berhasil diajukan oleh pemesan.',
                'created_at' => now(),
            ]);

            AuditLogger::log(
                action: 'booking_created',
                target: $booking,
                description: "Booking {$bookingCode} berhasil diajukan oleh {$buyer->name}.",
                userId: $buyer->id
            );

            return $booking;
        });
    }

    /**
     * Calculate cancellation refund percentage and nominal according to SPEC §8.4.
     */
    public function calculateRefund(Booking $booking, string $source = 'buyer'): array
    {
        // If cancellation is caused by partner rejection, SLA expiry, or force majeure by admin
        if (in_array($source, ['partner', 'sla', 'system'])) {
            return [
                'percent' => 100,
                'amount' => $booking->total_amount,
            ];
        }

        // If unpaid
        if ($booking->status === BookingStatus::MENUNGGU_PEMBAYARAN) {
            return [
                'percent' => 0,
                'amount' => 0,
            ];
        }

        // Buyer voluntary cancellation
        $policy = $booking->destination->cancellationPolicy
            ?? CancellationPolicy::where('is_default', true)->first();

        if (! $policy) {
            return [
                'percent' => 0,
                'amount' => 0,
            ];
        }

        // Days between today and visit start date
        $daysBefore = (int) now()->diffInDays(Carbon::parse($booking->planned_date_start), false);

        $refundPercent = 0;
        foreach ($policy->tiers as $tier) {
            if ($daysBefore >= $tier->min_days_before) {
                $refundPercent = $tier->refund_percent;
                break;
            }
        }

        $refundAmount = (int) round($booking->total_amount * ($refundPercent / 100));

        return [
            'percent' => $refundPercent,
            'amount' => $refundAmount,
        ];
    }

    /**
     * Cancel booking, release slots, and enqueue refund if eligible (SPEC §8.4).
     */
    public function cancel(
        Booking $booking,
        User $actor,
        ?string $reason = null,
        string $source = 'buyer',
        ?int $overridePercent = null,
        ?array $bankDetails = null
    ): Booking {
        return DB::transaction(function () use ($booking, $actor, $reason, $source, $overridePercent, $bankDetails) {
            $lockedBooking = Booking::where('id', $booking->id)->lockForUpdate()->first();

            // Calculate refund before state change
            $refundCalc = $this->calculateRefund($lockedBooking, $source);
            $refundPercent = $overridePercent !== null ? $overridePercent : $refundCalc['percent'];
            $refundAmount = (int) round($lockedBooking->total_amount * ($refundPercent / 100));

            $lockedBooking->cancellation_source = $source;
            $lockedBooking->cancel_reason = $reason;

            // Execute transition to BATAL
            $this->stateMachine->transition(
                booking: $lockedBooking,
                to: BookingStatus::BATAL,
                actor: $actor,
                note: "Dibatalkan ({$source}): {$reason}"
            );

            // Release slots
            $this->availabilityService->releaseSlots($lockedBooking);

            // Expire pending payments
            Payment::where('booking_id', $lockedBooking->id)
                ->where('status', PaymentStatus::PENDING)
                ->update(['status' => PaymentStatus::CANCELLED]);

            // Create refund record if refund amount > 0
            if ($refundAmount > 0) {
                $payment = $lockedBooking->payments()->where('status', PaymentStatus::PAID)->latest()->first();

                Refund::create([
                    'booking_id' => $lockedBooking->id,
                    'payment_id' => $payment?->id,
                    'amount' => $refundAmount,
                    'percent' => $refundPercent,
                    'reason' => $reason ?? 'Pembatalan pesanan',
                    'status' => RefundStatus::PENDING,
                    'buyer_bank_id' => $bankDetails['bank_id'] ?? null,
                    'buyer_account_number' => $bankDetails['account_number'] ?? null,
                    'buyer_account_holder' => $bankDetails['account_holder'] ?? null,
                ]);
            }

            return $lockedBooking;
        });
    }

    /**
     * Partner or Admin confirms paid booking (SPEC §7.1, §8.6).
     */
    public function confirm(Booking $booking, User $actor): Booking
    {
        return DB::transaction(function () use ($booking, $actor) {
            $locked = Booking::where('id', $booking->id)->lockForUpdate()->first();

            if ($locked->status !== BookingStatus::DIBAYAR) {
                throw new RuntimeException('Hanya booking yang telah dibayar lunas yang dapat dikonfirmasi.');
            }

            // Promote held slots to booked
            $this->availabilityService->promoteHeldToBooked($locked);

            // Transition to DIKONFIRMASI
            $this->stateMachine->transition(
                booking: $locked,
                to: BookingStatus::DIKONFIRMASI,
                actor: $actor,
                note: 'Pemesanan dikonfirmasi oleh pengelola tapak.'
            );

            return $locked;
        });
    }

    /**
     * Complete booking (SPEC §7.1, §8.8).
     */
    public function complete(Booking $booking, ?User $actor = null, ?string $note = null): Booking
    {
        return DB::transaction(function () use ($booking, $actor, $note) {
            $locked = Booking::where('id', $booking->id)->lockForUpdate()->first();

            $this->stateMachine->transition(
                booking: $locked,
                to: BookingStatus::SELESAI,
                actor: $actor,
                note: $note ?? 'Kunjungan telah selesai dilaksanakan.'
            );

            // Create eligible Partner Payout record if not already created
            if (! PartnerPayout::where('booking_id', $locked->id)->exists()) {
                $delayDays = (int) setting('payout_delay_days', 3);
                $partner = $locked->destination->partner;
                $profile = $partner?->partnerProfile;

                $seq = PartnerPayout::count() + 1;
                $payoutNumber = sprintf('PO/%s/%05d', now()->format('Y/m'), $seq);

                PartnerPayout::create([
                    'payout_number' => $payoutNumber,
                    'partner_id' => $partner->id,
                    'booking_id' => $locked->id,
                    'gross_amount' => $locked->subtotal_amount,
                    'commission_amount' => $locked->commission_amount,
                    'net_amount' => $locked->partner_net_amount,
                    'status' => PayoutStatus::PENDING,
                    'eligible_at' => now()->addDays($delayDays),
                    'bank_name' => $profile?->bank?->name ?? 'Bank Mitra',
                    'bank_account_number' => $profile?->bank_account_number ?? '-',
                    'bank_account_holder' => $profile?->bank_account_holder ?? '-',
                    'note' => "Pencairan dana untuk booking {$locked->booking_code}",
                ]);
            }

            return $locked;
        });
    }

    /**
     * Reschedule booking dates (Admin only) (SPEC §7.1, §14.4).
     */
    public function reschedule(Booking $booking, Carbon $newStartDate, User $admin, string $reason): Booking
    {
        return DB::transaction(function () use ($booking, $newStartDate, $reason) {
            $locked = Booking::where('id', $booking->id)->lockForUpdate()->first();

            if (! in_array($locked->status, [BookingStatus::DIBAYAR, BookingStatus::DIKONFIRMASI])) {
                throw new RuntimeException('Hanya booking berstatus dibayar atau dikonfirmasi yang dapat di-reschedule.');
            }

            $durationDays = $locked->package->duration_days;
            $newEndDate = $newStartDate->copy()->addDays($durationDays - 1);

            // 1. Release existing slot allocations
            $this->availabilityService->releaseSlots($locked);

            // 2. Temporarily update dates on model to hold new slots
            $oldDates = "{$locked->planned_date_start->format('d/m/Y')} - {$locked->planned_date_end->format('d/m/Y')}";
            $locked->planned_date_start = $newStartDate->format('Y-m-d');
            $locked->planned_date_end = $newEndDate->format('Y-m-d');

            // 3. Hold new slots and promote if already confirmed
            $this->availabilityService->holdSlots($locked);
            if ($locked->status === BookingStatus::DIKONFIRMASI) {
                $this->availabilityService->promoteHeldToBooked($locked);
            }

            $locked->save();

            AuditLogger::log(
                action: 'reschedule_booking',
                target: $locked,
                description: "Jadwal booking diubah dari {$oldDates} menjadi {$newStartDate->format('d/m/Y')} - {$newEndDate->format('d/m/Y')}. Alasan: {$reason}"
            );

            return $locked;
        });
    }
}
