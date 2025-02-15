<?php

namespace App\Http\Controllers\API\Admin\Property;

use App\Http\Controllers\BaseController;
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

        PropertyRequestApproved::send($propertyRequest->owner_email, [
            'name' => $propertyRequest->name,
            'email' => $propertyRequest->owner_email,
            'phone' => $propertyRequest->contact_number,
            'user' => $user
        ]);
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
