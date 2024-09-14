<?php

namespace App\Http\Controllers\API\Admin\Booking;

use App\Http\Controllers\BaseController;
use App\Models\BookingAccepted;
use App\Models\BookingRequest;
use App\Repositories\Booking\BookingRequestRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
                'with'     => ['user'],
                'where'    => [],
                'order_by' => 'id',
                'order'    => 'DESC',
            ]
        );
        $query['where'][] = ['status', 'Pending'];

        $bookingRequests = $bookingRequestRepository->paginate($query);

        $data = [
            'bookingRequests' => $bookingRequests
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
    public function update(Request $request, $booking_request): JsonResponse
    {
        try {
            if ($request->input('status') === 'Accepted') {
                BookingAccepted::updateOrCreate(
                    ['booking_requests_id' => $booking_request],
                    ['property_id' => 1]
                );
            } else {
                BookingAccepted::where('booking_requests_id', $booking_request)->delete();
            }
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }




    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BookingRequestRepository $bookingRequestRepository, $booking_request): JsonResponse
    {
        try {
            $booking_request = $bookingRequestRepository->getModel($booking_request);

            $bookingRequestRepository->delete($booking_request->id);

            return $this->sendSuccess(null, 'Booking Request deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function all(BookingRequestRepository $bookingRequestRepository): JsonResponse
    {

        $bookingRequests = $bookingRequestRepository->get();

        $data = [
            'bookingRequests' => $bookingRequests
        ];

        return $this->sendSuccess($data);
    }
}
