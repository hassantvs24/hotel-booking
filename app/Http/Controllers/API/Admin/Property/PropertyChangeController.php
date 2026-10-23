<?php

namespace App\Http\Controllers\API\Admin\Property;

use App\Http\Controllers\BaseController;
use App\Models\Property;
use App\Models\PropertyChangeLog;
use App\Models\PropertyNameRequest;
use App\Services\Property\PropertyChangeReverter;
use App\Services\Property\PropertyNameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * For the platform admin: the new names that wait for approval, and the log of every change made to a property (with undo).
 * (An owner sees and takes back their own name request through the property routes below.)
 */
class PropertyChangeController extends BaseController
{
    public function __construct(
        private readonly PropertyNameService $names,
        private readonly PropertyChangeReverter $reverter,
    ) {}

    // ── Names ───────────────────────────────────────────────

    /** GET property-changes/name-requests?status=pending|approved|rejected|cancelled|all */
    public function nameRequests(Request $request): JsonResponse
    {
        $status = $request->input('status', 'pending');

        $rows = PropertyNameRequest::with(['property:id,name,user_id', 'user:id,name,email', 'decider:id,name'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(min(max((int) $request->input('per_page', 20), 5), 100));

        return $this->sendSuccess([
            'requests' => $rows->through(fn ($r) => [
                'id' => $r->id, 'status' => $r->status, 'old_name' => $r->old_name, 'new_name' => $r->new_name,
                'current_name' => $r->property?->name, 'property_id' => $r->property_id,
                'requested_by' => $r->user?->name, 'requested_by_email' => $r->user?->email, 'requested_at' => $r->created_at?->toIso8601String(),
                'decided_by' => $r->decider?->name, 'decided_at' => $r->decided_at?->toIso8601String(), 'reason' => $r->reason,
            ]),
            'pending_count' => PropertyNameRequest::where('status', 'pending')->count(),
        ]);
    }

    public function approveName(Request $request, int $id): JsonResponse
    {
        $this->names->approve(PropertyNameRequest::findOrFail($id), $request->user());

        return $this->sendSuccess(null, 'The new name is live.');
    }

    public function rejectName(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']], ['reason.required' => 'Write why the name is not accepted: the owner will read it.']);

        $this->names->reject(PropertyNameRequest::findOrFail($id), $request->user(), $data['reason']);

        return $this->sendSuccess(null, 'The owner has been told.');
    }

    // ── The log ─────────────────────────────────────────────

    /** GET property-changes/log?property_id=&section=&q=&page= */
    public function log(Request $request): JsonResponse
    {
        $rows = PropertyChangeLog::with('property:id,name')
            ->when($request->filled('property_id'), fn ($q) => $q->where('property_id', (int) $request->input('property_id')))
            ->when($request->filled('section'), fn ($q) => $q->where('section', $request->input('section')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], trim($request->input('q'))) . '%';
                $q->where(fn ($w) => $w->where('label', 'like', $like)->orWhere('old_value', 'like', $like)->orWhere('new_value', 'like', $like)->orWhere('user_name', 'like', $like));
            })
            ->latest('id')
            ->paginate(min(max((int) $request->input('per_page', 30), 5), 100));

        return $this->sendSuccess(['entries' => $rows->through(fn (PropertyChangeLog $e) => $this->present($e))]);
    }

    public function revert(Request $request, int $id): JsonResponse
    {
        $this->reverter->revert(PropertyChangeLog::findOrFail($id), $request->user());

        return $this->sendSuccess(null, 'Put back as it was.');
    }

    private function present(PropertyChangeLog $e): array
    {
        $why = $this->reverter->why($e);

        return [
            'id' => $e->id, 'at' => $e->created_at?->toIso8601String(), 'property_id' => $e->property_id, 'property' => $e->property?->name,
            'by' => $e->user_name ?? 'System', 'role' => $e->role, 'section' => $e->section, 'field' => $e->field, 'label' => $e->label, 'kind' => $e->kind,
            'old' => $this->shown($e->old_value, $e->kind), 'new' => $this->shown($e->new_value, $e->kind),
            'reverted_at' => $e->reverted_at?->toIso8601String(), 'can_revert' => $why === null, 'why_not' => $why,
            'photo_url' => $e->kind === 'photo' && ($e->meta['name'] ?? null) ? url('storage/' . ($e->meta['path'] ?? 'properties') . '/' . $e->meta['name']) : null,
        ];
    }

    /** A list is shown as its words; the rest as it is */
    private function shown(?string $v, string $kind): mixed
    {
        if ($kind === 'list' && $v !== null) {
            $list = json_decode($v, true);

            return is_array($list) ? $list : $v;
        }

        return $v;
    }

    // ── For the owner: their own name request ───────────────

    public function ownRequest(Request $request, Property $property): JsonResponse
    {
        abort_unless($request->user()->is_admin || $property->user_id === $request->user()->id, 404);

        $r = $property->pendingNameRequest()->first();

        return $this->sendSuccess(['request' => $r ? ['id' => $r->id, 'new_name' => $r->new_name, 'requested_at' => $r->created_at?->toIso8601String()] : null]);
    }

    public function cancelOwnRequest(Request $request, Property $property): JsonResponse
    {
        abort_unless($request->user()->is_admin || $property->user_id === $request->user()->id, 404);

        $r = $property->pendingNameRequest()->first();

        abort_unless($r, 404, 'There is no name request waiting.');

        $this->names->cancel($r, $request->user());

        return $this->sendSuccess(null, 'The request was taken back. The current name stays.');
    }
}
