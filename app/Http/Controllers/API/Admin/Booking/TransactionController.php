<?php

namespace App\Http\Controllers\API\Admin\Booking;

use App\Http\Controllers\BaseController;
use App\Models\RefundRequest;
use App\Models\Transaction;
use App\Services\Money\LedgerSync;
use App\Services\Money\VendorMoney;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $user  = auth()->user();
        $query = Transaction::with([
            'user:id,name,email,phone',
            'refundRequests:id,transaction_id,status,original_amount,requested_amount,approved_amount,cancellation_fee,currency,requested_at,completed_at',
        ]);

        // Merchant scope — only transactions for their properties' bookings
        if ($user->is_merchant && !$user->is_admin) {
            $propertyIds = $user->properties()->pluck('id');
            $query->whereHas('booking.room', fn($q) =>
            $q->whereIn('property_id', $propertyIds)
            );
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

        $refundQuery = RefundRequest::query()->where('status', RefundRequest::STATUS_COMPLETED);

        if ($user->is_merchant && !$user->is_admin) {
            $propertyIds = $user->properties()->pluck('id');
            $query->whereHas('booking.room', fn($q) =>
            $q->whereIn('property_id', $propertyIds)
            );
            $refundQuery->whereHas('booking.room', fn($q) =>
            $q->whereIn('property_id', $propertyIds)
            );
        }

        // What the card "income" means depends on who is looking, and comes from the money ledger (not from adding up payments):
        // the platform admin earns the commission; a vendor earns their share (the VAT and the service charge included).
        app(LedgerSync::class)->run(2.0);
        $money    = app(VendorMoney::class);
        $isVendor = !$user->is_admin;   // (the same rule as the dashboard: the platform admin, or else a vendor)
        $mine     = $isVendor ? $money->summary($user) : $money->platform();
        $income   = $isVendor ? $mine['share_paisa'] : $mine['commission_paisa'];
        $received = $mine['guest_paid_paisa'];

        return $this->sendSuccess([
            'total'        => (clone $query)->count(),
            'completed'    => (clone $query)->where('status', 'completed')->count(),
            'pending'      => (clone $query)->where('status', 'pending')->count(),
            'failed'       => (clone $query)->where('status', 'failed')->count(),
            'total_refund' => (float) (clone $refundQuery)->sum('approved_amount'),
            'received'     => $received / 100,                                  // what the guests paid, after refunds (the VAT and the service charge are in it)
            'income'       => $income / 100,                                    // admin: the commission; vendor: their share
            'income_label' => $isVendor ? 'Your share' : 'Your commission',
        ]);
    }
}
