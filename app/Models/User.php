<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens, HasPushSubscriptions;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'profile_photo',
        'password',
        'user_type',
    ];

    protected $appends = ['is_admin', 'is_merchant', 'associated_property'];

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
