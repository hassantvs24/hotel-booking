<?php

namespace App\Repositories\Role;

use App\Repositories\Repository;
use Spatie\Permission\Models\Role;

class RoleRepository extends Repository
{
    public function __construct()
    {
        $this->model = new Role();
    }
}