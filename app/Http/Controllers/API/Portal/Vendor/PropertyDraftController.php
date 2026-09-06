<?php

namespace App\Http\Controllers\API\Portal\Vendor;

use App\Http\Controllers\BaseController;
use App\Models\City;
use App\Models\Country;
use App\Models\FacilitySub;
use App\Models\Place;
use App\Models\PropertyCategory;
use App\Models\PropertyRequest;
use App\Models\PropertyRule;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyDraftController extends BaseController
{
    /*
     * All options needed for the property draft management will be handled here.
     */

    public function options() : JsonResponse
    {
        $propertyTypes = PropertyCategory::select('id', 'name')->get();
        $countries = Country::select('id', 'name')->get();

        $states = State::select('id', 'name', 'country_id')->get();
        $cities = City::select('id', 'name', 'state_id')->get();
        $places = Place::select('id', 'name', 'city_id')->get();
        $facilities = FacilitySub::with('facility:id,name')
        ->select('id', 'name', 'facility_id')
        ->get();
        $rules = PropertyRule::select('id', 'name', 'type')->get();

        return $this->sendSuccess([
            'propertyTypes' => $propertyTypes,
            'countries' => $countries,
            'states' => $states,
            'cities' => $cities,
            'places' => $places,
            'facilities' => $facilities,
            'rules' => $rules
        ]);

    }

    /*
     * Load the owner's existing draft or create a new empty one
     */

    public function show(Request $request) : JsonResponse
    {
        $user = $request->user();

        $draft = PropertyRequest::query()
            ->where('user_id', $user->id)
            ->where('is_draft', true)
            ->latest()
            ->first();

        if (!$draft) {
            $draft = PropertyRequest::create([
                'user_id'               => $user->id,
                'name'                  => $user->name,
                'owner_email'           => $user->email,
                'contact_number'        => $user->phone ?? '',
                'reason'                => '',
                'property_title'        => '',
                'address'               => '',
                'status'                => 'pending',
                'is_draft'              => true,
                'draft_step'            => 1,
                'draft_data'            => [],
            ]);
        }

        return $this->sendSuccess([
            'draft' => $draft
        ]);
    }

    /*
     * Save one wizard step when owner clicks continue
     */

    public function saveStep(Request $request, $id, $step) : JsonResponse
    {
        $draft = $this->getDraft($request, $id);

        $stepData = $request->input('data', []);
        $draftData = $draft->draft_data ?? [];

        // Merge this step's data into the draft JSON
        $draftData['step_' . $step] = $stepData;
        $updates = [
            'draft_data' => $draftData,
            'draft_step' => max((int) $draft->draft_step, (int) $step),
        ];

        // Sync top level columns from step data
        match ((int) $step) {
            1 => $this->syncStep1($updates, $stepData),
            2 => $this->syncStep2($updates, $stepData),
            3 => $this->syncStep3($updates, $stepData),
            default => null,
        };

        $draft->update($updates);

        return $this->sendSuccess([
            'message'    => 'Step ' . $step . ' saved.',
            'draft'      => $draft->fresh(),
        ]);
    }

    // Helper Functions
    private function getDraft(Request $request, $id): PropertyRequest
    {
        return PropertyRequest::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->where('is_draft', true)
            ->firstOrFail();
    }

    private function syncStep1(array &$updates, array $data) : void
    {
        if (!empty($data['property_type_name'])) {
            $updates['reason'] = $data['property_type_name'];
        }
    }

    private function syncStep2(array &$updates, array $data): void
    {
        if (!empty($data['name']))         $updates['property_title']  = $data['name'];
        if (!empty($data['description'])) $updates['description']     = $data['description'];
        if (!empty($data['email']))        $updates['owner_email']     = $data['email'];
        if (!empty($data['phone']))        $updates['contact_number']  = $data['phone'];
    }

    private function syncStep3(array &$updates, array $data): void
    {
        if (!empty($data['address']))   $updates['address']   = $data['address'];
        if (!empty($data['city']))      $updates['city']      = $data['city'];
        if (!empty($data['state']))     $updates['state']     = $data['state'];
        if (isset($data['latitude']))   $updates['latitude']  = $data['latitude'];
        if (isset($data['longitude']))  $updates['longitude'] = $data['longitude'];
    }
}
