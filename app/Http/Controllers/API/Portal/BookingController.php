<?php

namespace App\Http\Controllers\API\Portal;

use App\Abstract\Payouts\SSLComm\Customer;
use App\Abstract\Payouts\SSLComm\Payments;
use App\Abstract\Payouts\SSLComm\SSLCommSession;
use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends BaseController
{
    public function paymentDetails(Room $room): JsonResponse
    {
        $room->load([
            'images',
            'property',
            'property.place.city'
        ]);

        $data = [
            'room' => $room
        ];

        return $this->sendSuccess($data);
    }

    public function bookingStore(Request $request)
    {
        $validated = $request->validate([
            'booking_number' => 'required|string',
            'checkin' => 'required|date',
            'checkout' => 'required|date',
            'adult' => 'required|integer',
            'amount' =>'required',
            'children' => 'required|integer',
            'rooms' => 'required|integer',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
            'room_id' => 'required|exists:rooms,id',
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            $booking = Booking::create($validated);
            $room = Room::find($validated['room_id']);
            if ($room) {
                $room->booked_date = $validated['checkin'];
                $room->booked_off_date = $validated['checkout'];
                $room->status = 'Booked';
                $room->save();
            }
            return response()->json(['success' => true, 'data' => $booking], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    public function bookingCheck(Request $request, $room): JsonResponse
    {

        $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after_or_equal:check_in',
        ]);

        $checkIn = Carbon::parse($request->input('check_in'))->format('Y-m-d');
        $checkOut = Carbon::parse($request->input('check_out'))->format('Y-m-d');

        $user = auth()->user();

        $roomIds = Room::where('id', $room)->pluck('id');

        $bookedRoomIds = Booking::where('user_id', $user->id)
            ->whereIn('room_id', $roomIds)
            ->CheckDateOverlap($roomIds, $checkIn, $checkOut)
            ->pluck('room_id');

        $isBooked = $bookedRoomIds->isNotEmpty();

        // Return the result
        return $this->sendSuccess([
            'existBooking' => $isBooked
        ]);
    }

    public function cartList(Request $request): JsonResponse
    {
        $roomRequest = RoomRequest::where('user_id', $request->user()->id)
            ->with([
                'room',
                'user',
                'user.profile',
                'room.property',
                'room.property.logoImage',
                'room.primaryImage',
                'room.facilities',
                'room.property.place.city'
            ])->get();

        $bookingList = Booking::where('user_id', $request->user()->id)
            ->with(['room', 'room.roomType', 'room.property', 'user', 'user.profile', 'room.property.place'])
            ->get();
        $data = [
            'roomRequest' => $roomRequest,
            'bookingList' => $bookingList,
        ];
        return $this->sendSuccess($data);
    }

    public function bookNow(Request $request)
    {
        $booking = Booking::where('user_id', $request->user()->id)->latest()->first();
        $user = User::whereId($booking->user_id)->first();



        $paymentSession = new Payments(SSLCommSession::create([
            // store config in database or config or env and load it here
            'store_id' => 'hotel674dd7e831e76',
            'store_password' => 'hotel674dd7e831e76@ssl',
            'success_url' => route('payment.success'),
            'fail_url' => route('payment.fail'),
            'cancel_url' => route('payment.cancel'),
            'currency' => 'BDT',
        ]));

        $customer = Customer::createFromArray([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '01711111111',
            'address1' => 'Dhaka',
            'address2' => 'Dhaka',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postcode' => '1000',
            'country' => 'Bangladesh',
            'fax' => '01711111111',
        ]);

        $paymentItem = \App\Abstract\Payouts\SSLComm\Booking::createFromArray([
            'transaction_id' => $booking->booking_number,
            'length_of_stay' => '2days',
            'hotel_name' => 'noorjahan',
            'hotel_city' => 'Dhaka',
            'rooms' => [
                [
                    'name' => 'Balcony View',
                    'price' => 200.00
                ],
                [
                    'name' => '3rd floor beach view',
                    'price' => 200.00
                ]
            ],
            'amount' => 400,
            'discount' => 0,
            'vat' => 0,
            'fee' => 0,
            'total' => $booking->amount, // this amount will be charged from customer
        ]);

        return $paymentSession->create($customer, $paymentItem);
    }
}
