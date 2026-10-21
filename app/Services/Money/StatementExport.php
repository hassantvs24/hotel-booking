<?php

namespace App\Services\Money;

use App\Models\PayoutStatement;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A payout statement as a spreadsheet (opens in Excel). The admin and the vendor get the same file: it holds only the lines and
 * totals, nothing internal (no notes, no names of who prepared it).
 */
class StatementExport
{
    public function stream(PayoutStatement $statement): StreamedResponse
    {
        $rows = $statement->lines()->where(fn ($q) => $statement->isDraft() || $statement->isPaid() ? $q->where('active', 1) : $q)->orderBy('booking_number')->orderBy('id')->get();
        $taka = fn (int $p) => ($p < 0 ? '-' : '') . intdiv(abs($p), 100) . '.' . str_pad((string) (abs($p) % 100), 2, '0', STR_PAD_LEFT);
        // a cell that starts with = + - @ would be run as a formula by a spreadsheet: make it plain text
        $safe = fn ($v) => is_string($v) && $v !== '' && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'" . $v : $v;

        return response()->streamDownload(function () use ($statement, $rows, $taka, $safe) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Statement', $statement->number, 'Vendor', $safe((string) $statement->vendor_name), 'Status', $statement->status, 'Paid on', $statement->paid_at?->timezone(MoneyRules::TZ)->toDateString(), 'Reference', $safe((string) $statement->reference)]);
            fputcsv($out, []);
            fputcsv($out, ['Booking', 'Property', 'Check-out', 'Type', 'Paid by guest (BDT)', 'Room price (BDT)', 'Service charge (BDT, yours)', 'VAT (BDT, yours)', 'Commission rate (%)', 'Commission (BDT)', 'To you (BDT)']);

            foreach ($rows as $l) {
                fputcsv($out, [$l->booking_number, $safe((string) $l->property_name), $l->checkout_date?->toDateString(), $l->type, $taka($l->gross_paisa), $taka($l->base_paisa), $taka($l->service_paisa), $taka($l->vat_paisa), $l->commission_percent === null ? '' : rtrim(rtrim(number_format((float) $l->commission_percent, 2, '.', ''), '0'), '.'), $taka($l->commission_paisa), $taka($l->vendor_paisa)]);
            }

            fputcsv($out, []);
            fputcsv($out, ['Total', '', '', '', $taka((int) $statement->gross_paisa), $taka((int) $statement->base_paisa), $taka((int) $statement->service_paisa), $taka((int) $statement->vat_paisa), '', $taka((int) $statement->commission_paisa), $taka((int) $statement->vendor_paisa)]);
            fclose($out);
        }, ($statement->number ?: 'statement') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
