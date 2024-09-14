<?php

namespace App\Http\Controllers\Admin\Booking;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index() : View
    {
        if (!hasPermission('can_view_booking')) {
            return $this->unauthorized();
        }
   

        $permissions = [
            'manage' => 'can_view_booking',
            'create' => 'can_create_booking',
            'update' => 'can_update_booking',
            'delete' => 'can_delete_booking',
        ];


        return view('admin.booking.booking.index',compact('permissions'));
    }
    
    /**
     * Show the form for creating a new resource.
     */
    public function create() : View
    {
        if (!hasPermission('can_create_booking')) { 
            return $this->unauthorized();
        }
        

        return view('admin.booking.booking.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
       
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
       
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id) : View
    {
        if (!hasPermission('can_edit_booking')) {  
            return $this->unauthorized();
        }

        
        $booking = Booking::find($id);

        return view('admin.booking.booking.edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
      
    }
}
