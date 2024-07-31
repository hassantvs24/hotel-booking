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
use Illuminate\Support\Facades\Request;

class BookingController extends Controller
{
    public function index(Room $room): View
    {
        $searchRequest = SearchHelper::getPreviousSearchRequest();
        $checkInDate = new DateTime($searchRequest['check_in']);
        $checkOutDate = new DateTime($searchRequest['check_out']);
        $numberOfNights = $checkInDate->diff($checkOutDate)->days;

        $data = [
            'room' => $room,
            'checkInDateFormatted' => $checkInDate->format('d F'),
            'checkOutDateFormatted' => $checkOutDate->format('d F'),
            'numberOfNights' => $numberOfNights,
            'numberAdults' => $searchRequest['number_adults'],
            'numberChildren' => $searchRequest['number_children'],
            'price' => $room->base_price * $numberOfNights
        ];

        return view('portal.payment.payment', $data);
    }



    public function booking(Request $request, $room): RedirectResponse
    {
        try {
            $searchRequest = SearchHelper::getPreviousSearchRequest();

            $bookingNumber = 'BOOK-' . strtoupper(uniqid());
            Booking::create([
                'booking_number' => $bookingNumber,
                'checkin' => $searchRequest['check_in'] ?? null,
                'checkout' => $searchRequest['check_out'] ?? null,
                'adult' => $searchRequest['number_adults'] ?? 1,
                'children' => $searchRequest['number_children'] ?? 0,
                'room' => $searchRequest['number_rooms'] ?? 1,
                'reference' => null,
                'notes' => null,
                'room_id' => $room,
                'user_id' => Auth::user()->id,
            ]);
            return redirect()->back()->with('success', 'Booking created successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with([
                'message'    => 'somthing error' . $e->getMessage(),
                'alert-type' => 'success'
            ]);
        }
    }
}
