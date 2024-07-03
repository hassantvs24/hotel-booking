<?php

namespace App\Http\Requests\admin\Review;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
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
        return [
            'rating' => "required|numeric|min:1|max:5",
            'review_category_id' => 'required|exists:review_categories,id',
            'property_id' => 'required|exists:properties,id',
            'user_id' => 'nullable',
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
            'rating.required' => 'The rating field is required.',
            'rating.numeric' => 'The rating must be a number.',
            'rating.min' => 'The rating must be at least 1.',
            'rating.max' => 'The rating may not be greater than 5.',
            'review_category_id.required' => 'The review category is required.',
            'review_category_id.exists' => 'The selected review category does not exist.',
            'property_id.required' => 'The property field is required.',
            'property_id.exists' => 'The selected property does not exist.',
        ];
    }
}
