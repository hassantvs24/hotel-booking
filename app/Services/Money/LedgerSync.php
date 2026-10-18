<?php

namespace App\Services\Money;

use App\Models\Booking;
use App\Models\LedgerEntry;
use App\Models\Property;
use App\Models\RefundRequest;
use App\Models\Room;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Writes the ledger from what really happened: a payment that came in, a refund that went out.
 *
 * It is safe to run as often as you like. Every line has a unique key, so the same payment or refund is never
 * written twice. It looks only at completed transactions and completed refunds (a booking that merely says
 * "paid" is not money), and only at ones from the ledger's start date onward.
 */
class LedgerSync
{
    public function __construct(private readonly MoneyRules $rules, private readonly MoneySplitter $splitter) {}

    /** When set, work stops once this many seconds have passed; what is left is picked up by the next run */
    private ?float $deadline = null;

    /**
     * @param float|null $seconds a page waiting for the answer passes a small budget; the scheduled run has none
     * @return array{payments:int,refunds:int} how many lines were added
     */
    public function run(?float $seconds = null): array
    {
        MoneyRules::forget();   // a long-running process must not use commission or settings it read earlier
        $this->deadline = $seconds === null ? null : microtime(true) + $seconds;

        try {
            return ['payments' => $this->syncPayments(), 'refunds' => $this->syncRefunds()];
        } finally {
            $this->deadline = null;
        }
    }

    private function outOfTime(): bool
    {
        return $this->deadline !== null && microtime(true) > $this->deadline;
    }

    public function syncPayments(): int
    {
        $from  = $this->rules->startsAt()->utc();
        $added = 0;

        $query = Transaction::query()
            ->whereIn('status', ['completed', 'refunded'])
            ->where(fn ($q) => $q
                ->where(fn ($w) => $w->where('status', 'completed')->where('updated_at', '>=', $from))
                ->orWhere(fn ($w) => $w->where('status', 'refunded')->where('created_at', '>=', $from)))
            ->whereNotExists(fn ($q) => $q->from('ledger_entries')->select(DB::raw(1))->where('ledger_entries.type', LedgerEntry::TYPE_PAYMENT)->whereColumn('ledger_entries.transaction_id', 'transactions.id'))
            ->orderBy('id')
            ->limit(200);

        $this->inBatches($query, function (Transaction $t) use (&$added) {
            try {
                $added += $this->recordPayment($t) ? 1 : 0;
            } catch (Throwable $e) {
                Log::error('[Ledger] Could not book payment ' . $t->id . ': ' . $e->getMessage());
            }
        });

        return $added;
    }

    public function syncRefunds(): int
    {
        $added = 0;

        $query = RefundRequest::query()
            ->where('status', RefundRequest::STATUS_COMPLETED)
            ->whereNotExists(fn ($q) => $q->from('ledger_entries')->select(DB::raw(1))->where('ledger_entries.type', LedgerEntry::TYPE_REFUND)->whereColumn('ledger_entries.refund_request_id', 'refund_requests.id'))
            ->orderBy('completed_at')
            ->orderBy('id')
            ->limit(200);

        $this->inBatches($query, function (RefundRequest $r) use (&$added) {
            try {
                $added += $this->recordRefund($r) ? 1 : 0;
            } catch (Throwable $e) {
                Log::error('[Ledger] Could not book refund ' . $r->id . ': ' . $e->getMessage());
            }
        });

        return $added;
    }

