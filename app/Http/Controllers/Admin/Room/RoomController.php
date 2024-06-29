<?php

namespace App\Http\Controllers\Admin\Room;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Room\RoomRequest;
use App\Models\Room;
use App\Repositories\Property\PropertyRepository;
use App\Repositories\Room\BedTypeRepository;
use App\Repositories\Room\RoomRepository;
use App\Repositories\Room\RoomTypeRepository;
use App\Traits\MediaMan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends BaseController
{
    use MediaMan;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, RoomRepository $roomRepository): View
    {
        if (!hasPermission('can_view_room')) {
            $this->unauthorized();
        }
        $query = array_merge(
            $request->only(['search', 'filters', 'order_by', 'order', 'per_page', 'page']),
            [
                'with'     => [],
                'where'    => [],
                'order_by' => 'id',
                'order'    => 'DESC',
            ]
        );

        $rooms = $roomRepository->paginate($query);

        $permissions = [
            'manage' => 'can_view_room',
            'create' => 'can_create_room',
            'update' => 'can_update_room',
            'delete' => 'can_delete_room',
        ];

        return view('admin.room.room.index', compact('rooms', 'permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(BedTypeRepository $bedTypeRepository, RoomTypeRepository $roomTypeRepository, PropertyRepository $propertyRepository): View
    {
        if (!hasPermission('can_create_room')) {
            $this->unauthorized();
        }
        $bedtypes = $bedTypeRepository->pluck('name', 'id')->toArray();
        $roomtypes = $roomTypeRepository->pluck('name', 'id')->toArray();
        $properties = $propertyRepository->pluck('name', 'id')->toArray();

        return view('admin.room.room.create', compact('bedtypes', 'roomtypes', 'properties'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoomRequest $request, RoomRepository $roomRepository): RedirectResponse
    {
        if (!hasPermission('can_create_room')) {
            $this->unauthorized();
        }
        try {
            $room = $roomRepository->create($request->validated());

            if ($request->hasFile('photo')) {
                $image = $this->storeFile($request->file('photo'), 'rooms');
                $room->primaryImage()->create([...$image, 'media_role' => 'room_image']);
            }

            return redirect()->route('admin.rooms.index')->with([
                'message'    => 'Room created successfully.',
                'alert-type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message'    => 'Something went wrong.',
                'alert-type' => 'error'
            ]);
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
    public function edit(BedTypeRepository $bedTypeRepository, RoomTypeRepository $roomTypeRepository, PropertyRepository $propertyRepository, Room $room): View
    {
        if (!hasPermission('can_update_room')) {
            $this->unauthorized();
        }
        $bedtypes = $bedTypeRepository->pluck('name', 'id')->toArray();
        $roomtypes = $roomTypeRepository->pluck('name', 'id')->toArray();
        $properties = $propertyRepository->pluck('name', 'id')->toArray();

        return view('admin.room.room.edit', compact('room', 'bedtypes', 'roomtypes', 'properties'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoomRequest $request, RoomRepository $roomRepository, $room): RedirectResponse
    {
        if (!hasPermission('can_update_room')) {
            $this->unauthorized();
        }
        try {
            $room = $roomRepository->getModel($room);
            $rooms = $roomRepository->update($request->validated(), $room);

            if ($request->hasFile('photo')) {

                if ($rooms->primaryImage) {
                    $this->deleteFile($rooms->primaryImage->name, 'rooms');
                    $rooms->primaryImage()->delete();
                }

                $image = $this->storeFile($request->file('photo'), 'rooms');
                $rooms->primaryImage()->create([...$image, 'media_role' => 'room_image']);
            }
            return redirect()->route('admin.rooms.index')->with([
                'message'    => 'Room updated successfully.',
                'alert-type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message'    => 'Something went wrong.',
                'alert-type' => 'error'
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RoomRepository $roomRepository, $room): RedirectResponse
    {
        if (!hasPermission('can_delete_room')) {
            $this->unauthorized();
        }
        try {
            $room = $roomRepository->getModel($room);
            $roomRepository->delete($room->id);
            return redirect()->route('admin.rooms.index')->with([
                'message'    => 'Room deleted successfully.',
                'alert-type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message'    => 'Something went wrong.',
                'alert-type' => 'error'
            ]);
        }
    }
}
