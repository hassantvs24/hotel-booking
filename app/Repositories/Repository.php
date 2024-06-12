<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

abstract class Repository
{
    protected $model;
    protected array $searchable = [];

    /**
     * @param array $attributes
     * @param array $columns
     * @return mixed
     */
    public function get(array $attributes = [], array $columns = ['*']) : mixed
    {
        $defaultData = [
            'order_by' => 'id',
            'order'    => 'DESC',
            'where'    => [],
            'with'     => [],
            'search'   => '',
            'filters'  => []
        ];

        $data = array_merge($defaultData, $attributes);

        $query = $this->model->query();

        $query = $this->applyWhere($query, $data['where'] ?: []);
        $query = $this->applyWith($query, $data['with'] ?: []);
        $query = $this->applySearch($query, $data['search'] ?: '');
        $query = $this->applyFilters($query, $data['filters'] ?: []);
        $query = $this->applyOrder($query, $data['order_by'], $data['order']);

        return $query->get($columns);
    }

    /**
     * @param array $attributes
     * @param array $columns
     * @return mixed
     */

    public function paginate(array $attributes = [], array $columns = ['*']) : mixed
    {
        $defaultData = [
            'order_by' => 'id',
            'order'    => 'DESC',
            'where'    => [],
            'with'     => [],
            'search'   => '',
            'filters'  => [],
            'per_page' => 15,
            'page'     => 1
        ];

        $data = array_merge($defaultData, $attributes);

        $query = $this->model->query()->select($columns);

        $query = $this->applySearch($query, $data['search'] ?: '');
        $query = $this->applyWhere($query, $data['where'] ?: []);
        $query = $this->applyFilters($query, $data['filters'] ?: []);
        $query = $this->applyWith($query, $data['with'] ?: []);
        $query = $this->applyOrder($query, $data['order_by'], $data['order']);

        return $query->paginate($data['per_page'], ['*'], 'page', $data['page']);
    }

    /**
     * Store a newly created resource in storage.
     * @param array $attributes
     * @param array $relations
     * @return mixed
     */
    public function create(array $attributes, array $relations = []) : mixed
    {
        $model = $this->model->create($attributes);

        if (!empty($load)) {
            $model->load($relations);
        }

        return $model;
    }

    /**
     * @param array $match
     * @param array $attributes
     * @param array $relations
     * @return mixed
     */
    public function createOrUpdate(array $match, array $attributes, array $relations = []) : mixed
    {
        $model = $this->model->updateOrCreate($match, $attributes);

        if (!empty($relations)) {
            $model->load($relations);
        }

        return $model;
    }

    /**
     * Update the specified resource in storage.
     * @param array $attributes
     * @param Model $model
     * @param array $relations
     * @return Model
     */
    public function update(array $attributes, Model $model, array $relations = []) : Model
    {
        $model->update($attributes);

        if (!empty($relations)) {
            $model->load($relations);
        }

        return $model;
    }

    public function delete(int $id) : mixed
    {
        $model = $this->getModel($id);

        if (!$model) {
            return null;
        }

        $model->delete();
        return $model;
    }

    /**
     * @param $identifier
     * @param string $id
     * @return mixed
     */

    public function getModel($identifier, string $id= 'id') : mixed
    {
        if ($identifier instanceof Model) {
            return $identifier;
        }

        $model = $this->model->where($id, $identifier)->first();
        return $model;
    }

    public function find($id, array $columns = ['*']) : mixed
    {
        $model = $this->model->find($id, $columns);
        return $model;
    }

    public function findOrFail($id, array $columns = ['*']) : mixed
    {
        $model = $this->model->findOrFail($id, $columns);
        return $model;
    }

    private function applyWhere($query, array $conditions)
    {
        foreach ($conditions as $condition) {
            if (count($condition) === 2) {
                $query->where($condition[0], $condition[1]);
            } elseif (count($condition) === 3) {
                $query->where($condition[0], $condition[1], $condition[2]);
            }
        }
        return $query;
    }

    private function applyWith($query, array $relations)
    {
        if (!empty($relations)) {
            $query->with($relations);
        }
        return $query;
    }

    private function applySearch($query, $searchKey)
    {
        $query->when($searchKey, function ($query, $searchKey) {
            $query->where(function ($query) use ($searchKey) {
                foreach ($this->searchable as $field) {
                    $query->orWhere($field, 'LIKE', "%$searchKey%");
                }
            });
        });

        return $query;
    }

    private function applyFilters($query, array $filters)
    {
        foreach ($filters as $field => $value) {
            $query->where($field, $value);
        }
        return $query;
    }

    private function applyOrder($query, $orderBy, $order)
    {
        return $query->orderBy($orderBy, $order);
    }
}
