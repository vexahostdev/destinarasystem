<?php

namespace App\Domain\Booking\Services;

use App\Domain\Audit\AuditLogger;
use App\Domain\Booking\StateMachine\BookingStateMachine;
use App\Domain\Document\Services\DocumentNumberGenerator;
use App\Domain\Finance\Services\RefundService;
use App\Enums\BookingStatus;
use App\Enums\DocumentType;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Events\BookingCancelled;
use App\Events\BookingCompleted;
use App\Events\BookingConfirmed;
use App\Events\BookingCreated;
use App\Events\BookingRejected;
use App\Events\BookingRescheduled;
use App\Events\PayoutCreated;
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
            $voucher = Voucher::where('code', strtoupper($data['voucher_code']))
                ->where('is_active', true)
                ->where('valid_from', '<=', now())
                ->where('valid_until', '>=', now())
                ->first();

            if (! $voucher) {
                throw new InvalidArgumentException('Kode voucher tidak valid atau telah kedaluwarsa.');
            }
        }

        // 7. Atomic DB Transaction with slot lock
        return DB::transaction(function () use (
            $buyer, $institution, $destination, $package, $inquiryType,
            $startDate, $endDate, $pax, $data, $voucher
        ) {
            if ($voucher) {
                $lockedVoucher = Voucher::where('id', $voucher->id)->lockForUpdate()->first();
                if ($lockedVoucher->total_quota !== null && $lockedVoucher->used_count >= $lockedVoucher->total_quota) {
                    throw new InvalidArgumentException('Kuota pemakaian voucher telah habis.');
                }

                $userUsage = VoucherRedemption::where('voucher_id', $lockedVoucher->id)
                    ->where('user_id', $buyer->id)
                    ->count();

                if ($lockedVoucher->per_user_limit !== null && $userUsage >= $lockedVoucher->per_user_limit) {
                    throw new InvalidArgumentException('Anda telah mencapai batas penggunaan voucher ini.');
                }
                $voucher = $lockedVoucher;
            }

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

            BookingCreated::dispatch($booking);

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

            if ($source === 'buyer' && $refundPercent > 0) {
                if (empty($bankDetails['account_number']) || empty($bankDetails['account_holder'])) {
                    throw new InvalidArgumentException('Nomor dan nama pemilik rekening pengembalian dana wajib diisi.');
                }
            }

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

            // Restore voucher if unpaid
            $redemption = VoucherRedemption::where('booking_id', $lockedBooking->id)->first();
            if ($redemption) {
                $redemption->voucher?->decrement('used_count');
                $redemption->delete();
            }

            // Expire pending payments
            Payment::where('booking_id', $lockedBooking->id)
                ->where('status', PaymentStatus::PENDING)
                ->update(['status' => PaymentStatus::CANCELLED]);

            // Create refund record if refund amount > 0 via RefundService
            if ($refundAmount > 0) {
                app(RefundService::class)->create(
                    booking: $lockedBooking,
                    amount: $refundAmount,
                    reason: $reason ?? 'Pembatalan pesanan',
                    percent: $refundPercent,
                    bankName: $bankDetails['bank_name'] ?? null,
                    accountNumber: $bankDetails['account_number'] ?? null,
                    accountHolder: $bankDetails['account_holder'] ?? null,
                    buyerBankId: $bankDetails['bank_id'] ?? null
                );
            }

            BookingCancelled::dispatch($lockedBooking, $reason ?? 'Pembatalan pesanan', $source, $actor);

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

            BookingConfirmed::dispatch($locked, $actor);

            return $locked;
        });
    }

    /**
     * Partner rejects paid booking (SPEC §7.1, §8.6).
     */
    public function reject(Booking $booking, User $actor, string $reason): Booking
    {
        return DB::transaction(function () use ($booking, $actor, $reason) {
            $locked = Booking::where('id', $booking->id)->lockForUpdate()->first();

            if ($locked->status !== BookingStatus::DIBAYAR) {
                throw new RuntimeException('Hanya booking yang telah dibayar yang dapat ditolak.');
            }

            $locked->cancellation_source = 'partner';
            $locked->cancel_reason = $reason;

            $this->stateMachine->transition(
                booking: $locked,
                to: BookingStatus::BATAL,
                actor: $actor,
                note: "Ditolak pengelola: {$reason}"
            );

            // Release held slots
            $this->availabilityService->releaseSlots($locked);

            // 100% refund initiated
            app(RefundService::class)->create(
                booking: $locked,
                amount: $locked->total_amount,
                reason: "Penolakan oleh pengelola: {$reason}",
                percent: 100
            );

            AuditLogger::log(
                action: 'booking_rejected',
                target: $locked,
                description: "Booking {$locked->booking_code} ditolak oleh {$actor->name}. Alasan: {$reason}",
                userId: $actor->id
            );

            BookingRejected::dispatch($locked, $reason, $actor);

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

            $locked->completed_at = now();
            $locked->save();

            // Create eligible Partner Payout record if not already created (SPEC §8.8)
            if (! PartnerPayout::where('booking_id', $locked->id)->exists()) {
                $delayDays = (int) setting('payout_delay_days', 3);
                $partner = $locked->destination->partner;
                $profile = $partner?->partnerProfile;

                $payoutNumber = app(DocumentNumberGenerator::class)->generate(DocumentType::PAYOUT_RECEIPT);

                $bankName = $profile?->bank?->name ?? $profile?->bank_name ?? 'Bank Belum Diatur';
                $hasValidBank = $profile && ! empty($profile->bank_account_number) && ($bankName !== 'Bank Belum Diatur');
                $initialStatus = $hasValidBank ? PayoutStatus::PENDING : PayoutStatus::ON_HOLD;
                $initialNote = $hasValidBank
                    ? "Pencairan dana untuk booking {$locked->booking_code}"
                    : 'Ditahan otomatis: rekening bank mitra belum dilengkapi di profil.';

                $payout = PartnerPayout::create([
                    'payout_number' => $payoutNumber,
                    'partner_id' => $partner->id,
                    'booking_id' => $locked->id,
                    'gross_amount' => $locked->subtotal_amount,
                    'commission_amount' => $locked->commission_amount,
                    'net_amount' => $locked->partner_net_amount,
                    'status' => $initialStatus,
                    'eligible_at' => now()->addDays($delayDays),
                    'bank_name' => $bankName,
                    'bank_account_number' => $profile?->bank_account_number ?? '-',
                    'bank_account_holder' => $profile?->bank_account_holder ?? '-',
                    'note' => $initialNote,
                ]);

                PayoutCreated::dispatch($payout);
            }

            BookingCompleted::dispatch($locked, $actor);

            return $locked;
        });
    }

    /**
     * Reschedule booking dates (Admin only) (SPEC §7.1, §14.4).
     */
    public function reschedule(Booking $booking, Carbon $newStartDate, User $admin, string $reason): Booking
    {
        return DB::transaction(function () use ($booking, $newStartDate, $admin, $reason) {
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

            BookingRescheduled::dispatch($locked, $oldDates, $admin);

            return $locked;
        });
    }
}
