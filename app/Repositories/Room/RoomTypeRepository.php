<?php

namespace App\Repositories\Room;

use App\Models\RoomType;
use App\Repositories\Repository;

class RoomTypeRepository extends Repository
{
    public function __construct()
    {
        $this->model = new RoomType();
    }
}