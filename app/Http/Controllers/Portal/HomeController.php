<?php

namespace App\Http\Controllers\Portal;

use App\Helpers\Helper;
use App\Http\Controllers\BaseController;
use App\Models\Place;
use App\Models\Room;
use Illuminate\View\View;

class HomeController extends BaseController
{
    public function index() : View
    {
        $allowedSlider = getSetting('home_page_number_of_slider');

        $rooms = Room::with('property.place')->paginate(9);
        $places = Place::with('city')
            ->latest()
            ->paginate($allowedSlider);

        return view('portal.home.index', compact('rooms', 'places'));
    }

    public function nonRequested() : View
    {
        return view('portal.non-requested.non-requested');
    }

    public function payment() : View
    {
        return view('portal.payment.payment');
    }

    public function propertyAdd() : View
    {
        return view('portal.property-add.property-add');
    }

    public function requestedWaiting() : View
    {
        return view('portal.requested-waiting.requested-waiting');
    }

    public function requested() : View
    {
        return view('portal.requested.requested');
    }


    public function singleHotelnonRequested() : View
    {
        return view('portal.single-hotel-non-requested.layout');
    }


    public function singleHotel() : View
    {
        return view('portal.single-hotel-page.layout');
    }
}
