<?php

namespace App\Repositories\User;

use App\Models\User;
use App\Repositories\Repository;

class UserRepository extends Repository
{
    public function __construct()
    {
        $this->model = new User();
    }
}