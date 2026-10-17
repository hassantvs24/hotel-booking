<?php

namespace App\Http\Requests\Admin\Room;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RoomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $modelId = $this->room ?: null;

        // Two hotels can both have a "Deluxe Room"; a name only has to be new within its own property
        $propertyId = $this->propertyIdForNameCheck($modelId);

        $uniqueName = \Illuminate\Validation\Rule::unique('rooms', 'name')
            ->where(fn ($q) => $propertyId ? $q->where('property_id', $propertyId) : $q)
            ->whereNull('deleted_at');

        if ($this->method() === 'PUT' && $modelId !== null) {
            $uniqueName->ignore($modelId);
        }

        $creating = $this->method() === 'POST';

        // "Deluxe" x 5 is saved as Deluxe 1 ... Deluxe 5, so the plain name does not have to be unused
        $nameRules = ['required', 'string', 'max:255'];
        if (!($creating && (int) $this->input('quantity', 1) > 1)) {
            $nameRules[] = $uniqueName;
        }

        return [
            'name'           => $nameRules,

            // a new room needs its main (cover) photo; on edit the old one stays unless a new one is sent
            'photo'          => [$creating ? 'required' : 'nullable', 'image', 'max:10240'],
            'gallery'        => 'nullable|array|max:20',
            'gallery.*'      => 'image|max:10240',
            'remove_gallery' => 'nullable|array',
            'gallery_order'  => 'nullable|array',
            'quantity'       => 'nullable|integer|min:1|max:20',
            'room_number'    => 'nullable|integer',   // no longer asked for
            'room_size'      => 'required|integer',
            'guest_capacity' => 'required|integer',
            'total_balcony'  => 'nullable|integer|min:0',
            'total_window'   => 'nullable|integer|min:0',
            'base_price'     => 'required|integer',
            'notes'          => 'nullable|string',
            'bed_type_id'    => 'required|integer|exists:bed_types,id',
            'room_type_id'   => 'required|integer|exists:room_types,id',
            'property_id'    => 'nullable|integer|exists:properties,id',

            // blank = use the property's rate, else the platform's. Never lower than what the admin set.
            'vat_percent'            => ['nullable', 'numeric', 'min:' . app(\App\Services\Pricing\PriceService::class)->platformRates()['vat_percent'], 'max:100'],
            'service_charge_percent' => ['nullable', 'numeric', 'min:' . app(\App\Services\Pricing\PriceService::class)->platformRates()['service_charge_percent'], 'max:100'],

        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */

    /** The property a room's name is checked against: its own on edit, else the owner's, else the one the admin picked */
    private function propertyIdForNameCheck($roomId): ?int
    {
        if ($roomId && ($room = \App\Models\Room::find($roomId))) {
            return $room->property_id;
        }

        $user = $this->user();

        return $user?->is_admin
            ? ($this->filled('property_id') ? (int) $this->input('property_id') : null)
            : $user?->associated_property?->id;
    }

    public function messages(): array
    {
        $rates = app(\App\Services\Pricing\PriceService::class)->platformRates();

        return [
            'vat_percent.min'            => "VAT cannot be lower than the platform rate ({$rates['vat_percent']}%).",
            'service_charge_percent.min' => "Service charge cannot be lower than the platform rate ({$rates['service_charge_percent']}%).",
            'name.unique'            => 'This property already has a room with this name.',
            'name.required'          => 'The name field is required.',
            'name.string'            => 'The name must be a string.',
            'name.max'               => 'The name may not be greater than 255 characters.',
            'photo.required'         => 'Please add a main photo for the room.',
            'photo.uploaded'         => 'The main photo could not be uploaded. Please use a photo of 10 MB or less.',
            'gallery.*.uploaded'     => 'An extra photo could not be uploaded. Please use photos of 10 MB or less.',
            'photo.image'           => 'The main photo must be an image (jpg, png, webp ...).',
            'photo.max'              => 'The main photo may not be larger than 10 MB.',
            'gallery.*.image'        => 'Every extra photo must be an image (jpg, png, webp ...).',
            'gallery.*.max'          => 'An extra photo may not be larger than 10 MB.',
            'gallery.max'            => 'You can add up to 20 extra photos.',
            'quantity.max'           => 'You can create up to 20 rooms at once.',
            'quantity.min'           => 'The number of rooms must be at least 1.',
            'room_number.integer'    => 'The room number must be an integer.',
            'room_size.integer'      => 'The room size must be an integer.',
            'guest_capacity.integer' => 'The guest capacity must be an integer.',
            'guest_capacity.required' => 'The guest capacity field is required.',
            'total_balcony.required' => 'The total balcony field is required.',
            'total_balcony.integer'  => 'The total balcony must be an integer.',
            'total_window.required'  => 'The total window field is required.',
            'base_price.required'    => 'The base price field is required.',
            'base_price.integer'     => 'The base price must be a number.',
            'notes.string'           => 'The notes must be a string.',
            'bed_type_id.integer'    => 'The bed type ID must be an integer.',
            'bed_type_id.required'          => 'The bed type field is required.',
            'bed_type_id.exists'     => 'The selected bed type ID is invalid.',
            'room_type_id.integer'   => 'The room type ID must be an integer.',
            'room_type_id.required'          => 'The room type field is required.',
            'room_type_id.exists'    => 'The selected room type ID is invalid.',
            'property_id.required'   => 'The property ID field is required.',
            'property_id.integer'    => 'The property ID must be an integer.',
            'property_id.exists'     => 'The selected property ID is invalid.',
        ];
    }
}
