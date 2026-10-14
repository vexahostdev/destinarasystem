<?php

namespace App\Domain\Finance\Services;

use App\Domain\Audit\AuditLogger;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Enums\RefundStatus;
use App\Models\Booking;
use App\Models\FinancialReconciliation;
use App\Models\PartnerPayout;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReconciliationService
{
    /**
     * Calculate period reconciliation strictly from real database transactions (SPEC §6.5).
     */
    public function calculatePeriod(string $period): FinancialReconciliation
    {
        $start = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $end = Carbon::createFromFormat('Y-m', $period)->endOfMonth();

        return DB::transaction(function () use ($period, $start, $end) {
            $existing = FinancialReconciliation::where('period', $period)->first();

            // Total uang masuk (payments with status 'paid' within period)
            $totalPaidIn = (int) Payment::where('status', PaymentStatus::PAID)
                ->whereBetween('paid_at', [$start, $end])
                ->sum('amount');

            // Total refund (refunds completed within period)
            $totalRefunded = (int) Refund::where('status', RefundStatus::COMPLETED)
                ->whereBetween('processed_at', [$start, $end])
                ->sum('amount');

            // Breakdown from bookings paid in this period
            $paidBookingIds = Payment::where('status', PaymentStatus::PAID)
                ->whereBetween('paid_at', [$start, $end])
                ->pluck('booking_id')
                ->unique();

            $bookings = Booking::whereIn('id', $paidBookingIds)->get();

            $totalPlatformFee = (int) $bookings->sum('platform_fee_amount');
            $totalInsurance = (int) $bookings->sum('insurance_amount');
            $totalCommission = (int) $bookings->sum('commission_amount');
            $totalDiscount = (int) $bookings->sum('discount_amount');

            // Payouts transferred in this period
            $totalPayoutTransferred = (int) PartnerPayout::where('status', PayoutStatus::TRANSFERRED)
                ->whereBetween('transferred_at', [$start, $end])
                ->sum('net_amount');

            // Payouts currently pending created in this period
            $totalPayoutPending = (int) PartnerPayout::where('status', PayoutStatus::PENDING)
                ->whereBetween('created_at', [$start, $end])
                ->sum('net_amount');

            // Net platform revenue = Komisi + Biaya Platform - Diskon Voucher (SPEC §6.5 & §8.2)
            $netPlatformRevenue = (int) ($totalCommission + $totalPlatformFee - $totalDiscount);

            $attributes = [
                'total_paid_in' => $totalPaidIn,
                'total_refunded' => $totalRefunded,
                'total_platform_fee' => $totalPlatformFee,
                'total_insurance' => $totalInsurance,
                'total_commission' => $totalCommission,
                'total_discount' => $totalDiscount,
                'total_payout_transferred' => $totalPayoutTransferred,
                'total_payout_pending' => $totalPayoutPending,
                'net_platform_revenue' => $netPlatformRevenue,
            ];

            if ($existing) {
                // If closed, do not overwrite closed_by or status
                if ($existing->status !== 'closed') {
                    $existing->update($attributes);
                }

                return $existing;
            }

            return FinancialReconciliation::create(array_merge($attributes, [
                'period' => $period,
                'status' => 'open',
            ]));
        });
    }

    /**
     * Close books for a given reconciliation period (Admin only).
     */
    public function closeBook(string $period, User $admin, ?string $notes = null): FinancialReconciliation
    {
        return DB::transaction(function () use ($period, $admin, $notes) {
            $recon = $this->calculatePeriod($period);

            if ($recon->status === 'closed') {
                throw new InvalidArgumentException("Periode rekonsiliasi {$period} sudah ditutup buku sebelumnya.");
            }

            $recon->status = 'closed';
            $recon->closed_by = $admin->id;
            $recon->closed_at = now();
            if ($notes) {
                $recon->notes = $notes;
            }
            $recon->save();

            AuditLogger::log(
                action: 'reconciliation_closed',
                target: $recon,
                description: "Tutup buku periode {$period} berhasil dilakukan oleh {$admin->name}. Pendapatan bersih: Rp ".number_format($recon->net_platform_revenue, 0, ',', '.'),
                userId: $admin->id
            );

            return $recon;
        });
    }

    /**
     * Export reconciliation summary and ledger lines to CSV string.
     */
    public function exportCsv(string $period): string
    {
        $recon = $this->calculatePeriod($period);
        $start = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $end = Carbon::createFromFormat('Y-m', $period)->endOfMonth();

        $output = fopen('php://temp', 'r+');

        // Header Summary
        fputcsv($output, ['REKONSILIASI KEUANGAN DESTINARA']);
        fputcsv($output, ['Periode', $period]);
        fputcsv($output, ['Status', strtoupper($recon->status)]);
        fputcsv($output, ['Total Uang Masuk (Paid In)', $recon->total_paid_in]);
        fputcsv($output, ['Total Pengembalian (Refunded)', $recon->total_refunded]);
        fputcsv($output, ['Total Komisi Platform', $recon->total_commission]);
        fputcsv($output, ['Total Biaya Layanan', $recon->total_platform_fee]);
        fputcsv($output, ['Total Asuransi', $recon->total_insurance]);
        fputcsv($output, ['Total Diskon Voucher', $recon->total_discount]);
        fputcsv($output, ['Total Payout Ditransfer', $recon->total_payout_transferred]);
        fputcsv($output, ['Total Payout Pending', $recon->total_payout_pending]);
        fputcsv($output, ['Pendapatan Bersih Platform', $recon->net_platform_revenue]);
        fputcsv($output, []); // empty line

        // Detail Transactions
        fputcsv($output, ['TANGGAL', 'TIPE', 'REFERENSI', 'METODE / BANK', 'NOMINAL (IDR)', 'STATUS']);

        $payments = Payment::where('status', PaymentStatus::PAID)
            ->whereBetween('paid_at', [$start, $end])
            ->get();

        foreach ($payments as $p) {
            fputcsv($output, [
                $p->paid_at?->format('Y-m-d H:i:s'),
                'PEMBAYARAN_MASUK',
                $p->external_id,
                $p->payment_channel ?? $p->gateway,
                $p->amount,
                $p->status->value,
            ]);
        }

        $refunds = Refund::where('status', RefundStatus::COMPLETED)
            ->whereBetween('processed_at', [$start, $end])
            ->get();

        foreach ($refunds as $r) {
            fputcsv($output, [
                $r->processed_at?->format('Y-m-d H:i:s'),
                'REFUND_KELUAR',
                "REF-{$r->id}-{$r->booking->booking_code}",
                $r->buyer_account_number ?? 'Transfer Manual',
                -$r->amount,
                $r->status->value,
            ]);
        }

        $payouts = PartnerPayout::where('status', PayoutStatus::TRANSFERRED)
            ->whereBetween('transferred_at', [$start, $end])
            ->get();

        foreach ($payouts as $po) {
            fputcsv($output, [
                $po->transferred_at?->format('Y-m-d H:i:s'),
                'PAYOUT_MITRA',
                $po->payout_number,
                "{$po->bank_name} {$po->bank_account_number}",
                -$po->net_amount,
                $po->status->value,
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
