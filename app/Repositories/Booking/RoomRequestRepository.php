<?php

namespace App\Repositories\Booking;

use App\Models\RoomRequest;
use App\Repositories\Repository;

class RoomRequestRepository extends Repository
{
    public function __construct()
    {
        $this->model = new RoomRequest();
    }
}
