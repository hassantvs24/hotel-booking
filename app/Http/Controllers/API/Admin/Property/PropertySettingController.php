<?php

namespace App\Http\Controllers\API\Admin\Property;

use App\Http\Controllers\BaseController;
use App\Models\FacilitySub;
use App\Models\Media;
use App\Models\Property;
use App\Models\PropertyRule;
use App\Services\Location\LocationImporter;
use App\Services\Property\PropertyChangeLogger;
use App\Services\Property\PropertyNameService;
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
 *
 * Every change is written down (PropertyChangeLogger). A new name is only asked for: the old one stays live until the
 * admin approves (PropertyNameService). Photos an owner removes are kept for a while so the admin can bring them back.
 */
class PropertySettingController extends BaseController
{
    use MediaMan;

    public const GALLERY_MAX = 12;

    private const CLASSES = ['Unrated', '1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars', '6 Stars', '7 Stars'];

    /** field => what the log calls it */
    private const BASIC_LABELS = [
        'description' => 'Description', 'address' => 'Street address', 'zip_code' => 'Zip code', 'lat' => 'Latitude', 'long' => 'Longitude',
        'phone_number' => 'Phone', 'email' => 'Email', 'website' => 'Website', 'property_class' => 'Star class', 'property_category_id' => 'Category',
        'check_in_time' => 'Check-in time', 'check_out_time' => 'Check-out time',
    ];

    private const SEO_LABELS = ['seo_title' => 'Search title', 'seo_meta' => 'Search description', 'status' => 'Visibility'];

    public function __construct(private readonly PropertyChangeLogger $log, private readonly PropertyNameService $names) {}

