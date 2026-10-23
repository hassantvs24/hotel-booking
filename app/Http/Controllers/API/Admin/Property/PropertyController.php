<?php

namespace App\Http\Controllers\API\Admin\Property;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Property\PropertyRequest;
use App\Models\Facility;
use App\Models\Property;
use App\Models\PropertyCategory;
use App\Models\PropertyRule;
use App\Models\Room;
use App\Models\User;
use App\Repositories\Admin\PropertyRepository;
use App\Services\Room\RoomUsage;
use App\Traits\MediaMan;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Throwable;

class PropertyController extends BaseController
{
    use MediaMan;

    /** The fields the property form may set (everything else, e.g. the owner or the commission rate, is not the form's to change) */
    private const FORM_FIELDS = [
        'name', 'description', 'lat', 'long', 'address', 'zip_code', 'total_room', 'currency', 'rating', 'google_review',
        'phone_number', 'seo_title', 'seo_meta', 'property_class', 'status', 'property_category_id', 'place_id',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, PropertyRepository $propertyRepository): JsonResponse
    {

        $query = [
            'with'     => ['user', 'place.city'],
            'where'    => [],
            'order_by' => 'id',
            'order'    => 'DESC',
            'per_page' => (int) $request->input('per_page', 15),
            'page'     => (int) $request->input('page', 1),
            'search'   => (string) $request->input('search', ''),
            'filters'  => array_filter([
                'status' => $request->input('status'),
            ]),
        ];

        $properties = $propertyRepository->paginate($query);

        // How many rooms each listed property really has (one query for the whole page)
        $properties->getCollection()->loadCount('rooms');

        // The tab numbers cover every property, whichever tab or page is open
        $counts = Property::query()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');

        $data = [
            'properties' => $properties,
            'stats'      => [
                'all'         => (int) $counts->sum(),
                'published'   => (int) ($counts[Property::STATUS_PUBLISHED] ?? 0),
                'pending'     => (int) ($counts[Property::STATUS_PENDING] ?? 0),
                'unpublished' => (int) ($counts[Property::STATUS_UNPUBLISHED] ?? 0),
            ],
        ];

        return $this->sendSuccess($data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PropertyRequest $request, PropertyRepository $propertyRepository): JsonResponse
    {

        DB::beginTransaction();
        try {
            $checkInTime = Carbon::parse($request->input('check_in_time'))->format('H:i:s');
            $checkOutTime = Carbon::parse($request->input('check_out_time'))->format('H:i:s');

            // Only the fields the form has: the owner, commission rate, verified mark... are never taken from the request
            $data = array_merge(
                $request->only(self::FORM_FIELDS),
                [
                    'user_id' => $request->user()->id,
                    'check_in_time' => $checkInTime,
                    'check_out_time' => $checkOutTime
                ]
            );

            $property = $propertyRepository->create($data);
            if (is_array($request->input('facility_sub_ids'))) {
                $property->facilities()->attach($request->input('facility_sub_ids'));
            }

            if ($request->hasFile('primaryImage')) {
                $image = $this->storeFile($request->file('primaryImage'), 'properties');
                $property->primaryImage()->create([...$image, 'media_role' => 'property_image']);
            }
            if ($request->hasFile('additionalImages')) {
                foreach ($request->file('additionalImages') as $file) {
                    $galleryImage = $this->storeFile($file, 'properties');
                    $property->images()->create(array_merge($galleryImage, ['media_role' => 'property_gallery_image']));
                }
            }

            if ($request->input('rules')) {
                $rules = $request->input('rules');
                foreach ($rules as $rule) {
                    $isActive =  $rule['is_active'] === true ? 1 : 0;

                    $property->rules()->updateOrCreate(
                        ['property_rule_id' => $rule['property_rule_id']],
                        [
                            'rule_description' => $rule['description'],
                            'is_active' => $isActive,
                        ]
                    );
                }
            }


            DB::commit();

            return $this->sendSuccess($property->load('facilities', 'primaryImage'));
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        PropertyRequest    $request,
        PropertyRepository $propertyRepository,
        $property
    ): JsonResponse {
        $property = $propertyRepository->getModel($property);

        if (!$property) {
            return $this->sendError('Property not found.');
        }

        DB::beginTransaction();
        try {
            // Only the form's own fields, and the owner stays the owner
            $data = $request->only(self::FORM_FIELDS);

            if ($request->filled('check_in_time')) {
                $data['check_in_time'] = Carbon::parse($request->input('check_in_time'))->format('H:i:s');
            }
            if ($request->filled('check_out_time')) {
                $data['check_out_time'] = Carbon::parse($request->input('check_out_time'))->format('H:i:s');
            }

            $propertyRepository->update($data, $property);

            if (is_array($request->input('facility_sub_ids'))) {
                $property->facilities()->sync($request->input('facility_sub_ids'));
            }

            if ($request->hasFile('primaryImage')) {
                $this->deletePrimaryImage($property);
                $image = $this->storeFile($request->file('primaryImage'), 'properties');
                $property->primaryImage()->create(array_merge($image, ['media_role' => 'property_image']));
            }

            if ($request->hasFile('additionalImages')) {
                $this->deleteAdditionalImage($property);
                foreach ($request->file('additionalImages') as $file) {
                    $galleryImage = $this->storeFile($file, 'properties');
                    $property->images()->create(array_merge($galleryImage, ['media_role' => 'property_gallery_image']));
                }
            }

            if ($request->input('rules')) {
                $rules = $request->input('rules');
                foreach ($rules as $rule) {
                    $isActive = $rule['is_active'] === true ? 1 : 0;

                    $property->rules()->updateOrCreate(
                        ['property_rule_id' => $rule['property_rule_id']],
                        [
                            'rule_description' => $rule['description'],
                            'is_active' => $isActive,
                        ]
                    );
                }
            }
            DB::commit();

            return $this->sendSuccess($property->load('facilities', 'primaryImage', 'images', 'rules'));
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to update property: ' . $e->getMessage());
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PropertyRepository $propertyRepository, $property): JsonResponse
    {
        try {
            $property = $propertyRepository->getModel($property);

            // Guests have paid for stays here: the property cannot simply vanish. (Unpublishing it stops new bookings.)
            $roomIds = Room::where('property_id', $property->id)->pluck('id')->all();
            $upcoming = array_sum(array_column(app(RoomUsage::class)->forRooms($roomIds), 'bookings'));

            if ($upcoming > 0) {
                return $this->sendError("This property has {$upcoming} booking(s) whose stay is not over yet, so it cannot be deleted. Unpublish it to stop new bookings, and delete it after the stays are over.", [], 422);
            }

            app(RoomUsage::class)->release($roomIds);
            $this->deleteImage($property);
            $propertyRepository->delete($property->id);

            return $this->sendSuccess([], 'Property deleted successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function all(PropertyRepository $propertyRepository): JsonResponse
    {
        // the admin sees every property; an owner only their own
        $user       = auth()->user();
        $properties = $propertyRepository->get($user->is_admin ? [] : ['where' => [['user_id', '=', $user->id]]]);

        $data = [
            'properties' => $properties
        ];

        return $this->sendSuccess($data);
    }

    private function deleteImage($property): void
    {
        if ($property->primaryImage()->exists()) {
            $this->deleteFile($property->primaryImage->name, 'properties');
            $property->primaryImage()->delete();
        }
    }

    /**
     * "Verified host" by hand, for properties that were listed before document checks existed.
     * New applications get it automatically when the NID and the licence are verified.
     */
    public function setVerified(Property $property, Request $request): JsonResponse
    {
        abort_unless($request->user()->is_admin, 403, 'Only the platform admin can mark a property as verified.');

        $verified = $request->boolean('verified');

        $property->update(['verified_at' => $verified ? now() : null]);

        app(\App\Services\Verification\VerificationService::class)->log(
            $request->user(), null, ($verified ? 'marked_verified_property_' : 'removed_verified_property_') . $property->id
        );

        return $this->sendSuccess(['is_verified' => $verified], $verified ? 'Marked as Verified host.' : 'Verified host mark removed.');
    }

    public function propertyAction(Property $property, Request $request): JsonResponse
    {
        $request->validate(['status' => ['required', 'in:Pending,Published,Unpublished']]);

        $originalStatus = $property->status;
        $statusInput = $request->input('status');

        DB::beginTransaction();
        try {

            if ($originalStatus === $statusInput) {
                return $this->sendSuccess($property, 'Property status updated successfully');
            }

            $property->update([
                'status' => $statusInput
            ]);

            $propertyOwnerRoleName = config('site.hotelOwnerGroup');
            $propertyOwner = User::query()->where('id', $property->user_id)->first();
            $propertyOwnerRole = Role::query()->where('name', $propertyOwnerRoleName)->first();


            if ($statusInput === 'Published') {

                $existingRoles = $propertyOwner->roles()->whereNotNull('property_id')->get();

                if ($existingRoles->isNotEmpty()) {
                    foreach ($existingRoles as $role) {
                        $propertyOwner->removeRole($role);
                    }
                }

                if ($propertyOwnerRole) {
                    $propertyOwner->assignRole($propertyOwnerRole);
                }
            } else if ($statusInput === 'Unpublished') {
                if (!$propertyOwner->isAdmin) {
                    $propertyOwner->removeRole($propertyOwnerRoleName);
                }
            }

            DB::commit();

            // Going live is the moment the owner has waited for: tell them (bell + email with the full details)
            if ($statusInput === 'Published') {
                app(\App\Services\Property\PropertyAnnouncements::class)->published($property->fresh());
            }

            return $this->sendSuccess($property, 'Property status updated successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage());
        }
    }

    public function propertyAttributes(): JsonResponse
    {
        $categories = PropertyCategory::query()->get(['id', 'name']);
        $facilities = Facility::with([
            'subFacilities' => function ($query) {
                $query->select('id', 'name', 'facility_id');
            }
        ])
            ->whereHas('subFacilities')
            ->where('facility_for', 'Property')
            ->get(['id', 'name']);

        $propertyRules = PropertyRule::query()->get();

        $data = [
            'categories' => $categories,
            'facilities' => $facilities,
            'property_rules' => $propertyRules
        ];

        return $this->sendSuccess($data);
    }

    public function details(Property $property): JsonResponse
    {
        // another owner's property is not theirs to read (it carries the owner's contact details)
        abort_unless(auth()->user()->is_admin || $property->user_id === auth()->id(), 404);

        $property->load([
            'primaryImage',
            'images',
            'logoImage',
            'facilities',
            'facilities.facility',
            'rules.propertyRule',
            'rooms',
            'propertyCategory',
            'place',
            'user',
            'staffs',
            'reviews',
            'hiddenImages',        // photos the team has hidden (the owner sees them, with the reason)
            'pendingNameRequest',  // a new name that waits for the admin
        ]);

        // the parts of the address, for the edit form (the plain `address` is these joined)
        $property->setAttribute('address_parts', $property->addressParts());

        return $this->sendSuccess($property);
    }

    /**
     * Delete the primary image of the property
     *
     * @param Property $property
     * @return void
     */
    private function deletePrimaryImage(Property $property): void
    {
        if ($property->primaryImage()->exists()) {
            $primaryImage = $property->primaryImage()->first();
            $this->deleteFile($primaryImage->name, 'properties');
            $primaryImage->delete();
        }
    }

    /**
     * Delete all gallery images of the property
     *
     * @param Property $property
     * @return void
     */
    private function deleteAdditionalImage(Property $property): void
    {
        if ($property->images()->exists()) {
            foreach ($property->images as $image) {
                $this->deleteFile($image->name, 'properties');
            }
            $property->images()->delete();
        }
    }
}
