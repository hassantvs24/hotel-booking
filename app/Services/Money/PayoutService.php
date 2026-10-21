<?php

namespace App\Services\Money;

use App\Mail\PayoutPaidMail;
use App\Models\LedgerEntry;
use App\Models\PayoutStatement;
use App\Models\PayoutStatementLine;
use App\Models\User;
use App\Models\VendorPayoutAccount;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Pays the vendors. It never moves money by itself: it prepares a statement (what is owed), the admin sends the money
 * outside the system and then marks the statement PAID.
 *
 * The rules that keep the money right:
 *  - Only lines that are READY (checked out, wait over, nothing held) go into a statement.
 *  - A ledger line is in one live statement at most. The database enforces it (unique ledger_entry_id + active).
 *  - Before a statement is marked PAID, every line is checked again: if a booking changed since (deleted, held...) it is refused.
 *  - A PAID statement is locked. A mistake is fixed by reversing it; its lines become payable again.
 *  - A refund that reaches the vendor after a payout is a negative line and is taken off the next statement.
 */
class PayoutService
{
    public function __construct(private readonly MoneyRules $rules, private readonly MoneyReport $report, private readonly MoneyChecks $checks) {}

    /** The smallest balance that is paid out (smaller balances wait for the next payout) */
    public function minimumPaisa(): int
    {
        return max(0, MoneySplitter::toPaisa($this->rules->get('payout_minimum')));
    }

    /**
     * What is ready to pay, per vendor, and not yet in any statement.
     *
     * @return array<int,array{vendor_id:int,vendor_name:string,bookings:int,lines:int,amount_paisa:int,payable:bool,reason:?string}>
     */
    public function candidates(?int $vendorId = null): array
    {
        $this->checks->forget();
        $min = $this->minimumPaisa();

        $rows = $this->freeReadyLines($vendorId)
            ->selectRaw('vendor_id, max(vendor_name) as vendor_name, count(distinct booking_number) as bookings, count(*) as line_count, sum(vendor_paisa) as amount')
            ->groupBy('vendor_id')->orderByRaw('max(vendor_name)')->get();

        return $rows->map(function ($r) use ($min) {
            $amount = (int) $r->amount;
            $reason = $amount <= 0 ? 'Nothing to pay: the refunds taken back are as much as the payments.'
                : ($amount < $min ? 'Below the minimum payout: it waits for the next payout.' : null);

            return [
                'vendor_id' => (int) $r->vendor_id, 'vendor_name' => $r->vendor_name ?: 'Unknown vendor',
                'bookings' => (int) $r->bookings, 'lines' => (int) $r->line_count, 'amount_paisa' => $amount,
                'payable' => $reason === null, 'reason' => $reason,
            ];
        })->all();
    }

    /**
     * Draft statements for every vendor that has enough ready money (or for one vendor).
     *
     * @return Collection<int,PayoutStatement> the statements that were created
     */
    public function generate(User $by, ?int $vendorId = null): Collection
    {
        $created = collect();

        foreach ($this->candidates($vendorId) as $c) {
            if (!$c['payable']) {
                continue;
            }

            try {
                $s = $this->createDraft($c['vendor_id'], $by);
            } catch (UniqueConstraintViolationException $e) {
                continue;   // another admin took some of these lines at the same moment: they have their statement
            }

            if ($s) {
                $created->push($s);
            }
        }

        return $created;
    }

    public function createDraft(int $vendorId, User $by): ?PayoutStatement
    {
        $this->checks->forget();

        try {
            return $this->createDraftNow($vendorId, $by);
        } catch (UniqueConstraintViolationException $e) {
            return null;   // another admin took some of these lines at the same moment: the database refused the second claim
        }
    }

