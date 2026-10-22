<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    // the saved ID (NID or passport) is encrypted at rest and never leaves the server inside a normal answer: the guest sees it masked
    protected $casts = ['nid' => 'encrypted', 'passport' => 'encrypted'];

    protected $hidden = ['nid', 'passport'];

    protected $appends = ['nid_masked', 'passport_masked'];

    public function getNidMaskedAttribute(): ?string
    {
        $n = $this->attributes['nid'] ?? null ? $this->nid : null;

        return $n ? \App\Services\Booking\GuestIdentity::mask($n) : null;
    }

    public function getPassportMaskedAttribute(): ?string
    {
        $n = $this->attributes['passport'] ?? null ? $this->passport : null;

        return $n ? \App\Services\Booking\GuestIdentity::mask($n) : null;
    }

    /*----------------------------------------
    Relations
    ----------------------------------------*/

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
