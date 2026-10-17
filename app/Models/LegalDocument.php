<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A page the admin can edit: privacy policy, host terms, terms of use. Every saved change raises the version. */
class LegalDocument extends Model
{
    /** What an owner agrees to in the property wizard */
    public const FOR_HOSTS = ['privacy-policy', 'host-terms'];

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Sample text still in place: it starts with the "[DEMO TEXT" line the seed puts there */
    public function isSample(): bool
    {
        return str_contains((string) $this->body, '[DEMO TEXT');
    }
}
