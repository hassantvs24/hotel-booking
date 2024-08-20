<?php

namespace App\Repositories\Booking;

use App\Models\BookingRequest;
use App\Repositories\Repository;

class BookingRequestRepository extends Repository
{
    public function __construct()
    {
        $this->model = new BookingRequest();
    }
}
