<?php

namespace App\Repositories\Admin;

use App\Repositories\Repository;
use Spatie\Permission\Models\Role;

class RoleRepository extends Repository
{
  public function __construct ()
  {
	$this->model = new Role();
	$this->searchable = ['name'];
  }
}
