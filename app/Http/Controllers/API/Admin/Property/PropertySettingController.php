<?php

namespace App\Http\Controllers\API\Admin\Property;

use App\Http\Controllers\BaseController;
use App\Models\FacilitySub;
use App\Models\Property;
use App\Services\Location\LocationImporter;
use App\Traits\MediaMan;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * The owner's own settings for a property: basic details, SEO and visibility, facilities, house rules and photos.
 * One request carries one section; whatever the section does not mention is left exactly as it was.
 */
class PropertySettingController extends BaseController
{
    use MediaMan;

    public const GALLERY_MAX = 12;

    private const CLASSES = ['Unrated', '1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars', '6 Stars', '7 Stars'];

    public function update(Request $request, Property $property): JsonResponse
    {
        // an owner changes their own property's settings, never another owner's
        abort_unless($request->user()->is_admin || $property->user_id === $request->user()->id, 404);

        // every answer is checked first, so a wrong value is refused with a reason and nothing is half saved
        $basic = $request->has('basicInfo') ? $this->validated($request, 'basicInfo', $this->basicRules(), $request->input('basicInfo.website')) : null;
        $seo   = $basic === null && $request->has('seoInfo') ? $this->validated($request, 'seoInfo', $this->seoRules($request, $property)) : null;
        $rules = $request->has('rules') || $request->boolean('rules_submitted') ? $this->validatedRules($request) : null;
        $facilityIds = $request->has('facility_sub_ids') || $request->boolean('facilities_submitted') ? $this->validatedFacilityIds($request) : null;
        $this->validateFiles($request, $property);

        $gone = [];

        try {
            DB::beginTransaction();

            if ($basic !== null) {
                $this->saveBasic($request, $property, $basic);
            } elseif ($seo !== null) {
                $this->saveSeo($request, $property, $seo);
            }

            if ($request->hasFile('photo')) {
                $gone[] = $this->deletePrimaryImage($property);

                $image = $this->storeFile($request->file('photo'), 'properties');
                $property->primaryImage()->create(array_merge($image, ['media_role' => 'property_image']));
            }

            if ($request->boolean('gallery_submitted') || $request->hasFile('gallery')) {
                $gone = array_merge($gone, $this->saveGallery($request, $property));
            }

            if ($facilityIds !== null) {
                $property->facilities()->sync($facilityIds);
            }

            if ($rules !== null) {
                $this->saveRules($property, $rules);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Property settings not saved: ' . $e->getMessage(), ['property' => $property->id]);

            return $this->sendError('The settings could not be saved. Nothing was changed. Please try again.', [], 500);
        }

        // photos that were replaced or removed leave the disk only once the change is safely saved
        foreach (array_filter($gone) as $name) {
            $this->deleteFile($name, 'properties');
        }

        return $this->sendSuccess(null, 'Property settings updated successfully');
    }

    // ── Checking ────────────────────────────────────────────

    private function validated(Request $request, string $section, array $rules, $website = null): array
    {
        // a website typed as "www.hotel.com" is made a full address
        if ($section === 'basicInfo' && filled($website) && !preg_match('#^https?://#i', $website)) {
            $request->merge(['basicInfo' => array_merge($request->input('basicInfo'), ['website' => 'https://' . $website])]);
        }

        // Bangla and other text typed on a keyboard is made uniform; stray spaces are trimmed
        $clean = array_map(fn ($v) => is_string($v) ? trim((string) LocationImporter::nfc($v)) : $v, (array) $request->input($section));
        $request->merge([$section => array_merge((array) $request->input($section), $clean)]);

        $prefixed = [];
        foreach ($rules as $field => $rule) {
            $prefixed["$section.$field"] = $rule;
        }

        Validator::make($request->all(), $prefixed, [
            'basicInfo.propertyName.required' => 'The property needs a name.',
            'basicInfo.email.email'           => 'That email address does not look right.',
            'basicInfo.website.url'           => 'That website address does not look right.',
            'basicInfo.lat.between'           => 'The latitude must be between -90 and 90.',
            'basicInfo.long.between'          => 'The longitude must be between -180 and 180.',
            'basicInfo.property_class.in'     => 'Choose one of the listed classes.',
            'seoInfo.status.in'               => 'Choose Published or Unpublished.',
        ])->validate();

        return array_intersect_key((array) $request->input($section), $rules);
    }

    private function basicRules(): array
    {
        return [
            'propertyName'         => ['required', 'string', 'max:255'],
            'description'          => ['nullable', 'string', 'max:5000'],
            'address'              => ['nullable', 'string', 'max:500'],
            'zip_code'             => ['nullable', 'string', 'max:20'],
            'lat'                  => ['nullable', 'numeric', 'between:-90,90'],
            'long'                 => ['nullable', 'numeric', 'between:-180,180'],
            'phone_number'         => ['nullable', 'string', 'max:30'],
            'email'                => ['nullable', 'email', 'max:255'],
            'website'              => ['nullable', 'url', 'max:255'],
            'total_room'           => ['nullable', 'integer', 'min:0', 'max:100000'],
            'currency'             => ['nullable', 'string', 'max:10'],
            'rating'               => ['nullable', 'numeric', 'between:0,10'],
            'google_review'        => ['nullable', 'string', 'max:255'],
            'property_class'       => ['required', Rule::in(self::CLASSES)],
            'property_category_id' => ['nullable', 'integer', Rule::exists('property_categories', 'id')->whereNull('deleted_at')],
            'check_in_time'        => ['nullable', 'date_format:H:i,H:i:s'],
            'check_out_time'       => ['nullable', 'date_format:H:i,H:i:s'],
        ];
    }

    private function seoRules(Request $request, Property $property): array
    {
        // an owner can publish or hide a live property; "Pending" is the admin's word for "waiting for approval"
        $allowed = $request->user()->is_admin ? ['Pending', 'Published', 'Unpublished'] : ['Published', 'Unpublished'];

        return [
            'seo_title' => ['nullable', 'string', 'max:120'],
            'seo_meta'  => ['nullable', 'string', 'max:255'],
            'status'    => ['sometimes', 'required', Rule::in($allowed)],
        ];
    }

    private function validatedRules(Request $request): array
    {
        $rows = collect((array) $request->input('rules', []))->map(function ($r) {
            return [
                'property_rule_id' => $r['property_rule_id'] ?? null,
                'description'      => trim((string) ($r['description'] ?? '')),
                'is_active'        => filter_var($r['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
        })->values();

        Validator::make(['rules' => $rows->all()], [
            'rules'                    => ['array', 'max:100'],
            'rules.*.property_rule_id' => ['required', 'integer', 'distinct', Rule::exists('property_rules', 'id')->whereNull('deleted_at')],
            'rules.*.description'      => ['nullable', 'string', 'max:500'],
        ], [
            'rules.*.property_rule_id.required' => 'Choose a rule for every row.',
            'rules.*.property_rule_id.distinct' => 'The same rule is listed twice.',
        ])->validate();

        return $rows->all();
    }

    private function validatedFacilityIds(Request $request): array
    {
        $ids = array_values(array_unique(array_map('intval', array_filter((array) $request->input('facility_sub_ids', []), 'is_numeric'))));

        // nothing that does not exist can be ticked
        if ($ids && FacilitySub::whereIn('id', $ids)->count() !== count($ids)) {
            throw ValidationException::withMessages(['facility_sub_ids' => ['One of the facilities no longer exists. Reload the page and try again.']]);
        }

        return $ids;
    }

    private function validateFiles(Request $request, Property $property): void
    {
        $image = ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];

        Validator::make($request->all(), [
            'photo'             => ['nullable', ...$image],
            'basicInfo.photo'   => ['nullable', ...$image],
            'gallery'           => ['nullable', 'array', 'max:' . self::GALLERY_MAX],
            'gallery.*'         => $image,
            'keep_gallery_ids'  => ['nullable', 'array'],
            'keep_gallery_ids.*' => ['integer'],
        ], [
            'photo.image'         => 'The main photo must be a picture (JPG, PNG or WebP).',
            'photo.max'           => 'The main photo is larger than 5 MB.',
            'gallery.*.image'     => 'Every gallery file must be a picture (JPG, PNG or WebP).',
            'gallery.*.max'       => 'A gallery photo is larger than 5 MB.',
            'basicInfo.photo.image' => 'The logo must be a picture (JPG, PNG or WebP).',
            'basicInfo.photo.max' => 'The logo is larger than 5 MB.',
        ])->validate();

        if ($request->boolean('gallery_submitted') || $request->hasFile('gallery')) {
            $keep = $this->keptGalleryIds($request, $property);
            $total = count($keep) + count((array) $request->file('gallery', []));

            if ($total > self::GALLERY_MAX) {
                throw ValidationException::withMessages(['gallery' => ['A property can have up to ' . self::GALLERY_MAX . " gallery photos. This would make $total."]]);
            }
        }
    }

    // ── Saving ──────────────────────────────────────────────

    private function saveBasic(Request $request, Property $property, array $d): void
    {
        $map = [
            'propertyName' => 'name', 'description' => 'description', 'zip_code' => 'zip_code', 'lat' => 'lat', 'long' => 'long',
            'phone_number' => 'phone_number', 'email' => 'email', 'website' => 'website', 'total_room' => 'total_room',
            'currency' => 'currency', 'rating' => 'rating', 'google_review' => 'google_review', 'property_class' => 'property_class',
            'property_category_id' => 'property_category_id',
        ];

        $update = [];
        foreach ($map as $from => $to) {
            if (array_key_exists($from, $d)) {
                $update[$to] = $d[$from];
            }
        }

        // the times only change when they are sent: leaving them out never wipes them
        foreach (['check_in_time', 'check_out_time'] as $t) {
            if (array_key_exists($t, $d)) {
                $update[$t] = $d[$t] ? Carbon::parse($d[$t])->format('H:i:s') : null;
            }
        }

        // the address is kept in its own shape (street, apartment, city, country): only the street is typed here
        if (array_key_exists('address', $d)) {
            $parts = $property->addressParts();
            $parts['address'] = $d['address'];
            $update['address'] = $parts;
        }

        $property->update($update);

        if ($request->hasFile('basicInfo.photo')) {
            $this->deleteLogoImage($property);

            $image = $this->storeFile($request->file('basicInfo.photo'), 'properties');
            $property->logoImage()->create(array_merge($image, ['media_role' => 'property_logo_image']));
        }
    }

    private function saveSeo(Request $request, Property $property, array $d): void
    {
        $update = [];

        foreach (['seo_title', 'seo_meta'] as $f) {
            if (array_key_exists($f, $d)) {
                $update[$f] = $d[$f];
            }
        }

        // a property still waiting for the admin's approval stays pending, whatever an owner asks for
        if (array_key_exists('status', $d) && ($request->user()->is_admin || $property->status !== Property::STATUS_PENDING)) {
            $update['status'] = $d['status'];
        }

        $property->update($update);
    }

    /** @return array<int,string> file names to delete once the change is saved */
    private function saveGallery(Request $request, Property $property): array
    {
        $gone = [];

        if ($request->boolean('gallery_submitted')) {
            $keep = $this->keptGalleryIds($request, $property);

            foreach ($property->images()->whereNotIn('id', $keep ?: [0])->get() as $image) {
                $gone[] = $image->name;
                $image->delete();
            }
        }

        foreach ((array) $request->file('gallery', []) as $file) {
            $stored = $this->storeFile($file, 'properties');
            $property->images()->create(array_merge($stored, ['media_role' => 'property_gallery_image']));
        }

        return $gone;
    }

    /** The photos of this property that stay (the ones named, and only if they really are this property's) */
    private function keptGalleryIds(Request $request, Property $property): array
    {
        $named = array_map('intval', (array) $request->input('keep_gallery_ids', []));

        return $property->images()->whereIn('id', $named)->pluck('id')->all();
    }

    private function saveRules(Property $property, array $rows): void
    {
        $ids = [];

        foreach ($rows as $r) {
            $ids[] = (int) $r['property_rule_id'];

            $property->rules()->updateOrCreate(
                ['property_rule_id' => $r['property_rule_id']],
                ['rule_description' => $r['description'], 'is_active' => $r['is_active'] ? 1 : 0, 'property_id' => $property->id]
            );
        }

        // a rule taken off the list is removed from the property
        $property->rules()->whereNotIn('property_rule_id', $ids ?: [0])->delete();
    }

    private function deletePrimaryImage(Property $property): ?string
    {
        $image = $property->primaryImage()->first();

        if (!$image) {
            return null;
        }

        $name = $image->name;
        $image->delete();

        return $name;
    }

    private function deleteLogoImage(Property $property): void
    {
        $image = $property->logoImage()->first();

        if ($image) {
            $this->deleteFile($image->name, 'properties');
            $image->delete();
        }
    }
}
