<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Finance\Services\PayoutService;
use App\Domain\Finance\Services\ReconciliationService;
use App\Domain\Finance\Services\RefundService;
use App\Domain\Payment\Services\PaymentGatewayManager;
use App\Domain\Payment\Services\PaymentService;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Enums\RefundStatus;
use App\Http\Controllers\Controller;
use App\Models\FinancialReconciliation;
use App\Models\PartnerPayout;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FinanceController extends Controller
{
    /**
     * Log Pembayaran & Gerbang Transaksi (SPEC §9, §14.4)
     */
    public function payments(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $query = Payment::with(['booking.institution', 'booking.user', 'booking.destination']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('external_id', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        $payments = $query->latest()->paginate(15);

        return view('admin.finance.payments', compact('payments'));
    }

    /**
     * Cek Status Transaksi ke Gateway
     */
    public function checkGatewayStatus(Request $request, $id, PaymentGatewayManager $gatewayManager)
    {
        $payment = Payment::findOrFail($id);
        if ($payment->gateway === 'manual_transfer') {
            return back()->with('info', 'Status transfer manual diverifikasi langsung oleh admin platform.');
        }

        try {
            $driver = $gatewayManager->driver($payment->gateway);
            $res = $driver->checkStatus($payment->external_id);

            return back()->with('info', 'Status gateway terverifikasi: '.json_encode($res));
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal cek status gateway: '.$e->getMessage());
        }
    }

    /**
     * Verifikasi Transfer Manual (SPEC §9.4)
     */
    public function manualPayments(Request $request)
    {
        $status = $request->query('status', 'pending');

        $query = Payment::with(['booking.user.institution', 'booking.destination'])
            ->where('gateway', 'manual_transfer');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $payments = $query->latest()->paginate(15);

        $counts = [
            'pending' => Payment::where('gateway', 'manual_transfer')->where('status', PaymentStatus::PENDING)->count(),
            'paid' => Payment::where('gateway', 'manual_transfer')->where('status', PaymentStatus::PAID)->count(),
            'failed' => Payment::where('gateway', 'manual_transfer')->where('status', PaymentStatus::FAILED)->count(),
        ];

        return view('admin.finance.manual-payments', compact('payments', 'status', 'counts'));
    }

    /**
     * Unduh Berkas Bukti Transfer Manual
     */
    public function downloadProof(Payment $payment)
    {
        if (empty($payment->proof_path)) {
            abort(404, 'Bukti transfer tidak ditemukan.');
        }

        if (! Storage::disk('local')->exists($payment->proof_path)) {
            abort(404, 'Berkas fisik bukti transfer tidak ditemukan di penyimpanan server.');
        }

        return Storage::disk('local')->download(
            $payment->proof_path,
            basename($payment->proof_path)
        );
    }

    /**
     * Konfirmasi Validitas Transfer Manual
     */
    public function verifyManualPayment(Request $request, $id, PaymentService $paymentService)
    {
        $payment = Payment::with('booking')->findOrFail($id);
        $user = Auth::user();

        try {
            $paymentService->verifyManualPayment($payment, $user, true);

            return back()->with('success', "Pembayaran transfer manual {$payment->external_id} berhasil disetujui!");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Tolak Transfer Manual
     */
    public function rejectManualPayment(Request $request, $id, PaymentService $paymentService)
    {
        $payment = Payment::with('booking')->findOrFail($id);
        $user = Auth::user();

        $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        try {
            $paymentService->verifyManualPayment($payment, $user, false, $request->reason);

            return back()->with('warning', "Pembayaran {$payment->external_id} telah ditolak.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Daftar Refund (SPEC §8.6, §9.5)
     */
    public function refunds(Request $request)
    {
        $status = $request->query('status', 'all');

        $query = Refund::with(['booking.institution', 'booking.user', 'buyerBank', 'processedBy']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $refunds = $query->latest()->paginate(15);

        $counts = [
            'all' => Refund::count(),
            'pending' => Refund::where('status', RefundStatus::PENDING)->count(),
            'processing' => Refund::where('status', RefundStatus::PROCESSING)->count(),
            'completed' => Refund::where('status', RefundStatus::COMPLETED)->count(),
            'rejected' => Refund::where('status', RefundStatus::REJECTED)->count(),
        ];

        return view('admin.finance.refunds', compact('refunds', 'status', 'counts'));
    }

    /**
     * Proses Pencairan Refund
     */
    public function processRefund(Request $request, $id, RefundService $refundService)
    {
        $request->validate([
            'proof_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'note' => 'nullable|string|max:500',
        ]);

        $refund = Refund::findOrFail($id);
        $user = Auth::user();

        try {
            $refundService->process($refund, $user, $request->file('proof_file'), $request->note);

            return back()->with('success', "Refund #{$refund->id} berhasil diproses!");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Tolak Permintaan Refund
     */
    public function rejectRefund(Request $request, $id, RefundService $refundService)
    {
        $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        $refund = Refund::findOrFail($id);
        $user = Auth::user();

        try {
            $refundService->reject($refund, $user, $request->reason);

            return back()->with('warning', "Refund #{$refund->id} telah ditolak.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Daftar Payout Mitra (SPEC §8.8)
     */
    public function payouts(Request $request)
    {
        $status = $request->query('status', 'all');

        $query = PartnerPayout::with(['partner.partnerProfile', 'booking.destination']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $payouts = $query->latest()->paginate(15);

        $counts = [
            'all' => PartnerPayout::count(),
            'pending' => PartnerPayout::where('status', PayoutStatus::PENDING)->count(),
            'scheduled' => PartnerPayout::where('status', PayoutStatus::SCHEDULED)->count(),
            'transferred' => PartnerPayout::where('status', PayoutStatus::TRANSFERRED)->count(),
            'on_hold' => PartnerPayout::where('status', PayoutStatus::ON_HOLD)->count(),
        ];

        return view('admin.finance.payouts', compact('payouts', 'status', 'counts'));
    }

    /**
     * Generate Draft Payouts untuk Booking Selesai
     */
    public function generateDraftPayouts(PayoutService $payoutService)
    {
        try {
            $count = $payoutService->generateEligiblePayouts();

            return back()->with('success', "Berhasil membuat {$count} draft payout baru dari pesanan yang telah selesai.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Transfer Payout Mitra dengan Unggah Bukti
     */
    public function transferPayout(Request $request, $id, PayoutService $payoutService)
    {
        $request->validate([
            'proof_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'note' => 'nullable|string|max:500',
        ]);

        $payout = PartnerPayout::findOrFail($id);
        $user = Auth::user();

        try {
            $payoutService->processTransfer($payout, $user, $request->file('proof_file'), $request->note);

            return back()->with('success', "Payout {$payout->payout_number} berhasil dicatat sebagai ditransfer!");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Alias proses payout (single / batch)
     */
    public function processPayout(Request $request, PayoutService $payoutService)
    {
        $request->validate([
            'proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'payout_ids' => 'required|array',
            'payout_ids.*' => 'integer|exists:partner_payouts,id',
            'note' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $processed = 0;

        foreach ($request->payout_ids as $id) {
            $payout = PartnerPayout::find($id);
            if ($payout && $payout->status === PayoutStatus::PENDING) {
                $payoutService->processTransfer($payout, $user, $request->file('proof'), $request->note);
                $processed++;
            }
        }

        return back()->with('success', "Sebanyak {$processed} payout berhasil ditransfer.");
    }

    /**
     * Tahan Penyaluran Payout (On-Hold)
     */
    public function holdPayout(Request $request, $id, PayoutService $payoutService)
    {
        $payout = PartnerPayout::findOrFail($id);
        $reason = $request->input('reason', 'Ditahan oleh bagian keuangan platform');
        $payoutService->hold($payout, Auth::user(), $reason);

        return back()->with('warning', "Payout {$payout->payout_number} telah ditandai on-hold (ditahan).");
    }

    /**
     * Lepas Status On-Hold Payout
     */
    public function releaseHoldPayout(Request $request, $id, PayoutService $payoutService)
    {
        $payout = PartnerPayout::findOrFail($id);
        try {
            $payoutService->releaseHold($payout, Auth::user());

            return back()->with('success', "Penahanan payout {$payout->payout_number} berhasil dilepas dan status kembali menjadi pending.");
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Rekonsiliasi Keuangan (SPEC §6.5)
     */
    public function reconciliation(Request $request, ReconciliationService $reconService)
    {
        $period = $request->query('period', now()->format('Y-m'));

        $recon = $reconService->calculatePeriod($period);
        $allReconciliations = FinancialReconciliation::with('closedBy')->latest('period')->get();

        return view('admin.finance.reconciliation', compact('recon', 'period', 'allReconciliations'));
    }

    /**
     * Tutup Buku Periode Rekonsiliasi
     */
    public function closePeriod(Request $request, ReconciliationService $reconService, $period = null)
    {
        $period = $period ?? $request->input('period', now()->format('Y-m'));
        $user = Auth::user();
        $notes = $request->input('notes');

        try {
            $reconService->closePeriod($period, $user, $notes);

            return back()->with('success', "Periode rekonsiliasi {$period} resmi ditutup buku.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Export CSV Rekonsiliasi
     */
    public function exportCsv(Request $request, ReconciliationService $reconService, $period = null)
    {
        $period = $period ?? $request->query('period', now()->format('Y-m'));
        $csv = $reconService->exportCsv($period);
        $filename = "rekonsiliasi-destinara-{$period}.csv";

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
