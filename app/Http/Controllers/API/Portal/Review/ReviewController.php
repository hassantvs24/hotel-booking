<?php

namespace App\Http\Controllers\API\Portal\Review;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\Property;
use App\Models\Review;
use App\Models\ReviewCategory;
use App\Models\ReviewSubmission;
use App\Services\Review\PropertyRatingService;
use App\Services\Review\ReviewEligibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewController extends BaseController
{
    public function __construct(
        private readonly ReviewEligibilityService $eligibilityService,
        private readonly PropertyRatingService $ratingService,
    ) {}

    /**
     * GET /portal/reviews/eligibility/{booking} — can this guest review
     * this stay, and if so, what categories should the form show.
     */
    public function eligibility(Request $request, Booking $booking): JsonResponse
    {
        $this->ensureOwnership($request, $booking);

        $eligibility = $this->eligibilityService->checkEligibility($booking);

        return $this->sendSuccess([
            ...$eligibility,
            'categories' => ReviewCategory::query()->orderBy('id')->get(['id', 'name']),
        ]);
    }

    /**
     * POST /portal/reviews — submit a review for a completed stay.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_number' => ['required', 'integer', 'exists:bookings,booking_number'],
            'positive_comment' => ['nullable', 'string', 'max:2000'],
            'negative_comment' => ['nullable', 'string', 'max:2000'],
            'ratings' => ['required', 'array', 'min:1'],
            'ratings.*.review_category_id' => ['required', 'integer', 'exists:review_categories,id'],
            'ratings.*.rating' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $submission = DB::transaction(function () use ($request, $validated) {
            $booking = Booking::query()
                ->where('booking_number', $validated['booking_number'])
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureOwnership($request, $booking);

            $eligibility = $this->eligibilityService->checkEligibility($booking);

            if (!$eligibility['eligible']) {
                throw ValidationException::withMessages([
                    'booking_number' => $eligibility['reason'],
                ]);
            }

            $overallRating = number_format(collect($validated['ratings'])->avg('rating'), 1, '.', '');

            $submission = ReviewSubmission::create([
                'booking_id' => $booking->id,
                'user_id' => $request->user()->id,
                'positive_comment' => $validated['positive_comment'] ?? null,
                'negative_comment' => $validated['negative_comment'] ?? null,
                'overall_rating' => $overallRating,
                'status' => ReviewSubmission::STATUS_PUBLISHED,
            ]);

            foreach ($validated['ratings'] as $categoryRating) {
                Review::create([
                    'review_submission_id' => $submission->id,
                    'review_category_id' => $categoryRating['review_category_id'],
                    'property_id' => $booking->room->property_id,
                    'user_id' => $request->user()->id,
                    'rating' => $categoryRating['rating'],
                ]);
            }

            return $submission;
        });

        $submission->load('booking.room.property');
        $this->ratingService->recalculate($submission->booking->room->property);

        return $this->sendSuccess(
            $submission->load(['categoryRatings.reviewCategory']),
            'Review submitted. Thank you!',
            201
        );
    }

    /**
     * GET /portal/properties/{property}/reviews — public.
     */
    public function index(Request $request, Property $property): JsonResponse
    {
        $query = ReviewSubmission::query()
            ->where('status', ReviewSubmission::STATUS_PUBLISHED)
            ->whereHas('booking.room', fn($q) => $q->where('property_id', $property->id))
            ->with([
                'user:id,name,profile_photo',
                'booking:id,checkin,checkout,adult,children',
                'categoryRatings.reviewCategory:id,name',
            ]);

        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'highest' => $query->orderByDesc('overall_rating'),
            'lowest' => $query->orderBy('overall_rating'),
            default => $query->latest(),
        };

        if ($minRating = $request->input('min_rating')) {
            $query->where('overall_rating', '>=', (float) $minRating);
        }

        $reviews = $query->paginate(min(50, max(1, (int) $request->input('per_page', 10))));

        $categoryBreakdown = Review::query()
            ->whereHas('reviewSubmission', fn($q) => $q
                ->where('status', ReviewSubmission::STATUS_PUBLISHED)
                ->whereHas('booking.room', fn($qq) => $qq->where('property_id', $property->id))
            )
            ->join('review_categories', 'review_categories.id', '=', 'reviews.review_category_id')
            ->selectRaw('review_categories.id, review_categories.name, AVG(reviews.rating) as avg_rating')
            ->groupBy('review_categories.id', 'review_categories.name')
            ->orderBy('review_categories.id')
            ->get();

        return $this->sendSuccess([
            'reviews' => $reviews,
            'summary' => [
                'avg_rating' => $property->reviews_avg_rating,
                'count' => $property->reviews_count,
                'category_breakdown' => $categoryBreakdown,
            ],
        ]);
    }

    /**
     * GET /portal/reviews/my-reviews
     */
    public function myReviews(Request $request): JsonResponse
    {
        $reviews = ReviewSubmission::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'booking.room.property:id,name',
                'categoryRatings.reviewCategory:id,name',
            ])
            ->latest()
            ->paginate(min(50, max(1, (int) $request->input('per_page', 10))));

        return $this->sendSuccess($reviews);
    }

    private function ensureOwnership(Request $request, Booking $booking): void
    {
        abort_unless(
            $booking->user_id === $request->user()->id,
            403,
            'You do not own this booking.'
        );
    }
}
