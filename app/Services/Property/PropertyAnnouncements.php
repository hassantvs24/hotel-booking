<?php

namespace App\Services\Property;

use App\Models\Property;
use App\Models\PropertyRequest;
use App\Models\User;
use App\Notifications\Property\PropertyPublished;
use App\Notifications\Property\PropertyStatusNotification;
use App\Services\Refund\RefundPolicyService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Tells the owner where their property stands: approved (not public yet), then published (live).
 * Approval and publishing are two moments on purpose: the property stays out of search until the admin publishes it.
 */
class PropertyAnnouncements
{
    /** The bell message for an approved application (the approval email itself is sent with the approval) */
    public function approved(PropertyRequest $request): void
    {
        $this->bell($request->user_id, PropertyStatusNotification::approved($request));
    }

    /** The bell message and the email, with the full details, for a property that has just been published */
    public function published(Property $property): void
    {
        $application = $property->request;
        $owner       = $property->user_id ? User::find($property->user_id) : null;

        $this->bell($property->user_id, PropertyStatusNotification::published($property));

        $recipient = $application?->owner_email ?: $owner?->email;

        if (!$recipient) {
            return;
        }

        PropertyPublished::send($recipient, $this->publishedDetails($property, $application?->name ?: $owner?->name ?: 'there'));
    }

    public function publicUrl(Property $property): string
    {
        return rtrim((string) config('app.frontend_url'), '/') . '/properties/' . $property->id . '/details/' . Str::slug($property->name);
    }

    /** Everything the "your property is live" email shows */
    public function publishedDetails(Property $property, string $ownerName): array
    {
        $property->loadMissing(['facilities', 'rooms', 'primaryImage']);

        $refund = app(RefundPolicyService::class)->summary($property);

        return [
            'owner_name'    => $ownerName,
            'name'          => $property->name,
            'type'          => $property->property_type,
            'address'       => $property->address,
            'phone'         => $property->phone_number,
            'email'         => $property->email,
            'check_in'      => $property->check_in_time ? substr((string) $property->check_in_time, 0, 5) : null,
            'check_out'     => $property->check_out_time ? substr((string) $property->check_out_time, 0, 5) : null,
            'facilities'    => $property->facilities->pluck('name')->filter()->values()->all(),
            'photo_count'   => $property->images()->count() + ($property->primaryImage ? 1 : 0),
            'room_count'    => $property->rooms->count(),
            'refund'        => $refund['headline'] ?? null,
            'is_verified'   => (bool) $property->verified_at,
            'public_url'    => $this->publicUrl($property),
            'dashboard_url' => rtrim((string) config('app.admin_url'), '/') . '/login',
            'support_email' => config('mail.from.address'),
        ];
    }

    private function bell(?int $userId, PropertyStatusNotification $notification): void
    {
        if (!$userId || !($user = User::find($userId))) {
            return;
        }

        try {
            $user->notify($notification);
        } catch (\Throwable $e) {
            Log::warning('Owner property notification failed: ' . $e->getMessage());
        }
    }
}
