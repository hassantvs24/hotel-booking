<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function show(Room $room)
    {

        $room->load('property.place');

        return view('portal.rooms.show', compact('room'));
    }
}
