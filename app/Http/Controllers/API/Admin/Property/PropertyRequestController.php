<?php

namespace App\Http\Controllers\API\Admin\Property;

use App\Http\Controllers\BaseController;
use App\Models\User;
use App\Notifications\Property\PropertyRequestApproved;
use App\Notifications\Property\PropertyRequestRejected;
use App\Repositories\Admin\PropertyRequestRepository;
use App\Services\MailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function updateStatus(Request $request, PropertyRequestRepository $propertyRequestRepository, $id) : JsonResponse
    {
        try {
            $propertyRequest = $propertyRequestRepository->find($id);

            if ($propertyRequest === null) {
                return $this->sendError('Property request not found', [], 404);
            }

            $statusUpdated = $propertyRequest->update([
                'status' => $request->status,
                'admin_message' => $request->admin_message,
                'approved_at' => $request->status === 'approved' ? now() : null
            ]);

            if (!$statusUpdated) {
                return $this->sendError('Failed to update property request status', [], 500);
            }

            if ($request->status === 'approved') {
                $user = $this->createUser($propertyRequest);

                $propertyRequest->update([
                    'user_id' => $user->id
                ]);

                PropertyRequestApproved::send('sukanta.atcfbd@gmail.com', [
                    'name' => $propertyRequest->name,
                    'email' => $propertyRequest->owner_email,
                    'phone' => $propertyRequest->contact_number,
                    'user' => $user
                ]);
            }

            if ($request->status === 'rejected') {
                PropertyRequestRejected::send('sukanta.atcfbd@gmail.com', [
                    'name' => $propertyRequest->name,
                    'email' => $propertyRequest->owner_email,
                    'phone' => $propertyRequest->contact_number,
                ]);
            }

            return $this->sendSuccess($propertyRequest, 'Property request status updated successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 500);
        }
    }

    private function createUser($data) : User
    {

        $existingUser = User::where('email', $data->owner_email)->first();

        $dataToStore = [
            'name' => $data->name,
            'email' => $data->owner_email,
            'password' => $existingUser ? $existingUser->password : Hash::make('password'),
            'phone' => $data->contact_number,
        ];

        $user = User::updateOrCreate(
            ['email' => $data->owner_email],
            $dataToStore
        );

        return $user;
    }
}
