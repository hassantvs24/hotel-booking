<?php

namespace App\Http\Controllers\API\Admin\Booking;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\Room;
use App\Repositories\Admin\BookingRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        if ($user->is_admin) {
            $bookings = Booking::with(['room', 'room.property', 'room.property.place.city', 'user', 'user.profile'])->paginate();
        } elseif ($user->is_merchant && $user->associated_property) {
            $bookings = Booking::whereHas('room', function ($query) use ($request) {
                $query->where('property_id', $request->user()->associated_property->id);
            })->with(['room', 'room.property', 'room.property.place', 'user'])->paginate();
        }

        $data = [
            'bookings' => $bookings
        ];

        return $this->sendSuccess($data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        if (!hasPermission('can_create_booking')) {
            return $this->unauthorized();
        }


        return view('admin.booking.booking.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Display the specified resource.
     */
    public function show(string $id) {}

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
    public function update() {}





    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BookingRepository $bookingRepository, $bookingId)
    {
        try {
            $booking = $bookingRepository->getModel($bookingId);
            $room = Room::find($booking->room_id);
            if ($room) {
                $room->update([
                    'status' => 'Available',
                    'booked_date' => null,
                    'booked_off_date' => null
                ]);
            }
            $bookingRepository->delete($booking->id);
            return $this->sendSuccess('Booking deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'status' => 'required|string'
            ]);

            // Fetch the booking by its ID
            $booking = Booking::findOrFail($id);

            // Update the status
            $booking->update([
                'status' => $validatedData['status']
            ]);

            return $this->sendSuccess($booking);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }
}
