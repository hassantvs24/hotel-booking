<?php

namespace App\Http\Controllers\API\Portal\Property;

use App\Http\Controllers\BaseController;
use App\Models\Media;
use App\Models\Property;
use App\Models\PropertyReport;
use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** A guest tells the team that a property is not what it says (wrong photos, misleading information, something else). */
class PropertyReportController extends BaseController
{
    public function store(Request $request, int $property): JsonResponse
    {
        // only a property a guest can see can be reported
        $property = Property::where('status', Property::STATUS_PUBLISHED)->findOrFail($property);

        $data = $request->validate([
            'reason'   => ['required', 'in:' . implode(',', array_keys(PropertyReport::REASONS))],
            'details'  => ['nullable', 'string', 'max:1000', 'required_if:reason,other'],
            'media_id' => ['nullable', 'integer'],
        ], ['details.required_if' => 'Please tell us what is wrong.', 'reason.required' => 'Choose what is wrong.', 'reason.in' => 'Choose what is wrong.']);

        // a photo can only be one of this property's own, still shown
        if (!empty($data['media_id']) && !Media::where('media_type', Property::class)->where('media_id', $property->id)->whereIn('media_role', ['property_image', 'property_gallery_image'])->whereKey($data['media_id'])->exists()) {
            throw ValidationException::withMessages(['media_id' => ['That photo is not on this property.']]);
        }

        // one open report per guest per property: asking again does not pile up
        $open = PropertyReport::where('property_id', $property->id)->where('user_id', $request->user()->id)->where('status', 'open')->exists();

        if ($open) {
            return $this->sendSuccess(null, 'Thank you. We already have your report and the team will look at it.');
        }

        $report = PropertyReport::create([
            'property_id' => $property->id, 'user_id' => $request->user()->id, 'reason' => $data['reason'],
            'details' => isset($data['details']) ? trim($data['details']) : null, 'media_id' => $data['media_id'] ?? null,
        ]);

        User::admins()->each(fn ($admin) => $admin->notify(new AdminNotification(
            type: 'property', title: 'A property was reported', icon: 'bx-flag', color: 'red',
            message: '"' . $property->name . '": ' . (PropertyReport::REASONS[$report->reason] ?? $report->reason),
            extra: ['property_id' => $property->id, 'report_id' => $report->id, 'always' => true], channel: 'admin',
        )));

        return $this->sendSuccess(null, 'Thank you. The team will look at your report.', 201);
    }
}
