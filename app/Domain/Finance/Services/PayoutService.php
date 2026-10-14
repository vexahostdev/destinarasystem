<?php

namespace App\Domain\Finance\Services;

use App\Domain\Audit\AuditLogger;
use App\Domain\Document\Services\DocumentNumberGenerator;
use App\Enums\BookingStatus;
use App\Enums\PayoutStatus;
use App\Enums\RefundStatus;
use App\Events\PayoutCreated;
use App\Events\PayoutTransferred;
use App\Models\Booking;
use App\Models\PartnerPayout;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PayoutService
{
    public function __construct(
        protected DocumentNumberGenerator $numberGenerator
    ) {}

    /**
     * Generate payouts for completed bookings that have passed eligibility delay (SPEC §8.8).
     */
    public function generateEligiblePayouts(): int
    {
        $delayDays = (int) setting('payout_delay_days', 3);
        $threshold = now()->subDays($delayDays);

        $eligibleBookings = Booking::where('status', BookingStatus::SELESAI)
            ->whereNotNull('completed_at')
            ->where('completed_at', '<=', $threshold)
            ->whereDoesntHave('partnerPayout')
            ->whereDoesntHave('refunds', function ($q) {
                $q->whereIn('status', [RefundStatus::PENDING, RefundStatus::PROCESSING, RefundStatus::COMPLETED]);
            })
            ->get();

        $count = 0;
        foreach ($eligibleBookings as $booking) {
            DB::transaction(function () use ($booking, &$count) {
                $partner = $booking->destination->partner;
                $profile = $partner?->partnerProfile;

                $payoutNumber = $this->numberGenerator->generate('payout');

                $bankName = $profile?->bank?->name ?? $profile?->bank_name;
                $bankAccountNumber = $profile?->bank_account_number;
                $bankAccountHolder = $profile?->bank_account_holder;

                $hasValidBank = ! empty($bankName) && ! empty($bankAccountNumber) && ! empty($bankAccountHolder);
                $initialStatus = $hasValidBank ? PayoutStatus::PENDING : PayoutStatus::ON_HOLD;
                $initialNote = $hasValidBank
                    ? "Pencairan dana untuk booking {$booking->booking_code}"
                    : 'Ditahan otomatis: rekening bank mitra belum dilengkapi di profil.';

                $payout = PartnerPayout::create([
                    'payout_number' => $payoutNumber,
                    'partner_id' => $partner->id,
                    'booking_id' => $booking->id,
                    'gross_amount' => $booking->subtotal_amount,
                    'commission_amount' => $booking->commission_amount,
                    'net_amount' => $booking->partner_net_amount,
                    'status' => $initialStatus,
                    'eligible_at' => now(),
                    'bank_name' => $bankName,
                    'bank_account_number' => $bankAccountNumber,
                    'bank_account_holder' => $bankAccountHolder,
                    'note' => $initialNote,
                ]);

                PayoutCreated::dispatch($payout);

                $count++;
            });
        }

        return $count;
    }

    /**
     * Admin processes payout transfer with proof of transfer.
     */
    public function processTransfer(
        PartnerPayout $payout,
        User $admin,
        UploadedFile $proofFile,
        ?string $note = null
    ): PartnerPayout {
        if (! in_array($payout->status, [PayoutStatus::PENDING, PayoutStatus::SCHEDULED])) {
            throw new InvalidArgumentException('Hanya payout berstatus pending atau scheduled yang dapat ditransfer.');
        }

        return DB::transaction(function () use ($payout, $admin, $proofFile, $note) {
            $lockedPayout = PartnerPayout::where('id', $payout->id)->lockForUpdate()->first();

            $dir = 'payouts/'.now()->format('Y/m');
            $fileName = "payout_{$lockedPayout->payout_number}_".uniqid().'.'.$proofFile->getClientOriginalExtension();
            $proofPath = $proofFile->storeAs($dir, str_replace('/', '_', $fileName), 'local');

            $lockedPayout->status = PayoutStatus::TRANSFERRED;
            $lockedPayout->transfer_proof_path = $proofPath;
            $lockedPayout->transferred_at = now();
            $lockedPayout->processed_by = $admin->id;
            if ($note) {
                $lockedPayout->note = $note;
            }
            $lockedPayout->save();

            PayoutTransferred::dispatch($lockedPayout, $admin);

            AuditLogger::log(
                action: 'payout_transferred',
                target: $lockedPayout,
                description: "Dana kemitraan {$lockedPayout->payout_number} sebesar Rp ".number_format($lockedPayout->net_amount, 0, ',', '.')." berhasil ditransfer ke {$lockedPayout->bank_name} ({$lockedPayout->bank_account_number} a.n {$lockedPayout->bank_account_holder}) oleh {$admin->name}.",
                userId: $admin->id
            );

            return $lockedPayout;
        });
    }

    /**
     * Admin processes batch payouts with a single batch transfer proof file.
     */
    public function processBatch(
        array $payoutIds,
        User $admin,
        UploadedFile $proofFile,
        ?string $note = null
    ): int {
        return DB::transaction(function () use ($payoutIds, $admin, $proofFile, $note) {
            $dir = 'payouts/'.now()->format('Y/m');
            $fileName = 'payout_batch_'.uniqid().'.'.$proofFile->getClientOriginalExtension();
            $proofPath = $proofFile->storeAs($dir, $fileName, 'local');

            $payouts = PartnerPayout::whereIn('id', $payoutIds)
                ->whereIn('status', [PayoutStatus::PENDING, PayoutStatus::SCHEDULED])
                ->lockForUpdate()
                ->get();

            $now = now();
            $count = 0;

            foreach ($payouts as $payout) {
                $payout->status = PayoutStatus::TRANSFERRED;
                $payout->transfer_proof_path = $proofPath;
                $payout->transferred_at = $now;
                $payout->processed_by = $admin->id;
                if ($note) {
                    $payout->note = $note;
                }
                $payout->save();

                PayoutTransferred::dispatch($payout, $admin);

                AuditLogger::log(
                    action: 'payout_transferred_batch',
                    target: $payout,
                    description: "Batch transfer {$payout->payout_number} sebesar Rp ".number_format($payout->net_amount, 0, ',', '.')." diproses oleh {$admin->name}.",
                    userId: $admin->id
                );

                $count++;
            }

            return $count;
        });
    }

    /**
     * Put a payout on hold.
     */
    public function hold(PartnerPayout $payout, User $admin, string $reason): PartnerPayout
    {
        $payout->status = PayoutStatus::ON_HOLD;
        $payout->note = $reason;
        $payout->save();

        AuditLogger::log(
            action: 'payout_on_hold',
            target: $payout,
            description: "Payout {$payout->payout_number} ditahan oleh {$admin->name}. Alasan: {$reason}",
            userId: $admin->id
        );

        return $payout;
    }

    /**
     * Admin releases on-hold status once partner updates bank account details.
     */
    public function releaseHold(PartnerPayout $payout, User $admin): PartnerPayout
    {
        if ($payout->status !== PayoutStatus::ON_HOLD) {
            throw new InvalidArgumentException('Hanya payout berstatus on_hold yang dapat dilepas penahanannya.');
        }

        $profile = $payout->partner?->partnerProfile;
        $bankName = $profile?->bank?->name ?? $profile?->bank_name;
        $accountNumber = $profile?->bank_account_number;
        $accountHolder = $profile?->bank_account_holder;

        if (empty($bankName) || empty($accountNumber) || empty($accountHolder)) {
            throw new InvalidArgumentException('Rekening bank mitra belum lengkap di profil.');
        }

        return DB::transaction(function () use ($payout, $admin, $bankName, $accountNumber, $accountHolder) {
            $locked = PartnerPayout::where('id', $payout->id)->lockForUpdate()->first();
            $locked->bank_name = $bankName;
            $locked->bank_account_number = $accountNumber;
            $locked->bank_account_holder = $accountHolder;
            $locked->status = PayoutStatus::PENDING;
            $locked->note = "Penahanan dilepas oleh {$admin->name}. Snapshot rekening bank diperbarui.";
            $locked->save();

            AuditLogger::log(
                action: 'payout_hold_released',
                target: $locked,
                description: "Penahanan payout {$locked->payout_number} dilepas oleh {$admin->name}. Snapshot rekening diperbarui ke {$bankName} ({$accountNumber} a.n {$accountHolder}).",
                userId: $admin->id
            );

            return $locked;
        });
    }
}
