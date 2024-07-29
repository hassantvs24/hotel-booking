<?php

namespace App\Http\Controllers\Portal\Booking;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use App\Helpers\SearchHelper;
use DateTime;

class BookingController extends Controller
{
    public function index(Room $room): View
    {
        $searchRequest = SearchHelper::getPreviousSearchRequest();
        $checkInDate = new DateTime($searchRequest['check_in']);
        $checkOutDate = new DateTime($searchRequest['check_out']);
        $numberOfNights = $checkInDate->diff($checkOutDate)->days;
        $checkInDateFormatted = $checkInDate->format('d F');
        $checkOutDateFormatted = $checkOutDate->format('d F');
        $numberAdults = $searchRequest['number_adults'];
        $numberChildren = $searchRequest['number_children'];

        $price = $room->base_price * $numberOfNights;

        // Pass data to the view
        return view('portal.payment.payment', compact('room', 'checkInDateFormatted', 'checkOutDateFormatted', 'numberOfNights', 'numberAdults', 'numberChildren', 'price'));
    }
}
