<?php

namespace App\Repositories\Admin;

use App\Models\RoomRequest;
use App\Repositories\Repository;

class RoomRequestRepository extends Repository
{
    protected array $searchable = [
        'bid_number',
        'status',
    ];

    public function __construct()
    {
        $this->model = new RoomRequest();
    }
}
