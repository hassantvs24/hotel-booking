<?php

namespace App\Http\Controllers\API\Admin\Room;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Room\RoomTypeRequest;
use App\Repositories\Room\RoomTypeRepository;
use App\Traits\MediaMan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class RoomTypeController extends BaseController
{
    use MediaMan;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, RoomTypeRepository $typeRepository):JsonResponse
    {

        $query = array_merge(
            $request->only(['search', 'filters', 'order_by', 'order', 'per_page', 'page']),
            [
                'with'     => [],
                'where'    => [],
                'order_by' => 'id',
                'order'    => 'DESC',
            ]
        );

        $roomTypes= $typeRepository->paginate($query);

        return $this->sendSuccess(['roomTypes' => $roomTypes]);
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
    public function store(RoomTypeRequest $request, RoomTypeRepository $typeRepository):JsonResponse
    {

        try {
            $roomTypes = $typeRepository->create($request->only('name', 'notes'));

            if ($request->hasFile('icon')) {
                $icon = $this->storeFile($request->file('icon'), 'room_types');
                $roomTypes->icon()->create([
                    ...$icon,
                    'media_role' => 'room_icon'
                ]);
            }

            return $this->sendSuccess($roomTypes->load('icon'), 'Room Type created successfully.');
        } catch (\Exception $e) {
            return $this->sendError(
                'Room Type creation failed.',
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
    public function edit(string $id)
    {
      //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoomTypeRequest $request, RoomTypeRepository $typeRepository, $roomTypes):JsonResponse
    {
    

        try {

            $roomTypes = $typeRepository->getModel($roomTypes);
            $typeRepository->update( $request->only(['name', 'notes']),$roomTypes);
            
            if (!$request->hasFile('icon') && $request->input('remove_icon')) {
                $this->deleteImage($roomTypes);
            }

            if ($request->hasFile('icon')) {

                if ($roomTypes->icon()->exists()) {
                    $this->deleteFile($roomTypes->icon->name, 'room_types');
                    $roomTypes->icon()->delete();
                }

                $icon = $this->storeFile($request->file('icon'), 'room_types');
                $roomTypes->icon()->create([
                    ...$icon,
                    'media_role' => 'room_icon'
                ]);
            }

            return $this->sendSuccess($roomTypes->load('icon'), 'Room Type updated successfully.');

        } catch (\Exception $e) {

            return $this->sendError('Room Type update failed.',(array)$e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RoomTypeRepository $typeRepository, $roomTypes):JsonResponse
    {
        try {
            $roomTypes = $typeRepository->getModel($roomTypes);

            if ($roomTypes->icon()->exists()) {
                $this->deleteFile($roomTypes->icon->name, 'room_types');
                $roomTypes->icon()->delete();
            }

            $typeRepository->delete($roomTypes->id);

            return $this->sendSuccess(null, 'Room Type deleted successfully.');


        } catch (\Exception $e) {

            return $this->sendError('Room Type deletion failed.',(array)$e->getMessage());
        }
    }
    private function deleteImage($roomTypes) : void
    {
        if ($roomTypes->icon()->exists()) {
            $this->deleteFile($roomTypes->icon->name, 'room_types');
            $roomTypes->icon()->delete();
        }
    }
}
