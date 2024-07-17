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

        $settings = Setting::query()->paginate(10);
        return view('admin.setting.index', compact('settings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() : View
    {
        if (!hasPermission('can_view_settings')) {
            $this->unauthorized();
        }

        return view('admin.setting.create');
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
    public function update(Request $request, Setting $setting) : \Illuminate\Http\RedirectResponse
    {
        if (!hasPermission('can_view_settings')) {
            $this->unauthorized();
        }

        $data = $this->validateData($request);

        dd($data);

        return redirect()->back()->with([
            'message'    => 'Setting Updated successfully.',
            'alert-type' => 'success'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    private function validateData(Request $request) : array
    {
        dd($request->all()); // null
        $id = $request->setting?->id;
        $updating = (bool) $id;

        $rules = $request->validate([
            'key'        => 'required|string',
            'value_type' => 'required|string|in:text,bool,image,video',
        ]);

        if ($rules['value_type'] === 'bool') {
            $rules['value'] = 'required|boolean';
        }

        if ($rules['value_type'] === 'text') {
            $rules['value'] = 'required|string';
        }

        if ($rules['value_type'] === 'image') {
            if (is_uploaded_file($this->value)) {
                $rules['value'] = 'nullable|mimes:jpg,jpeg,png,gif|max:2048'; // maximum file size 2MB
            }
        }

        if ($rules['value_type'] === 'video') {
            if (is_uploaded_file($this->value)) {
                $rules['value'] = 'nullable|mimes:mp4|max:2048'; // maximum file size 2MB
            }
        }

        if ($id) {
            $rules['key'] .= ",${id}";
        }

        return $rules;
    }
}
