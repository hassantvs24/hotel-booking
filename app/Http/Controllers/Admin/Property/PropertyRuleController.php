<?php

namespace App\Http\Controllers\Admin\Property;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Property\PropertyRuleRequest;
use App\Models\PropertyRule;
use App\Repositories\Property\PropertyRuleRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PropertyRuleController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, PropertyRuleRepository $ruleRepository) : View
    {
        if (!hasPermission('can_view_property_rule')) {
            $this->unauthorized();
        }

        //

        return view('admin.property.rule.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() : View
    {
        if (!hasPermission('can_create_property_rule')) {
            $this->unauthorized();
        }

        return view('admin.property.rule.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PropertyRuleRequest $request, PropertyRuleRepository $ruleRepository) : RedirectResponse
    {
        if (!hasPermission('can_create_property_rule')) {
            $this->unauthorized();
        }

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
    public function edit(PropertyRule $propertyRule) : View
    {
        if (!hasPermission('can_edit_property_rule')) {
            $this->unauthorized();
        }

        return view('admin.property.rule.edit', compact('propertyRule'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PropertyRuleRequest $request, PropertyRule $propertyRule) : RedirectResponse
    {
        if (!hasPermission('can_edit_property_rule')) {
            $this->unauthorized();
        }

        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PropertyRuleRepository $ruleRepository, $propertyRule) : RedirectResponse
    {
        if (!hasPermission('can_delete_property_rule')) {
            $this->unauthorized();
        }

        //
    }
}
