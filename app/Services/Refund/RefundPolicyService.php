<?php

namespace App\Services\Refund;

use App\Models\Booking;
use App\Models\Property;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Which refund tiers apply to a property.
 *
 * The owner decides: `properties.refund_policy` holds their tiers. A property that never set one
 * falls back to the platform default in config/refund.php, so nothing changes for existing hotels.
 *
 * Stored shape:  { "preset": "moderate", "tiers": [ {"days": 5, "refund_percentage": 100}, ... ] }
 * "days" = how many full days before the start of check-in day a cancellation must come to get that
 * percentage; 0 means "any time until check-in day starts". An empty tier list means non-refundable.
 */
class RefundPolicyService
{
    public const MAX_TIERS = 5;

    /** Ready-made choices an owner can start from */
    public const PRESETS = [
        'flexible' => [
            'label' => 'Flexible',
            'description' => 'Full refund up to 1 day before check-in.',
            'tiers' => [['days' => 1, 'refund_percentage' => 100]],
        ],
        'moderate' => [
            'label' => 'Moderate',
            'description' => 'Full refund up to 5 days before, half back up to 1 day before.',
            'tiers' => [['days' => 5, 'refund_percentage' => 100], ['days' => 1, 'refund_percentage' => 50]],
        ],
        'strict' => [
            'label' => 'Strict',
            'description' => 'Full refund up to 14 days before, half back up to 7 days before.',
            'tiers' => [['days' => 14, 'refund_percentage' => 100], ['days' => 7, 'refund_percentage' => 50]],
        ],
        'non_refundable' => [
            'label' => 'Non-refundable',
            'description' => 'No refund once the booking is paid.',
            'tiers' => [],
        ],
    ];

    /** Tiers in the shape RefundQuoteService works with, most generous first */
    public function tiersFor(?Property $property): array
    {
        $policy = $property?->refund_policy;

        if (!is_array($policy) || !array_key_exists('tiers', $policy)) {
            return $this->platformTiers();
        }

        return collect($policy['tiers'])
            ->map(fn (array $t) => $this->toTier((int) $t['days'], (int) $t['refund_percentage']))
            ->sortByDesc('minimum_hours_before_checkin')
            ->values()
            ->all();
    }

    /**
     * A copy of the policy as it stands now, ready to be saved with a booking. It holds the finished tiers (the platform
     * default included), so neither a later change by the owner nor a later change of the platform default can touch it.
     */
    public function snapshot(?Property $property): array
    {
        $policy = $property?->refund_policy;
        $custom = is_array($policy) && array_key_exists('tiers', $policy);

        return [
            'source' => $custom ? 'property' : 'platform',
            'preset' => $custom ? ($policy['preset'] ?? 'custom') : 'platform',
            'tiers'  => array_map(fn (array $t) => [
                'minimum_hours_before_checkin' => (int) $t['minimum_hours_before_checkin'],
                'refund_percentage'            => (int) $t['refund_percentage'],
                'label'                        => (string) $t['label'],
            ], $this->tiersFor($property)),
        ];
    }

    /**
     * The tiers that apply to ONE booking: the copy saved when it was made, or, for a booking made before copies
     * were saved, the property's policy as it is now.
     */
    public function tiersForBooking(Booking $booking, ?Property $property): array
    {
        $saved = $booking->refund_policy;

        if (is_array($saved) && isset($saved['tiers']) && is_array($saved['tiers'])) {
            return collect($saved['tiers'])
                ->map(fn (array $t) => [
                    'minimum_hours_before_checkin' => (int) $t['minimum_hours_before_checkin'],
                    'refund_percentage'            => (int) $t['refund_percentage'],
                    'label'                        => (string) ($t['label'] ?? ((int) $t['refund_percentage'] >= 100 ? 'Free cancellation' : (100 - (int) $t['refund_percentage']) . '% cancellation fee')),
                ])
                ->sortByDesc('minimum_hours_before_checkin')
                ->values()
                ->all();
        }

        return $this->tiersFor($property);
    }

    /** Like resolveTier(), for a booking: what it was promised when it was made */
    public function resolveTierForBooking(Booking $booking, ?Property $property, int $hoursBeforeCheckIn): ?array
    {
        foreach ($this->tiersForBooking($booking, $property) as $tier) {
            if ($hoursBeforeCheckIn >= (int) $tier['minimum_hours_before_checkin']) {
                return (int) $tier['refund_percentage'] > 0 ? $tier : null;
            }
        }

        return null;
    }

