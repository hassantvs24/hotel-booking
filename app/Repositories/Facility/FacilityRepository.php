<?php

namespace App\Repositories\Facility;

use App\Models\Facility;
use App\Repositories\Repository;

class FacilityRepository extends Repository
{
    public function __construct()
    {
        $this->model= new Facility();
    }

}
