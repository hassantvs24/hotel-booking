<?php

namespace App\Http\Controllers\API\Admin\Surrounding;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Surrounding\SurroundingRequest;
use App\Models\Surrounding;
use App\Repositories\Admin\SurroundingRepository;
use App\Traits\MediaMan;
use Database\Factories\SurroundingFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SurroundingController extends BaseController
{
    use MediaMan;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, SurroundingRepository $surroundingRepository): JsonResponse
    {
        $query = array_merge(
            $request->only(['search', 'filters', 'order_by', 'order', 'per_page', 'page']),
            [
                'with' => [],
                'where' => [],
                'order_by' => 'id',
                'order' => 'DESC',
            ]
        );

        $surroundings = $surroundingRepository->paginate($query);

        $data = [
            'surroundings' => $surroundings
        ];

        return $this->sendSuccess($data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SurroundingRequest $request, SurroundingRepository $surroundingRepository): JsonResponse
    {
        try {
            $surrounding = $surroundingRepository->create($request->only('name', 'notes'));

            if ($request->hasFile('icon')) {
                $image = $this->storeFile($request->file('icon'), 'surrounding_icons');
                $surrounding->icon()->create([
                    ...$image,
                    'media_role' => 'surrounding_icon'
                ]);
            }

            return $this->sendSuccess($surrounding->load('icon'), 'Surrounding created successfully.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Surrounding creation failed.',
                (array)$e->getMessage()
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Surrounding $surrounding)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SurroundingRequest $request, SurroundingRepository $surroundingRepository, $surrounding): JsonResponse
    {
        try {
            $surrounding = $surroundingRepository->getModel($surrounding);
            $surroundingRepository->update($request->only('name', 'notes'), $surrounding);

            if (!$request->hasFile('icon') && $request->input('remove_icon')) {
                $this->deleteImage($surrounding);
            }

            if ($request->hasFile('icon')) {

                $this->deleteImage($surrounding);

                $image = $this->storeFile($request->file('icon'), 'surrounding_icons');
                $surrounding->icon()->create([
                    ...$image,
                    'media_role' => 'surrounding_icon'
                ]);
            }

            return $this->sendSuccess($surrounding->load('icon'), 'Surrounding updated successfully.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Surrounding update failed.',
                (array)$e->getMessage()
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SurroundingRepository $surroundingRepository, $surrounding) : JsonResponse
    {
        try {

            $surrounding = $surroundingRepository->getModel($surrounding);

            if ($surrounding->icon()->exists()) {
                $this->deleteFile($surrounding->icon->name, 'surrounding_icons');
                $surrounding->icon()->delete();
            }

            $surroundingRepository->delete($surrounding->id);

            return $this->sendSuccess(null, 'Surrounding deleted successfully.');
            
        } catch (\Exception $e) {
            return $this->sendError(
                'Surrounding deletion failed.',
                (array)$e->getMessage()
            );
        }
    }

    private function deleteImage($surrounding) : void
    {
        if ($surrounding->icon()->exists()) {
            $this->deleteFile($surrounding->icon->name, 'surrounding_icons');
            $surrounding->icon()->delete();
        }
    }


    public function all (SurroundingRepository $surroundingRepository): JsonResponse
    {
        $surroundings = $surroundingRepository->get();

        $data = [
            'surroundings' => $surroundings
        ];

        return $this->sendSuccess($data);
    }
}
