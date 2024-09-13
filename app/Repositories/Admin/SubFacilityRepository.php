<?php

namespace App\Repositories\Admin;

use App\Models\FacilitySub;
use App\Repositories\Repository;

class SubFacilityRepository extends Repository
{
    public function __construct()
    {
        $this->model= new FacilitySub();
    }
}
