<?php

namespace App\Http\Controllers\Admin\Booking;

use App\Http\Controllers\BaseController;
use App\Models\BookingAccepted;
use App\Models\BookingRequest;
use App\Repositories\Booking\BookingRequestRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingRequestController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, BookingRequestRepository $bookingRequestRepository): View
    {

        if (!hasPermission('can_view_facility')) {
            $this->unauthorized();
        }

        $query = array_merge(
            $request->only(['search', 'filters', 'order_by', 'order', 'per_page', 'page']),
            [
                'with'     => [],
                'where'    => [],
                'order_by' => 'id',
                'order'    => 'DESC',
            ]
        );
        $query['where'][] = ['status', 'Pending'];

        $bookingRequests = $bookingRequestRepository->paginate($query);


        $bookingAccepted = BookingAccepted::whereIn('booking_requests_id', $bookingRequests->pluck('id'))->get();

        $acceptedBookingIds = $bookingAccepted->pluck('booking_requests_id')->toArray();

        $permissions = [
            'manage' => 'can_view_booking_request',
            'create' => 'can_create_booking_request',
            'update' => 'can_update_booking_request',
            'delete' => 'can_delete_booking_request',
        ];
        $status = [
            'Pending' => 'Pending',
            'Accepted'   => 'Accepted',
        ];
        return view('admin.booking.request.index', compact('bookingRequests', 'permissions', 'status', 'acceptedBookingIds'));
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
    public function edit(BookingRequestRepository $bookingRequestRepository, BookingRequest $bookingRequest): View
    {
        if (!hasPermission('can_update_booking_request')) {
            $this->unauthorized();
        }

        return view('admin.booking.request.edit', compact('bookingRequest'));
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
    public function destroy(string $id)
    {
        //
    }
}
