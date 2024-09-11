<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'profile_photo',
        'password',
        'user_type',
    ];

    protected $appends = ['is_admin', 'is_merchant'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


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
    Accessors
    ----------------------------------------*/
    public function getIsAdminAttribute(): bool
    {
        return $this->hasRole(config('site.adminGroup'));
    }

    public function getIsMerchantAttribute(): bool
    {
        return $this->hasRole(config('site.hotelOwnerGroup'));
    }

    /*----------------------------------------
    Relations
    ----------------------------------------*/

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
