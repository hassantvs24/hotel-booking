<?php

namespace App\Http\Controllers\API\Admin\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RoomRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request,)
    {
        $query = array_merge(
            $request->only(['search', 'filters', 'order_by', 'order', 'per_page', 'page']),
            [
                'with'     => ['user'],
                'where'    => [],
                'order_by' => 'id',
                'order'    => 'DESC',
            ]
        );
        $query['where'][] = ['status', 'Pending'];

        $bookingRequests = $bookingRequestRepository->paginate($query);

        $data = [
            'bookingRequests' => $bookingRequests
        ];

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
    public function store(Request $request)
    {
        //
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
