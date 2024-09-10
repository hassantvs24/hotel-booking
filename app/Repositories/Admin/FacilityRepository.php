<?php

namespace App\Repositories\Admin;

use App\Models\Facility;
use App\Repositories\Repository;

class FacilityRepository extends Repository
{
    public function __construct()
    {
        $this->model= new Facility();
        $this->searchable = [];
    }

}
