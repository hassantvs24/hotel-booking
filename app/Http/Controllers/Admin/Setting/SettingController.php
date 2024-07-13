<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\BaseController;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index() : View
    {
        if (!hasPermission('can_view_settings')) {
            $this->unauthorized();
        }

        $settings = Setting::query()->get()->groupBy('group');
        return view('admin.setting.index', compact('settings'));
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
