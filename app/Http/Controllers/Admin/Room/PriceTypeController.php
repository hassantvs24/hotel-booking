<?php

namespace App\Http\Controllers\Admin\Room;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Room\PriceTypeRequest;
use App\Repositories\Room\PriceTypeRepository;
use App\Repositories\Room\RoomTypeRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PriceTypeController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, PriceTypeRepository $typeRepository) : View
    {
        if (!hasPermission('can_view_price_type')) {
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

        $priceTypes= $typeRepository->paginate($query);

        $permissions = [
            'manage' => 'can_view_price_type',
            'create' => 'can_create_price_type',
            'update' => 'can_update_price_type',
            'delete' => 'can_delete_price_type',
        ];

        
        return view('admin.room.price-type.index', compact ('priceTypes' , 'permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() : View
    {
        if (!hasPermission('can_create_price_type')) {
            $this->unauthorized();
        }
        
        return view('admin.room.price-type.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store( PriceTypeRequest $request, PriceTypeRepository $priceTypeRepository ) : RedirectResponse
    {
        if (!hasPermission('can_create_price_type')) {
            $this->unauthorized();
        }

        try {
            $priceTypeRepository->create($request->validated());

            return redirect()->route('admin.rooms.price-types.index')->with([
                'message'    => 'Price Type created successfully.',
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
    public function edit(PriceTypeRepository $priceTypeRepository, $priceType) : View
    {
        if (!hasPermission('can_update_price_type')) {
            $this->unauthorized();
        }

        $priceType = $priceTypeRepository->getModel($priceType);

        return view('admin.room.price-type.edit' , compact('priceType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PriceTypeRequest $request, PriceTypeRepository $priceTypeRepository, $priceType) : RedirectResponse
    {
        if (!hasPermission('can_update_price_type')) {
            $this->unauthorized();
        }

        try {

            $priceType = $priceTypeRepository->getModel($priceType);
            $priceTypeRepository->update($request->validated(), $priceType);

            return redirect()->route('admin.rooms.price-types.index')->with([
                'message'    => 'Price Type updated successfully.',
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
    public function destroy(PriceTypeRepository $priceTypeRepository, $priceType) : RedirectResponse
    {
        if (!hasPermission('can_delete_price_type')) {
            $this->unauthorized();
        }

        try {

            $priceTypeRepository->delete($priceType);

            return redirect()->route('admin.rooms.price-types.index')->with([
                'message'    => 'Price Type deleted successfully.',
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
