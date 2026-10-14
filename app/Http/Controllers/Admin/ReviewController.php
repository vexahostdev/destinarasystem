<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Catalog\Services\ReviewService;
use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function __construct(
        protected ReviewService $reviewService
    ) {}

    public function index(Request $request)
    {
        $query = Review::with(['destination', 'booking.user.institution']);

        if ($request->filled('destination_id')) {
            $query->where('destination_id', $request->query('destination_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('rating')) {
            $query->where('rating', $request->query('rating'));
        }

        $reviews = $query->latest('id')->paginate(15)->withQueryString();
        $destinations = Destination::orderBy('name')->get(['id', 'name']);

        return view('admin.reviews.index', compact('reviews', 'destinations'));
    }

    public function moderate(Request $request, Review $review)
    {
        $validated = $request->validate([
            'status' => 'required|in:published,hidden,pending',
        ]);

        $this->reviewService->moderate($review, Auth::user(), $validated['status']);

        $label = match ($validated['status']) {
            'published' => 'diterbitkan',
            'hidden' => 'disembunyikan',
            default => 'ditandai pending',
        };

        return back()->with('success', "Ulasan #{$review->id} berhasil {$label}.");
    }
}
