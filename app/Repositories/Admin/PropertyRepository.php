<?php

namespace App\Repositories\Admin;

use App\Models\Property;
use App\Repositories\Repository;

class PropertyRepository extends Repository
{
    protected array $searchable = [
        'name',
        'email',
        'phone_number',
        'address',
    ];

    public function __construct()
    {
        $this->model = new Property();
    }

    protected function applySearch($query, $searchKey)
    {
        if (!$searchKey) return $query;

        return $query->where(function ($q) use ($searchKey) {
            foreach ($this->searchable as $field) {
                $q->orWhere($field, 'LIKE', "%{$searchKey}%");
            }
            $q->orWhereHas('user', function ($q) use ($searchKey) {
                $q->where('name', 'LIKE', "%{$searchKey}%");
            });
        });
    }
}
