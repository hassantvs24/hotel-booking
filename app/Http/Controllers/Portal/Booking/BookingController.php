<?php

namespace App\Http\Controllers\Portal\Booking;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use App\Helpers\SearchHelper;
use App\Models\Booking;
use DateTime;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Room $room): View
    {
        $searchRequest = SearchHelper::getPreviousSearchRequest();
        $checkInDate = new DateTime($searchRequest['check_in']);
        $checkOutDate = new DateTime($searchRequest['check_out']);
        $numberOfNights = $checkInDate->diff($checkOutDate)->days;

        $existingBooking = Booking::where('room_id', $room->id)
            ->where('user_id', 1)
            ->first(); // Use first() to get the booking record

        $bookingId = $existingBooking ? $existingBooking->id : null;

        $data = [
            'room' => $room,
            'checkInDateFormatted' => $checkInDate->format('d F'),
            'checkOutDateFormatted' => $checkOutDate->format('d F'),
            'numberOfNights' => $numberOfNights,
            'numberAdults' => $searchRequest['number_adults'],
            'numberChildren' => $searchRequest['number_children'],
            'price' => $room->base_price * $numberOfNights,
            'existingBooking' => $existingBooking, // True or false
            'bookingId' => $bookingId
        ];

        return view('portal.payment.payment', $data);
    }




    public function booking(Request $request, $room): RedirectResponse
    {
        $request->validate([
            'agree1' => 'required|accepted',
            'agree2' => 'required|accepted'
        ]);
        try {
            $searchRequest = SearchHelper::getPreviousSearchRequest();
            $userId = 1;

            // Create the booking
            $bookingNumber = 'BOOK-' . strtoupper(uniqid());
            $newBooking = Booking::create([
                'booking_number' => $bookingNumber,
                'checkin' => $searchRequest['check_in'] ?? null,
                'checkout' => $searchRequest['check_out'] ?? null,
                'adult' => $searchRequest['number_adults'] ?? 1,
                'children' => $searchRequest['number_children'] ?? 0,
                'room' => $searchRequest['number_rooms'] ?? 1,
                'reference' => null,
                'notes' => null,
                'room_id' => $room,
                'user_id' => $userId,
            ]);

            // Check if booking exists and get the ID
            $existingBooking = Booking::where('room_id', $room)
                ->where('user_id', $userId)
                ->first(); // Use first() to get the booking record

            $bookingId = $existingBooking ? $existingBooking->id : null;

            return redirect()->back()->with([
                'message'    => 'Booking created successfully.',
                'alert-type' => 'success',
                'existingBooking' => $existingBooking !== null, // True or false
                'bookingId' => $bookingId
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with([
                'message'    => 'Something went wrong: ' . $e->getMessage(),
                'alert-type' => 'error'
            ]);
        }
    }

    public function cancel(Request $request, $bookingId): RedirectResponse
    {
        try {
            $userId = 1; // Use authenticated user ID

            Booking::where('id', $bookingId)
                ->where('user_id', $userId)
                ->delete();

            return redirect()->back()->with([
                'message'    => 'Booking deleted successfully',
                'alert-type' => 'success',
                'existingBooking' => false,
                'bookingId' => null
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with([
                'message'    => 'Something went wrong: ' . $e->getMessage(),
                'alert-type' => 'error'
            ]);
        }
    }
}
