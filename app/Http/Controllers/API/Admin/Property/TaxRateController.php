<?php

namespace App\Http\Controllers\API\Admin\Property;

use App\Http\Controllers\BaseController;
use App\Models\Property;
use App\Services\Pricing\PriceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * VAT and service charge rates.
 *
 *  - Platform rates (admin only): what applies when neither the room nor its property has its own.
 *  - Property rates (the owner, or the admin): the default for every room of one property.
 * A blank property rate means "use the platform rate"; 0 means "no tax".
 */
class TaxRateController extends BaseController
{
    public function __construct(private readonly PriceService $prices) {}

    // ── Platform (admin) ────────────────────────────────────

    public function platform(Request $request): JsonResponse
    {
        abort_unless($request->user()->is_admin, 403, 'Only the platform admin can see the platform tax rates.');

        return $this->sendSuccess($this->prices->platformRates());
    }

    public function updatePlatform(Request $request): JsonResponse
    {
        abort_unless($request->user()->is_admin, 403, 'Only the platform admin can change the platform tax rates.');

        $data = $request->validate([
            'vat_percent'            => ['required', 'numeric', 'min:0', 'max:100'],
            'service_charge_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        foreach ($data as $key => $value) {
            $exists = DB::table('settings')->where('key', $key)->exists();

            if ($exists) {
                DB::table('settings')->where('key', $key)->update(['value' => (string) $value, 'group' => 'tax', 'updated_at' => now()]);
            } else {
                DB::table('settings')->insert([
                    'key' => $key, 'value' => (string) $value, 'value_type' => 'text', 'group' => 'tax',
                    'admin_only' => 1, 'is_deletable' => 0, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        PriceService::forgetPlatformRates();

        return $this->sendSuccess($this->prices->platformRates(), 'Platform tax rates saved.');
    }

    // ── One property ────────────────────────────────────────

    public function property(Request $request, Property $property): JsonResponse
    {
        abort_unless($this->canView($request, $property), 403, 'You cannot see this property\'s tax rates.');

        return $this->sendSuccess($this->propertyPayload($property, $request));
    }

    public function updateProperty(Request $request, Property $property): JsonResponse
    {
        abort_unless($this->canEdit($request, $property), 403, 'Only the property owner or the platform admin can change these rates.');

        // A property can charge more than the platform rate, never less
        $floor = $this->prices->platformRates();

        $data = $request->validate([
            'vat_percent'            => ['nullable', 'numeric', 'min:' . $floor['vat_percent'], 'max:100'],
            'service_charge_percent' => ['nullable', 'numeric', 'min:' . $floor['service_charge_percent'], 'max:100'],
        ], [
            'vat_percent.min'            => "VAT cannot be lower than the platform rate ({$floor['vat_percent']}%).",
            'service_charge_percent.min' => "Service charge cannot be lower than the platform rate ({$floor['service_charge_percent']}%).",
        ]);

        $property->update([
            'vat_percent'            => $data['vat_percent'] ?? null,
            'service_charge_percent' => $data['service_charge_percent'] ?? null,
        ]);

        return $this->sendSuccess($this->propertyPayload($property->refresh(), $request), 'Tax rates saved.');
    }

    private function propertyPayload(Property $property, Request $request): array
    {
        return [
            'property_id' => $property->id,
            'can_edit'    => $this->canEdit($request, $property),
            // what this property has set itself (null = "use the platform rate")
            'own'         => [
                'vat_percent'            => $property->vat_percent,
                'service_charge_percent' => $property->service_charge_percent,
            ],
            'platform'    => $this->prices->platformRates(),
        ];
    }

    private function canEdit(Request $request, Property $property): bool
    {
        $user = $request->user();

        return $user->is_admin || $property->user_id === $user->id;
    }

    private function canView(Request $request, Property $property): bool
    {
        return $this->canEdit($request, $property)
            || $property->staffs()->where('users.id', $request->user()->id)->exists();
    }
}
