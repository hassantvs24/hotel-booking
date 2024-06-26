<?php

namespace App\Repositories\Room;

use App\Models\Room;
use App\Repositories\Repository;

class RoomRepository extends Repository
{
    public function __construct()
    {
        $this->model = new Room();
    }
}