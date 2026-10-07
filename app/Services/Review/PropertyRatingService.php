<?php

namespace App\Services\Review;

use App\Models\Property;
use App\Models\ReviewSubmission;

class PropertyRatingService
{
    /**
     * Recompute reviews_avg_rating / reviews_count from this property's
     * published submissions. Deliberately separate from the manually
     * editable `rating` column (Property Settings) — never touches it.
     */
    public function recalculate(Property $property): void
    {
        $query = ReviewSubmission::query()
            ->where('status', ReviewSubmission::STATUS_PUBLISHED)
            ->whereHas('booking.room', fn($q) => $q->where('property_id', $property->id));

        $avg = (clone $query)->avg('overall_rating');

        $property->update([
            'reviews_avg_rating' => $avg !== null ? number_format((float) $avg, 1, '.', '') : null,
            'reviews_count' => (clone $query)->count(),
        ]);
    }
}
