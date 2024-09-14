<?php

namespace App\Repositories\Booking;

use App\Models\Booking;
use App\Repositories\Repository;

class BookingRepository extends Repository
{
    public function __construct()
    {
        $this->model = new Booking();
    }
}
