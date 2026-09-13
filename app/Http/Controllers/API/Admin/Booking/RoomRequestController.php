<?php

namespace App\Http\Controllers\API\Admin\Booking;

use App\Events\Booking\BidStatusUpdated;
use App\Http\Controllers\BaseController;
use App\Models\Room;
use App\Models\RoomRequest;
use App\Models\RoomRequestAccepted;
use App\Repositories\Admin\RoomRequestRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RoomRequestController extends BaseController
{
    public function index(Request $request, RoomRequestRepository $repo): JsonResponse
    {
        $user = auth()->user();

        $query = [
            'with'     => ['room', 'room.property', 'user', 'property'],
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

        if ($user->is_merchant && !$user->is_admin) {
            $query['where'][] = ['property_id', '=', $user->associated_property->id];
        }

        $roomRequests = $repo->paginate($query);

        return $this->sendSuccess(['room_requests' => $roomRequests]);
    }

    public function show(RoomRequestRepository $repo, $id): JsonResponse
    {
        $roomRequest = RoomRequest::with([
            'room',
            'room.property',
            'room.primaryImage',
            'user',
            'property',
            'acceptedRequest',
        ])->findOrFail($id);

        return $this->sendSuccess($roomRequest);
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status'     => 'required|string|in:Approved,Counter,Declined,Done',
            'base_price' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $roomRequest = RoomRequest::with(['room', 'room.property', 'user'])->findOrFail($id);
            $status      = $request->input('status');
            $basePrice   = $request->input('base_price');

            if ($status === 'Approved') {
                RoomRequestAccepted::updateOrCreate(
                    ['room_requests_id' => $id],
                    [
                        'property_id'             => $roomRequest->property_id,
                        'request_expiration_time' => Carbon::now()->addHours(2),
                    ]
                );
                $roomRequest->update(['status' => $status]);

            } elseif ($status === 'Counter') {
                RoomRequestAccepted::updateOrCreate(
                    ['room_requests_id' => $id],
                    [
                        'property_id'             => $roomRequest->property_id,
                        'request_expiration_time' => Carbon::now()->addHours(2),
                    ]
                );
                $roomRequest->update([
                    'status'        => $status,
                    'counter_price' => $basePrice,
                ]);

            } elseif ($status === 'Declined') {
                RoomRequestAccepted::where('room_requests_id', $id)->delete();
                $roomRequest->update(['status' => $status]);

            } elseif ($status === 'Done') {
                $roomRequest->update(['status' => $status]);
            }

            DB::commit();

            // ── Fire real-time event — notifies guest + admin ──
            $fresh = $roomRequest->fresh(['room', 'room.property', 'user']);
            $event = new BidStatusUpdated($fresh);
            event($event);
            $event->notifyAll();

            return $this->sendSuccess(
                $fresh->load(['acceptedRequest']),
                'Bid status updated successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage());
        }
    }

    public function destroy(RoomRequestRepository $repo, $id): JsonResponse
    {
        try {
            $roomRequest = $repo->getModel($id);

            $room = Room::find($roomRequest->room_id);
            if ($room) {
                $room->update([
                    'status'          => 'Available',
                    'booked_date'     => null,
                    'booked_off_date' => null,
                ]);
            }

            $repo->delete($roomRequest->id);

            return $this->sendSuccess(null, 'Bid deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function stats(): JsonResponse
    {
        $stats = [
            'total'    => RoomRequest::count(),
            'pending'  => RoomRequest::where('status', 'Pending')->count(),
            'approved' => RoomRequest::where('status', 'Approved')->count(),
            'counter'  => RoomRequest::where('status', 'Counter')->count(),
            'declined' => RoomRequest::where('status', 'Declined')->count(),
            'done'     => RoomRequest::where('status', 'Done')->count(),
        ];

        return $this->sendSuccess($stats);
    }
}
