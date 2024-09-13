<?php

namespace App\Repositories\Admin;

use App\Models\PriceType;
use App\Repositories\Repository;

class PriceTypeRepository extends Repository
{
    public function __construct()
    {
        $this->model = new PriceType();
    }
}
