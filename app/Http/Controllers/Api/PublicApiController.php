<?php

namespace App\Http\Controllers\Api;

use App\Enums\BookingStatus;
use App\Enums\DestinationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PublicCategoryResource;
use App\Http\Resources\Api\PublicDestinationDetailResource;
use App\Http\Resources\Api\PublicDestinationResource;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\DestinationCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class PublicApiController extends Controller
{
    /**
     * Helper to get cache version for invalidation
     */
    protected function cacheVersion(): int
    {
        return (int) Cache::get('public_api_version', 1);
    }

    /**
     * GET /api/v1/public/destinations
     */
    public function destinations(Request $request): AnonymousResourceCollection
    {
        $version = $this->cacheVersion();
        $queryString = http_build_query($request->query());
        $cacheKey = "public_api_v{$version}_destinations_" . md5($queryString);

        $destinations = Cache::remember($cacheKey, 300, function () use ($request) {
            $query = Destination::with(['category', 'province', 'packages'])
                ->published();

            // Filter Kategori
            if ($request->filled('category')) {
                $categorySlug = $request->query('category');
                $query->whereHas('category', function ($q) use ($categorySlug) {
                    $q->where('slug', $categorySlug)->orWhere('id', $categorySlug);
                });
            }

            // Filter Provinsi
            if ($request->filled('province')) {
                $prov = $request->query('province');
                $query->whereHas('province', function ($q) use ($prov) {
                    $q->where('name', 'like', "%{$prov}%")
                      ->orWhere('slug', $prov)
                      ->orWhere('id', $prov);
                });
            }

            // Filter Featured
            if ($request->boolean('featured')) {
                $query->where('is_featured', true);
            }

            // Filter Inquiry Type (dari paket)
            if ($request->filled('inquiry_type')) {
                $inquiry = $request->query('inquiry_type');
                $query->whereHas('packages', function ($q) use ($inquiry) {
                    $q->whereJsonContains('inquiry_types', $inquiry);
                });
            }

            // Pencarian Teks
            if ($request->filled('q')) {
                $q = trim($request->query('q'));
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('city', 'like', "%{$q}%")
                        ->orWhere('short_description', 'like', "%{$q}%");
                });
            }

            // Sorting
            $sort = $request->query('sort', 'latest');
            match ($sort) {
                'price_low' => $query->orderBy('price_per_pax', 'asc'),
                'rating_high' => $query->orderByDesc('rating_avg')->orderByDesc('rating_count'),
                'popular' => $query->withCount(['bookings as completed_count' => function ($b) {
                    $b->where('status', BookingStatus::SELESAI);
                }])->orderByDesc('completed_count'),
                default => $query->latest('published_at')->latest('id'),
            };

            $perPage = min((int) $request->query('per_page', 12), 50);
            if ($perPage <= 0) {
                $perPage = 12;
            }

            return $query->paginate($perPage);
        });

        return PublicDestinationResource::collection($destinations);
    }

    /**
     * GET /api/v1/public/destinations/{slug}
     */
    public function destination(string $slug): PublicDestinationDetailResource
    {
        $version = $this->cacheVersion();
        $cacheKey = "public_api_v{$version}_destination_{$slug}";

        $destination = Cache::remember($cacheKey, 300, function () use ($slug) {
            return Destination::with([
                'category',
                'province',
                'facilities',
                'media',
                'packages',
                'reviews.institution',
            ])
                ->published()
                ->where('slug', $slug)
                ->first();
        });

        if (! $destination) {
            abort(404, 'Destinasi tidak ditemukan atau belum dipublikasikan.');
        }

        return new PublicDestinationDetailResource($destination);
    }

    /**
     * GET /api/v1/public/categories
     */
    public function categories(): AnonymousResourceCollection
    {
        $version = $this->cacheVersion();
        $cacheKey = "public_api_v{$version}_categories";

        $categories = Cache::remember($cacheKey, 300, function () {
            return DestinationCategory::withCount([
                'destinations as published_destinations_count' => function ($q) {
                    $q->where('status', DestinationStatus::PUBLISHED);
                },
            ])
                ->orderBy('name')
                ->get();
        });

        return PublicCategoryResource::collection($categories);
    }

    /**
     * GET /api/v1/public/stats
     */
    public function stats(): JsonResponse
    {
        $version = $this->cacheVersion();
        $cacheKey = "public_api_v{$version}_stats";

        $stats = Cache::remember($cacheKey, 300, function () {
            $publishedCount = Destination::where('status', DestinationStatus::PUBLISHED)->count();

            $provincesCount = Destination::where('status', DestinationStatus::PUBLISHED)
                ->distinct('province_id')
                ->count('province_id');

            $totalPax = (int) Booking::where('status', BookingStatus::SELESAI)
                ->selectRaw('COALESCE(SUM(participant_count + companion_count), 0) as total_pax')
                ->value('total_pax');

            $totalInstitutions = Booking::whereIn('status', [
                BookingStatus::DIKONFIRMASI,
                BookingStatus::BERLANGSUNG,
                BookingStatus::SELESAI,
            ])
                ->distinct('institution_id')
                ->count('institution_id');

            return [
                'published_destinations' => $publishedCount,
                'provinces_count' => $provincesCount,
                'total_pax_facilitated' => $totalPax,
                'total_institutions' => $totalInstitutions,
            ];
        });

        return response()->json([
            'data' => $stats,
        ]);
    }
}
