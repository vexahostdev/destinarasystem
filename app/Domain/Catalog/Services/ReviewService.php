<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Audit\AuditLogger;
use App\Domain\Notification\Services\NotificationDispatcher;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReviewService
{
    public function __construct(
        protected NotificationDispatcher $notifier
    ) {}

    /**
     * Submit review by buyer for a completed booking (SPEC §8.7).
     */
    public function createReview(
        Booking $booking,
        User $buyer,
        int $rating,
        ?string $comment = null
    ): Review {
        if ($booking->status !== BookingStatus::SELESAI) {
            throw new InvalidArgumentException('Ulasan hanya dapat diberikan untuk pesanan yang telah selesai.');
        }

        if ($booking->user_id !== $buyer->id) {
            throw new InvalidArgumentException('Anda tidak berhak memberikan ulasan untuk pesanan ini.');
        }

        if (Review::where('booking_id', $booking->id)->exists()) {
            throw new InvalidArgumentException('Ulasan untuk pesanan ini sudah pernah dikirimkan.');
        }

        if ($booking->completed_at && $booking->completed_at->diffInDays(now()) > 30) {
            throw new InvalidArgumentException('Batas waktu pemberian ulasan adalah maksimal 30 hari setelah kunjungan selesai.');
        }

        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Rating harus berada di antara angka 1 sampai 5.');
        }

        return DB::transaction(function () use ($booking, $buyer, $rating, $comment) {
            $review = Review::create([
                'booking_id' => $booking->id,
                'destination_id' => $booking->destination_id,
                'institution_id' => $booking->institution_id,
                'rating' => $rating,
                'comment' => $comment,
                'status' => 'published',
            ]);

            // Recalculate destination aggregate ratings
            $this->recalculateDestinationRating($booking->destination);

            // Notify partner
            $this->notifier->send('review.received', [
                'booking' => $booking,
                'destination_name' => $booking->destination->name,
                'rating' => $rating,
                'comment' => $comment,
            ], ['partner']);

            AuditLogger::log(
                action: 'review_created',
                target: $review,
                description: "Ulasan bintang {$rating} diberikan oleh {$buyer->name} untuk destinasi {$booking->destination->name}.",
                userId: $buyer->id
            );

            return $review;
        });
    }

    /**
     * Partner replies to a published review once (SPEC §8.7).
     */
    public function reply(Review $review, User $partner, string $replyText): Review
    {
        if ($review->destination->partner_id !== $partner->id && ! $partner->hasRole('super_admin')) {
            throw new InvalidArgumentException('Anda bukan mitra pengelola destinasi dari ulasan ini.');
        }

        if (! empty($review->partner_reply)) {
            throw new InvalidArgumentException('Mitra hanya dapat membalas ulasan satu kali.');
        }

        $review->partner_reply = $replyText;
        $review->replied_at = now();
        $review->save();

        AuditLogger::log(
            action: 'review_replied',
            target: $review,
            description: "Balasan ulasan diberikan oleh mitra {$partner->name} untuk ulasan #{$review->id}.",
            userId: $partner->id
        );

        return $review;
    }

    /**
     * Admin moderates review visibility (publish or hide) (SPEC §8.7).
     */
    public function moderate(Review $review, User $admin, string $status): Review
    {
        if (! in_array($status, ['published', 'hidden', 'pending'])) {
            throw new InvalidArgumentException("Status moderasi [{$status}] tidak valid.");
        }

        $review->status = $status;
        $review->moderated_by = $admin->id;
        $review->save();

        // Recalculate aggregate ratings
        $this->recalculateDestinationRating($review->destination);

        AuditLogger::log(
            action: 'review_moderated',
            target: $review,
            description: "Status ulasan #{$review->id} dimoderasi menjadi [{$status}] oleh admin {$admin->name}.",
            userId: $admin->id
        );

        return $review;
    }

    /**
     * Strictly recalculate destination rating_avg and rating_count from published reviews.
     */
    public function recalculateDestinationRating(Destination $destination): void
    {
        $publishedReviews = Review::where('destination_id', $destination->id)
            ->where('status', 'published');

        $count = $publishedReviews->count();
        $avg = $count > 0 ? (float) $publishedReviews->avg('rating') : 0.00;

        $destination->rating_count = $count;
        $destination->rating_avg = round($avg, 2);
        $destination->save();
    }
}