    /** The first tier a cancellation made this many hours before check-in falls into (null = no refund) */
    public function resolveTier(?Property $property, int $hoursBeforeCheckIn): ?array
    {
        foreach ($this->tiersFor($property) as $tier) {
            if ($hoursBeforeCheckIn >= (int) $tier['minimum_hours_before_checkin']) {
                return (int) $tier['refund_percentage'] > 0 ? $tier : null;
            }
        }

        return null;
    }

    /** Everything a screen or email needs to show the policy */
    public function summary(?Property $property): array
    {
        $policy = $property?->refund_policy;
        $custom = is_array($policy) && array_key_exists('tiers', $policy);
        $tiers  = $this->tiersFor($property);

        return [
            'source'  => $custom ? 'property' : 'platform',
            'preset'  => $custom ? ($policy['preset'] ?? 'custom') : 'platform',
            'tiers'   => collect($tiers)->map(fn (array $t) => [
                'days'              => $this->daysOf($t),
                'refund_percentage' => (int) $t['refund_percentage'],
                'fee_percentage'    => 100 - (int) $t['refund_percentage'],
            ])->values()->all(),
            'lines'   => $this->lines($tiers),
            'headline' => $this->headline($tiers),
        ];
    }

    /** One short sentence for a badge, e.g. "Free cancellation up to 5 days before check-in" */
    public function headline(array $tiers): string
    {
        $full = collect($tiers)->first(fn ($t) => (int) $t['refund_percentage'] >= 100);

        if ($full) {
            return 'Free cancellation up to ' . $this->duration((int) $full['minimum_hours_before_checkin']) . ' before check-in';
        }

        return collect($tiers)->contains(fn ($t) => (int) $t['refund_percentage'] > 0)
            ? 'Partial refund if you cancel in time'
            : 'Non-refundable';
    }

    /**
     * What the policy means for ONE stay, today: the hotel page used to say "Free cancellation up to 7 days before
     * check-in" even for a stay that starts today, when that time is long gone. This counts the way a cancellation is
     * judged (RefundQuoteService): whole hours from now to the start of check-in day in the hotel's time zone.
     *
     * @return array{headline:string, percent_now:int, free_until:?string, hours_before:int}
     */
    public function forStay(?Property $property, string $checkIn, ?\DateTimeInterface $now = null): array
    {
        $tz    = config('refund.timezone', 'Asia/Dhaka');
        $now   = $now ? CarbonImmutable::instance($now) : CarbonImmutable::now();
        $start = CarbonImmutable::parse(substr($checkIn, 0, 10), $tz)->startOfDay();
        $hours = (int) floor($now->diffInHours($start, false));
        $tiers = $this->tiersFor($property);

        $result = fn (string $headline, int $percent = 0, ?CarbonImmutable $freeUntil = null) => [
            'headline'     => $headline,
            'percent_now'  => $percent,
            'free_until'   => $freeUntil?->toDateString(),
            'hours_before' => $hours,
        ];

        // no tier gives anything back
        if (!collect($tiers)->contains(fn ($t) => (int) $t['refund_percentage'] > 0)) {
            return $result('Non-refundable');
        }

        // check-in day has begun: the system refuses every refund from then on
        if ($hours <= 0) {
            return $result('No refund: check-in day has already started');
        }

        $full = collect($tiers)->first(fn ($t) => (int) $t['refund_percentage'] >= 100);

        if ($full && $hours >= (int) $full['minimum_hours_before_checkin']) {
            // the last day a cancellation is still free (the moment itself is the start of the day before the cut-off)
            $lastFreeDay = $start->subHours((int) $full['minimum_hours_before_checkin'])->subSecond();

            return $result('Free cancellation until ' . $lastFreeDay->format('j M'), 100, $lastFreeDay);
        }

        $tier    = $this->resolveTier($property, $hours);
        $percent = $tier ? (int) $tier['refund_percentage'] : 0;

        return $percent > 0
            ? $result($percent . '% refunded if you cancel now', $percent)
            : $result('No refund if you cancel now');
    }

