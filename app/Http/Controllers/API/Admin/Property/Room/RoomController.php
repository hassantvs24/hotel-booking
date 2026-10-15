<?php

namespace App\Http\Controllers\API\Admin\Property\Room;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Room\RoomRequest;
use App\Models\Media;
use App\Models\Room;
use App\Repositories\Admin\RoomRepository;
use App\Services\Pricing\PriceService;
use App\Services\Room\RoomDuplicator;
use App\Traits\MediaMan;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomController extends BaseController
{
    use MediaMan;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Room::with(['property', 'bedType', 'roomType', 'facilities'])->latest('id');

        if ($user->is_admin) {
            if ($request->filled('property_id')) {
                $query->where('property_id', (int) $request->input('property_id'));
            }
        } elseif ($user->is_merchant && $user->associated_property) {
            $query->where('property_id', $user->associated_property->id);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($request->filled('search')) {
            $term = '%' . trim($request->input('search')) . '%';
            $query->where('name', 'like', $term);
        }

        $rooms = $query->paginate(min(max((int) $request->input('per_page', 15), 1), 100));

        // Show what a guest pays per night next to the room's own price
        $rooms->getCollection()->transform(fn (Room $room) => $this->withTax($room));

        return $this->sendSuccess(['rooms' => $rooms]);
    }

    /** The room with its tax-inclusive price per night and the rates behind it */
    private function withTax(Room $room): Room
    {
        $room->tax = app(PriceService::class)->forStay($room, (float) $room->base_price, 1)['per_night']
            + ['rates' => app(PriceService::class)->ratesFor($room)];

        return $room;
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
     *
     * `quantity` makes several identical rooms at once: "Deluxe" x 5 becomes Deluxe 1 ... Deluxe 5. Each room gets
     * its own copy of the photos.
     */
    public function store(RoomRequest $request, RoomRepository $roomRepository, RoomDuplicator $duplicator): JsonResponse
    {
        // Several rooms with several photos take a while on a slow machine
        @set_time_limit(300);

        // The platform admin chooses the property; an owner can only add rooms to their own
        $propertyId = $this->propertyIdForNewRoom($request);

        if (!$propertyId) {
            return $this->sendError('Please choose the property this room belongs to.', ['property_id' => ['Please choose the property this room belongs to.']], 422);
        }

        $quantity = max(1, min(20, (int) $request->input('quantity', 1)));
        $names    = $quantity === 1 ? [$request->input('name')] : $this->numberedNames($request->input('name'), $propertyId, $quantity);

        $createdIds = [];

        DB::beginTransaction();

        try {
            $first = $roomRepository->create(
                array_merge($this->roomFields($request), ['property_id' => $propertyId, 'name' => $names[0]])
            );
            $createdIds[] = $first->id;

            if (is_array($request->input('room_facilities'))) {
                $first->facilities()->attach($request->input('room_facilities'));
            }

            if ($request->hasFile('photo')) {
                $primaryImage = $this->storeFile($request->file('photo'), 'room');
                $first->primaryImage()->create(array_merge($primaryImage, ['media_role' => 'room_image']));
            }

            $this->applyGallery($request, $first);

            foreach (array_slice($names, 1) as $name) {
                $createdIds[] = $duplicator->copy($first->refresh(), $name)->id;
            }

            DB::commit();
        } catch (\Throwable $e) {
            // all or nothing: no half-made set of rooms, and no photo files left behind
            foreach (Media::where('media_type', Room::class)->whereIn('media_id', $createdIds)->get() as $media) {
                $this->deleteFile($media->name, 'room');
            }
            DB::rollBack();

            return $this->sendError($e->getMessage());
        }

        return $this->sendSuccess(
            ['count' => count($names), 'names' => $names],
            count($names) === 1 ? 'Room created successfully' : count($names) . ' rooms created successfully'
        );
    }
    /** One more room like this one, with a new name (a "Duplicate" button) */
    public function duplicate(Request $request, RoomRepository $roomRepository, RoomDuplicator $duplicator, $room): JsonResponse
    {
        try {
            $source = $roomRepository->getModel($room);
            $this->authorizeRoom($request, $source);

            $name = trim((string) $request->input('name', ''));
            if ($name === '') {
                $name = $duplicator->copyName($source);
            } elseif (Room::where('property_id', $source->property_id)->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
                return $this->sendError('This property already has a room with this name.', ['name' => ['This property already has a room with this name.']], 422);
            }

            $copy = $duplicator->copy($source, $name);

            return $this->sendSuccess($copy->load(['primaryImage', 'property']), 'Room duplicated as "' . $copy->name . '".');
        } catch (Exception $e) {
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
    public function update(RoomRequest $request, RoomRepository $roomRepository, $room)
    {
        try {

            $room = $roomRepository->getModel($room);
            $this->authorizeRoom($request, $room);

            // A room stays in the property it belongs to
            $data = array_merge($this->roomFields($request), ['property_id' => $room->property_id]);

            $room->update($data);

            if (is_array($request->input('room_facilities'))) {
                $room->facilities()->sync($request->input('room_facilities'));
            }

            // A new main photo replaces only the old main photo; the extra photos are changed separately
            if ($request->hasFile('photo')) {
                $this->deletePrimaryImage($room);

                $image = $this->storeFile($request->file('photo'), 'room');
                $room->primaryImage()->create([...$image, 'media_role' => 'room_image']);
            }

            $this->applyGallery($request, $room);

            return $this->sendSuccess($room);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, RoomRepository $roomRepository, $room)
    {
        try {
            $room = $roomRepository->getModel($room);
            $this->authorizeRoom($request, $room);
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
            'images',
            'facilities',
            'property',
        ]);
        $this->withTax($rooms);

        $data = [
            'rooms' => $rooms
        ];
        return $this->sendSuccess($data);
    }
    /**
     * What the form sent, minus the files. "Extra bed" is optional on the form but the column can not be
     * empty, so a blank one is left out and the database default applies.
     */
    private function roomFields(Request $request): array
    {
        $fields = $request->except('photo', 'gallery', 'gallery_order', 'remove_gallery', 'room_facilities', 'quantity');

        if (!isset($fields['extra_bed']) || $fields['extra_bed'] === '') {
            unset($fields['extra_bed']);
        }

        // windows and balconies are optional details
        foreach (['total_window', 'total_balcony'] as $key) {
            if (!isset($fields[$key]) || $fields[$key] === '') {
                $fields[$key] = 0;
            }
        }

        return $fields;
    }

    /** Which property a new room goes to: the admin picks one, an owner's is fixed */
    private function propertyIdForNewRoom(Request $request): ?int
    {
        $user = $request->user();

        if ($user->is_admin) {
            return $request->filled('property_id') ? (int) $request->input('property_id') : null;
        }

        return $user->associated_property?->id;
    }

    /** Admin: any room. Owner: only rooms of their own property. */
    private function authorizeRoom(Request $request, Room $room): void
    {
        $user = $request->user();

        abort_unless(
            $user->is_admin || ($user->associated_property && $user->associated_property->id === $room->property_id),
            403,
            'You can only manage rooms of your own property.'
        );
    }

    /** "Deluxe 1", "Deluxe 2" ... skipping numbers the property already uses */
    private function numberedNames(string $base, int $propertyId, int $quantity): array
    {
        $taken = Room::where('property_id', $propertyId)->pluck('name')->map(fn ($n) => mb_strtolower($n))->all();

        $names = [];
        for ($n = 1; count($names) < $quantity; $n++) {
            $candidate = "{$base} {$n}";
            if (!in_array(mb_strtolower($candidate), $taken, true)) {
                $names[] = $candidate;
            }
        }

        return $names;
    }

    /**
     * The extra photos of a room, from what the form sent:
     *   remove_gallery[]  ids of existing photos to delete
     *   gallery[]         new files
     *   gallery_order[]   the final order: "e:<id>" for an existing photo, "n:<i>" for the i-th new file.
     *                     Anything not listed goes to the end.
     */
    private function applyGallery(Request $request, Room $room): void
    {
        $removeIds = array_map('intval', (array) $request->input('remove_gallery', []));
        if ($removeIds) {
            foreach ($room->images()->whereIn('id', $removeIds)->get() as $photo) {
                $this->deleteFile($photo->name, 'room');
                $photo->delete();
            }
        }

        $created = [];   // "n:<i>" => Media
        foreach ((array) $request->file('gallery', []) as $i => $file) {
            $stored = $this->storeFile($file, 'room');
            $created["n:{$i}"] = $room->images()->create(array_merge($stored, ['media_role' => 'room_gallery_image']));
        }

        // existing photos only: the ones just uploaded are already in $created
        $newIds   = collect($created)->pluck('id')->all();
        $existing = $room->images()->whereNotIn('id', $newIds)->get()->keyBy(fn ($m) => "e:{$m->id}");
        $all      = $existing->all() + $created;

        $sequence = [];
        foreach ((array) $request->input('gallery_order', []) as $token) {
            if (isset($all[$token]) && !isset($sequence[$token])) {
                $sequence[$token] = $all[$token];
            }
        }
        foreach ($all as $token => $media) {
            $sequence[$token] ??= $media;
        }

        $position = 1;
        foreach ($sequence as $media) {
            if ((int) $media->sort_order !== $position) {
                $media->update(['sort_order' => $position]);
            }
            $position++;
        }
    }

    private function deletePrimaryImage(Room $room): void
    {
        if ($primary = $room->primaryImage()->first()) {
            $this->deleteFile($primary->name, 'room');
            $primary->delete();
        }
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
