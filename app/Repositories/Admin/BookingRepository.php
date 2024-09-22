<?php

namespace App\Repositories\Admin;

use App\Models\Booking;
use App\Repositories\Repository;

class BookingRepository extends Repository
{
    public function __construct()
    {
        $this->model = new Booking();
    }
}
