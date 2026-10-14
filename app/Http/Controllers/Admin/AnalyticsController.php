<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Institution;
use App\Models\PartnerProfile;
use App\Models\Province;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->query('start_date'))->startOfDay()
            : now()->subDays(30)->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->query('end_date'))->endOfDay()
            : now()->endOfDay();

        // 1. Funnel
        $bookingBase = Booking::whereBetween('created_at', [$startDate, $endDate]);
        $totalCreated = (clone $bookingBase)->count();
        $totalPaid = (clone $bookingBase)->whereIn('status', [
            BookingStatus::DIBAYAR,
            BookingStatus::DIKONFIRMASI,
            BookingStatus::BERLANGSUNG,
            BookingStatus::SELESAI,
        ])->count();
        $totalConfirmed = (clone $bookingBase)->whereIn('status', [
            BookingStatus::DIKONFIRMASI,
            BookingStatus::BERLANGSUNG,
            BookingStatus::SELESAI,
        ])->count();
        $totalCompleted = (clone $bookingBase)->where('status', BookingStatus::SELESAI)->count();
        $totalExpired = (clone $bookingBase)->where('status', BookingStatus::KEDALUWARSA)->count();
        $totalCancelled = (clone $bookingBase)->where('status', BookingStatus::BATAL)->count();

        $funnel = [
            'created' => $totalCreated,
            'paid' => $totalPaid,
            'confirmed' => $totalConfirmed,
            'completed' => $totalCompleted,
            'expired' => $totalExpired,
            'cancelled' => $totalCancelled,
        ];

        // 2. GMV & Revenue
        $successfulBookings = Booking::whereIn('status', [
            BookingStatus::DIBAYAR,
            BookingStatus::DIKONFIRMASI,
            BookingStatus::BERLANGSUNG,
            BookingStatus::SELESAI,
        ])->whereBetween('created_at', [$startDate, $endDate]);

        $gmv = (int) (clone $successfulBookings)->sum('total_amount');
        $platformRevenue = (int) (clone $successfulBookings)->sum('commission_amount');

        // Monthly trends (past 6 months)
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $monthExpr = $isSqlite ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";

        $monthlyTrends = Booking::select(
            DB::raw("{$monthExpr} as month"),
            DB::raw("SUM(CASE WHEN status IN ('dibayar', 'dikonfirmasi', 'berlangsung', 'selesai') THEN total_amount ELSE 0 END) as monthly_gmv"),
            DB::raw("SUM(CASE WHEN status IN ('dibayar', 'dikonfirmasi', 'berlangsung', 'selesai') THEN commission_amount ELSE 0 END) as monthly_rev"),
            DB::raw("COUNT(*) as total_bookings")
        )
            ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        // 3. Top Destinations
        $topDestinations = Destination::select('destinations.id', 'destinations.name', 'destinations.city')
            ->withCount(['bookings as completed_bookings' => function ($q) {
                $q->where('status', BookingStatus::SELESAI);
            }])
            ->withSum(['bookings as total_gmv' => function ($q) {
                $q->whereIn('status', [BookingStatus::DIBAYAR, BookingStatus::DIKONFIRMASI, BookingStatus::SELESAI]);
            }], 'total_amount')
            ->orderByDesc('completed_bookings')
            ->take(5)
            ->get();

        // 4. Average Time from Booking to Paid (in minutes)
        $paidBookings = Booking::whereNotNull('paid_at')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get(['created_at', 'paid_at']);

        $avgPayMinutes = 0;
        if ($paidBookings->count() > 0) {
            $totalMinutes = $paidBookings->sum(function ($b) {
                return $b->created_at->diffInMinutes($b->paid_at);
            });
            $avgPayMinutes = round($totalMinutes / $paidBookings->count());
        }

        // 5. Repeat Order Rate
        $institutionsWithBookings = Booking::whereIn('status', [BookingStatus::DIKONFIRMASI, BookingStatus::SELESAI])
            ->select('institution_id', DB::raw('count(*) as count'))
            ->groupBy('institution_id')
            ->get();

        $repeatCount = $institutionsWithBookings->where('count', '>', 1)->count();
        $totalInstitutionsActive = $institutionsWithBookings->count();
        $repeatOrderRate = $totalInstitutionsActive > 0 ? round(($repeatCount / $totalInstitutionsActive) * 100, 1) : 0;

        // 6. Churn (No bookings in last 6 months)
        $sixMonthsAgo = now()->subMonths(6);
        $churnInstitutionsCount = Institution::whereDoesntHave('bookings', function ($q) use ($sixMonthsAgo) {
            $q->where('created_at', '>=', $sixMonthsAgo);
        })->count();

        // 7. Province distribution
        $provinceStats = Province::withCount(['destinations as destination_count'])
            ->has('destinations')
            ->orderByDesc('destination_count')
            ->take(8)
            ->get();

        return view('admin.analytics.index', compact(
            'startDate',
            'endDate',
            'funnel',
            'gmv',
            'platformRevenue',
            'monthlyTrends',
            'topDestinations',
            'avgPayMinutes',
            'repeatOrderRate',
            'churnInstitutionsCount',
            'provinceStats'
        ));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->query('start_date'))->startOfDay()
            : now()->subDays(30)->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->query('end_date'))->endOfDay()
            : now()->endOfDay();

        $bookings = Booking::with(['destination', 'user.institution'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest('id')
            ->get();

        $filename = 'destinara-analytics-' . now()->format('YmdHis') . '.csv';

        return response()->streamDownload(function () use ($bookings) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Kode Booking',
                'Tanggal Dibuat',
                'Institusi / Pemesan',
                'Destinasi',
                'Paket',
                'Pax',
                'Total Nominal (IDR)',
                'Komisi Platform (IDR)',
                'Hak Mitra (IDR)',
                'Status',
                'Waktu Bayar',
            ]);

            foreach ($bookings as $b) {
                fputcsv($handle, [
                    $b->booking_code,
                    $b->created_at->format('Y-m-d H:i:s'),
                    $b->user?->institution?->institution_name ?? $b->user?->name,
                    $b->destination?->name,
                    $b->package?->name,
                    $b->participant_count,
                    $b->total_amount,
                    $b->commission_amount,
                    $b->partner_net_amount,
                    $b->status->value ?? $b->status,
                    $b->paid_at ? $b->paid_at->format('Y-m-d H:i:s') : '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
