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
            'store_id' => 'hotel674dd7e831e76',
            'store_password' => 'hotel674dd7e831e76@ssl',
            'success_url' => route('payment.success'),
            'fail_url' => route('payment.fail'),
            'cancel_url' => route('payment.cancel'),
            'currency' => 'BDT',
        ]));

        $customer = Customer::createFromArray([
            'name'      => $user->name,
            'email'     => $user->email,
            'phone'     => $user->phone ?? 'N/A',
            'address1'  => $user->address1 ?? 'Unknown Address',
            'address2'  => $user->address2 ?? '',
            'city'      => $user->city ?? 'Unknown City',
            'state'     => $user->state ?? '',
            'postcode'  => $user->postcode ?? '',
            'country'   => $user->country ?? 'Bangladesh',
            'fax'       => $user->fax ?? '',
        ]);

        $checkin = Carbon::parse($booking->checkin);
        $checkout = Carbon::parse($booking->checkout);

        $days = $checkin->diffInDays($checkout);

        $paymentItem = \App\Abstract\Payouts\SSLComm\Booking::createFromArray([
            'transaction_id' => $booking->booking_number,
            'length_of_stay' => $days . 'days',
            'hotel_name' => $booking->room->property->name ?? 'Unknown Hotel',
            'hotel_city' => $booking->room->property->place->city->name ?? 'Unknown City',
            'rooms' => [
                [
                    'name' => $booking->room->name,
                    'price' => $booking->room->base_price
                ]
            ],
            'amount'   => $booking->amount,
            'discount' => $booking->discount ?? 0,
            'vat'      => $booking->vat ?? 0,
            'fee'      => $booking->fee ?? 0,
            'total'    => $booking->amount, // this amount will be charged from customer
        ]);

        return $paymentSession->create($customer, $paymentItem);
    }

    public function bookingDetails(Request $request) : JsonResponse
    {
        $booking = Booking::query()->where('booking_number', $request->booking_number)->first();
        if (!$booking) {
            return $this->sendError('Booking not found', [], 404);
        }

        return $this->sendSuccess($booking);
    }

    public function tryToPayAgain(Request $request)
    {
        $booking = Booking::query()->where('booking_number', $request->booking_number)->first();
        $user = User::whereId($booking->user_id)->first();

        $paymentSession = new Payments(SSLCommSession::create([
            'store_id' => 'hotel674dd7e831e76',
            'store_password' => 'hotel674dd7e831e76@ssl',
            'success_url' => route('payment.success'),
            'fail_url' => route('payment.fail'),
            'cancel_url' => route('payment.cancel'),
            'currency' => 'BDT',
        ]));

        $customer = Customer::createFromArray([
            'name'      => $user->name,
            'email'     => $user->email,
            'phone'     => $user->phone ?? 'N/A', // Ensure fallback if phone is missing
            'address1'  => $user->address1 ?? 'Unknown Address',
            'address2'  => $user->address2 ?? '',
            'city'      => $user->city ?? 'Unknown City',
            'state'     => $user->state ?? '',
            'postcode'  => $user->postcode ?? '',
            'country'   => $user->country ?? 'Bangladesh',
            'fax'       => $user->fax ?? '',
        ]);

        $checkin = Carbon::parse($booking->checkin);
        $checkout = Carbon::parse($booking->checkout);

        $days = $checkin->diffInDays($checkout);

        $paymentItem = \App\Abstract\Payouts\SSLComm\Booking::createFromArray([
            'transaction_id' => $booking->booking_number,
            'length_of_stay' => $days . 'days',
            'hotel_name' => $booking->room->property->name ?? 'Unknown Hotel',
            'hotel_city' => $booking->room->property->place->city->name ?? 'Unknown City',
            'rooms' => [
                [
                    'name' => $booking->room->name,
                    'price' => $booking->room->base_price
                ]
            ],
            'amount'   => $booking->amount,
            'discount' => $booking->discount ?? 0,
            'vat'      => $booking->vat ?? 0,
            'fee'      => $booking->fee ?? 0,
            'total'    => $booking->amount,
        ]);

        return $paymentSession->create($customer, $paymentItem);
    }
}
