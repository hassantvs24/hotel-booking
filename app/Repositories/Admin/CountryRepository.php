<?php

namespace App\Repositories\Admin;

use App\Models\Country;
use App\Repositories\Repository;

class CountryRepository extends Repository
{
  public function __construct ()
  {
	$this->model = new Country();
	$this->searchable = [];
  }
}
