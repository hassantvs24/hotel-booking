<?php

namespace App\Services\Pricing;

use App\Models\Property;
use App\Models\Room;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * VAT and service charge on a room price.
 *
 * One place does the sums so the search page, the room cards, the cart, the payment and the emails
 * can never disagree. Rates come from the room, else its property, else the platform settings the
 * admin edits (15% VAT, 10% service charge to begin with). NULL means "ask the level above";
 * 0 means "no tax here".
 */
class PriceService
{
    /** Platform rates are read once per request */
    private static ?array $platform = null;

    /** What the admin has set for the whole platform */
    public function platformRates(): array
    {
        if (self::$platform === null) {
            $rows = DB::table('settings')
                ->where('group', 'tax')
                ->whereNull('deleted_at')
                ->pluck('value', 'key');

            self::$platform = [
                'vat_percent'            => $this->percent($rows['vat_percent'] ?? null, (float) config('pricing.default_vat_percent', 15)),
                'service_charge_percent' => $this->percent($rows['service_charge_percent'] ?? null, (float) config('pricing.default_service_charge_percent', 10)),
            ];
        }

        return self::$platform;
    }

    /** Call after the admin changes the platform rates */
    public static function forgetPlatformRates(): void
    {
        self::$platform = null;
    }

    /**
     * The rates that apply to a room, and where each one came from ("room", "property" or "platform").
     *
     * @return array{vat_percent: float, service_charge_percent: float, vat_from: string, service_charge_from: string}
     */
    public function ratesFor(?Room $room, ?Property $property = null): array
    {
        $platform = $this->platformRates();
        $property ??= $room?->relationLoaded('property') ? $room->property : $room?->property()->first();

        [$vat, $vatFrom] = $this->pick($room?->vat_percent, $property?->vat_percent, $platform['vat_percent']);
        [$svc, $svcFrom] = $this->pick($room?->service_charge_percent, $property?->service_charge_percent, $platform['service_charge_percent']);

        return [
            'vat_percent'            => $vat,
            'service_charge_percent' => $svc,
            'vat_from'               => $vatFrom,
            'service_charge_from'    => $svcFrom,
            'vat_on_service_charge'  => (bool) config('pricing.vat_on_service_charge'),
        ];
    }

    /**
     * Price + service charge + VAT for an amount (one night, or a whole stay).
     *
     * @return array{base: float, service_charge: float, vat: float, total: float, vat_percent: float, service_charge_percent: float}
     */
    public function breakdown(float $base, array $rates): array
    {
        $vatPct = (float) $rates['vat_percent'];
        $svcPct = (float) $rates['service_charge_percent'];

        $service = round($base * $svcPct / 100, 2);
        $vatBase = config('pricing.vat_on_service_charge') ? $base + $service : $base;
        $vat     = round($vatBase * $vatPct / 100, 2);

        return [
            'base'                   => round($base, 2),
            'service_charge'         => $service,
            'vat'                    => $vat,
            'taxes'                  => round($service + $vat, 2),   // "taxes and fees": service charge + VAT together
            'total'                  => round($base + $service + $vat, 2),
            'vat_percent'            => $vatPct,
            'service_charge_percent' => $svcPct,
        ];
    }

    /** The same, using the rates of a room */
    public function forRoom(?Room $room, float $base, ?Property $property = null): array
    {
        return $this->breakdown($base, $this->ratesFor($room, $property));
    }

    /**
     * The cheapest room of a property once VAT and service charge are on it (what a "from BDT …" label shows).
     *
     * @param Collection<int, Room> $rooms
     */
    public function lowestWithTax(Collection $rooms, ?Property $property = null): ?float
    {
        return $this->cheapest($rooms, $property)['total'] ?? null;
    }

    /**
     * The cheapest room per night as the price before taxes, the taxes and fees, and the total —
     * the "BDT 8,999 + BDT 2,384 taxes and fees" a search card shows.
     *
     * @param Collection<int, Room> $rooms
     */
    public function cheapest(Collection $rooms, ?Property $property = null): ?array
    {
        $best = null;

        foreach ($rooms as $room) {
            $one = $this->forRoom($room, (float) $room->base_price, $property);

            if ($best === null || $one['total'] < $best['total']) {
                $best = $one;
            }
        }

        return $best;
    }

    /**
     * Everything a screen needs for a room and a stay: per night and for all nights.
     *
     * @return array{rates: array, per_night: array, stay: array, nights: int}
     */
    public function forStay(?Room $room, float $basePerNight, int $nights, ?Property $property = null): array
    {
        $rates  = $this->ratesFor($room, $property);
        $nights = max(1, $nights);

        return [
            'rates'     => $rates,
            'nights'    => $nights,
            'per_night' => $this->breakdown($basePerNight, $rates),
            'stay'      => $this->breakdown($basePerNight * $nights, $rates),
        ];
    }

    /**
     * The columns a booking stores: the total the guest pays in `amount`, and how it was made up.
     * Rates are saved with it, so changing a rate later does not rewrite old bookings.
     */
    public function bookingFields(?Room $room, float $basePerNight, int $nights): array
    {
        $stay = $this->forStay($room, $basePerNight, $nights)['stay'];

        return [
            'amount'                 => $stay['total'],
            'subtotal_amount'        => $stay['base'],
            'service_charge_amount'  => $stay['service_charge'],
            'vat_amount'             => $stay['vat'],
            'vat_percent'            => $stay['vat_percent'],
            'service_charge_percent' => $stay['service_charge_percent'],
            // the refund rules the guest is shown now: they stay with this booking even if the owner changes the policy later
            'refund_policy'          => app(\App\Services\Refund\RefundPolicyService::class)->snapshot($room?->property),
        ];
    }

    /**
     * First rate that has been set, with the level it came from.
     * The platform rate is a floor: a property or room can charge more, never less (so if the admin
     * raises the platform rate later, nobody is left below it).
     */
    private function pick($room, $property, float $platform): array
    {
        if ($room !== null && $room !== '') {
            return $this->atLeast((float) $room, 'room', $platform);
        }
        if ($property !== null && $property !== '') {
            return $this->atLeast((float) $property, 'property', $platform);
        }

        return [$platform, 'platform'];
    }

    private function atLeast(float $own, string $level, float $platform): array
    {
        return $own >= $platform ? [$own, $level] : [$platform, 'platform'];
    }

    /** A clean 0–100 number, or the fallback when the setting is empty or nonsense */
    private function percent(mixed $value, float $fallback): float
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return $fallback;
        }

        return max(0.0, min(100.0, (float) $value));
    }
}