    /** The tiers worded for a guest, most generous first, ending with the "no refund" row */
    public function lines(array $tiers): array
    {
        $tiers = collect($tiers)->sortByDesc('minimum_hours_before_checkin')->values();
        $rows  = [];
        $upper = null;   // hours — the tier above covers everything beyond this

        foreach ($tiers as $tier) {
            $hours   = (int) $tier['minimum_hours_before_checkin'];
            $percent = (int) $tier['refund_percentage'];

            $when = $upper === null
                ? ($hours <= 1 ? 'Until check-in day' : $this->duration($hours) . ' or more before check-in')
                : ($hours <= 1
                    ? 'Less than ' . $this->duration($upper) . ' before check-in'
                    : 'Less than ' . $this->duration($upper) . ' but at least ' . $this->duration($hours) . ' before check-in');

            $rows[] = [
                'when'    => $when,
                'outcome' => $percent >= 100 ? 'Full refund' : ($percent > 0 ? $percent . '% refunded' : 'No refund'),
                'percent' => $percent,
            ];
            $upper = $hours;
        }

        if ($upper === null) {
            return [['when' => 'Any time', 'outcome' => 'No refund', 'percent' => 0]];
        }

        // Below the last tier nothing is refunded
        $rows[] = [
            'when'    => $upper > 1 ? 'Less than ' . $this->duration($upper) . ' before check-in' : 'Once check-in day has started',
            'outcome' => 'No refund',
            'percent' => 0,
        ];

        return $rows;
    }

    /**
     * Check what an owner sent and turn it into the stored shape.
     *
     * @throws ValidationException
     */
    public function normalize(?string $preset, array $tiers): array
    {
        $errors = [];

        if (count($tiers) > self::MAX_TIERS) {
            $errors['tiers'] = 'Use at most ' . self::MAX_TIERS . ' refund steps.';
        }

        $clean = [];
        foreach ($tiers as $i => $tier) {
            $days    = $tier['days'] ?? null;
            $percent = $tier['refund_percentage'] ?? null;
            $row     = $i + 1;

            if (!is_numeric($days) || (int) $days != $days || $days < 0 || $days > 365) {
                $errors["tiers.$i.days"] = "Step $row: days must be a whole number from 0 to 365.";
                continue;
            }
            if (!is_numeric($percent) || (int) $percent != $percent || $percent < 1 || $percent > 100) {
                $errors["tiers.$i.refund_percentage"] = "Step $row: refund must be a whole number from 1 to 100%. (Anything after the last step is already no refund.)";
                continue;
            }

            $clean[] = ['days' => (int) $days, 'refund_percentage' => (int) $percent];
        }

        if (!$errors) {
            $days = array_column($clean, 'days');
            if (count($days) !== count(array_unique($days))) {
                $errors['tiers'] = 'Two steps cannot use the same number of days.';
            }
        }

        if (!$errors) {
            usort($clean, fn ($a, $b) => $b['days'] <=> $a['days']);

            // Cancelling later must never give back more than cancelling earlier
            for ($i = 1; $i < count($clean); $i++) {
                if ($clean[$i]['refund_percentage'] > $clean[$i - 1]['refund_percentage']) {
                    $errors['tiers'] = 'A later cancellation cannot get a higher refund than an earlier one.';
                    break;
                }
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $known = $preset && isset(self::PRESETS[$preset]) && self::PRESETS[$preset]['tiers'] == $clean;

        return ['preset' => $known ? $preset : 'custom', 'tiers' => $clean];
    }

    /** What an old vendor-form answer ("free_48h") means in the new tiers */
    public function fromLegacy(?string $legacy): ?array
    {
        return match ($legacy) {
            'free_24h'       => ['preset' => 'custom', 'tiers' => [['days' => 1, 'refund_percentage' => 100]]],
            'free_48h'       => ['preset' => 'custom', 'tiers' => [['days' => 2, 'refund_percentage' => 100]]],
            'non_refundable' => ['preset' => 'non_refundable', 'tiers' => []],
            default          => null,
        };
    }

    private function platformTiers(): array
    {
        return collect(config('refund.tiers', []))
            ->sortByDesc('minimum_hours_before_checkin')
            ->values()
            ->all();
    }

    private function toTier(int $days, int $percent): array
    {
        return [
            'minimum_hours_before_checkin' => max(1, $days * 24),   // 0 days = any time before check-in day
            'refund_percentage'            => $percent,
            'label'                        => $percent >= 100 ? 'Free cancellation' : (100 - $percent) . '% cancellation fee',
        ];
    }

    private function daysOf(array $tier): int
    {
        $hours = (int) $tier['minimum_hours_before_checkin'];

        return $hours <= 1 ? 0 : intdiv($hours, 24);
    }

    public function duration(int $hours): string
    {
        if ($hours >= 24 && $hours % 24 === 0) {
            $days = intdiv($hours, 24);

            return $days . ' day' . ($days > 1 ? 's' : '');
        }

        return $hours . ' hour' . ($hours > 1 ? 's' : '');
    }
}
