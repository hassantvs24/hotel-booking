<?php

namespace App\Services\Money;

use App\Models\Property;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The money settings the admin controls (settings table, group "payout"), read in one place.
 * Until the owner confirms them (payout_settings_confirmed) the values are only temporary defaults.
 */
class MoneyRules
{
    public const TZ = 'Asia/Dhaka';

    public const DEFAULTS = [
        'money_start_date'          => null,       // filled with the day the ledger was created
        'commission_percent'        => '10',
        'service_charge_to'         => 'vendor',
        'payout_frequency'          => 'weekly',
        'payout_weekday'            => '1',
        'payout_wait_days'          => '2',
        'payout_minimum'            => '500',
        'payout_settings_confirmed' => '0',
    ];

    private static ?array $cache = null;

    /**
     * A moment (stored in UTC) as Dhaka time, so a screen that shows only the day shows the Dhaka calendar day:
     * something done at 1 a.m. in Dhaka is the next day there, but still the day before in UTC.
     */
    public static function local(mixed $moment): ?string
    {
        return $moment ? CarbonImmutable::parse($moment, 'UTC')->setTimezone(self::TZ)->toIso8601String() : null;
    }

    public static function forget(): void
    {
        self::$cache = null;
    }

    public function all(): array
    {
        if (self::$cache === null) {
            $rows = DB::table('settings')->where('group', 'payout')->whereNull('deleted_at')->pluck('value', 'key')->all();
            self::$cache = array_merge(self::DEFAULTS, $rows);
        }

        return self::$cache;
    }

    public function get(string $key): ?string
    {
        return $this->all()[$key] ?? null;
    }

    /** The first moment (Dhaka time) the ledger counts. Anything paid before it is not booked. */
    public function startsAt(): CarbonImmutable
    {
        $day = $this->get('money_start_date');

        return $day
            ? CarbonImmutable::parse($day, self::TZ)->startOfDay()
            : CarbonImmutable::now(self::TZ)->startOfDay();
    }

    /** The platform commission rate, or the property's own rate when it has one */
    public function commissionPercentFor(?Property $property): float
    {
        $own = $property?->commission_percent;

        return $this->clamp($own !== null && $own !== '' ? (float) $own : (float) $this->get('commission_percent'));
    }

    /** "vendor" or "admin": who the service charge belongs to */
    public function serviceChargeTo(): string
    {
        return $this->get('service_charge_to') === 'admin' ? 'admin' : 'vendor';
    }

    public function waitDays(): int
    {
        return max(0, (int) $this->get('payout_wait_days'));
    }

    public function isConfirmed(): bool
    {
        return $this->get('payout_settings_confirmed') === '1';
    }

    private function clamp(float $percent): float
    {
        return max(0.0, min(100.0, $percent));
    }
}
