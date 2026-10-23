<?php

namespace App\Services\Property;

use App\Models\Property;
use App\Models\PropertyNameRequest;
use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use App\Services\Location\LocationImporter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A property's name is what guests search for and what appears on their bookings, so a new name is not applied at once:
 * the owner asks, the old name stays live, and the admin approves or turns it down (with a reason).
 * The platform admin's own change to a name is applied straight away.
 */
class PropertyNameService
{
    public function __construct(private readonly PropertyChangeLogger $log) {}

    /** Spaces squeezed, text made uniform */
    public static function clean(string $name): string
    {
        return trim((string) LocationImporter::nfc(preg_replace('/\s+/u', ' ', $name)));
    }

    /** Why a name is not acceptable, or null */
    public static function problem(string $name): ?string
    {
        $n = self::clean($name);

        if (mb_strlen($n) < 3) {
            return 'The name needs at least 3 letters.';
        }

        if (mb_strlen($n) > 255) {
            return 'The name is too long (255 letters at most).';
        }

        // six or more digits in a row (spaces, dashes and dots between them do not hide a phone number)
        if (preg_match('/\d(?:[\s().\-]*\d){5,}/u', $n)) {
            return 'The name cannot contain a phone number.';
        }

        if (preg_match('/[<>]/', $n)) {
            return 'The name cannot contain < or >.';
        }

        // all capitals: it has capital letters and no small ones (a name in Bangla has neither, and is fine)
        if (preg_match('/\p{Lu}/u', $n) && !preg_match('/\p{Ll}/u', $n)) {
            return 'Please do not write the whole name in capital letters.';
        }

        return null;
    }

    /**
     * An owner asks for a new name (an admin changes it at once).
     *
     * @return array{applied: bool, request: ?PropertyNameRequest}
     */
    public function submit(Property $property, User $by, string $newName): array
    {
        $new = self::clean($newName);

        if ($why = self::problem($new)) {
            throw ValidationException::withMessages(['basicInfo.propertyName' => [$why]]);
        }

        if ($by->is_admin) {
            $this->apply($property, $new, $by);

            return ['applied' => true, 'request' => null];
        }

        return DB::transaction(function () use ($property, $by, $new) {
            $open = PropertyNameRequest::where('property_id', $property->id)->where('status', 'pending')->lockForUpdate()->first();

            // asking for the name that is already asked for changes nothing
            if ($open && $open->new_name === $new) {
                return ['applied' => false, 'request' => $open];
            }

            // one request at a time: a new wish replaces the one waiting
            $open?->update(['status' => 'cancelled', 'decided_at' => now()]);

            $request = PropertyNameRequest::create([
                'property_id' => $property->id, 'user_id' => $by->id, 'old_name' => $property->name, 'new_name' => $new,
            ]);

            $this->log->record($property, $by, 'basic', 'name_request', 'Name change asked for', $property->name, $new, 'event', ['request_id' => $request->id]);

            User::admins()->each(fn ($admin) => $admin->notify(new AdminNotification(
                type: 'property',
                title: 'Name change to approve',
                message: '"' . $property->name . '" wants to be called "' . $new . '"',
                icon: 'bx-edit',
                color: 'amber',
                extra: ['property_id' => $property->id, 'name_request_id' => $request->id, 'always' => true],
                channel: 'admin',
            )));

            return ['applied' => false, 'request' => $request];
        });
    }

    public function approve(PropertyNameRequest $request, User $admin): PropertyNameRequest
    {
        return DB::transaction(function () use ($request, $admin) {
            $request = PropertyNameRequest::lockForUpdate()->findOrFail($request->id);

            $this->mustBePending($request);

            $property = Property::findOrFail($request->property_id);

            $this->apply($property, $request->new_name, $admin, ['request_id' => $request->id, 'asked_by' => $request->user_id]);
            $request->update(['status' => 'approved', 'decided_by' => $admin->id, 'decided_at' => now()]);

            $this->tell($request, 'Your new name was approved', '"' . $request->old_name . '" is now "' . $request->new_name . '"', 'green');

            return $request;
        });
    }

    public function reject(PropertyNameRequest $request, User $admin, string $reason): PropertyNameRequest
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => ['Write why the name is not accepted: the owner will read it.']]);
        }

        return DB::transaction(function () use ($request, $admin, $reason) {
            $request = PropertyNameRequest::lockForUpdate()->findOrFail($request->id);

            $this->mustBePending($request);

            $request->update(['status' => 'rejected', 'reason' => $reason, 'decided_by' => $admin->id, 'decided_at' => now()]);

            $this->log->record(Property::findOrFail($request->property_id), $admin, 'basic', 'name_request', 'Name change turned down', $request->old_name, $request->new_name, 'event', ['request_id' => $request->id, 'reason' => $reason]);
            $this->tell($request, 'Your new name was not accepted', '"' . $request->new_name . '": ' . $reason, 'red');

            return $request;
        });
    }

    /** The owner takes the request back */
    public function cancel(PropertyNameRequest $request, User $by): PropertyNameRequest
    {
        return DB::transaction(function () use ($request, $by) {
            $request = PropertyNameRequest::lockForUpdate()->findOrFail($request->id);

            $this->mustBePending($request);

            $request->update(['status' => 'cancelled', 'decided_at' => now()]);
            $this->log->record(Property::findOrFail($request->property_id), $by, 'basic', 'name_request', 'Name change taken back', $request->old_name, $request->new_name, 'event', ['request_id' => $request->id]);

            return $request;
        });
    }

    /** The name is changed and written down (this is the one that can be undone from the log) */
    public function apply(Property $property, string $name, User $by, array $meta = []): void
    {
        $old = $property->name;

        if ($old === $name) {
            return;
        }

        $property->update(['name' => $name]);
        $this->log->record($property, $by, 'basic', 'name', 'Property name', $old, $name, 'text', $meta);
    }

    private function mustBePending(PropertyNameRequest $request): void
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages(['request' => ['This request has already been dealt with.']]);
        }
    }

    private function tell(PropertyNameRequest $request, string $title, string $message, string $color): void
    {
        $request->user?->notify(new AdminNotification(
            type: 'property', title: $title, message: $message, icon: 'bx-edit', color: $color,
            extra: ['property_id' => $request->property_id, 'name_request_id' => $request->id, 'always' => true], channel: 'admin',
        ));
    }
}
