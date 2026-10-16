<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An owner's NID: one per person, kept for every property they list. Files are on the private disk. */
class OwnerIdentity extends Model
{
    protected $casts = [
        'nid_number'  => 'encrypted',
        'verified_at' => 'datetime',
    ];

    // never leaves the server inside a normal JSON response
    protected $hidden = ['nid_number', 'nid_hash', 'nid_front_path', 'nid_back_path'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** "•••••••1234" — what the owner sees back about their own number */
    public function maskedNumber(): string
    {
        $n = (string) $this->nid_number;

        return str_repeat('•', max(0, strlen($n) - 4)) . substr($n, -4);
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }
}
