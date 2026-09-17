<?php

namespace App\Http\Controllers\API\Admin\Review;

use App\Http\Controllers\BaseController;
use App\Models\ReviewSubmission;
use App\Services\Review\PropertyRatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewSubmissionController extends BaseController
{
    public function __construct(
        private readonly PropertyRatingService $ratingService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        $query = ReviewSubmission::query()->with([
            'user:id,name,profile_photo',
            'booking:id,booking_number,room_id,checkin,checkout',
            'booking.room:id,name,property_id',
            'booking.room.property:id,name',
            'categoryRatings.reviewCategory:id,name',
        ]);

        // Merchant scope — only reviews for their properties' bookings
        if ($user->is_merchant && !$user->is_admin) {
            $propertyIds = $user->properties()->pluck('id');
            $query->whereHas('booking.room', fn($q) =>
            $q->whereIn('property_id', $propertyIds)
            );
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn($uq) =>
                $uq->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                )
                    ->orWhereHas('booking.room.property', fn($pq) =>
                    $pq->where('name', 'LIKE', "%{$search}%")
                    );
            });
        }

        $submissions = $query
            ->latest()
            ->paginate(
                (int) $request->input('per_page', 15),
                ['*'],
                'page',
                (int) $request->input('page', 1)
            );

        return $this->sendSuccess(['reviews' => $submissions]);
    }

    public function stats(): JsonResponse
    {
        $user = auth()->user();
        $query = ReviewSubmission::query();

        if ($user->is_merchant && !$user->is_admin) {
            $propertyIds = $user->properties()->pluck('id');
            $query->whereHas('booking.room', fn($q) =>
            $q->whereIn('property_id', $propertyIds)
            );
        }

        return $this->sendSuccess([
            'total' => (clone $query)->count(),
            'published' => (clone $query)->where('status', ReviewSubmission::STATUS_PUBLISHED)->count(),
            'hidden' => (clone $query)->where('status', ReviewSubmission::STATUS_HIDDEN)->count(),
            'avg_rating' => (float) ((clone $query)->where('status', ReviewSubmission::STATUS_PUBLISHED)->avg('overall_rating') ?? 0),
        ]);
    }

    public function show(ReviewSubmission $submission): JsonResponse
    {
        $this->ensureVisible($submission);

        return $this->sendSuccess($submission->load([
            'user:id,name,profile_photo,email',
            'booking.room.property',
            'categoryRatings.reviewCategory',
            'adminReplier:id,name',
        ]));
    }

    public function reply(Request $request, ReviewSubmission $submission): JsonResponse
    {
        $this->ensureVisible($submission);

        $validated = $request->validate([
            'admin_reply' => ['required', 'string', 'max:2000'],
        ]);

        $submission->update([
            'admin_reply' => $validated['admin_reply'],
            'admin_replied_by' => auth()->id(),
            'admin_replied_at' => now(),
        ]);

        return $this->sendSuccess(
            $submission->fresh(['user', 'booking.room.property', 'categoryRatings.reviewCategory', 'adminReplier:id,name']),
            'Reply posted.'
        );
    }

    public function updateStatus(Request $request, ReviewSubmission $submission): JsonResponse
    {
        abort_unless(auth()->user()->is_admin, 403, 'Only an administrator can hide or restore a review.');

        $validated = $request->validate([
            'status' => ['required', 'in:published,hidden'],
        ]);

        $submission->update(['status' => $validated['status']]);

        $this->ratingService->recalculate($submission->booking->room->property);

        return $this->sendSuccess(
            $submission->fresh(['user', 'booking.room.property', 'categoryRatings.reviewCategory']),
            'Review status updated.'
        );
    }

    private function ensureVisible(ReviewSubmission $submission): void
    {
        $user = auth()->user();

        if ($user->is_admin || !$user->is_merchant) {
            return;
        }

        $propertyIds = $user->properties()->pluck('id');
        $propertyId = $submission->booking->room->property_id;

        abort_unless($propertyIds->contains($propertyId), 403, 'This review is not for one of your properties.');
    }
}
