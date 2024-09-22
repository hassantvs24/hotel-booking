<?php

namespace App\Repositories\Admin;

use App\Models\Review;
use App\Repositories\Repository;

class ReviewRepositroy extends Repository
{
    public function __construct()
    {
        $this->model = new Review();
    }
}
