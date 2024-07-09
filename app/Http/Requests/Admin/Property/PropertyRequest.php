<?php

namespace App\Http\Requests\Admin\Property;

use Illuminate\Foundation\Http\FormRequest;

class PropertyRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $modelId = $this->property ?: null;

        $uniqueNameRule = ($this->method() === 'PUT' && $modelId !== null)
            ? 'unique:properties,name,' . $modelId
            : 'unique:properties,name';

        return [
            'name' => "required|string|max:255|{$uniqueNameRule}",
            'lat' => 'required|numeric|between:-90,90',
            'long' => 'required|numeric|between:-180,180',
            'photo' => 'nullable|image|max:2048',
            'address' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:10',
            'total_room' => 'nullable|integer|min:0',
            'currency' => 'nullable|string|max:5',
            'rating' => 'nullable|numeric|min:0|max:5',
            'google_review' => 'nullable|url',
            'seo_title' => 'nullable|string|max:255',
            'seo_meta' => 'nullable|string|max:255',
            'property_class' => 'required|in:7 Stars,6 Stars,5 Stars,4 Stars,3 Stars,2 Stars,1 Star,Unrated',
            'status' => 'required|in:Pending,Published,Unpublished',
            'property_category_id' => 'nullable|exists:property_categories,id',
            'place_id' => 'nullable|exists:places,id',
        ];
    }
    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */

    public function messages(): array
    {
        return [
            'name.required' => 'The property name is required.',
            'name.unique' => 'The property name must be unique.',
            'lat.required' => 'The latitude is required.',
            'lat.numeric' => 'The latitude must be a number.',
            'lat.between' => 'The latitude must be between -90 and 90 degrees.',
            'long.required' => 'The longitude is required.',
            'long.numeric' => 'The longitude must be a number.',
            'long.between' => 'The longitude must be between -180 and 180 degrees.',
            'photo.image' => 'The photo must be an image.',
            'photo.max' => 'The photo must not be larger than 2MB.',
            'total_room.integer' => 'The total room count must be an integer.',
            'total_room.min' => 'The total room count must be at least 0.',
            'rating.numeric' => 'The rating must be a number.',
            'rating.min' => 'The rating must be at least 0.',
            'rating.max' => 'The rating must not be greater than 5.',
            'google_review.url' => 'The Google review link must be a valid URL.',
            'property_class.required' => 'The property class is required.',
            'property_class.in' => 'The selected property class is invalid.',
            'status.required' => 'The status is required.',
            'status.in' => 'The selected status is invalid.',
            'property_category_id.exists' => 'The selected property category is invalid.',
            'place_id.exists' => 'The selected place is invalid.',
        ];
    }
}
