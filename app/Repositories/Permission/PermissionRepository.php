<?php

namespace App\Repositories\Permission;

use App\Repositories\Repository;
use Spatie\Permission\Models\Permission;

class PermissionRepository extends Repository
{
    public function __construct()
    {
        $this->model = new Permission();
    }
}