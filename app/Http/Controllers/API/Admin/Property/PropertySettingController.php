<?php

namespace App\Http\Controllers\API\Admin\Property;

use App\Http\Controllers\BaseController;
use App\Models\Property;
use App\Traits\MediaMan;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropertySettingController extends BaseController
{
    use MediaMan;

    public function update(Request $request, Property $property): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Process basic information updates
            if ($request->input('basicInfo')) {
                $data = $request->input('basicInfo');

                $checkInTime = isset($data['check_in_time']) ? Carbon::parse($data['check_in_time'])->format('H:i:s') : null;
                $checkOutTime = isset($data['check_out_time']) ? Carbon::parse($data['check_out_time'])->format('H:i:s') : null;

                $property->update([
                    'name' => $data['propertyName'],
                    'description' => $data['description'],
                    'address' => $data['address'],
                    'zip_code' => $data['zip_code'],
                    'lat' => $data['lat'],
                    'long' => $data['long'],
                    'phone_number' => $data['phone_number'],
                    'email' => $data['email'],
                    'website' => $data['website'],
                    'total_room' => $data['total_room'],
                    'currency' => $data['currency'],
                    'rating' => $data['rating'],
                    'google_review' => $data['google_review'],
                    'property_class' => $data['property_class'],
                    'property_category_id' => $data['property_category_id'],
                    'check_in_time' => $checkInTime,
                    'check_out_time' => $checkOutTime,
                ]);
            }
            // Process SEO information updates
            elseif ($request->input('seoInfo')) {
                $data = $request->input('seoInfo');
                $property->update([
                    'seo_meta' => $data['seo_meta'],
                    'seo_title' => $data['seo_title'],
                    'status' => $data['status'],
                ]);
            }

            if ($request->hasFile('photo')) {
                $this->deletePrimaryImage($property);

                $image = $this->storeFile($request->file('photo'), 'properties');
                $property->primaryImage()->create(array_merge($image, ['media_role' => 'property_image']));
            }

            // Handle gallery images upload
            if ($request->hasFile('gallery')) {
                $this->deleteGalleryImages($property);

                foreach ($request->file('gallery') as $file) {
                    $galleryImage = $this->storeFile($file, 'properties');
                    $property->images()->create(array_merge($galleryImage, ['media_role' => 'property_gallery_image']));
                }
            }
            if (is_array($request->input('facility_sub_ids'))) {
                $property->facilities()->sync($request->input('facility_sub_ids'));
            }

            if ($request->input('rules')) {
                $rules = $request->input('rules');
                foreach ($rules as $rule) {
                    $isActive =  $rule['is_active'] === true ? 1 : 0;

                    $property->rules()->updateOrCreate(
                        ['property_rule_id' => $rule['property_rule_id']],
                        [
                            'rule_description' => $rule['description'],
                            'is_active' => $isActive,
                            'property_id' => $property->id,
                        ]
                    );
                }
            }



            DB::commit();
            return $this->sendSuccess('Property settings updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to update property settings: ' . $e->getMessage());
        }
    }

    /**
     * Delete the primary image of the property
     *
     * @param Property $property
     * @return void
     */
    private function deletePrimaryImage(Property $property): void
    {
        if ($property->primaryImage()->exists()) {
            $primaryImage = $property->primaryImage()->first();
            $this->deleteFile($primaryImage->name, 'properties');
            $primaryImage->delete();
        }
    }

    /**
     * Delete all gallery images of the property
     *
     * @param Property $property
     * @return void
     */
    private function deleteGalleryImages(Property $property): void
    {
        if ($property->images()->exists()) {
            foreach ($property->images as $image) {
                $this->deleteFile($image->name, 'properties');
            }
            $property->images()->delete();
        }
    }
}
