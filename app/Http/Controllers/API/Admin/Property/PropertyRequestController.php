<?php

namespace App\Http\Controllers\API\Admin\Property;

use App\Http\Controllers\BaseController;
use App\Models\City;
use App\Models\Country;
use App\Models\Property;
use App\Models\PropertyRequest;
use App\Models\User;
use App\Notifications\Property\PropertyRequestApproved;
use App\Notifications\Property\PropertyRequestRejected;
use App\Repositories\Admin\PropertyRequestRepository;
use App\Services\MailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PropertyRequestController extends BaseController
{
    public function index(Request $request, PropertyRequestRepository $propertyRequestRepository) : JsonResponse
    {
        $query = array_merge(
            $request->only(['search', 'filters', 'order_by', 'order', 'per_page', 'page']),
            [
                'with' => [],
                'where' => [],
                'order_by' => 'id',
                'order' => 'DESC',
            ]
        );

        $propertyRequests = $propertyRequestRepository->paginate($query);

        return $this->sendSuccess(['property_requests' => $propertyRequests]);
    }

    public function updateStatus(Request $request, PropertyRequestRepository $propertyRequestRepository, $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $propertyRequest = $propertyRequestRepository->find($id);

            if (!$propertyRequest) {
                return $this->sendError('Property request not found', [], 404);
            }

            $previousStatus = $propertyRequest->status;
            $newStatus = $request->status;

            $statusUpdated = $propertyRequest->update([
                'status' => $newStatus,
                'admin_message' => $request->admin_message,
                'approved_at' => $newStatus === 'approved' ? now() : null
            ]);

            if (!$statusUpdated) {
                return $this->sendError('Failed to update property request status', [], 500);
            }

            if ($newStatus === 'approved') {
                $this->handleApproval($propertyRequest);
            } elseif ($newStatus === 'rejected') {
                $this->handleRejection($propertyRequest, $previousStatus);
            } elseif ($newStatus === 'pending') {
                $this->handlePending($propertyRequest, $previousStatus);
            }

            DB::commit();
            return $this->sendSuccess($propertyRequest, 'Property request status updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage(), [], 500);
        }
    }

    /**
     * @throws \Exception
     */
    private function handleApproval(PropertyRequest $propertyRequest): void
    {
        if (User::where('email', $propertyRequest->owner_email)->where('id', '!=', $propertyRequest->user_id)->exists()) {
            throw new \Exception('User with the same email already exists. Please update the email and try again.');
        }

        if (User::where('phone', $propertyRequest->contact_number)->where('id', '!=', $propertyRequest->user_id)->exists()) {
            throw new \Exception('User with the same phone number already exists. Please update the phone number and try again.');
        }

        $user = $propertyRequest->user_id
            ? User::find($propertyRequest->user_id)
            : $this->createUser($propertyRequest);

        $propertyRequest->update(['user_id' => $user->id]);

        if (!$propertyRequest->property_id) {
            $property = $this->createPropertyFromDraft($propertyRequest, $user);
            $propertyRequest->update(['property_id' => $property->id]);
        }

        PropertyRequestApproved::send($propertyRequest->owner_email, [
            'name' => $propertyRequest->name,
            'email' => $propertyRequest->owner_email,
            'phone' => $propertyRequest->contact_number,
            'user' => $user
        ]);
    }

    /**
     * Build the actual Property record from the wizard's draft_data,
     * so approving a request is what finally puts the property in
     * front of admins/guests — the wizard itself only ever writes
     * to property_requests.
     */
    private function createPropertyFromDraft(PropertyRequest $propertyRequest, User $user): Property
    {
        $draftData = $propertyRequest->draft_data ?? [];
        $step1 = $draftData['step_1'] ?? [];
        $step3 = $draftData['step_3'] ?? [];
        $step5 = $draftData['step_5'] ?? [];
        $step6 = $draftData['step_6'] ?? [];
        $step7 = $draftData['step_7'] ?? [];

        // Property::address is a serialize()/unserialize() accessor that
        // expects this exact shape — a plain string gets silently nulled.
        $address = [
            'address'   => $propertyRequest->address,
            'apartment' => null,
            'city'      => City::find($step3['city_id'] ?? null)?->name,
            'country'   => Country::find($step3['country_id'] ?? null)?->name,
        ];

        $property = Property::create([
            'name'                  => $propertyRequest->property_title,
            'description'           => $propertyRequest->description,
            'property_type'         => $step1['property_type_name'] ?? null,
            'lat'                   => $propertyRequest->latitude,
            'long'                  => $propertyRequest->longitude,
            'address'               => $address,
            'phone_number'          => $propertyRequest->contact_number,
            'email'                 => $propertyRequest->owner_email,
            'check_in_time'         => $step7['check_in_time'] ?? null,
            'check_out_time'        => $step7['check_out_time'] ?? null,
            'status'                => Property::STATUS_PENDING,
            'property_category_id'  => $step1['property_type_id'] ?? null,
            'place_id'              => $step3['place_id'] ?: null,
            'user_id'               => $user->id,
            'meta'                  => json_encode([
                'surroundings'        => $step6['surroundings'] ?? [],
                'distance_to_centre'  => $step6['distance_to_centre'] ?? null,
                'cancellation_policy' => $step7['cancellation_policy'] ?? null,
                'min_stay'            => $step7['min_stay'] ?? null,
                'policies'            => $step7['policies'] ?? [],
            ]),
        ]);

        $facilityIds = $draftData['step_4']['facility_ids'] ?? [];
        if (!empty($facilityIds)) {
            $property->facilities()->sync($facilityIds);
        }

        foreach (($step5['photos'] ?? []) as $index => $photo) {
            if (empty($photo['name']) || empty($photo['path'])) {
                continue;
            }

            $property->images()->create([
                'name'       => $photo['name'],
                'type'       => 'image',
                'path'       => $photo['path'],
                'media_role' => $index === 0 ? 'property_image' : 'property_gallery_image',
                'size'       => $photo['size'] ?? null,
                'mime'       => $photo['mime'] ?? null,
            ]);
        }

        return $property;
    }

    private function handleRejection(PropertyRequest $propertyRequest, string $previousStatus): void
    {
        if ($previousStatus === 'approved' && $propertyRequest->user_id) {
            $this->unlinkOrDeleteUser($propertyRequest);
        }

        PropertyRequestRejected::send($propertyRequest->owner_email, [
            'name' => $propertyRequest->name,
            'email' => $propertyRequest->owner_email,
            'phone' => $propertyRequest->contact_number,
        ]);
    }

    private function handlePending(PropertyRequest $propertyRequest, string $previousStatus): void
    {
        if ($previousStatus === 'approved') {
            $propertyRequest->update(['approved_at' => null]);
        }
    }

    private function unlinkOrDeleteUser(PropertyRequest $propertyRequest): void
    {
        $user = User::find($propertyRequest->user_id);

        if ($user && $user->propertyRequests()->count() === 1) {
            $user->delete();
        }

        $propertyRequest->update(['user_id' => null]);
    }

    private function createUser($data): User
    {
        return User::create([
            'name' => $data->name,
            'email' => $data->owner_email,
            'password' => Hash::make('password'),
            'phone' => $data->contact_number,
        ]);
    }
}
