<?php

namespace App\Http\Controllers\API\Portal\Vendor;

use App\Http\Controllers\BaseController;
use App\Models\PropertyRequest;
use App\Services\Verification\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * The wizard's Verification step: the owner's NID and the property's trade licence.
 * The owner only ever gets statuses and a masked number back — the files are for the admin.
 */
class PropertyVerificationController extends BaseController
{
    public function __construct(private readonly VerificationService $verification)
    {
    }

    public function show(Request $request, $id): JsonResponse
    {
        $draft = $this->ownRequest($request, $id);

        return $this->sendSuccess($this->verification->ownerSummary($request->user(), $draft));
    }

    public function save(Request $request, $id): JsonResponse
    {
        $draft = $this->ownRequest($request, $id);
        $owner = $request->user();

        $identity = $this->verification->identityOf($owner->id);
        $locked   = (bool) $identity?->isVerified();
        $hasLic   = $draft->license()->exists();

        $image = ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'];

        $validator = Validator::make($request->all(), [
            'nid_number'     => [$identity ? 'nullable' : 'required', 'string', 'max:40'],
            'nid_front'      => [$locked || $identity ? 'nullable' : 'required', ...$image],
            'nid_back'       => [$locked || $identity ? 'nullable' : 'required', ...$image],
            'license_number' => ['required', 'string', 'max:100'],
            'license_file'   => [$hasLic ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'license_expiry' => ['nullable', 'date', 'after:today'],
        ], [
            'nid_number.required'     => 'Enter the owner\'s NID number.',
            'nid_front.required'      => 'Upload the front of the NID card.',
            'nid_back.required'       => 'Upload the back of the NID card.',
            'nid_front.mimes'         => 'The NID photo must be a JPG, PNG or WEBP image.',
            'nid_back.mimes'          => 'The NID photo must be a JPG, PNG or WEBP image.',
            'nid_front.max'           => 'The NID photo must be under 5 MB.',
            'nid_back.max'            => 'The NID photo must be under 5 MB.',
            'license_number.required' => 'Enter the trade licence number.',
            'license_file.required'   => 'Upload the trade licence (photo or PDF).',
            'license_file.mimes'      => 'The trade licence must be a JPG, PNG, WEBP or PDF file.',
            'license_file.max'        => 'The trade licence file must be under 5 MB.',
            'license_expiry.after'    => 'This trade licence has expired. Upload a valid, renewed one.',
        ]);

        $validator->after(function ($v) use ($request, $locked) {
            if ($locked) {
                return;
            }
            $nid = $this->verification->cleanNid($request->input('nid_number'));

            if ($nid !== '' && ($error = $this->verification->nidError($nid))) {
                $v->errors()->add('nid_number', $error);
            }
        });

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 422);
        }

        $this->verification->save($owner, $draft, $request->only(['nid_number', 'license_number', 'license_expiry']), [
            'nid_front'    => $request->file('nid_front'),
            'nid_back'     => $request->file('nid_back'),
            'license_file' => $request->file('license_file'),
        ]);

        return $this->sendSuccess($this->verification->ownerSummary($owner, $draft->fresh()), 'Verification details saved.');
    }

    private function ownRequest(Request $request, $id): PropertyRequest
    {
        $req = PropertyRequest::query()->where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();

        abort_unless($req->isEditableByOwner(), 403, 'This application is already with our team and cannot be changed right now.');

        return $req;
    }
}
