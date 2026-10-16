<?php

namespace App\Http\Controllers\API\Admin\Property;

use App\Http\Controllers\BaseController;
use App\Models\City;
use App\Models\Country;
use App\Models\FacilitySub;
use App\Models\Place;
use App\Models\State;
use App\Models\Property;
use App\Models\PropertyRequest;
use App\Models\User;
use App\Notifications\Property\PropertyRequestApproved;
use App\Notifications\Property\PropertyRequestRejected;
use App\Repositories\Admin\PropertyRequestRepository;
use App\Services\MailService;
use App\Services\Verification\VerificationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PropertyRequestController extends BaseController
{
    /**
     * The review queue: only applications the owner has actually submitted (not half-filled drafts),
     * with the numbers for the status tabs and everything the reviewer needs already spelled out.
     */
    public function index(Request $request) : JsonResponse
    {
        abort_unless($request->user()->is_admin, 403, 'Only the platform admin can review property requests.');

        $submitted = PropertyRequest::query()->where('is_draft', false);

        $stats = [
            'all'      => (clone $submitted)->count(),
            'pending'  => (clone $submitted)->where('status', PropertyRequest::STATUS_PENDING)->count(),
            'approved' => (clone $submitted)->where('status', PropertyRequest::STATUS_APPROVED)->count(),
            'rejected' => (clone $submitted)->where('status', PropertyRequest::STATUS_REJECTED)->count(),
        ];

        $requests = $submitted
            ->when($request->filled('status') && $request->status !== 'all', fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where(fn ($w) => $w->where('property_title', 'like', $term)
                    ->orWhere('name', 'like', $term)
                    ->orWhere('owner_email', 'like', $term)
                    ->orWhere('unique_request_number', 'like', $term));
            })
            ->orderByRaw("status = 'pending' desc")   // what needs a decision comes first
            ->orderByDesc('id')
            ->paginate(min((int) $request->input('per_page', 10), 50), ['*'], 'page', (int) $request->input('page', 1));

        $requests->getCollection()->transform(fn (PropertyRequest $r) => array_merge($r->toArray(), ['summary' => $this->summary($r), 'verification' => app(VerificationService::class)->listSummary($r)]));

        return $this->sendSuccess(['property_requests' => $requests, 'stats' => $stats]);
    }

    /** The application's wizard answers turned from ids into words a reviewer can read */
    private function summary(PropertyRequest $r): array
    {
        $d  = $r->draft_data ?? [];
        $s3 = $d['step_3'] ?? [];
        $s7 = $d['step_7'] ?? [];

        $place = collect([
            Place::find($s3['place_id'] ?? null)?->name,
            City::find($s3['city_id'] ?? null)?->name,
            State::find($s3['state_id'] ?? null)?->name,
            Country::find($s3['country_id'] ?? null)?->name,
        ])->filter()->unique()->implode(', ');

        $facilityIds = $d['step_4']['facility_ids'] ?? [];

        return [
            'type'          => $d['step_1']['property_type_name'] ?? null,
            'stars'         => $d['step_2']['star_rating'] ?? null,
            'phone'         => $d['step_2']['phone'] ?? null,
            'location'      => $place,
            'facilities'    => $facilityIds ? FacilitySub::whereIn('id', $facilityIds)->pluck('name')->all() : [],
            'photos'        => collect($d['step_5']['photos'] ?? [])->map(fn ($p) => $p['url'] ?? (isset($p['path'], $p['name']) ? asset('storage/' . $p['path'] . '/' . $p['name']) : null))->filter()->values()->all(),
            'surroundings'  => $d['step_6']['surroundings'] ?? [],
            'check_in'      => $s7['check_in_time'] ?? null,
            'check_out'     => $s7['check_out_time'] ?? null,
            'min_stay'      => $s7['min_stay'] ?? null,
            'house_rules'   => collect($s7['policies'] ?? [])->map(fn ($on) => (bool) $on)->all(),
            'refund_policy' => app(\App\Services\Refund\RefundPolicyService::class)->fromLegacy($s7['cancellation_policy'] ?? null),
            'has_account'   => (bool) $r->user_id,
        ];
    }
    public function updateStatus(Request $request, PropertyRequestRepository $propertyRequestRepository, $id): JsonResponse
    {
        abort_unless($request->user()->is_admin, 403, 'Only the platform admin can review property requests.');

        $request->validate([
            'status'        => ['required', 'in:pending,approved,rejected'],
            'admin_message' => ['nullable', 'string', 'max:1000', 'required_if:status,rejected'],
        ]);

        // An application that went through the Verification step is approved only once the NID and the licence are both verified
        if ($request->status === 'approved' && ($existing = PropertyRequest::find($id)) && $existing->verification_required
            && !app(VerificationService::class)->isFullyVerified($existing)) {
            return $this->sendError("Verify the owner's NID and the trade licence before approving this property.", [], 422);
        }

        DB::beginTransaction();
        try {
            $propertyRequest = $propertyRequestRepository->find($id);

            if (!$propertyRequest) {
                return $this->sendError('Property request not found', [], 404);
            }

            $previousStatus = $propertyRequest->status;
            $newStatus = $request->status;

            $statusUpdated = $propertyRequest->update([
                'status' => $newStatus,
                'admin_message' => $request->admin_message,
                'approved_at' => $newStatus === 'approved' ? now() : null
            ]);

            if (!$statusUpdated) {
                return $this->sendError('Failed to update property request status', [], 500);
            }

            if ($newStatus === 'approved') {
                $this->handleApproval($propertyRequest);
            } elseif ($newStatus === 'rejected') {
                $this->handleRejection($propertyRequest, $previousStatus);
            } elseif ($newStatus === 'pending') {
                $this->handlePending($propertyRequest, $previousStatus);
            }

            DB::commit();
            return $this->sendSuccess($propertyRequest, 'Property request status updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage(), [], 500);
        }
    }

    // ── owner verification (NID + trade licence) ──────────

    public function verification(Request $request, $id): JsonResponse
    {
        abort_unless($request->user()->is_admin, 403, 'Only the platform admin can review documents.');

        $service = app(VerificationService::class);
        $req     = PropertyRequest::findOrFail($id);
        $service->log($request->user(), $req->id, 'viewed_details');

        return $this->sendSuccess($service->adminDetails($req));
    }

    /** One of the three pictures, streamed through here only — it has no public address */
    public function verificationFile(Request $request, $id, string $kind)
    {
        abort_unless($request->user()->is_admin, 403, 'Only the platform admin can review documents.');
        abort_unless(in_array($kind, VerificationService::FILE_KINDS, true), 404);

        $service = app(VerificationService::class);
        $req     = PropertyRequest::findOrFail($id);
        $path    = $service->filePath($req, $kind);

        abort_unless($path, 404, 'This document has not been uploaded.');
        $service->log($request->user(), $req->id, 'viewed_' . $kind);

        return Storage::disk(VerificationService::DISK)->response($path, null, [
            'Cache-Control'          => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function verificationDecision(Request $request, $id, string $target): JsonResponse
    {
        abort_unless($request->user()->is_admin, 403, 'Only the platform admin can review documents.');
        abort_unless(in_array($target, ['nid', 'license'], true), 404);

        $data = $request->validate([
            'status' => ['required', 'in:verified,rejected'],
            'reason' => ['nullable', 'string', 'max:500', 'required_if:status,rejected'],
        ], ['reason.required_if' => 'Tell the owner what is wrong with it, so they can fix it.']);

        $service = app(VerificationService::class);
        $req     = PropertyRequest::where('is_draft', false)->findOrFail($id);

        // while it waits for review, or after it was sent back and before the owner submits it again (so a second document can still be refused)
        abort_unless(in_array($req->status, [PropertyRequest::STATUS_PENDING, PropertyRequest::STATUS_REJECTED], true), 422, 'Documents can only be checked before the application is approved.');

        $service->decide($request->user(), $req, $target, $data['status'], $data['reason'] ?? null);
        $service->log($request->user(), $req->id, $data['status'] . '_' . $target);

        // A refused document sends the whole application back to the owner (bell + email), so they can fix it
        $returned = $data['status'] === 'rejected';
        if ($returned) {
            $service->returnToOwner($req->fresh());
        }

        return $this->sendSuccess(
            array_merge($service->adminDetails($req->fresh()), ['returned' => $returned]),
            $returned ? 'Sent back to the owner.' : 'Saved.'
        );
    }

    /**
     * @throws \Exception
     */
    private function handleApproval(PropertyRequest $propertyRequest): void
    {
        if (User::where('email', $propertyRequest->owner_email)->where('id', '!=', $propertyRequest->user_id)->exists()) {
            throw new \Exception('User with the same email already exists. Please update the email and try again.');
        }

        if (User::where('phone', $propertyRequest->contact_number)->where('id', '!=', $propertyRequest->user_id)->exists()) {
            throw new \Exception('User with the same phone number already exists. Please update the phone number and try again.');
        }

        // Someone who applied while signed in already has an account and keeps their own password.
        // Only an owner added without an account gets a new one — with a random temporary password.
        $tempPassword = null;

        if ($propertyRequest->user_id) {
            $user = User::find($propertyRequest->user_id);
        } else {
            $tempPassword = Str::random(10);
            $user = $this->createUser($propertyRequest, $tempPassword);
        }

        $propertyRequest->update(['user_id' => $user->id]);

        if (!$propertyRequest->property_id) {
            $property = $this->createPropertyFromDraft($propertyRequest, $user);
            $propertyRequest->update(['property_id' => $property->id]);
        }

        // both documents were verified to get here, so the property carries the Verified host badge
        if ($propertyRequest->verification_required && $propertyRequest->property_id) {
            Property::where('id', $propertyRequest->property_id)->update(['verified_at' => now()]);
        }

        PropertyRequestApproved::send($propertyRequest->owner_email, [
            'name' => $propertyRequest->name,
            'email' => $propertyRequest->owner_email,
            'phone' => $propertyRequest->contact_number,
            'user' => $user,
            'temp_password' => $tempPassword,
            'login_url' => rtrim((string) config('app.admin_url'), '/') . '/login',
            'set_password_url' => rtrim((string) config('app.admin_url'), '/') . '/forgot-password?' . http_build_query(['email' => $propertyRequest->owner_email]),
        ]);
    }

    /**
     * Build the actual Property record from the wizard's draft_data,
     * so approving a request is what finally puts the property in
     * front of admins/guests — the wizard itself only ever writes
     * to property_requests.
     */
    private function createPropertyFromDraft(PropertyRequest $propertyRequest, User $user): Property
    {
        $draftData = $propertyRequest->draft_data ?? [];
        $step1 = $draftData['step_1'] ?? [];
        $step3 = $draftData['step_3'] ?? [];
        $step5 = $draftData['step_5'] ?? [];
        $step6 = $draftData['step_6'] ?? [];
        $step7 = $draftData['step_7'] ?? [];

        // Property::address is a serialize()/unserialize() accessor that
        // expects this exact shape — a plain string gets silently nulled.
        $address = [
            'address'   => $propertyRequest->address,
            'apartment' => null,
            'city'      => City::find($step3['city_id'] ?? null)?->name,
            'country'   => Country::find($step3['country_id'] ?? null)?->name,
        ];

        $property = Property::create([
            'name'                  => $propertyRequest->property_title,
            'description'           => $propertyRequest->description,
            'property_type'         => $step1['property_type_name'] ?? null,
            'lat'                   => $propertyRequest->latitude,
            'long'                  => $propertyRequest->longitude,
            'address'               => $address,
            'phone_number'          => $propertyRequest->contact_number,
            'email'                 => $propertyRequest->owner_email,
            'check_in_time'         => $step7['check_in_time'] ?? null,
            'check_out_time'        => $step7['check_out_time'] ?? null,
            'status'                => Property::STATUS_PENDING,
            'property_category_id'  => $step1['property_type_id'] ?? null,
            'place_id'              => ($step3['place_id'] ?? null) ?: null,
            'user_id'               => $user->id,
            // What the owner picked on the application form becomes their real refund policy
            'refund_policy'         => app(\App\Services\Refund\RefundPolicyService::class)->fromLegacy($step7['cancellation_policy'] ?? null),
            'meta'                  => json_encode([
                'surroundings'        => $step6['surroundings'] ?? [],
                'distance_to_centre'  => $step6['distance_to_centre'] ?? null,
                'cancellation_policy' => $step7['cancellation_policy'] ?? null,
                'min_stay'            => $step7['min_stay'] ?? null,
                'policies'            => $step7['policies'] ?? [],
            ]),
        ]);

        $facilityIds = $draftData['step_4']['facility_ids'] ?? [];
        if (!empty($facilityIds)) {
            $property->facilities()->sync($facilityIds);
        }

        foreach (($step5['photos'] ?? []) as $index => $photo) {
            if (empty($photo['name']) || empty($photo['path'])) {
                continue;
            }

            $property->images()->create([
                'name'       => $photo['name'],
                'type'       => 'image',
                'path'       => $photo['path'],
                'media_role' => $index === 0 ? 'property_image' : 'property_gallery_image',
                'size'       => $photo['size'] ?? null,
                'mime'       => $photo['mime'] ?? null,
            ]);
        }

        return $property;
    }

    private function handleRejection(PropertyRequest $propertyRequest, string $previousStatus): void
    {
        // told before any account clean-up below, while the owner still exists
        $notifyBell = $previousStatus !== 'approved';

        if ($previousStatus === 'approved' && $propertyRequest->user_id) {
            $this->unlinkOrDeleteUser($propertyRequest);
        }

        if ($notifyBell) {
            app(VerificationService::class)->notifyOwner($propertyRequest->fresh(), 'rejected');
        } else {
            PropertyRequestRejected::send($propertyRequest->owner_email, [
                'name'  => $propertyRequest->name,
                'email' => $propertyRequest->owner_email,
                'phone' => $propertyRequest->contact_number,
                'property' => $propertyRequest->property_title,
                'reason'   => $propertyRequest->admin_message,
                'edit_url' => app(VerificationService::class)->myPropertiesUrl(),
                'support_email' => config('mail.from.address'),
            ]);
        }
    }

    private function handlePending(PropertyRequest $propertyRequest, string $previousStatus): void
    {
        if ($previousStatus === 'approved') {
            $propertyRequest->update(['approved_at' => null]);
        }
    }

    private function unlinkOrDeleteUser(PropertyRequest $propertyRequest): void
    {
        $user = User::find($propertyRequest->user_id);

        if ($user && $user->propertyRequests()->count() === 1) {
            $user->delete();
        }

        $propertyRequest->update(['user_id' => null]);
    }

    private function createUser($data, string $password): User
    {
        return User::create([
            'name' => $data->name,
            'email' => $data->owner_email,
            'password' => Hash::make($password),
            'phone' => $data->contact_number,
        ]);
    }
}
