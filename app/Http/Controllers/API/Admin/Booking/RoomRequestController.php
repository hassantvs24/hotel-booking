<?php

namespace App\Http\Controllers\API\Admin\Booking;

use App\Events\Booking\BidStatusUpdated;
use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomRequest;
use App\Models\RoomRequestAccepted;
use App\Repositories\Admin\RoomRequestRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RoomRequestController extends BaseController
{
    // Same windows the guest-facing portal controller uses
    // How long an offer waits for an answer, and how long a guest has to pay: see config/bidding.php

    // current status => statuses it may move to. Declined/Timeout/Done are final.
    private const TRANSITIONS = [
        'Pending'  => ['Approved', 'Counter', 'Declined'],
        'Counter'  => ['Counter', 'Declined'],
        'Approved' => ['Declined', 'Done'],
    ];

    public function index(Request $request, RoomRequestRepository $repo): JsonResponse
    {
        $propertyIds = $this->accessiblePropertyIds();

        $query = [
            'with'     => ['room', 'room.property', 'user', 'property'],
            // One line per negotiation (guest + room + stay dates): its latest offer.
            // The earlier offers come back as `history`.
            'where'    => array_merge(
                [fn ($q) => $q->latestPerNegotiation()],
                $propertyIds === null ? [] : [fn ($q) => $q->whereIn('property_id', $propertyIds)],
            ),
            'order_by' => 'id',
            'order'    => 'DESC',
            'per_page' => (int) $request->input('per_page', 15),
            'page'     => (int) $request->input('page', 1),
            'search'   => (string) $request->input('search', ''),
            'filters'  => array_filter([
                'status' => $request->input('status'),
            ]),
        ];

        $roomRequests = $repo->paginate($query);
        $roomRequests->getCollection()->each(
            fn ($bid) => $bid->setAttribute('history', $this->history($bid))
        );

        return $this->sendSuccess(['room_requests' => $roomRequests]);
    }

    public function show($id): JsonResponse
    {
        $roomRequest = $this->scoped()->with([
            'room',
            'room.property',
            'room.primaryImage',
            'user',
            'property',
            'acceptedRequest',
        ])->findOrFail($id);

        $roomRequest->setAttribute('history', $this->history($roomRequest));

        return $this->sendSuccess($roomRequest);
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status'     => 'required|string|in:Approved,Counter,Declined,Done',
            'base_price' => 'required_if:status,Counter|nullable|numeric|min:1',
        ]);

        $roomRequest = $this->scoped()->with(['room', 'room.property', 'user'])->findOrFail($id);
        $status      = $request->input('status');
        $current     = $roomRequest->status;

        // The guest has made a newer offer for this stay — only the latest one is open to action
        if ($status !== 'Declined' && $roomRequest->isSuperseded()) {
            return $this->sendError('This offer was replaced by the guest\'s newer offer. Please act on the latest one.', [], 422);
        }

        if (!in_array($status, self::TRANSITIONS[$current] ?? [], true)) {
            return $this->sendError("A {$current} bid cannot be changed to {$status}.", [], 422);
        }

        // Declining is always allowed (clean-up). Done is settled by a paid booking,
        // not by the clock. Anything else on a lapsed bid is too late.
        if (!in_array($status, ['Declined', 'Done'], true) && $roomRequest->isExpired()) {
            return $this->sendError('This bid has expired and can no longer be updated.', [], 422);
        }

        if ($status === 'Done' && !Booking::where('room_request_id', $roomRequest->id)
                ->where('payment_status', 'paid')->exists()) {
            return $this->sendError('This bid has no paid booking, so it cannot be marked as done.', [], 422);
        }

        DB::beginTransaction();
        try {
            $basePrice = $request->input('base_price');

            if ($status === 'Approved') {
                $expiresAt = Carbon::now()->addHours(config('bidding.payment_hours'));

                RoomRequestAccepted::updateOrCreate(
                    ['room_requests_id' => $id],
                    [
                        'property_id'             => $roomRequest->property_id,
                        'request_expiration_time' => $expiresAt,
                    ]
                );
                $roomRequest->update([
                    'status'                  => $status,
                    'request_expiration_time' => $expiresAt,
                ]);

            } elseif ($status === 'Counter') {
                $expiresAt = Carbon::now()->addHours(config('bidding.pending_hours'));

                RoomRequestAccepted::updateOrCreate(
                    ['room_requests_id' => $id],
                    [
                        'property_id'             => $roomRequest->property_id,
                        'request_expiration_time' => $expiresAt,
                    ]
                );
                $roomRequest->update([
                    'status'                  => $status,
                    'counter_price'           => $basePrice,
                    'request_expiration_time' => $expiresAt,
                ]);

            } elseif ($status === 'Declined') {
                RoomRequestAccepted::where('room_requests_id', $id)->delete();
                $roomRequest->update(['status' => $status]);

            } elseif ($status === 'Done') {
                $roomRequest->update(['status' => $status]);
            }

            DB::commit();

            // ── Fire real-time event — notifies guest + admin ──
            $fresh = $roomRequest->fresh(['room', 'room.property', 'user']);
            $event = new BidStatusUpdated($fresh);
            $event->notifyAll();
            try {
                event($event);   // live push is best-effort — the change is already saved
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Bid status broadcast failed (is Reverb running?): ' . $e->getMessage());
            }

            return $this->sendSuccess(
                $fresh->load(['acceptedRequest']),
                'Bid status updated successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage());
        }
    }

    public function destroy(RoomRequestRepository $repo, $id): JsonResponse
    {
        // Resolved outside the try so a 403/404 isn't swallowed into a generic error
        $roomRequest = $this->scoped()->findOrFail($id);

        try {
            $room = Room::find($roomRequest->room_id);
            if ($room) {
                $room->update([
                    'status'          => 'Available',
                    'booked_date'     => null,
                    'booked_off_date' => null,
                ]);
            }

            $repo->delete($roomRequest->id);

            return $this->sendSuccess(null, 'Bid deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function stats(): JsonResponse
    {
        $counts = $this->scoped()
            ->latestPerNegotiation()
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $stats = [
            'total'    => $counts->sum(),
            'pending'  => (int) ($counts['Pending']  ?? 0),
            'approved' => (int) ($counts['Approved'] ?? 0),
            'counter'  => (int) ($counts['Counter']  ?? 0),
            'declined' => (int) ($counts['Declined'] ?? 0),
            'done'     => (int) ($counts['Done']     ?? 0),
        ];

        return $this->sendSuccess($stats);
    }

    /** The earlier offers of the same negotiation, for the detail view */
    private function history(RoomRequest $bid)
    {
        return $bid->earlierOffers()->get([
            'id', 'bid_number', 'status', 'discount_price', 'counter_price',
            'request_expiration_time', 'created_at', 'updated_at',
        ]);
    }

    // ══════════════════════════════════════════════════════
    //  ACCESS SCOPE
    //  Admin: every bid. Vendor: bids on properties they own.
    //  Anyone else (e.g. a guest account): forbidden.
    // ══════════════════════════════════════════════════════

    /** null = unrestricted (admin) */
    private function accessiblePropertyIds(): ?Collection
    {
        $user = auth()->user();

        if ($user->is_admin) {
            return null;
        }

        if ($user->is_merchant) {
            return $user->properties()->pluck('id');
        }

        abort(403, 'Unauthorized Action');
    }

    private function scoped(): Builder
    {
        $propertyIds = $this->accessiblePropertyIds();

        return RoomRequest::query()
            ->when($propertyIds !== null, fn ($q) => $q->whereIn('property_id', $propertyIds));
    }
}
