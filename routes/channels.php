<?php

use Illuminate\Support\Facades\Broadcast;

// ── Main admin channel ─────────────────────────────────────
// Platform-wide events — only super admins
Broadcast::channel('admin.notifications', function ($user) {
    return (bool) $user->is_admin;
});

// ── Property owner channel ─────────────────────────────────
// Per-property events — property owner OR admin
// NOTE: channel name uses .notifications suffix to avoid
// collision with the old property.{id} channel below
Broadcast::channel('property.{propertyId}.notifications', function ($user, $propertyId) {
    if ((bool) $user->is_admin) return true;

    if (!(bool) $user->is_merchant) return false;

    return $user->associated_property
        && (int) $user->associated_property->id === (int) $propertyId;
});

// ── Legacy property channel — keep for backward compatibility ──
Broadcast::channel('property.{propertyId}', function ($user, $propertyId) {
    return (bool) $user->is_admin
        || ($user->associated_property && $user->associated_property->id == $propertyId);
});

// ── Per-user channel ───────────────────────────────────────
// Bid status updates, booking confirmations — only that user
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
