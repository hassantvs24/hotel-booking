<?php

namespace App\Services\Property;

use App\Models\Media;
use App\Models\Property;
use App\Models\PropertyReport;
use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * What the admin does about a property or a photo a guest has reported: hide one photo, hide the whole property
 * (and show it again), or dismiss the report. The owner is told, with the reason, and every step is written in the change log.
 */
class PropertyModerationService
{
    public function __construct(private readonly PropertyChangeLogger $log) {}

    // ── Photos ──────────────────────────────────────────────

    public function hidePhoto(Media $media, User $admin, string $reason, ?PropertyReport $report = null): void
    {
        $property = $this->ownerOf($media);
        $reason = $this->reason($reason);

        DB::transaction(function () use ($media, $property, $admin, $reason, $report) {
            if ($media->media_role === 'hidden_image') {
                return;
            }

            $from = $media->media_role;

            $media->update(['media_role' => 'hidden_image', 'hidden_at' => now(), 'hidden_reason' => $reason, 'hidden_from_role' => $from]);

            $this->log->record($property, $admin, 'moderation', 'photo_hidden', 'Photo hidden by the team', $media->name, null, 'event', ['media_id' => $media->id, 'reason' => $reason, 'report_id' => $report?->id]);
            $this->tell($property, 'A photo of your property was hidden', '"' . $property->name . '": ' . $reason, 'red');

            $report?->update(['status' => 'actioned', 'handled_by' => $admin->id, 'handled_at' => now(), 'admin_note' => 'Photo hidden: ' . $reason]);
        });
    }

    public function unhidePhoto(Media $media, User $admin): void
    {
        $property = $this->ownerOf($media);

        if ($media->media_role !== 'hidden_image') {
            throw ValidationException::withMessages(['photo' => ['This photo is not hidden.']]);
        }

        DB::transaction(function () use ($media, $property, $admin) {
            $role = $media->hidden_from_role ?: 'property_gallery_image';

            // the main photo or logo it was can be taken by a newer one: then it comes back in the gallery
            if ($role !== 'property_gallery_image' && Media::where('media_type', Property::class)->where('media_id', $property->id)->where('media_role', $role)->exists()) {
                $role = 'property_gallery_image';
            }

            $media->update(['media_role' => $role, 'hidden_at' => null, 'hidden_reason' => null, 'hidden_from_role' => null]);

            $this->log->record($property, $admin, 'moderation', 'photo_unhidden', 'Photo shown again', null, $media->name, 'event', ['media_id' => $media->id]);
            $this->tell($property, 'A photo of your property is shown again', '"' . $property->name . '"', 'green');
        });
    }

    // ── The whole property ──────────────────────────────────

    public function hideProperty(Property $property, User $admin, string $reason, ?PropertyReport $report = null): void
    {
        $reason = $this->reason($reason);

        DB::transaction(function () use ($property, $admin, $reason, $report) {
            $was = $property->status;

            $property->update([
                'status' => $was === Property::STATUS_PENDING ? $was : Property::STATUS_UNPUBLISHED,
                'hidden_by_admin_at' => now(),
                'hidden_reason' => $reason,
            ]);

            $this->log->record($property, $admin, 'moderation', 'property_hidden', 'Property hidden by the team', $was, $property->status, 'event', ['reason' => $reason, 'was' => $was, 'report_id' => $report?->id]);
            $this->tell($property, 'Your property was hidden', '"' . $property->name . '": ' . $reason . ' Contact us to have it shown again.', 'red');

            $report?->update(['status' => 'actioned', 'handled_by' => $admin->id, 'handled_at' => now(), 'admin_note' => 'Property hidden: ' . $reason]);
        });
    }

    public function unhideProperty(Property $property, User $admin): void
    {
        if (!$property->hidden_by_admin_at) {
            throw ValidationException::withMessages(['property' => ['This property is not hidden by the team.']]);
        }

        DB::transaction(function () use ($property, $admin) {
            // it goes back to what it was before it was hidden (live, in nearly every case)
            $was = \App\Models\PropertyChangeLog::where('property_id', $property->id)->where('field', 'property_hidden')->latest('id')->first()?->meta['was'] ?? Property::STATUS_PUBLISHED;

            $property->update(['hidden_by_admin_at' => null, 'hidden_reason' => null, 'status' => $was === Property::STATUS_PENDING ? $was : Property::STATUS_PUBLISHED]);

            $this->log->record($property, $admin, 'moderation', 'property_unhidden', 'Property shown again', null, $property->status, 'event');
            $this->tell($property, 'Your property is shown again', '"' . $property->name . '" is live again.', 'green');
        });
    }

    // ── Reports ─────────────────────────────────────────────

    public function dismiss(PropertyReport $report, User $admin, ?string $note = null): void
    {
        if ($report->status !== 'open') {
            throw ValidationException::withMessages(['report' => ['This report has already been dealt with.']]);
        }

        $report->update(['status' => 'dismissed', 'handled_by' => $admin->id, 'handled_at' => now(), 'admin_note' => $note ? trim($note) : null]);
    }

    // ── Helpers ─────────────────────────────────────────────

    private function reason(string $reason): string
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => ['Write the reason: the owner will read it.']]);
        }

        return mb_substr($reason, 0, 500);
    }

    private function ownerOf(Media $media): Property
    {
        $property = $media->media_type === Property::class ? Property::find($media->media_id) : null;

        if (!$property) {
            throw ValidationException::withMessages(['photo' => ['This photo does not belong to a property.']]);
        }

        return $property;
    }

    private function tell(Property $property, string $title, string $message, string $color): void
    {
        $property->user?->notify(new AdminNotification(
            type: 'property', title: $title, message: $message, icon: 'bx-shield-quarter', color: $color,
            extra: ['property_id' => $property->id, 'always' => true], channel: 'admin',
        ));
    }
}
