<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The trade licence of one property application. The file is on the private disk. */
class PropertyLicense extends Model
{
    protected $casts = [
        'expires_on'  => 'date',
        'verified_at' => 'datetime',
    ];

    protected $hidden = ['file_path'];

    public function propertyRequest(): BelongsTo
    {
        return $this->belongsTo(PropertyRequest::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }
}
