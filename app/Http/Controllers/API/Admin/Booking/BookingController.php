<?php

namespace App\Http\Controllers\API\Admin\Booking;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Repositories\Admin\BookingRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request,BookingRepository $bookingRepository) : JsonResponse
    {
        $query = array_merge(
            $request->only(['search', 'filters', 'order_by', 'order', 'per_page', 'page']),
            [
                'with'     => ['room', 'user'],
                'where'    => [],
                'order_by' => 'id',
                'order'    => 'DESC',
            ]
        );
        $bookings = $bookingRepository->paginate($query);

        $data = [
            'bookings' => $bookings
        ];

        return $this->sendSuccess($data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() : View
    {
        if (!hasPermission('can_create_booking')) {
            return $this->unauthorized();
        }


        return view('admin.booking.booking.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id) : View
    {
        if (!hasPermission('can_edit_booking')) {
            return $this->unauthorized();
        }


        $booking = Booking::find($id);

        return view('admin.booking.booking.edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BookingRepository $bookingRepository,$bookingId)
    {
        try {
            $bookingId = $bookingRepository->getModel($bookingId);

            $bookingRepository->delete($bookingId->id);

            return $this->sendSuccess(null, 'Booking deleted successfully');

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
