<?php

namespace App\Domain\Finance\Services;

use App\Domain\Audit\AuditLogger;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Enums\RefundStatus;
use App\Events\RefundCompleted;
use App\Events\RefundCreated;
use App\Models\Booking;
use App\Models\PartnerPayout;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RefundService
{
    /**
     * Create a new refund record for a booking (SPEC §8.4, §9.5).
     */
    public function create(
        Booking $booking,
        int $amount,
        string $reason,
        int $percent = 100,
        ?int $paymentId = null,
        ?string $bankName = null,
        ?string $accountNumber = null,
        ?string $accountHolder = null,
        ?int $buyerBankId = null
    ): Refund {
        return DB::transaction(function () use (
            $booking, $amount, $reason, $percent, $paymentId, $accountNumber, $accountHolder, $buyerBankId
        ) {
            $payment = $paymentId
                ? Payment::find($paymentId)
                : $booking->payments()->where('status', PaymentStatus::PAID)->latest()->first();

            $refund = Refund::create([
                'booking_id' => $booking->id,
                'payment_id' => $payment?->id,
                'amount' => $amount,
                'percent' => $percent,
                'reason' => $reason,
                'status' => RefundStatus::PENDING,
                'buyer_bank_id' => $buyerBankId,
                'buyer_account_number' => $accountNumber,
                'buyer_account_holder' => $accountHolder,
            ]);

            // Put partner payout on hold if already generated
            PartnerPayout::where('booking_id', $booking->id)
                ->whereIn('status', [PayoutStatus::PENDING, PayoutStatus::SCHEDULED])
                ->update([
                    'status' => PayoutStatus::ON_HOLD,
                    'note' => 'Ditahan karena terdapat pengajuan refund untuk booking ini.',
                ]);

            RefundCreated::dispatch($refund);

            AuditLogger::log(
                action: 'refund_created',
                target: $refund,
                description: 'Refund sebesar Rp '.number_format($amount, 0, ',', '.')." dibuat untuk booking {$booking->booking_code} ({$reason})."
            );

            return $refund;
        });
    }

    /**
     * Admin processes a pending refund and uploads transfer proof.
     */
    public function process(
        Refund $refund,
        User $admin,
        UploadedFile $proofFile,
        ?string $note = null
    ): Refund {
        if (! in_array($refund->status, [RefundStatus::PENDING, RefundStatus::PROCESSING])) {
            throw new InvalidArgumentException('Hanya refund berstatus pending atau processing yang dapat diproses.');
        }

        return DB::transaction(function () use ($refund, $admin, $proofFile, $note) {
            $lockedRefund = Refund::where('id', $refund->id)->lockForUpdate()->first();

            $dir = 'refunds/'.now()->format('Y/m');
            $fileName = "refund_{$lockedRefund->id}_".uniqid().'.'.$proofFile->getClientOriginalExtension();
            $proofPath = $proofFile->storeAs($dir, $fileName, 'local');

            $lockedRefund->status = RefundStatus::COMPLETED;
            $lockedRefund->proof_path = $proofPath;
            $lockedRefund->processed_by = $admin->id;
            $lockedRefund->processed_at = now();
            if ($note) {
                $lockedRefund->note = $note;
            }
            $lockedRefund->save();

            // Put partner payout on hold if already generated
            PartnerPayout::where('booking_id', $lockedRefund->booking_id)
                ->where('status', PayoutStatus::PENDING)
                ->update(['status' => PayoutStatus::ON_HOLD, 'note' => 'Ditahan karena terdapat pengembalian dana (refund) untuk pemesan.']);

            RefundCompleted::dispatch($lockedRefund);

            AuditLogger::log(
                action: 'refund_completed',
                target: $lockedRefund,
                description: 'Refund sebesar Rp '.number_format($lockedRefund->amount, 0, ',', '.')." untuk booking {$lockedRefund->booking->booking_code} berhasil diproses oleh {$admin->name}.",
                userId: $admin->id
            );

            return $lockedRefund;
        });
    }

    /**
     * Admin rejects a pending refund with a required reason.
     */
    public function reject(Refund $refund, User $admin, string $reason): Refund
    {
        if (! in_array($refund->status, [RefundStatus::PENDING, RefundStatus::PROCESSING])) {
            throw new InvalidArgumentException('Hanya refund berstatus pending atau processing yang dapat ditolak.');
        }

        return DB::transaction(function () use ($refund, $admin, $reason) {
            $lockedRefund = Refund::where('id', $refund->id)->lockForUpdate()->first();
            $lockedRefund->status = RefundStatus::REJECTED;
            $lockedRefund->processed_by = $admin->id;
            $lockedRefund->processed_at = now();
            $lockedRefund->note = $reason;
            $lockedRefund->save();

            AuditLogger::log(
                action: 'refund_rejected',
                target: $lockedRefund,
                description: "Refund booking {$lockedRefund->booking->booking_code} ditolak oleh {$admin->name}. Alasan: {$reason}",
                userId: $admin->id
            );

            return $lockedRefund;
        });
    }
}