    public function recordPayment(Transaction $t): ?LedgerEntry
    {
        $key = 'payment:txn:' . $t->id;
        if (LedgerEntry::where('unique_key', $key)->exists()) {
            return null;
        }

        $booking  = Booking::withTrashed()->where('booking_number', $t->booking_id)->first();
        $room     = $booking ? $this->find(Room::class, $booking->room_id) : null;
        $property = $room ? $this->find(Property::class, $room->property_id) : null;
        $vendor   = $property ? $this->find(User::class, $property->user_id) : null;

        $flags = [];
        if (!$booking)  { $flags[] = 'booking_missing'; }
        if (!$property) { $flags[] = 'property_missing'; }
        if (!$vendor)   { $flags[] = 'vendor_missing'; }

        $gross = MoneySplitter::toPaisa($t->amount);
        if ($booking && MoneySplitter::toPaisa($booking->amount) !== $gross) {
            $flags[] = 'amount_mismatch';
        }

        $tax = ($booking && $booking->subtotal_amount !== null) ? [
            'base'    => MoneySplitter::toPaisa($booking->subtotal_amount),
            'service' => MoneySplitter::toPaisa($booking->service_charge_amount),
            'vat'     => MoneySplitter::toPaisa($booking->vat_amount),
        ] : null;

        $percent   = $this->rules->commissionPercentFor($property);
        $serviceTo = $this->rules->serviceChargeTo();
        $split     = $this->splitter->payment($gross, $tax, $percent, $serviceTo);

        // A payment still in "completed" was last touched when it was paid. One already "refunded" was touched again by the
        // refund, so only its created_at is left to go by.
        $at = CarbonImmutable::parse($t->status === 'completed' ? $t->updated_at : $t->created_at)->utc();

        [$fee, $risky] = $this->bankDetails($t);
        if ($risky) {
            $flags[] = 'gateway_risk';
        }

        return $this->write([
            'unique_key'             => $key,
            'type'                   => LedgerEntry::TYPE_PAYMENT,
            'transaction_id'         => $t->id,
            'booking_id'             => $booking?->id,
            'booking_number'         => $t->booking_id,
            'property_id'            => $property?->id,
            'property_name'          => $property?->name,
            'vendor_id'              => $vendor?->id,
            'vendor_name'            => $vendor?->name,
            'checkout_date'          => $booking?->checkout,
            'occurred_at'            => $at,
            'occurred_on'            => $at->setTimezone(MoneyRules::TZ)->toDateString(),
            'gross_paisa'            => $gross,
            'base_paisa'             => $split['base'],
            'service_paisa'          => $split['service'],
            'vat_paisa'              => $split['vat'],
            'platform_service_paisa' => $split['platform_service'],
            'commission_paisa'       => $split['commission'],
            'vendor_paisa'           => $split['vendor'],
            'gateway_fee_paisa'      => $fee,
            'commission_percent'     => $percent,
            'service_charge_to'      => $serviceTo,
            'is_test'                => str_starts_with((string) $t->transaction_reference, 'LOCAL-TEST'),
            'flags'                  => array_values(array_unique(array_merge($flags, $split['flags']))) ?: null,
        ]);
    }

    public function recordRefund(RefundRequest $r): ?LedgerEntry
    {
        // Two runs at the same moment must not work out two refunds of one payment side by side: the second waits for the
        // payment line here, then sees the first refund line and carries on from it.
        return DB::transaction(function () use ($r) {
            LedgerEntry::where('unique_key', 'payment:txn:' . $r->transaction_id)->lockForUpdate()->first();

            return $this->bookRefund($r);
        });
    }

    private function bookRefund(RefundRequest $r): ?LedgerEntry
    {
        $key = 'refund:rr:' . $r->id;
        if (LedgerEntry::where('unique_key', $key)->exists()) {
            return null;
        }

        // A refund of a payment that is not in the ledger (paid before the start date) is not in the ledger either.
        $payment = LedgerEntry::where('unique_key', 'payment:txn:' . $r->transaction_id)->first();
        if (!$payment) {
            return null;
        }

        $earlier = LedgerEntry::where('type', LedgerEntry::TYPE_REFUND)->where('transaction_id', $r->transaction_id)->get();
        $already = [];
        foreach (['gross', 'base', 'service', 'vat', 'platform_service', 'commission', 'vendor'] as $part) {
            $already[$part] = -1 * (int) $earlier->sum($part . '_paisa');
        }

        $approved   = MoneySplitter::toPaisa($r->approved_amount);
        $cumulative = $already['gross'] + $approved;
        // A refund inherits the payment's flags: if the payment is held for a look, so is what was taken back from it.
        $flags      = array_values(array_unique(array_merge($payment->flags ?? [], $cumulative > $payment->gross_paisa ? ['over_refund'] : [])));

        $now = $this->splitter->refund([
            'gross'            => $payment->gross_paisa,
            'base'             => $payment->base_paisa,
            'service'          => $payment->service_paisa,
            'vat'              => $payment->vat_paisa,
            'platform_service' => $payment->platform_service_paisa,
            'commission'       => $payment->commission_paisa,
            'vendor'           => $payment->vendor_paisa,
        ], $cumulative, $already);

        $at = CarbonImmutable::parse($r->completed_at ?? $r->updated_at)->utc();

        return $this->write([
            'unique_key'             => $key,
            'type'                   => LedgerEntry::TYPE_REFUND,
            'transaction_id'         => $r->transaction_id,
            'refund_request_id'      => $r->id,
            'booking_id'             => $payment->booking_id,
            'booking_number'         => $payment->booking_number,
            'property_id'            => $payment->property_id,
            'property_name'          => $payment->property_name,
            'vendor_id'              => $payment->vendor_id,
            'vendor_name'            => $payment->vendor_name,
            'checkout_date'          => $payment->checkout_date,
            'occurred_at'            => $at,
            'occurred_on'            => $at->setTimezone(MoneyRules::TZ)->toDateString(),
            'gross_paisa'            => -$now['gross'],
            'base_paisa'             => -$now['base'],
            'service_paisa'          => -$now['service'],
            'vat_paisa'              => -$now['vat'],
            'platform_service_paisa' => -$now['platform_service'],
            'commission_paisa'       => -$now['commission'],
            'vendor_paisa'           => -$now['vendor'],
            'commission_percent'     => $payment->commission_percent,
            'service_charge_to'      => $payment->service_charge_to,
            'is_test'                => $payment->is_test,
            'flags'                  => $flags ?: null,
        ]);
    }

