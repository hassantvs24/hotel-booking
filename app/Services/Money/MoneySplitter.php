<?php

namespace App\Services\Money;

/**
 * The one place that splits a payment between the platform and the vendor.
 * Works in whole paisa only, so the parts always add up to the whole:
 *
 *     gross = commission + vendor            (the platform keeps ONLY its commission)
 *
 * The VAT and the service charge are part of the vendor's money: they are paid to the vendor in full, and paying the VAT
 * to the government is the vendor's own business. They are still written down on every line (so the vendor can see
 * them) and they add up with the room price to the whole:
 *
 *     gross = base + service + vat
 *
 * `platform_service` is kept at 0 (it used to be the service charge when the platform kept it).
 *
 * Nothing here reads the database. Pure arithmetic, easy to test.
 */
class MoneySplitter
{
    /** "1234.50" / 1234.5 / null → 123450 paisa. Works from the text, so no float surprises. */
    public static function toPaisa(mixed $amount): int
    {
        if ($amount === null || $amount === '') {
            return 0;
        }

        return (int) str_replace('.', '', number_format((float) $amount, 2, '.', ''));
    }

    /** 123450 → "1,234.50" for screens and exports */
    public static function format(int $paisa): string
    {
        return number_format($paisa / 100, 2);
    }

    /**
     * Split one payment.
     *
     * @param array{base:int,service:int,vat:int}|null $tax  what the booking saved; null when it saved nothing
     * @return array{base:int,service:int,vat:int,platform_service:int,commission:int,vendor:int,flags:string[]}
     */
    public function payment(int $gross, ?array $tax, float $commissionPercent): array
    {
        $flags = [];

        if ($tax === null) {
            $flags[] = 'no_tax_snapshot';
        } elseif ($tax['base'] + $tax['service'] + $tax['vat'] !== $gross) {
            $flags[] = 'amount_mismatch';
            $tax = null;
        }

        // Cannot tell room price from taxes: keep everything as room price and let a person look.
        if ($tax === null) {
            $flags[] = 'needs_review';
            $tax = ['base' => $gross, 'service' => 0, 'vat' => 0];
        }

        // The commission is a percentage of the ROOM PRICE only (not of the VAT or the service charge).
        // whole-number maths (rate in hundredths of a percent, half a paisa rounds up): a float here is 1 paisa off now and then
        $commission = intdiv($tax['base'] * (int) round($commissionPercent * 100) + 5000, 10000);
        // Everything else, the VAT and the service charge included, is the vendor's.
        $vendor     = $gross - $commission;

        return [
            'base'             => $tax['base'],
            'service'          => $tax['service'],
            'vat'              => $tax['vat'],
            'platform_service' => 0,
            'commission'       => $commission,
            'vendor'           => $vendor,
            'flags'            => array_values(array_unique($flags)),
        ];
    }

    /**
     * Split `$total` paisa between the weights, in proportion, so the parts add up to exactly `$total`
     * (the leftover paisa go to the largest remainders).
     *
     * @param array<string,int> $weights
     * @return array<string,int>
     */
    public function allocate(int $total, array $weights): array
    {
        $sum = array_sum($weights);
        if ($sum <= 0 || $total === 0) {
            return array_map(fn () => 0, $weights);
        }

        $sign   = $total < 0 ? -1 : 1;
        $abs    = abs($total);
        $parts  = [];
        $rest   = [];
        $given  = 0;

        foreach ($weights as $key => $weight) {
            $exact        = $abs * $weight / $sum;
            $parts[$key]  = (int) floor($exact);
            $rest[$key]   = $exact - $parts[$key];
            $given       += $parts[$key];
        }

        arsort($rest);
        foreach (array_keys($rest) as $key) {
            if ($given >= $abs) {
                break;
            }
            $parts[$key]++;
            $given++;
        }

        return array_map(fn ($v) => $v * $sign, $parts);
    }

    /**
     * The share of a payment that a refund takes back. `$cumulativeRefund` is everything refunded on that payment
     * so far INCLUDING this refund; `$already` is what earlier refunds took (same keys, positive numbers).
     * Working from the running total means a series of partial refunds still ends exactly on the right sum.
     *
     * @param array{gross:int,base:int,service:int,vat:int,platform_service:int,commission:int,vendor:int} $payment
     * @param array<string,int> $already
     * @return array{gross:int,base:int,service:int,vat:int,platform_service:int,commission:int,vendor:int} positive numbers
     */
    public function refund(array $payment, int $cumulativeRefund, array $already): array
    {
        $cumulativeRefund = max(0, min($cumulativeRefund, $payment['gross']));

        // who gives the money back: the platform its commission, the vendor the rest (in proportion to what each kept)
        $money = $this->allocate($cumulativeRefund, [
            'platform_service' => $payment['platform_service'],
            'commission'       => $payment['commission'],
            'vendor'           => $payment['vendor'],
        ]);

        // and the same refund seen as room price, service charge and VAT (information for the vendor)
        $parts = $this->allocate($cumulativeRefund, [
            'base'    => $payment['base'],
            'service' => $payment['service'],
            'vat'     => $payment['vat'],
        ]);

        $cumulative = ['gross' => $cumulativeRefund] + $money + $parts;

        $now = [];
        foreach ($cumulative as $key => $value) {
            $now[$key] = $value - ($already[$key] ?? 0);
        }

        return $now;
    }
}
