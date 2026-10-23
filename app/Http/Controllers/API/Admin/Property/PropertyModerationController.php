<?php

namespace App\Http\Controllers\API\Admin\Property;

use App\Http\Controllers\BaseController;
use App\Models\Media;
use App\Models\Property;
use App\Models\PropertyReport;
use App\Services\Property\PropertyModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** For the platform admin: what guests reported, and hiding (or showing again) a photo or a whole property. */
class PropertyModerationController extends BaseController
{
    public function __construct(private readonly PropertyModerationService $moderation) {}

    /** GET property-changes/reports?status=open|actioned|dismissed|all */
    public function reports(Request $request): JsonResponse
    {
        $status = $request->input('status', 'open');

        $rows = PropertyReport::with(['property:id,name,user_id,status,hidden_by_admin_at,hidden_reason', 'property.user:id,name', 'user:id,name,email', 'handler:id,name'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(min(max((int) $request->input('per_page', 20), 5), 100));

        return $this->sendSuccess([
            'reports' => $rows->through(function (PropertyReport $r) {
                $photo = $r->media_id ? Media::find($r->media_id) : null;

                return [
                    'id' => $r->id, 'status' => $r->status, 'reason' => $r->reason, 'reason_text' => PropertyReport::REASONS[$r->reason] ?? $r->reason,
                    'details' => $r->details, 'at' => $r->created_at?->toIso8601String(),
                    'property_id' => $r->property_id, 'property' => $r->property?->name, 'owner' => $r->property?->user?->name,
                    'property_hidden' => (bool) $r->property?->hidden_by_admin_at,
                    'reporter' => $r->user?->name, 'reporter_email' => $r->user?->email,
                    'photo' => $photo ? ['id' => $photo->id, 'url' => $photo->url, 'hidden' => $photo->media_role === 'hidden_image'] : null,
                    'admin_note' => $r->admin_note, 'handled_by' => $r->handler?->name, 'handled_at' => $r->handled_at?->toIso8601String(),
                ];
            }),
            'open_count' => PropertyReport::where('status', 'open')->count(),
        ]);
    }

    /** The photos of a property, so the admin can pick the one to hide */
    public function photos(Property $property): JsonResponse
    {
        $all = $property->media()->whereIn('media_role', ['property_image', 'property_gallery_image', 'property_logo_image', 'hidden_image'])->get();

        return $this->sendSuccess(['photos' => $all->map(fn (Media $m) => [
            'id' => $m->id, 'url' => $m->url, 'role' => $m->media_role === 'hidden_image' ? ($m->hidden_from_role ?? 'hidden') : $m->media_role,
            'hidden' => $m->media_role === 'hidden_image', 'hidden_reason' => $m->hidden_reason,
        ])->values()]);
    }

    public function dismiss(Request $request, int $id): JsonResponse
    {
        $this->moderation->dismiss(PropertyReport::findOrFail($id), $request->user(), $request->input('note'));

        return $this->sendSuccess(null, 'Report closed. Nothing was hidden.');
    }

    public function hidePhoto(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['media_id' => ['required', 'integer'], 'reason' => ['required', 'string', 'max:500']], ['reason.required' => 'Write the reason: the owner will read it.']);
        $report = PropertyReport::findOrFail($id);

        // only a photo of the reported property
        $media = Media::where('media_type', Property::class)->where('media_id', $report->property_id)->findOrFail($data['media_id']);

        $this->moderation->hidePhoto($media, $request->user(), $data['reason'], $report);

        return $this->sendSuccess(null, 'The photo is hidden and the owner has been told.');
    }

    public function hideProperty(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']], ['reason.required' => 'Write the reason: the owner will read it.']);
        $report = PropertyReport::findOrFail($id);

        $this->moderation->hideProperty(Property::findOrFail($report->property_id), $request->user(), $data['reason'], $report);

        return $this->sendSuccess(null, 'The property is hidden and the owner has been told.');
    }

    /** POST property-changes/properties/{property}/unhide */
    public function unhideProperty(Request $request, Property $property): JsonResponse
    {
        $this->moderation->unhideProperty($property, $request->user());

        return $this->sendSuccess(null, 'The property is shown again.');
    }

    /** POST property-changes/photos/{media}/unhide */
    public function unhidePhoto(Request $request, int $media): JsonResponse
    {
        $this->moderation->unhidePhoto(Media::where('media_type', Property::class)->findOrFail($media), $request->user());

        return $this->sendSuccess(null, 'The photo is shown again.');
    }
}
