<?php

namespace App\Abstract\Payouts\SSLComm;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JetBrains\PhpStorm\ArrayShape;

class Booking implements Contracts\BookingContracts
{

    protected array $booking = [
        'product_profile'  => 'travel-vertical',
        'check_in_time'    => '24 Hours',
        'product_category' => 'Booking',
        'product_name'     => 'Hotel Booking',
        'shipping_method'  => 'no',
        'hotel_name'       => null,
        'length_of_stay'   => null,
        'hotel_city'       => null,
        'tran_id'          => null,
        'cart'             => [],
        'total_amount'     => 0,
        'product_amount'   => 0,
        'discount_amount'  => 0,
        'vat'              => 0,
        'convenience_fee'  => 0,
        'amount'           => 0,
        'discount'         => 0,
        'fee'              => 0,
    ];

    /**
     * Booking constructor.
     *
     * @param array $data
     *
     * @throws ValidationException If the validation of the data fails.
     */
    // #[ArrayShape([
    //     'transaction_id' => "string",
    //     'length_of_stay' => "string",
    //     'hotel_name'     => "string",
    //     'hotel_city'     => "string",
    //     'total'          => "float",
    //     'rooms'          => "array",
    //     'rooms.*.name'   => "string",
    //     'rooms.*.price'  => "float",
    //     'amount'         => "float",
    //     'discount'       => "float",
    //     'vat'            => "float",
    //     'fee'            => "float",
    // ])]
    public function __construct(array $data = [])
    {
        $validated = Validator::make($data, [
            'transaction_id' => 'required',
            'length_of_stay' => 'required|string|max:255',
            'hotel_name'     => 'required|string|max:255',
            'hotel_city'     => 'required|string|max:255',
           'total'          => 'required|numeric',
            'rooms'          => 'required|array',
            'rooms.*.name'   => 'required|string|max:255',
            'rooms.*.price'  => 'required|numeric',
            'amount'         => 'nullable|numeric',
            'discount'       => 'nullable|numeric',
            'vat'            => 'nullable|numeric',
            'fee'            => 'nullable|numeric',
        ])->validated();

        $bookingInfo = array_merge($this->booking, $validated);

        foreach ($data as $key => $value) {

            if (empty($value)) {
                continue;
            }

            if (array_key_exists($key, $this->booking)) {
                $bookingInfo[$key] = $value;
            }

            if (array_key_exists($key, $this->fieldMaps())) {
                $bookingInfo[$this->fieldMaps()[$key]] = $value;
            }
        }

        $this->booking = array_merge($this->booking, $bookingInfo);
        $this->booking['cart'] = json_encode($this->booking['cart']);
    }

    /**
     * @throws ValidationException
     */
    public static function createFromArray($data = []): static
    {
        return new static($data);
    }

    public function toArray(): array
    {
        return $this->booking;
    }

    public function fieldMaps(): array
    {
        return [
            'transaction_id' => 'tran_id',
            'length_of_stay' => 'length_of_stay',
            'hotel_name'     => 'hotel_name',
            'hotel_city'     => 'hotel_city',
            'total'          => 'total_amount',
            'rooms'          => 'cart',
            'amount'         => 'product_amount',
            'discount'       => 'discount_amount',
            'vat'            => 'vat',
            'fee'            => 'convenience_fee',
        ];
    }
}