    private function createDraftNow(int $vendorId, User $by): ?PayoutStatement
    {
        return DB::transaction(function () use ($vendorId, $by) {
            $entries = $this->freeReadyLines($vendorId)->orderBy('id')->lockForUpdate()->get();
            $total   = (int) $entries->sum('vendor_paisa');

            if ($entries->isEmpty() || $total <= 0 || $total < $this->minimumPaisa()) {
                return null;
            }

            $statement = PayoutStatement::create([
                'vendor_id' => $vendorId, 'vendor_name' => $entries->first()->vendor_name, 'status' => PayoutStatement::DRAFT,
                'payable_until' => $this->report->payableCutoff(),
                'created_by' => $by->id, 'created_by_name' => $by->name,
            ]);
            $statement->update(['number' => 'ST-' . str_pad((string) $statement->id, 6, '0', STR_PAD_LEFT)]);

            // written in batches (a vendor can have thousands of bookings in a week); the database still refuses a line that is already live
            $now = now();
            foreach ($entries->chunk(500) as $chunk) {
                PayoutStatementLine::insert($chunk->map(fn ($e) => [
                    'statement_id' => $statement->id, 'ledger_entry_id' => $e->id, 'active' => 1,
                    'booking_number' => $e->booking_number, 'type' => $e->type, 'checkout_date' => $e->checkout_date?->toDateString(),
                    'property_name' => $e->property_name, 'gross_paisa' => $e->gross_paisa, 'base_paisa' => $e->base_paisa, 'service_paisa' => $e->service_paisa, 'vat_paisa' => $e->vat_paisa,
                    'platform_service_paisa' => $e->platform_service_paisa, 'commission_paisa' => $e->commission_paisa, 'commission_percent' => $e->commission_percent, 'vendor_paisa' => $e->vendor_paisa,
                    'created_at' => $now, 'updated_at' => $now,
                ])->all());
            }

            $this->recount($statement);
            $this->event($statement, 'created', 'Statement prepared with ' . $statement->lines_count . ' lines.', $by);

            return $statement->fresh();
        });
    }

    /** Take one booking out of a draft and hold its money back until the admin lifts the hold */
    public function holdBooking(PayoutStatement $statement, int $bookingNumber, string $note, User $by): PayoutStatement
    {
        return DB::transaction(function () use ($statement, $bookingNumber, $note, $by) {
            $locked = PayoutStatement::lockForUpdate()->findOrFail($statement->id);
            $this->mustBeDraft($locked);

            if (!$locked->lines()->where('booking_number', $bookingNumber)->where('active', 1)->exists()) {
                $this->fail('booking_number', 'That booking is not in this statement.');
            }

            $this->hold($bookingNumber, $note, $by);

            foreach ($locked->lines()->where('booking_number', $bookingNumber)->where('active', 1)->get() as $line) {
                $line->delete();
            }

            $this->event($locked, 'booking_removed', "Booking #{$bookingNumber} taken out and put on hold: {$note}", $by);
            $this->recount($locked);

            if ($locked->lines_count === 0) {
                $this->cancelLocked($locked, $by, 'No bookings are left in this statement.');
            }

            return $locked->fresh();
        });
    }

