<?php

namespace App\Http\Controllers\API\Admin\Booking;

use App\Http\Controllers\BaseController;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $user  = auth()->user();
        $query = Transaction::with(['user:id,name,email,phone']);

        // Merchant scope — only their property transactions
        if ($user->is_merchant && !$user->is_admin) {
            $query->whereHas('booking.room', function ($q) use ($user) {
                $q->where('property_id', $user->associated_property->id);
            });
        }

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_id',            'LIKE', "%{$search}%")
                    ->orWhere('transaction_reference','LIKE', "%{$search}%")
                    ->orWhereHas('user', fn($q) => $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email','LIKE', "%{$search}%"));
            });
        }

        // Status filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Sort
        $sortBy  = in_array($request->input('sort_by'), ['id', 'amount', 'status', 'created_at'])
            ? $request->input('sort_by')
            : 'id';
        $sortDir = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';

        $transactions = $query
            ->orderBy($sortBy, $sortDir)
            ->paginate(
                $request->input('per_page', 15),
                ['*'],
                'page',
                $request->input('page', 1)
            );

        return $this->sendSuccess([
            'transactions' => $transactions,
        ]);
    }

    public function stats(): JsonResponse
    {
        $user = auth()->user();

        $query = Transaction::query();

        if ($user->is_merchant && !$user->is_admin) {
            $query->whereHas('booking.room', function ($q) use ($user) {
                $q->where('property_id', $user->associated_property->id);
            });
        }

        $stats = [
            'total'     => (clone $query)->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'pending'   => (clone $query)->where('status', 'pending')->count(),
            'failed'    => (clone $query)->where('status', 'failed')->count(),
            'revenue'   => (clone $query)->where('status', 'completed')->sum('amount'),
        ];

        return $this->sendSuccess($stats);
    }
}
