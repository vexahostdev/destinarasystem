<?php

namespace App\Domain\Payment\Services;

use App\Domain\Audit\AuditLogger;
use App\Domain\Booking\Services\AvailabilityService;
use App\Domain\Booking\StateMachine\BookingStateMachine;
use App\Domain\Payment\Data\WebhookResult;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    public function __construct(
        protected PaymentGatewayManager $gatewayManager,
        protected AvailabilityService $availabilityService,
        protected BookingStateMachine $stateMachine
    ) {}

    /**
     * Initiate or retrieve checkout URL for a pending payment.
     */
    public function initiatePayment(Payment $payment): Payment
    {
        $booking = $payment->booking;

        if ($payment->checkout_url && $payment->expires_at && $payment->expires_at->isFuture()) {
            return $payment;
        }

        $gateway = $this->gatewayManager->driver($payment->gateway);
        $charge = $gateway->createCharge($payment, $booking);

        $payment->checkout_url = $charge->checkoutUrl;
        $payment->gateway_reference = $charge->gatewayReference;
        if ($charge->expiresAt) {
            $payment->expires_at = $charge->expiresAt;
        }
        $payment->raw_response = $charge->raw;
        $payment->save();

        return $payment;
    }

    /**
     * Buyer retries payment with a new attempt sequence (-P{n+1}) (SPEC §9.5).
     */
    public function retryPayment(Booking $booking, ?string $gatewayKey = null): Payment
    {
        if ($booking->status !== BookingStatus::MENUNGGU_PEMBAYARAN) {
            throw new InvalidArgumentException('Pembayaran ulang hanya dapat dilakukan untuk pesanan yang menunggu pembayaran.');
        }

        if ($booking->expires_at && $booking->expires_at->isPast()) {
            throw new InvalidArgumentException('Batas waktu pembayaran pesanan ini telah habis.');
        }

        return DB::transaction(function () use ($booking, $gatewayKey) {
            // Cancel any pending payments for this booking
            Payment::where('booking_id', $booking->id)
                ->where('status', PaymentStatus::PENDING)
                ->update(['status' => PaymentStatus::CANCELLED]);

            $attempt = Payment::where('booking_id', $booking->id)->count() + 1;
            $gateway = $gatewayKey ?: setting('default_gateway', 'xendit');

            $newPayment = Payment::create([
                'booking_id' => $booking->id,
                'gateway' => $gateway,
                'external_id' => "{$booking->booking_code}-P{$attempt}",
                'amount' => $booking->total_amount,
                'status' => PaymentStatus::PENDING,
                'expires_at' => $booking->expires_at,
            ]);

            return $this->initiatePayment($newPayment);
        });
    }

    /**
     * Process successful payment webhook or manual verification (SPEC §9.5, §8.4).
     */
    public function processPaymentSuccess(Payment $payment, WebhookResult $result): void
    {
        DB::transaction(function () use ($payment, $result) {
            $lockedPayment = Payment::where('id', $payment->id)->lockForUpdate()->first();
            $lockedBooking = Booking::where('id', $lockedPayment->booking_id)->lockForUpdate()->first();

            // 1. Idempotency check: if payment already marked as PAID
            if ($lockedPayment->status === PaymentStatus::PAID) {
                return;
            }

            // 2. Amount verification check (SPEC §9.5)
            if ($result->amount !== null && (int) $result->amount !== (int) $lockedPayment->amount) {
                $lockedPayment->status = PaymentStatus::FAILED;
                $lockedPayment->raw_response = array_merge($lockedPayment->raw_response ?? [], [
                    'amount_mismatch_error' => "Nominal webhook ({$result->amount}) tidak sama dengan tagihan ({$lockedPayment->amount}).",
                ]);
                $lockedPayment->save();

                AuditLogger::log(
                    action: 'payment_amount_mismatch',
                    target: $lockedPayment,
                    description: "Pembayaran {$lockedPayment->external_id} gagal: Nominal webhook (Rp ".number_format($result->amount, 0, ',', '.').') berbeda dari tagihan (Rp '.number_format($lockedPayment->amount, 0, ',', '.').').'
                );

                return;
            }

            // 3. Late Payment check: booking already cancelled or expired (SPEC §8.4)
            if (in_array($lockedBooking->status, [BookingStatus::BATAL, BookingStatus::KEDALUWARSA])) {
                $lockedPayment->status = PaymentStatus::PAID;
                $lockedPayment->paid_at = $result->paidAt ?? now();
                $lockedPayment->payment_method = $result->paymentMethod;
                $lockedPayment->payment_channel = $result->paymentChannel;
                $lockedPayment->gateway_reference = $result->gatewayReference ?: $lockedPayment->gateway_reference;
                $lockedPayment->raw_response = $result->raw;
                $lockedPayment->save();

                // Automatic 100% refund record
                Refund::create([
                    'booking_id' => $lockedBooking->id,
                    'payment_id' => $lockedPayment->id,
                    'amount' => $lockedPayment->amount,
                    'percent' => 100,
                    'reason' => 'Late payment: pembayaran diterima setelah pesanan kedaluwarsa atau dibatalkan.',
                    'status' => RefundStatus::PENDING,
                ]);

                AuditLogger::log(
                    action: 'late_payment_received',
                    target: $lockedPayment,
                    description: "Late payment diterima untuk booking {$lockedBooking->booking_code} yang telah {$lockedBooking->status->label()}. Refund 100% otomatis dibuat."
                );

                return;
            }

            // 4. Normal flow: update payment to PAID
            $lockedPayment->status = PaymentStatus::PAID;
            $lockedPayment->paid_at = $result->paidAt ?? now();
            $lockedPayment->payment_method = $result->paymentMethod;
            $lockedPayment->payment_channel = $result->paymentChannel;
            $lockedPayment->gateway_reference = $result->gatewayReference ?: $lockedPayment->gateway_reference;
            $lockedPayment->raw_response = $result->raw;
            $lockedPayment->save();

            // 5. Promote held slots to booked
            $this->availabilityService->promoteHeldToBooked($lockedBooking);

            // 6. Set partner confirm deadline according to SLA (SPEC §7.1)
            $partnerSlaHours = (int) setting('partner_sla_hours', 24);
            $lockedBooking->partner_confirm_deadline = now()->addHours($partnerSlaHours);
            $lockedBooking->save();

            // 7. Transition booking status to DIBAYAR
            $this->stateMachine->transition(
                booking: $lockedBooking,
                to: BookingStatus::DIBAYAR,
                actor: null,
                note: "Pembayaran terverifikasi melalui {$lockedPayment->gateway} ({$lockedPayment->external_id})."
            );
        });
    }

    /**
     * Process payment expiration.
     */
    public function processPaymentExpiry(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $lockedPayment = Payment::where('id', $payment->id)->lockForUpdate()->first();
            if ($lockedPayment->status === PaymentStatus::PENDING) {
                $lockedPayment->status = PaymentStatus::EXPIRED;
                $lockedPayment->save();

                try {
                    $this->gatewayManager->driver($lockedPayment->gateway)->expire($lockedPayment);
                } catch (\Throwable) {
                    // Gateway error on expire is non-fatal
                }
            }
        });
    }

    /**
     * Handle buyer uploading manual transfer receipt.
     */
    public function processManualProofUpload(Payment $payment, UploadedFile $file, User $buyer): Payment
    {
        if ($payment->gateway !== 'manual_transfer') {
            throw new InvalidArgumentException('Bukti transfer hanya dapat diunggah untuk metode transfer manual.');
        }

        if ($payment->status !== PaymentStatus::PENDING) {
            throw new InvalidArgumentException('Pembayaran ini tidak dalam status menunggu pembayaran.');
        }

        $dir = 'private/payments/'.now()->format('Y/m');
        $fileName = 'proof_'.$payment->external_id.'_'.uniqid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs($dir, $fileName, 'local');

        $payment->proof_path = $path;
        $payment->save();

        AuditLogger::log(
            action: 'payment_proof_uploaded',
            target: $payment,
            description: "Bukti transfer manual diunggah oleh pemesan {$buyer->name}.",
            userId: $buyer->id
        );

        return $payment;
    }

    /**
     * Admin verifies manual payment proof (SPEC §9.4).
     */
    public function verifyManualPayment(Payment $payment, User $admin, bool $approved, ?string $rejectionReason = null): void
    {
        if ($payment->gateway !== 'manual_transfer') {
            throw new InvalidArgumentException('Verifikasi manual hanya berlaku untuk metode transfer manual.');
        }

        if ($approved) {
            $result = new WebhookResult(
                eventId: "manual-verify-{$payment->id}-".time(),
                externalId: $payment->external_id,
                status: PaymentStatus::PAID,
                amount: (int) $payment->amount,
                paidAt: now(),
                paymentMethod: 'manual_transfer',
                paymentChannel: 'bank_transfer',
                gatewayReference: $payment->external_id,
                raw: ['verified_by' => $admin->id, 'verified_at' => now()->toIso8601String()]
            );

            $payment->verified_by = $admin->id;
            $payment->save();

            $this->processPaymentSuccess($payment, $result);
        } else {
            $payment->raw_response = array_merge($payment->raw_response ?? [], [
                'rejection_reason' => $rejectionReason ?? 'Bukti transfer tidak valid atau tidak terbaca.',
                'rejected_by' => $admin->id,
                'rejected_at' => now()->toIso8601String(),
            ]);
            $payment->save();

            AuditLogger::log(
                action: 'payment_proof_rejected',
                target: $payment,
                description: "Bukti transfer ditolak oleh admin {$admin->name}. Alasan: {$rejectionReason}",
                userId: $admin->id
            );
        }
    }

    /**
     * Query gateway status and sync state.
     */
    public function checkStatus(Payment $payment): WebhookResult
    {
        $gateway = $this->gatewayManager->driver($payment->gateway);
        $result = $gateway->fetchStatus($payment);

        if ($result->status === PaymentStatus::PAID && $payment->status !== PaymentStatus::PAID) {
            $this->processPaymentSuccess($payment, $result);
        } elseif ($result->status === PaymentStatus::EXPIRED && $payment->status === PaymentStatus::PENDING) {
            $this->processPaymentExpiry($payment);
        }

        return $result;
    }
}
