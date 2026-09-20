<?php

namespace App\Services\Chat;

use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Database\Query\Builder;

class ChatAccessService
{
    public function canAccess(User $user, ChatConversation $conversation): bool
    {
        if ($user->is_admin || $conversation->customer_id === $user->id) {
            return true;
        }

        return $conversation->property()->where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhereHas('staffs', fn ($staffs) => $staffs
                    ->where('users.id', $user->id)
                    ->where('property_user.is_active', true));
        })->exists();
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->is_admin) {
            return $query;
        }

        return $query->where(function ($visible) use ($user) {
            $visible->where('customer_id', $user->id)
                ->orWhereHas('property', function ($property) use ($user) {
                    $property->where('user_id', $user->id)
                        ->orWhereHas('staffs', fn ($staffs) => $staffs
                            ->where('users.id', $user->id)
                            ->where('property_user.is_active', true));
                });
        });
    }
}
