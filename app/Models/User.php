<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    // SoftDeletes: deleting a user hides the account but keeps their bookings, payments, reviews and agreements.
    // (A real delete would make the database erase all of those, and an owner's properties, with it.)
    use HasFactory, Notifiable, HasRoles, HasApiTokens, HasPushSubscriptions, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'profile_photo',
        'password',
        'user_type',
    ];

    protected $appends = ['is_admin', 'is_merchant', 'associated_property', 'profile_photo_url'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    /*----------------------------------------
    | Permission helper
    ----------------------------------------*/
    public function hasPermission($permission): bool
    {
        return $this->permissions()->where('slug', $permission)
                ->orWhere('name', $permission)->exists() ||
            $this->roles()->whereHas('permissions', function ($query) use ($permission) {
                $query->where('name', $permission);
                $query->orWhere('slug', $permission);
            })->exists();
    }

    /*----------------------------------------
    | Accessors
    ----------------------------------------*/
    /** The address of the profile photo (it is saved as a path on the public disk), or null when there is none */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        $p = $this->attributes['profile_photo'] ?? null;

        if (!$p) {
            return null;
        }

        return str_starts_with($p, 'http') ? $p : asset('storage/' . ltrim($p, '/'));
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->hasRole(config('site.adminGroup'));
    }

    public function getIsMerchantAttribute(): bool
    {
        return $this->hasRole(config('site.hotelOwnerGroup'));
    }

    public function getAssociatedPropertyAttribute()
    {
        return $this->properties()->select('id', 'name', 'address')->first();
    }

    /*----------------------------------------
    | Notification routing
    |
    | Tells Laravel which channel to broadcast
    | database notifications on — must match
    | what Echo listens to in NotificationBell.vue
    ----------------------------------------*/
    public function receivesBroadcastNotificationsOn(): string
    {
        return 'App.Models.User.' . $this->id;
    }

    /**
     * A notification (the bell line, the live message, a push, an email) is only news about the work that sent it.
     * Whatever goes wrong in sending it must never stop that work (a bid, a booking, a payment, a refund, a payout):
     * the problem is written to the log and the work carries on.
     */
    public function notify($instance)
    {
        try {
            app(\Illuminate\Contracts\Notifications\Dispatcher::class)->send($this, $instance);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /*----------------------------------------
    | Static helpers
    ----------------------------------------*/

    /**
     * Get all admin users — use this instead of where('is_admin', true)
     * since is_admin is an accessor, not a real column.
     */
    public static function admins()
    {
        return static::role(config('site.adminGroup'));
    }

    /*----------------------------------------
    | Relations
    ----------------------------------------*/
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function refundRequests(): HasMany
    {
        return $this->hasMany(RefundRequest::class);
    }

    public function processedRefunds(): HasMany
    {
        return $this->hasMany(RefundRequest::class, 'processed_by');
    }

    public function roomRequests(): HasMany
    {
        return $this->hasMany(RoomRequest::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function propertyRequests(): HasMany
    {
        return $this->hasMany(PropertyRequest::class);
    }
}
