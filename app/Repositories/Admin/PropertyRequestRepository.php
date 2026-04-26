<?php

namespace App\Repositories\Admin;

use App\Models\PropertyRequest;
use App\Repositories\Repository;

class PropertyRequestRepository extends Repository
{
    public function __construct()
    {
        $this->model = new PropertyRequest();
    }
}