    /** Hold the vendor's money for a booking (not allowed once the vendor has been paid for it) */
    public function hold(int $bookingNumber, string $note, User $by): void
    {
        $paid = DB::table('payout_statement_lines as pl')->join('payout_statements as ps', 'ps.id', '=', 'pl.statement_id')
            ->where('pl.booking_number', $bookingNumber)->where('pl.active', 1)->where('ps.status', PayoutStatement::PAID)->exists();

        if ($paid) {
            $this->fail('booking_number', 'The vendor has already been paid for this booking, so it cannot be held.');
        }

        if (!LedgerEntry::where('booking_number', $bookingNumber)->exists()) {
            $this->fail('booking_number', 'This booking is not in the money records.');
        }

        DB::table('money_holds')->updateOrInsert(['booking_number' => $bookingNumber], [
            'note' => $note, 'held_by' => $by->id, 'held_by_name' => $by->name, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->checks->forget();
    }

    public function unhold(int $bookingNumber, string $note, User $by): void
    {
        if (!DB::table('money_holds')->where('booking_number', $bookingNumber)->exists()) {
            $this->fail('booking_number', 'This booking is not on hold.');
        }

        DB::table('money_holds')->where('booking_number', $bookingNumber)->delete();
        $this->checks->forget();

        foreach (DB::table('payout_statement_lines')->where('booking_number', $bookingNumber)->distinct()->pluck('statement_id') as $id) {
            $this->event(PayoutStatement::find($id), 'hold_lifted', "Hold on booking #{$bookingNumber} lifted: {$note}", $by);
        }
    }

    public function cancelDraft(PayoutStatement $statement, User $by, string $note): PayoutStatement
    {
        return DB::transaction(function () use ($statement, $by, $note) {
            $locked = PayoutStatement::lockForUpdate()->findOrFail($statement->id);
            $this->mustBeDraft($locked);
            $this->cancelLocked($locked, $by, $note);

            return $locked->fresh();
        });
    }

    /**
     * The admin has sent the money. Records how, locks the statement and tells the vendor.
     *
     * @param array{method:string,reference:string,note?:?string,confirm_account?:bool} $d
     */
    public function markPaid(PayoutStatement $statement, User $by, array $d): PayoutStatement
    {
        $paid = DB::transaction(function () use ($statement, $by, $d) {
            $locked = PayoutStatement::lockForUpdate()->findOrFail($statement->id);
            $this->mustBeDraft($locked);

            if (!in_array($d['method'], PayoutStatement::METHODS, true)) {
                $this->fail('method', 'Choose how the money was sent.');
            }
            if (mb_strlen(trim((string) ($d['reference'] ?? ''))) < 3) {
                $this->fail('reference', 'Write the bank or bKash transaction number of this payment.');
            }

            // Everything is looked at again, exactly as it is now
            $this->checks->forget();
            $this->report->forgetPaidOut();
            $lines   = $locked->lines()->where('active', 1)->get();
            $entries = LedgerEntry::whereIn('id', $lines->pluck('ledger_entry_id'))->lockForUpdate()->get()->keyBy('id');

            if ($lines->isEmpty() || $entries->count() !== $lines->count()) {
                $this->fail('statement', 'This statement has no lines to pay.');
            }

            foreach ($lines as $l) {
                $e = $entries[$l->ledger_entry_id];

                if ((int) $e->vendor_id !== (int) $locked->vendor_id || (int) $e->vendor_paisa !== (int) $l->vendor_paisa || (int) $e->gross_paisa !== (int) $l->gross_paisa) {
                    $this->fail('statement', 'The money records of this statement no longer match it. Cancel it and prepare a new one.');
                }
            }

            $notReady = LedgerEntry::whereIn('id', $lines->pluck('ledger_entry_id'))->whereRaw('not ' . $this->report->readySql())->pluck('booking_number')->unique()->values();

            if ($notReady->isNotEmpty()) {
                $this->fail('statement', 'These bookings changed or are on hold since the statement was prepared: #' . $notReady->implode(', #') . '. Take them out first.');
            }

            $total = (int) $lines->sum('vendor_paisa');

            if ($total <= 0) {
                $this->fail('statement', 'There is nothing to pay in this statement.');
            }

            // The account may have been changed on purpose... or by someone who should not. The admin must say they saw it.
            $account = VendorPayoutAccount::where('user_id', $locked->vendor_id)->first();
            $changed = $account && ($account->updated_at->gt($locked->created_at) || $account->updated_at->gt(now()->subDays(3)))
                && DB::table('vendor_payout_account_changes')->where('user_id', $locked->vendor_id)->count() > 1;

            if ($changed && empty($d['confirm_account'])) {
                throw ValidationException::withMessages(['confirm_account' => 'The vendor changed their payout account recently. Check with the vendor, then confirm.']);
            }

            $locked->update([
                'status' => PayoutStatement::PAID, 'paid_at' => now(), 'paid_by' => $by->id, 'paid_by_name' => $by->name,
                'method' => $d['method'], 'reference' => trim($d['reference']), 'note' => ($d['note'] ?? null) ?: null,
                'account_snapshot' => $account?->details(), 'account_recently_changed' => (bool) $changed,
            ]);
            $this->event($locked, 'paid', "Paid {$this->taka($total)} by {$d['method']}, reference " . trim($d['reference']) . '.', $by);
            $this->report->forgetPaidOut();

            return $locked->fresh();
        });

        $this->tellVendor($paid);

        return $paid->fresh();
    }

    /** A paid statement was a mistake (the transfer failed, or went to the wrong place). It stays on record; its lines become payable again. */
    public function reverse(PayoutStatement $statement, User $by, string $reason): PayoutStatement
    {
        if (mb_strlen(trim($reason)) < 5) {
            $this->fail('reason', 'Write why this payment is being reversed (at least a few words).');
        }

        return DB::transaction(function () use ($statement, $by, $reason) {
            $locked = PayoutStatement::lockForUpdate()->findOrFail($statement->id);

            if (!$locked->isPaid()) {
                $this->fail('statement', 'Only a paid statement can be reversed.');
            }

            $locked->update(['status' => PayoutStatement::REVERSED, 'reversed_at' => now(), 'reversed_by' => $by->id, 'reversed_by_name' => $by->name, 'reverse_reason' => trim($reason)]);
            $locked->lines()->where('active', 1)->update(['active' => null]);   // frees the lines (the only change a line may ever get), in one go
            $this->event($locked, 'reversed', trim($reason), $by);
            $this->report->forgetPaidOut();

            return $locked->fresh();
        });
    }

    /** Facts that should always hold, for the health check: [] when everything is in order */
    public function problems(): array
    {
        $out = [];

        $twice = count(DB::select('select ledger_entry_id from payout_statement_lines where active = 1 group by ledger_entry_id having count(*) > 1'));
        if ($twice) { $out[] = "{$twice} ledger line(s) are in more than one live statement."; }

        $wrongTotals = DB::select("select ps.id from payout_statements ps
            left join payout_statement_lines pl on pl.statement_id = ps.id and pl.active = 1
            where ps.status in ('draft','paid') group by ps.id, ps.vendor_paisa having coalesce(sum(pl.vendor_paisa), 0) <> ps.vendor_paisa");
        if ($wrongTotals) { $out[] = count($wrongTotals) . ' statement(s) do not add up to their lines.'; }

        $moreThanOwed = DB::select("select pl.ledger_entry_id from payout_statement_lines pl
            left join ledger_entries le on le.id = pl.ledger_entry_id
            where le.id is null or le.vendor_paisa <> pl.vendor_paisa or le.gross_paisa <> pl.gross_paisa limit 1");
        if ($moreThanOwed) { $out[] = 'A statement line does not match its money record.'; }

        $wrongVendor = DB::select('select pl.id from payout_statement_lines pl join payout_statements ps on ps.id = pl.statement_id join ledger_entries le on le.id = pl.ledger_entry_id where le.vendor_id <> ps.vendor_id limit 1');
        if ($wrongVendor) { $out[] = "A statement holds another vendor's money."; }

        return $out;
    }

    // ────────────────────────────────────────────────────────

    /** Ledger lines that are ready to pay and in no live statement */
    private function freeReadyLines(?int $vendorId)
    {
        $q = LedgerEntry::query()->whereNotNull('vendor_id')
            ->whereRaw($this->report->readySql())
            ->whereRaw('not ' . MoneyReport::inStatementSql());

        if ($vendorId) {
            $q->where('vendor_id', $vendorId);
        }

        return $q;
    }

    private function recount(PayoutStatement $s): void
    {
        $lines = $s->lines()->where('active', 1)->get();

        $s->update([
            'bookings_count' => $lines->pluck('booking_number')->unique()->count(),
            'lines_count' => $lines->count(),
            'gross_paisa' => (int) $lines->sum('gross_paisa'), 'base_paisa' => (int) $lines->sum('base_paisa'), 'service_paisa' => (int) $lines->sum('service_paisa'), 'vat_paisa' => (int) $lines->sum('vat_paisa'),
            'platform_service_paisa' => (int) $lines->sum('platform_service_paisa'), 'commission_paisa' => (int) $lines->sum('commission_paisa'),
            'vendor_paisa' => (int) $lines->sum('vendor_paisa'),
        ]);
    }

    private function cancelLocked(PayoutStatement $s, User $by, string $note): void
    {
        $s->update(['status' => PayoutStatement::CANCELLED, 'cancelled_at' => now(), 'cancelled_by' => $by->id, 'cancelled_by_name' => $by->name]);
        $s->lines()->where('active', 1)->update(['active' => null]);   // frees the lines (the only change a line may ever get), in one go
        $this->event($s, 'cancelled', $note, $by);
    }

    private function mustBeDraft(PayoutStatement $s): void
    {
        if (!$s->isDraft()) {
            $this->fail('statement', 'This statement is ' . $s->status . ', so it cannot be changed.');
        }
    }

    public function event(?PayoutStatement $s, string $action, ?string $note, ?User $by): void
    {
        if (!$s) {
            return;
        }

        DB::table('payout_events')->insert(['statement_id' => $s->id, 'action' => $action, 'note' => $note, 'by_id' => $by?->id, 'by_name' => $by?->name, 'created_at' => now()]);
    }

    /** The email to the vendor. It must never make the payment fail, so a problem is only written down. */
    private function tellVendor(PayoutStatement $s): void
    {
        try {
            $vendor = User::find($s->vendor_id);   // not deleted users: they have no mailbox any more

            if (!$vendor || !filled($vendor->email)) {
                $this->event($s, 'email_failed', 'The vendor has no email address on record.', null);

                return;
            }

            Mail::to($vendor->email)->send(new PayoutPaidMail($this->mailData($s, $vendor)));
            $s->update(['emailed_at' => now()]);
            $this->event($s, 'emailed', 'Email sent to the vendor.', null);
        } catch (Throwable $e) {
            Log::warning('[Payout] Could not email statement ' . $s->number . ': ' . $e->getMessage());
            $this->event($s, 'email_failed', substr($e->getMessage(), 0, 200), null);
        }
    }

    /** What the vendor's email shows */
    public function mailData(PayoutStatement $s, User $vendor): array
    {
        $rows = $s->lines()->where('active', 1)->orderBy('booking_number')->get()->groupBy('booking_number')->map(fn ($g, $n) => [
            'booking_number' => $n, 'property' => $g->first()->property_name, 'checkout' => $g->first()->checkout_date?->toDateString(),
            'gross' => (int) $g->sum('gross_paisa') / 100, 'commission' => (int) $g->sum('commission_paisa') / 100, 'percent' => $g->first()->commission_percent === null ? null : (float) $g->first()->commission_percent,
            'amount' => (int) $g->sum('vendor_paisa') / 100,
        ])->values()->all();

        // one rate for the whole statement when every booking used the same one (otherwise the rows each carry their own)
        $rates = $s->lines()->where('active', 1)->whereNotNull('commission_percent')->distinct()->pluck('commission_percent');

        return [
            'commission_percent' => $rates->count() === 1 ? (float) $rates->first() : null,
            'vendor_name' => $vendor->name, 'number' => $s->number, 'amount' => $s->vendor_paisa / 100,
            'method' => $s->method, 'reference' => $s->reference, 'paid_on' => $s->paid_at?->timezone(MoneyRules::TZ)->format('j M Y'),
            'bookings_count' => $s->bookings_count, 'commission' => $s->commission_paisa / 100, 'gross' => $s->gross_paisa / 100,
            'base' => $s->base_paisa / 100, 'vat' => $s->vat_paisa / 100, 'service' => $s->service_paisa / 100, 'rows' => array_slice($rows, 0, 40), 'more_rows' => max(0, count($rows) - 40),
            'account' => $s->account_snapshot,
        ];
    }

    private function taka(int $paisa): string
    {
        return 'BDT ' . number_format($paisa / 100, 2);
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
