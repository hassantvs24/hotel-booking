<?php

namespace App\Repositories\Review;

use App\Models\ReviewCategory;
use App\Repositories\Repository;

class ReviewCategoryRepository extends Repository
{
    public function __construct()
    {
        $this->model = new ReviewCategory();
    }
}