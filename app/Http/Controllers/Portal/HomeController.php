<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends BaseController
{
    public function index() : View
    {
        return view('portal.home.index');
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

    public function property() : View
    {
        return view('portal.property.property');
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