    /**
     * Work through the rows that still have no ledger line, 200 at a time. Each pass only sees rows not yet booked,
     * so it stops when a pass books nothing (a row that cannot be booked would otherwise make it loop for ever).
     */
    private function inBatches($query, callable $each): void
    {
        $seen = [];

        while (true) {
            $rows = $query->clone()->get()->reject(fn ($row) => isset($seen[$row->getKey()]));
            if ($rows->isEmpty()) {
                return;
            }

            foreach ($rows as $row) {
                if ($this->outOfTime()) {
                    return;
                }

                $seen[$row->getKey()] = true;
                $each($row);
            }
        }
    }

    /**
     * What SSLCommerz kept (null when it did not say) and whether it called the payment risky, from its own answer
     * saved with the payment. One bank payment can cover several bookings (a cart): the fee is shared out between
     * them in proportion, from the running total, so the shares add up to the exact fee.
     *
     * @return array{0:?int,1:bool}
     */
    private function bankDetails(Transaction $t): array
    {
        $meta  = is_string($t->meta) ? json_decode($t->meta, true) : null;
        $bank  = is_array($meta) ? ($meta['validated'] ?? null) : null;
        $risky = is_array($bank) && (string) ($bank['risk_level'] ?? '0') === '1';

        if (!is_array($bank) || !isset($bank['amount'], $bank['store_amount']) || !is_numeric($bank['amount']) || !is_numeric($bank['store_amount'])) {
            return [null, $risky];
        }

        $paid = MoneySplitter::toPaisa($bank['amount']);
        $kept = MoneySplitter::toPaisa($bank['store_amount']);
        $fee  = $paid - $kept;

        if ($paid <= 0 || $fee < 0 || $fee > $paid) {
            return [null, $risky];
        }

        // the bookings this one payment covered, in the order they were written down
        $siblings = Transaction::query()
            ->where('transaction_reference', $t->transaction_reference)
            ->whereIn('status', ['completed', 'refunded'])
            ->orderBy('id')
            ->pluck('amount', 'id');

        $total = MoneySplitter::toPaisa($siblings->sum());
        if ($siblings->count() <= 1 || $total !== $paid) {
            return [$siblings->count() <= 1 ? $fee : (int) round($fee * MoneySplitter::toPaisa($t->amount) / max(1, $paid)), $risky];
        }

        $before = 0;
        foreach ($siblings as $id => $amount) {
            if ($id === $t->id) {
                break;
            }
            $before += MoneySplitter::toPaisa($amount);
        }
        $upTo = $before + MoneySplitter::toPaisa($t->amount);

        return [intdiv($fee * $upTo, $paid) - intdiv($fee * $before, $paid), $risky];
    }

    private function write(array $row): ?LedgerEntry
    {
        // The seven parts must add up. If they ever do not, stop: a wrong line is worse than a missing one.
        $sum = $row['vat_paisa'] + $row['platform_service_paisa'] + $row['commission_paisa'] + $row['vendor_paisa'];
        if ($sum !== $row['gross_paisa']) {
            Log::error('[Ledger] Parts do not add up for ' . $row['unique_key'], $row);
            return null;
        }

        try {
            $entry = DB::transaction(fn () => LedgerEntry::firstOrCreate(['unique_key' => $row['unique_key']], $row));

            // when another run booked it a moment earlier, this run did not add anything
            return $entry->wasRecentlyCreated ? $entry : null;
        } catch (UniqueConstraintViolationException) {
            return null;   // the other run booked it first
        }
    }

    /** @param class-string $class */
    private function find(string $class, mixed $id): mixed
    {
        if (!$id) {
            return null;
        }

        return in_array(SoftDeletes::class, class_uses_recursive($class), true)
            ? $class::withTrashed()->find($id)
            : $class::find($id);
    }
}
