<?php

namespace App\Http\Controllers\API\Admin\Surrounding;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Surrounding\SurroundingPlaceRequest;
use App\Repositories\Admin\SurroundingPlaceRepository;
use App\Traits\MediaMan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SurroundingPlaceController extends BaseController
{
    use MediaMan;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, SurroundingPlaceRepository $surroundingPlaceRepository): JsonResponse
    {


        $query = array_merge(
            $request->only(['search', 'filters', 'order_by', 'order', 'per_page', 'page']),
            [
                'with'     => ['surrounding', 'place', 'icon'],
                'where'    => [],
                'order_by' => 'id',
                'order'    => 'DESC',
            ]
        );

        $surroundingPlaces = $surroundingPlaceRepository->paginate($query);



        return $this->sendSuccess(['surroundingPlaces' => $surroundingPlaces]);
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
     * @throws \Exception
     */
    public function store(
        SurroundingPlaceRequest $request,SurroundingPlaceRepository $surroundingPlaceRepository): JsonResponse
    {
        try {
            $surroundingPlace = $surroundingPlaceRepository->create($request->only('name', 'lat', 'long', 'notes', 'place_id', 'surrounding_id'));

            if ($request->hasFile('icon')) {
                $icon = $this->storeFile($request->file('icon'), 'surrounding_icons');
                $surroundingPlace->icon()->create([
                    ...$icon,
                    'media_role' => 'surrounding_icon'
                ]);
            }
           return $this->sendSuccess($surroundingPlace->load('place','surrounding', 'icon'), 'Surrounding Created Successfully');


        } catch (\Exception $e) {

            return $this->sendError('Error creating Surrounding Place.', [$e->getMessage()]);
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
    public function edit(string $id){

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        SurroundingPlaceRequest $request,SurroundingPlaceRepository $surroundingPlaceRepository, $surroundingPlace): JsonResponse
        {
        try {
            $surroundingPlace = $surroundingPlaceRepository->getModel($surroundingPlace);
            $surroundingPlaceRepository->update($request->except('icon'), $surroundingPlace);

            if (!$request->hasFile('icon') && $request->input('remove_icon')) {
                $this->deleteImage($surroundingPlace);
            }

            if ($request->hasFile('icon')) {

                if ($surroundingPlace->icon()->exists()) {
                    $this->deleteFile($surroundingPlace->icon->name, 'surrounding_icons');
                    $surroundingPlace->icon()->delete();
                }

                $icon = $this->storeFile($request->file('icon'), 'surrounding_icons');
                $surroundingPlace->icon()->create([
                    ...$icon,
                    'media_role' => 'surrounding_icon'
                ]);
            }

            $surroundingPlace->load(['icon', 'surrounding', 'place']);

        return $this->sendSuccess($surroundingPlace, 'Surrounding Place updated successfully.');
     } catch (\Exception $e) {

            return $this->sendError('Surrounding Place update failed.', [$e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(
        SurroundingPlaceRepository $surroundingPlaceRepository, $surroundingPlace): JsonResponse {

        try {
            $surroundingPlace = $surroundingPlaceRepository->getModel($surroundingPlace);

            if ($surroundingPlace->icon()->exists()) {
                $this->deleteFile($surroundingPlace->icon->name, 'surrounding_icons');
                $surroundingPlace->icon()->delete();
            }
            $surroundingPlaceRepository->delete($surroundingPlace->id);

            return $this->sendSuccess(null, 'Surrrrounding Place deleted successfully.');

        }
        catch (\Exception $e) {

            return $this->sendError('Surrdounding deletion failed.', [$e->getMessage()]);
        }
    }
        /**
     * Delete the icon associated with a sub facility.
     */
    private function deleteImage($surroundingPlace): void
    {
        if ($surroundingPlace->icon()->exists()) {
            $this->deleteFile($surroundingPlace->icon->name, 'surrounding_icons');
            $surroundingPlace->icon()->delete();
        }
    }

}
