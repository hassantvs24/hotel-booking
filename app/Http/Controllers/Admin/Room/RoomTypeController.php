<?php

namespace App\Http\Controllers\Admin\Room;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Room\RoomTypeRequest;
use App\Repositories\Room\RoomTypeRepository;
use App\Traits\MediaMan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomTypeController extends BaseController
{
    use MediaMan;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, RoomTypeRepository $typeRepository) : View
    {
        if (!hasPermission('can_view_room_type')) {
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

        $roomTypes= $typeRepository->paginate($query);

        $permissions = [
            'manage' => 'can_view_room_type',
            'create' => 'can_create_room_type',
            'update' => 'can_update_room_type',
            'delete' => 'can_delete_room_type',
        ];

        return view('admin.room.room-type.index',compact('roomTypes', 'permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() : View
    {
        if (!hasPermission('can_create_room_type')) {
            $this->unauthorized();
        }

        return view('admin.room.room-type.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoomTypeRequest $request, RoomTypeRepository $typeRepository) : RedirectResponse
    {
        if (!hasPermission('can_create_room_type')) {
            $this->unauthorized();
        }

        try {
            $roomType = $typeRepository->create(
                $request->only(['name', 'notes'])
            );

            if ($request->hasFile('icon')) {
                $image = $this->storeFile($request->file('icon'), 'room_types');
                $roomType->icon()->create([
                    ...$image,
                    'media_role' => 'room_icon'
                ]);
            }

            return redirect()->route('admin.rooms.room-types.index')->with([
                'message'    => 'Room Type created successfully.',
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
    public function edit(RoomTypeRepository $typeRepository, $roomType) : View
    {
        if (!hasPermission('can_update_room_type')) {
            $this->unauthorized();
        }

        $roomType = $typeRepository->getModel($roomType);

        return view('admin.room.room-type.edit', compact('roomType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoomTypeRequest $request, RoomTypeRepository $typeRepository, $roomType) : RedirectResponse
    {
        if (!hasPermission('can_update_room_type')) {
            $this->unauthorized();
        }

        try {

            $roomType = $typeRepository->getModel($roomType);
            $typeRepository->update(
                $request->only(['name', 'notes']),
                $roomType
            );

            if ($request->hasFile('icon')) {

                if ($roomType->icon()->exists()) {
                    $this->deleteFile($roomType->icon->name, 'room_types');
                    $roomType->icon()->delete();
                }

                $image = $this->storeFile($request->file('icon'), 'room_types');
                $roomType->icon()->create([
                    ...$image,
                    'media_role' => 'room_icon'
                ]);
            }

            return redirect()->route('admin.rooms.room-types.index')->with([
                'message'    => 'Room Type updated successfully.',
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
    public function destroy(RoomTypeRepository $typeRepository, $roomType) : RedirectResponse
    {
        if (!hasPermission('can_delete_room_type')) {
            $this->unauthorized();
        }

        try {
            $roomType = $typeRepository->getModel($roomType);

            if ($roomType->icon()->exists()) {
                $this->deleteFile($roomType->icon->name, 'room_types');
                $roomType->icon()->delete();
            }

            $typeRepository->delete($roomType->id);

            return redirect()->route('admin.rooms.room-types.index')->with([
                'message'    => 'Room Type deleted successfully.',
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
