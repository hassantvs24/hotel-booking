<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewSubmission extends Model
{
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_HIDDEN = 'hidden';

    protected $casts = [
        'overall_rating' => 'decimal:1',
        'admin_replied_at' => 'datetime',
    ];

    public function booking() : BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function adminReplier() : BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_replied_by')->withTrashed();
    }

    public function categoryRatings() : HasMany
    {
        return $this->hasMany(Review::class);
    }
}
