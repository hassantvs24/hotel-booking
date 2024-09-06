<?php

namespace App\Repositories\Admin;

use App\Models\Place;
use App\Repositories\Repository;

class PlaceRepository extends Repository
{
  public function __construct ()
  {
	$this->model = new Place();
	$this->searchable = [];
  }
}
