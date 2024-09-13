<?php

namespace App\Repositories\Admin;

use App\Models\BedType;
use App\Repositories\Repository;

class BedTypeRepository extends Repository
{
    public function __construct()
    {
        $this->model = new BedType();
    }
}
