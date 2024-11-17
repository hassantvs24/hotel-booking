<?php

namespace App\Http\Controllers\API\Admin\Booking;

use App\Http\Controllers\BaseController;
use App\Models\Room;
use App\Models\RoomRequest;
use App\Models\RoomRequestAccepted;
use App\Repositories\Admin\RoomRequestRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RoomRequestController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, RoomRequestRepository $roomRequestRepository): JsonResponse
    {
        $query = array_merge(
            $request->only(['search', 'filters', 'order_by', 'order', 'per_page', 'page']),
            [
                'with'     => ['room', 'user'],
                'where'    => [['property_id', '=', $request->user()->associated_property->id]],
                'order_by' => 'id',
                'order'    => 'DESC',
            ]
        );
        $query['whereIn'] = ['status', ['Pending', 'Approved']];
        $room_requests = $roomRequestRepository->paginate($query);

        $data = [
            'room_requests' => $room_requests
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
    public function store(Request $request)
    {
        //
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, RoomRequestRepository $roomRequestRepository, $roomRequestId): JsonResponse
    {
        try {
            $roomRequest = $roomRequestRepository->getModel($roomRequestId);
            // RoomRequest::where('property_id', $request->user()->associated_property->id)
            //     ->where('id', $roomRequestId)
            //     ->delete();

            $room = Room::find($roomRequest->room_id);
            if ($room) {
                $room->update([
                    'status' => 'Available',
                    'booked_date' => null,
                    'booked_off_date' => null
                ]);
            }
            $roomRequestRepository->delete($roomRequest->id);


            return $this->sendSuccess(null, 'Request deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateStatus(Request $request, $roomRequestId): JsonResponse
    {
        DB::beginTransaction();
        $status = $request->input('status');
        $base_price = $request->input('base_price');
        $requestData = $request->all();

        try {
            $bookingRequest = RoomRequest::find($roomRequestId);

            if ($status === 'Approved') {
                RoomRequestAccepted::updateOrCreate(
                    ['room_requests_id' => $roomRequestId],
                    [
                        'property_id' => $request->user()->associated_property->id,
                        'request_expiration_time' => Carbon::now()->addMinutes(6),
                    ]
                );
                $bookingRequest->update($requestData);
            } elseif ($status === 'Counter') {
                RoomRequestAccepted::updateOrCreate(
                    ['room_requests_id' => $roomRequestId],
                    [
                        'property_id' => $request->user()->associated_property->id,
                        'request_expiration_time' => Carbon::now()->addMinutes(6),
                    ]
                );
                $bookingRequest->update([
                    'status' => $status,
                    'discount_price' => $base_price
                ]);
                DB::commit();
            } else {
                RoomRequestAccepted::where('room_requests_id', $roomRequestId)
                    ->where('property_id', $request->user()->associated_property->id)
                    ->delete();
                $bookingRequest->update(['status' => $status]);
            }

            DB::commit();
            return response()->json([
                'status' => 'Success',
                'message' => 'Status updated successfully.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'Failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
