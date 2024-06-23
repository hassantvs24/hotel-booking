<?php

namespace App\Repositories\Surrounding;

use App\Models\Surrounding;
use App\Repositories\Repository;

class SurroundingRepository extends Repository
{
    public function __construct()
    {
        $this->model = new Surrounding();
    }
}