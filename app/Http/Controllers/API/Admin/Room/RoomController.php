<?php

namespace App\Http\Controllers\API\Admin\Room;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Room\RoomRequest;
use App\Models\Room;
use App\Repositories\Admin\RoomRepository;
use App\Traits\MediaMan;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends BaseController
{
    use MediaMan;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, RoomRepository $roomRepository)
    {
        $query = array_merge(
            $request->only(['search', 'filters', 'order_by', 'order', 'per_page', 'page']),
            [
                'with'     => ['property', 'bedType', 'roomType', 'facilities'],
                'where'    => [],
                'order_by' => 'id',
                'order'    => 'DESC',
            ]
        );

        $rooms = $roomRepository->paginate($query);

        $data = [
            'rooms' => $rooms
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
    public function store(RoomRequest $request, RoomRepository $roomRepository): JsonResponse
    {
        try {
            $room = $roomRepository->create(
                array_merge($request->except('photo', 'gallery', 'room_facilities'), ['property_id' => $request->user()->associated_property->id])
            );

            if (is_array($request->input('room_facilities'))) {
                $room->facilities()->attach($request->input('room_facilities'));
            }
            if ($request->hasFile('photo')) {
                $primaryImage = $this->storeFile($request->file('photo'), 'room');
                $room->primaryImage()->create(array_merge($primaryImage, ['media_role' => 'room_image']));
            }

            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $file) {
                    $galleryImage = $this->storeFile($file, 'room');
                    $room->images()->create(array_merge($galleryImage, ['media_role' => 'room_gallery_image']));
                }
            }
            return $this->sendSuccess('Room created successfully');
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
    public function update(Request $request, RoomRepository $roomRepository, $room)
    {
        try {

            $room = $roomRepository->getModel($room);
            $data = array_merge(
                $request->except('photo', 'gallery', 'room_facilities'),
                ['property_id' => $request->user()->associated_property->id]
            );

            $room->update($data);

            if (is_array($request->input('room_facilities'))) {
                $room->facilities()->sync($request->input('room_facilities'));
            }

            if ($request->hasFile('photo')) {

                $this->deleteImage($room);

                $image = $this->storeFile($request->file('photo'), 'room');
                $room->primaryImage()->create([...$image, 'media_role' => 'room_image']);
            }

            if ($request->hasFile('gallery')) {
                $this->deleteImage($room);
                foreach ($request->file('gallery') as $file) {
                    $galleryImage = $this->storeFile($file, 'room');
                    $room->images()->create(array_merge($galleryImage, ['media_role' => 'room_gallery_image']));
                }
            }

            return $this->sendSuccess($room);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RoomRepository $roomRepository, $room)
    {
        try {
            $room = $roomRepository->getModel($room);
            $this->deleteImage($room);
            $roomRepository->delete($room->id);

            return $this->sendSuccess([], 'Room deleted successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function all(RoomRepository $roomRepository): JsonResponse
    {
        $rooms = $roomRepository->get();

        $data = [
            'rooms' => $rooms
        ];
        return $this->sendSuccess($data);
    }

    public function showDetails(Room $room): JsonResponse
    {
        $rooms = $room->load([
            'primaryImage',
            'facilities',
            'property',
        ]);

        $data = [
            'rooms' => $rooms
        ];
        return $this->sendSuccess($data);
    }
    private function deleteImage($room): void
    {
        if ($room->primaryImage()->exists()) {
            $primaryImage = $room->primaryImage()->first();
            if ($primaryImage) {
                $this->deleteFile($primaryImage->name, 'room');
                $primaryImage->delete();
            }
        }

        if ($room->images()->exists()) {
            foreach ($room->images as $image) {
                $this->deleteFile($image->name, 'room');
            }
            $room->images()->delete();
        }
    }
}
