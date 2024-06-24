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
    public function index(Request $request, PropertyRuleRepository $ruleRepository): View
    {
        if (!hasPermission('can_view_property_rule')) {
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

        $propertyRules = $ruleRepository->paginate($query);

        $permissions = [
            'manage' => 'can_view_property_rule',
            'create' => 'can_create_property_rule',
            'update' => 'can_update_property_rule',
            'delete' => 'can_delete_property_rule',
        ];

        return view('admin.property.rule.index', compact('propertyRules', 'permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        if (!hasPermission('can_create_property_rule')) {
            $this->unauthorized();
        }

        return view('admin.property.rule.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PropertyRuleRequest $request, PropertyRuleRepository $ruleRepository): RedirectResponse
    {
        if (!hasPermission('can_create_property_rule')) {
            $this->unauthorized();
        }
        try {
            $ruleRepository->create($request->validated());

            return redirect()->route('admin.properties.rules.index')->with([
                'message'    => 'Property Rule created successfully.',
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
    public function edit(PropertyRule $propertyRule): View
    {
        if (!hasPermission('can_update_property_rule')) {
            $this->unauthorized();
        }

        return view('admin.property.rule.edit', compact('propertyRule'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PropertyRuleRequest $request, PropertyRuleRepository $propertyRuleRepository, $propertyRule): RedirectResponse
    {
        if (!hasPermission('can_update_property_rule')) {
            $this->unauthorized();
        }
        // try {
        $propertyRule = $propertyRuleRepository->getModel($propertyRule);
        $propertyRuleRepository->update($request->validated(), $propertyRule);

        return redirect()->route('admin.properties.rules.index')->with([
            'message'    => 'Property Rule updated successfully.',
            'alert-type' => 'success'
        ]);
        // } catch (\Exception $e) {
        return redirect()->back()->with([
            'message'    => 'Something went wrong.',
            'alert-type' => 'error'
        ]);
        // }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PropertyRuleRepository $propertyRuleRepository, $propertyRule): RedirectResponse
    {
        if (!hasPermission('can_delete_property_rule')) {
            $this->unauthorized();
        }
        try {
            $propertyRule = $propertyRuleRepository->getModel($propertyRule);
            $propertyRuleRepository->delete($propertyRule->id);

            return redirect()->route('admin.properties.rules.index')->with([
                'message'    => 'Property Rule deleted successfully.',
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
