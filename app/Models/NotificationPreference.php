<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** What one person wants from each kind of notification (see App\Services\Notify\NotificationPreferences). */
class NotificationPreference extends Model
{
    protected $guarded = [];

    protected $casts = ['settings' => 'array'];
}
