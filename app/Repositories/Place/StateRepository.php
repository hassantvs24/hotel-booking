<?php

namespace App\Repositories\Place;

use App\Models\State;
use App\Repositories\Repository;

class StateRepository extends Repository
{
    public function __construct()
    {
        $this->model = new State();
    }
}