    public function update(Request $request, Property $property): JsonResponse
    {
        $user = $request->user();

        // an owner changes their own property's settings, never another owner's
        abort_unless($user->is_admin || $property->user_id === $user->id, 404);

        // every answer is checked first, so a wrong value is refused with a reason and nothing is half saved
        $basic = $request->has('basicInfo') ? $this->validated($request, 'basicInfo', $this->basicRules(), $request->input('basicInfo.website')) : null;
        $seo   = $basic === null && $request->has('seoInfo') ? $this->validated($request, 'seoInfo', $this->seoRules($request, $property)) : null;
        $rules = $request->has('rules') || $request->boolean('rules_submitted') ? $this->validatedRules($request) : null;
        $facilityIds = $request->has('facility_sub_ids') || $request->boolean('facilities_submitted') ? $this->validatedFacilityIds($request) : null;
        $this->validateFiles($request, $property);

        if (isset($basic['propertyName']) && ($why = PropertyNameService::problem($basic['propertyName']))) {
            throw ValidationException::withMessages(['basicInfo.propertyName' => [$why]]);
        }

        if ($seo && ($seo['status'] ?? null) === 'Published' && $property->hidden_by_admin_at && !$user->is_admin) {
            throw ValidationException::withMessages(['seoInfo.status' => ['The team has hidden this property' . ($property->hidden_reason ? ': ' . $property->hidden_reason : '.') . ' Contact us to have it shown again.']]);
        }

        $namePending = null;

        try {
            DB::beginTransaction();

            if ($basic !== null) {
                $namePending = $this->saveBasic($request, $property, $basic);
            } elseif ($seo !== null) {
                $this->saveSeo($request, $property, $seo);
            }

            if ($request->hasFile('photo')) {
                $this->replaceMainPhoto($request, $property);
            }

            if ($request->boolean('gallery_submitted') || $request->hasFile('gallery')) {
                $this->saveGallery($request, $property);
            }

            if ($facilityIds !== null) {
                $this->saveFacilities($request, $property, $facilityIds);
            }

            if ($rules !== null) {
                $this->saveRules($request, $property, $rules);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Property settings not saved: ' . $e->getMessage(), ['property' => $property->id]);

            return $this->sendError('The settings could not be saved. Nothing was changed. Please try again.', [], 500);
        }

        return $this->sendSuccess(
            ['name_pending' => $namePending?->new_name],
            $namePending ? 'Saved. The new name is waiting for approval; the current name stays until then.' : 'Property settings updated successfully'
        );
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
            'photo'              => ['nullable', ...$image],
            'basicInfo.photo'    => ['nullable', ...$image],
            'gallery'            => ['nullable', 'array', 'max:' . self::GALLERY_MAX],
            'gallery.*'          => $image,
            'keep_gallery_ids'   => ['nullable', 'array'],
            'keep_gallery_ids.*' => ['integer'],
        ], [
            'photo.image'           => 'The main photo must be a picture (JPG, PNG or WebP).',
            'photo.max'             => 'The main photo is larger than 5 MB.',
            'gallery.*.image'       => 'Every gallery file must be a picture (JPG, PNG or WebP).',
            'gallery.*.max'         => 'A gallery photo is larger than 5 MB.',
            'basicInfo.photo.image' => 'The logo must be a picture (JPG, PNG or WebP).',
            'basicInfo.photo.max'   => 'The logo is larger than 5 MB.',
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

    /** @return ?\App\Models\PropertyNameRequest the name request that is waiting, when the owner asked for a new name */
    private function saveBasic(Request $request, Property $property, array $d)
    {
        $by = $request->user();
        $before = $this->basicSnapshot($property);

        $map = [
            'description' => 'description', 'zip_code' => 'zip_code', 'lat' => 'lat', 'long' => 'long',
            'phone_number' => 'phone_number', 'email' => 'email', 'website' => 'website', 'property_class' => 'property_class',
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

        $this->log->diff($property, $by, 'basic', self::BASIC_LABELS, $before, $this->basicSnapshot($property->fresh()));

        // the name: an owner asks (the old one stays live), the admin changes it at once
        $pending = null;
        if (isset($d['propertyName']) && PropertyNameService::clean($d['propertyName']) !== $property->name) {
            $pending = $this->names->submit($property, $by, $d['propertyName'])['request'];
        }

        if ($request->hasFile('basicInfo.photo')) {
            $this->replaceLogo($request, $property);
        }

        return $pending;
    }

    private function basicSnapshot(Property $property): array
    {
        return [
            'description' => $property->description, 'address' => $property->addressParts()['address'], 'zip_code' => $property->zip_code,
            'lat' => $property->lat, 'long' => $property->long, 'phone_number' => $property->phone_number, 'email' => $property->email,
            'website' => $property->website, 'property_class' => $property->property_class,
            'property_category_id' => $property->property_category_id ? ($property->propertyCategory?->name ?? $property->property_category_id) : null,
            'check_in_time' => $property->check_in_time, 'check_out_time' => $property->check_out_time,
        ];
    }

    private function saveSeo(Request $request, Property $property, array $d): void
    {
        $before = $property->only(['seo_title', 'seo_meta', 'status']);
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
        $this->log->diff($property, $request->user(), 'seo', self::SEO_LABELS, $before, $property->fresh()->only(['seo_title', 'seo_meta', 'status']));
    }

    // ── Photos: what leaves is kept, and written down ───────

    private function replaceMainPhoto(Request $request, Property $property): void
    {
        $old = $property->primaryImage()->first();

        if ($old) {
            $this->log->record($property, $request->user(), 'photos', 'main_photo', 'Main photo removed', $old->name, null, 'photo', $this->photoMeta($old));
            $old->delete();   // the file stays on the disk for a while
        }

        $image = $this->storeFile($request->file('photo'), 'properties');
        $new = $property->primaryImage()->create(array_merge($image, ['media_role' => 'property_image']));

        $this->log->record($property, $request->user(), 'photos', 'main_photo', 'Main photo added', null, $new->name, 'photo', $this->photoMeta($new));
    }

    private function replaceLogo(Request $request, Property $property): void
    {
        $old = $property->logoImage()->first();

        if ($old) {
            $this->log->record($property, $request->user(), 'photos', 'logo', 'Logo removed', $old->name, null, 'photo', $this->photoMeta($old));
            $old->delete();
        }

        $image = $this->storeFile($request->file('basicInfo.photo'), 'properties');
        $new = $property->logoImage()->create(array_merge($image, ['media_role' => 'property_logo_image']));

        $this->log->record($property, $request->user(), 'photos', 'logo', 'Logo added', null, $new->name, 'photo', $this->photoMeta($new));
    }

    private function saveGallery(Request $request, Property $property): void
    {
        if ($request->boolean('gallery_submitted')) {
            $keep = $this->keptGalleryIds($request, $property);

            foreach ($property->images()->whereNotIn('id', $keep ?: [0])->get() as $image) {
                $this->log->record($property, $request->user(), 'photos', 'gallery', 'Gallery photo removed', $image->name, null, 'photo', $this->photoMeta($image));
                $image->delete();
            }
        }

        foreach ((array) $request->file('gallery', []) as $file) {
            $stored = $this->storeFile($file, 'properties');
            $new = $property->images()->create(array_merge($stored, ['media_role' => 'property_gallery_image']));

            $this->log->record($property, $request->user(), 'photos', 'gallery', 'Gallery photo added', null, $new->name, 'photo', $this->photoMeta($new));
        }
    }

    /** Everything needed to bring a removed photo back (the file is still on the disk) */
    private function photoMeta(Media $m): array
    {
        return [
            'media_id' => $m->id, 'role' => $m->media_role, 'name' => $m->name, 'path' => $m->path, 'type' => $m->type,
            'size' => $m->size, 'mime' => $m->mime, 'sort_order' => $m->sort_order,
        ];
    }

    /** The photos of this property that stay (the ones named, and only if they really are this property's) */
    private function keptGalleryIds(Request $request, Property $property): array
    {
        $named = array_map('intval', (array) $request->input('keep_gallery_ids', []));

        return $property->images()->whereIn('id', $named)->pluck('id')->all();
    }

    // ── Facilities and rules ────────────────────────────────

    private function saveFacilities(Request $request, Property $property, array $ids): void
    {
        $before = $property->facilities()->pluck('facility_subs.id')->map(fn ($i) => (int) $i)->sort()->values()->all();

        $property->facilities()->sync($ids);

        $after = collect($ids)->map(fn ($i) => (int) $i)->sort()->values()->all();

        if ($before === $after) {
            return;
        }

        $names = fn (array $list) => FacilitySub::whereIn('id', $list)->orderBy('name')->pluck('name')->all();

        $this->log->record($property, $request->user(), 'facilities', 'facilities', 'Facilities', $names($before), $names($after), 'list', ['before' => $before, 'after' => $after]);
    }

    private function saveRules(Request $request, Property $property, array $rows): void
    {
        $existing = $property->rules()->get(['property_rule_id', 'rule_description', 'is_active']);
        $before = $existing->map(fn ($r) => ['property_rule_id' => (int) $r->property_rule_id, 'description' => (string) $r->rule_description, 'is_active' => (bool) $r->is_active])->sortBy('property_rule_id')->values()->all();

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

        $after = collect($rows)->map(fn ($r) => ['property_rule_id' => (int) $r['property_rule_id'], 'description' => (string) $r['description'], 'is_active' => (bool) $r['is_active']])->sortBy('property_rule_id')->values()->all();

        if ($before === $after) {
            return;
        }

        $this->log->record($property, $request->user(), 'rules', 'rules', 'House rules', $this->readableRules($before), $this->readableRules($after), 'list', ['before' => $before, 'after' => $after]);
    }

    private function readableRules(array $rows): array
    {
        $titles = PropertyRule::whereIn('id', array_column($rows, 'property_rule_id'))->pluck('rule_title', 'id');

        return array_map(fn ($r) => ($titles[$r['property_rule_id']] ?? 'Rule') . ($r['is_active'] ? '' : ' (off)') . ($r['description'] !== '' ? ': ' . $r['description'] : ''), $rows);
    }
}
