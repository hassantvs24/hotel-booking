<?php

namespace App\Http\Controllers\API\Admin\Property;

use App\Http\Controllers\BaseController;
use App\Models\Property;
use App\Services\Refund\RefundPolicyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A property's refund policy belongs to its owner: the platform admin can look at it but cannot change it.
 */
class PropertyRefundPolicyController extends BaseController
{
    public function __construct(private readonly RefundPolicyService $policies) {}

    public function show(Request $request, Property $property): JsonResponse
    {
        abort_unless($this->canView($request, $property), 403, 'You cannot view this property\'s refund policy.');

        return $this->sendSuccess($this->payload($property, $request));
    }

    public function update(Request $request, Property $property): JsonResponse
    {
        abort_unless(
            $property->user_id === $request->user()->id,
            403,
            'Only the property owner can change the refund policy.'
        );

        $data = $request->validate([
            'preset'                       => ['nullable', 'string', 'max:30'],
            'use_platform_default'         => ['nullable', 'boolean'],
            'tiers'                        => ['nullable', 'array'],
            'tiers.*.days'                 => ['nullable'],
            'tiers.*.refund_percentage'    => ['nullable'],
        ]);

        // "Go back to the platform default" clears the owner's own policy
        $property->refund_policy = !empty($data['use_platform_default'])
            ? null
            : $this->policies->normalize($data['preset'] ?? null, $data['tiers'] ?? []);
        $property->save();

        return $this->sendSuccess($this->payload($property->refresh(), $request), 'Refund policy saved.');
    }

    private function payload(Property $property, Request $request): array
    {
        return [
            'property_id' => $property->id,
            'can_edit'    => $property->user_id === $request->user()->id,
            'policy'      => $this->policies->summary($property),
            'presets'     => collect(RefundPolicyService::PRESETS)
                ->map(fn (array $p, string $key) => ['key' => $key] + $p)
                ->values()
                ->all(),
            'max_tiers'   => RefundPolicyService::MAX_TIERS,
        ];
    }

    private function canView(Request $request, Property $property): bool
    {
        $user = $request->user();

        return $user->is_admin
            || $property->user_id === $user->id
            || $property->staffs()->where('users.id', $user->id)->exists();
    }
}
