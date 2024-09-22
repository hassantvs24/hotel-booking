<?php

namespace App\Http\Controllers\API\Admin\Booking;

use App\Http\Controllers\BaseController;
use App\Models\BookingAccepted;
use App\Models\BookingRequest;
use App\Repositories\Admin\BookingRequestRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class BookingRequestController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, BookingRequestRepository $bookingRequestRepository): JsonResponse
    {

        $query = array_merge(
            $request->only(['search', 'filters', 'order_by', 'order', 'per_page', 'page']),
            [
                'with'     => [],
                'where'    => [],
                'order_by' => 'id',
                'order'    => 'DESC',
            ]
        );
        $query['whereIn'] = ['status', ['Pending', 'Approved']];
        $booking_requests = $bookingRequestRepository->paginate($query);

        $data = [
            'booking_requests' => $booking_requests
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
    public function update(Request $request, BookingRequestRepository $bookingRequestRepository, $bookingRequestId)
    {
        try {
            if ($request->input('status') === 'Accepted') {
                BookingAccepted::updateOrCreate(
                    ['booking_requests_id' => $bookingRequestId],
                    ['property_id' => 1]
                );
            } else {
                BookingAccepted::where('booking_requests_id', $bookingRequestId)->delete();
            }
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }




    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BookingRequestRepository $bookingRequestRepository, $bookingRequestId):JsonResponse
    {
        try {
            $bookingRequestId = $bookingRequestRepository->getModel($bookingRequestId);

            $bookingRequestRepository->delete($bookingRequestId->id);

            return $this->sendSuccess(null, 'Request deleted successfully');

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
    public function updateStatus(Request $request, $bookingRequest): JsonResponse
    {
        DB::beginTransaction();
        $status = $request->input('status');
        $requestData = $request->all();
        try {
            if ($status === 'Approved') {
                BookingAccepted::updateOrCreate(
                    ['booking_requests_id' => $bookingRequest],
                    [
                    'property_id' => 1,
                    'request_expiration_time' => Carbon::now()->addMinutes(6)
                    ]
                );
                BookingRequest::updateOrCreate(
                    ['id' => $bookingRequest],
                    $requestData
                );
                DB::commit();
                return response()->json(['status' => $status]);
            } else {
                BookingAccepted::where('booking_requests_id', $bookingRequest)->delete();
                BookingRequest::updateOrCreate(
                    ['id' => $bookingRequest],
                    ['status' => $status]
                );
                DB::commit();
                return $this->sendSuccess($bookingRequest);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'Failed', 'message' => $e->getMessage()], 500);
        }
    }
}
