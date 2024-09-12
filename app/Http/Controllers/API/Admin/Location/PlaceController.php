<?php

namespace App\Http\Controllers\API\Admin\Location;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Place\PlaceRequest;
use App\Models\Place;
use App\Repositories\Place\CityRepository;
use App\Repositories\Place\PlaceRepository;
use App\Traits\MediaMan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlaceController extends BaseController
{
    use MediaMan;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, PlaceRepository $placeRepository): JsonResponse
    {
        $query = array_merge(
            $request->only(['search', 'filters', 'order_by', 'order', 'per_page', 'page']),
            [
                'with'     => ['city'],
                'where'    => [],
                'order_by' => 'id',
                'order'    => 'DESC',
            ]
        );

        $places = $placeRepository->paginate($query);

        $data = [
            'places' => $places
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
    public function store(PlaceRequest $request, PlaceRepository $placeRepository): JsonResponse
    {
        try {
            $place = $placeRepository->create($request->except('photo', 'icon'));

            if ($request->hasFile('photo')) {
                $primaryImage = $this->storeFile($request->file('photo'), 'places');
                $place->primaryImage()->create(array_merge($primaryImage, ['media_role' => 'place_image']));
            }

            if ($request->hasFile('icon')) {
                $icon = $this->storeFile($request->file('icon'), 'place_icons');
                $place->icon()->create(array_merge($icon, ['media_role' => 'place_icon']));
            }

            return $this->sendSuccess($place->load('primaryImage', 'icon'), 'Place created successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
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
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PlaceRequest $request, PlaceRepository $placeRepository, $place): JsonResponse
    {


        DB::beginTransaction();
        try {
            $place = $placeRepository->getModel($place);
            $placeRepository->update($request->except('photo', 'icon'), $place);

            if ($request->hasFile('photo')) {
                $this->deleteIcon($place);

                $placeImage = $this->storeFile($request->file('photo'), 'places');
                $place->primaryImage()->create(array_merge($placeImage, ['media_role' => 'place_image']));
            }

            if ($request->hasFile('icon')) {
                if ($place->icon) {
                    $this->deleteIcon($place);
                }
                $icon = $this->storeFile($request->file('icon'), 'place_icons');
                $place->icon()->create(array_merge($icon, ['media_role' => 'place_icon']));
            }

            DB::commit();

            return $this->sendSuccess($place->load('primaryImage', 'icon'), 'Place updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PlaceRepository $placeRepository, $place): JsonResponse
    {
        try {
            $place = $placeRepository->getModel($place);
            $this->deleteIcon($place);
            $placeRepository->delete($place->id);

            return $this->sendSuccess([], 'Place deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function all(PlaceRepository $placeRepository): JsonResponse
    {
        $places = $placeRepository->get();

        $data = [
            'places' => $places
        ];
        return $this->sendSuccess($data);
    }

    private function deleteIcon($place): void
    {
        if ($place->icon()->exists()) {
            $this->deleteFile($place->icon->name, 'place_icons');
            $place->icon()->delete();
        }
         if ($place->primaryImage()->exists()) {
            $this->deleteFile($place->primaryImage->name, 'places');
            $place->primaryImage()->delete();
        }
    }
}
