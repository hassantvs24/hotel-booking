<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

/** Who agreed to which version of which document, and when */
class LegalConsent extends Model
{
    protected $casts = ['accepted_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /** Record that this owner agreed to the host documents, as they read at this moment (once per submission) */
    public static function recordHostAgreement(User $user, PropertyRequest $application, Request $request): void
    {
        foreach (LegalDocument::whereIn('slug', LegalDocument::FOR_HOSTS)->get() as $doc) {
            static::create([
                'user_id'             => $user->id,
                'property_request_id' => $application->id,
                'document_slug'       => $doc->slug,
                'document_version'    => $doc->version,
                'ip'                  => $request->ip(),
                'user_agent'          => mb_substr((string) $request->userAgent(), 0, 255),
                'accepted_at'         => now(),
            ]);
        }
    }
}
