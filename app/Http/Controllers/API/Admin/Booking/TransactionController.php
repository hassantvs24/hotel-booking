<?php

namespace App\Http\Controllers\API\Admin\Booking;

use App\Http\Controllers\BaseController;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $user  = auth()->user();
        $query = Transaction::with(['user:id,name,email,phone']);

        // Merchant scope — only transactions for their property's bookings
        if ($user->is_merchant && !$user->is_admin) {
            $propertyId = $user->associated_property?->id;
            if ($propertyId) {
                $query->whereHas('booking.room', fn($q) =>
                $q->where('property_id', $propertyId)
                );
            }
        }

        // Search — booking_id, transaction_reference, or guest name/email
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_id',             'LIKE', "%{$search}%")
                    ->orWhere('transaction_reference', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', fn($uq) =>
                    $uq->where('name',  'LIKE', "%{$search}%")
                        ->orWhere('email','LIKE', "%{$search}%")
                    );
            });
        }

        // Status filter — skip 'all' and empty values
        $status = $request->input('status');
        $allowedStatuses = ['pending', 'completed', 'failed', 'refunded'];
        if ($status && $status !== 'all' && in_array($status, $allowedStatuses)) {
            $query->where('status', $status);
        }

        // Sort — whitelist to prevent injection
        $allowedSorts = ['id', 'amount', 'status', 'created_at'];
        $sortBy  = in_array($request->input('sort_by'), $allowedSorts)
            ? $request->input('sort_by')
            : 'id';
        $sortDir = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';

        $transactions = $query
            ->orderBy($sortBy, $sortDir)
            ->paginate(
                (int) $request->input('per_page', 15),
                ['*'],
                'page',
                (int) $request->input('page', 1)
            );

        return $this->sendSuccess([
            'transactions' => $transactions,
        ]);
    }

    public function stats(): JsonResponse
    {
        $user  = auth()->user();
        $query = Transaction::query();

        if ($user->is_merchant && !$user->is_admin) {
            $propertyId = $user->associated_property?->id;
            if ($propertyId) {
                $query->whereHas('booking.room', fn($q) =>
                $q->where('property_id', $propertyId)
                );
            }
        }

        return $this->sendSuccess([
            'total'     => (clone $query)->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'pending'   => (clone $query)->where('status', 'pending')->count(),
            'failed'    => (clone $query)->where('status', 'failed')->count(),
            'revenue'   => (clone $query)->where('status', 'completed')->sum('amount'),
        ]);
    }
}
