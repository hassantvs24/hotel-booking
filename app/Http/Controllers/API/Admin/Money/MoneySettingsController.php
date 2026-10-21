<?php

namespace App\Http\Controllers\API\Admin\Money;

use App\Http\Controllers\BaseController;
use App\Models\Property;
use App\Services\Money\LedgerSync;
use App\Services\Money\MoneyRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * The money settings: the commission, how often vendors are paid, and the other payout rules.
 * Only the platform admin can see or change them, and every change is written down (who, from what, to what).
 * A change applies to payments that come in AFTER it; payments already booked keep the rate they were booked with.
 */
class MoneySettingsController extends BaseController
{
    public function __construct(private readonly MoneyRules $rules, private readonly LedgerSync $ledger) {}

    public function show(Request $request): JsonResponse
    {
        $this->adminOnly($request);

        return $this->sendSuccess($this->payload());
    }

    public function update(Request $request): JsonResponse
    {
        $this->adminOnly($request);

        $data = $request->validate([
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'payout_frequency'   => ['required', Rule::in(['daily', 'weekly', 'biweekly', 'monthly'])],
            'payout_weekday'     => ['required', 'integer', 'between:1,7'],
            'payout_wait_days'   => ['required', 'integer', 'between:0,30'],
            'payout_minimum'     => ['required', 'numeric', 'min:0', 'max:10000000'],
            'bank_check_days'    => ['sometimes', 'integer', 'between:1,90'],
            'money_start_date'   => ['required', 'date_format:Y-m-d'],
        ]);

        // Payments that came in a moment ago but are not booked yet are booked now, at the rates that were in force when they were paid.
        $this->ledger->run();

        DB::transaction(function () use ($data, $request) {
            foreach ($data as $key => $value) {
                $this->save($key, $this->canonical($key, $value), $request);
            }
        });

        MoneyRules::forget();

        return $this->sendSuccess($this->payload(), 'Money settings saved. They apply to payments that come in from now on.');
    }

    /** The owner has gone through every value: the "temporary" warnings go away */
    public function confirm(Request $request): JsonResponse
    {
        $this->adminOnly($request);

        DB::transaction(fn () => $this->save('payout_settings_confirmed', '1', $request));
        MoneyRules::forget();

        return $this->sendSuccess($this->payload(), 'Settings marked as confirmed.');
    }

    public function property(Request $request, Property $property): JsonResponse
    {
        $this->adminOnly($request);

        return $this->sendSuccess($this->propertyPayload($property));
    }

    /** One property's own commission rate; empty means "use the platform rate" */
    public function updateProperty(Request $request, Property $property): JsonResponse
    {
        $this->adminOnly($request);

        $data = $request->validate(['commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100']]);
        $raw  = $data['commission_percent'] ?? null;
        $new  = $raw === null || $raw === '' ? null : round((float) $raw, 2);
        $old  = $property->commission_percent === null ? null : round((float) $property->commission_percent, 2);

        if ($old !== $new) {
            $this->ledger->run();   // book what is already paid at the old rate first

            DB::transaction(function () use ($property, $new, $old, $request) {
                $property->update(['commission_percent' => $new]);
                $this->log('property_commission', $old === null ? null : (string) $old, $new === null ? null : (string) $new, $request, $property);
            });
        }

        return $this->sendSuccess($this->propertyPayload($property->fresh()), 'Saved.');
    }

    // ────────────────────────────────────────────────────────

    private function payload(): array
    {
        $all = $this->rules->all();

        return [
            'settings' => [
                'commission_percent' => (float) $all['commission_percent'],
                'payout_frequency'   => $all['payout_frequency'],
                'payout_weekday'     => (int) $all['payout_weekday'],
                'payout_wait_days'   => (int) $all['payout_wait_days'],
                'payout_minimum'     => (float) $all['payout_minimum'],
                'bank_check_days'    => (int) ($all['bank_check_days'] ?? 7),
                'money_start_date'   => $this->rules->startsAt()->toDateString(),
            ],
            'confirmed' => $this->rules->isConfirmed(),
            'own_rates' => Property::whereNotNull('commission_percent')->orderBy('name')->get(['id', 'name', 'commission_percent'])
                ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'commission_percent' => (float) $p->commission_percent]),
            'history'   => DB::table('money_setting_changes')->orderByDesc('id')->limit(50)->get()
                ->map(fn ($r) => [
                    'setting' => $r->setting, 'property' => $r->property_name, 'old' => $r->old_value, 'new' => $r->new_value,
                    'by' => $r->changed_by_name, 'at' => $r->created_at,
                ]),
        ];
    }

    private function propertyPayload(Property $property): array
    {
        return [
            'id'                 => $property->id,
            'name'               => $property->name,
            'commission_percent' => $property->commission_percent === null ? null : (float) $property->commission_percent,
            'platform_percent'   => (float) $this->rules->get('commission_percent'),
        ];
    }

    /** "12.50", "12.5" and 12.5 are one value: written the same way, so saving it again is not a change */
    private function canonical(string $key, mixed $value): string
    {
        if (in_array($key, ['commission_percent', 'payout_minimum'], true)) {
            return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
        }
        if (in_array($key, ['payout_weekday', 'payout_wait_days', 'bank_check_days'], true)) {
            return (string) (int) $value;   // "07" and 7 are one value
        }

        return (string) $value;
    }

    private function save(string $key, string $value, Request $request): void
    {
        $old = $this->rules->get($key);

        if ($old === $value) {
            return;
        }

        if (DB::table('settings')->where('key', $key)->exists()) {
            DB::table('settings')->where('key', $key)->update(['value' => $value, 'updated_at' => now()]);
        } else {
            DB::table('settings')->insert([
                'key' => $key, 'value' => $value, 'value_type' => 'text', 'group' => 'payout',
                'admin_only' => 1, 'is_deletable' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->log($key, $old, $value, $request);
    }

    private function log(string $setting, ?string $old, ?string $new, Request $request, ?Property $property = null): void
    {
        DB::table('money_setting_changes')->insert([
            'setting' => $setting, 'property_id' => $property?->id, 'property_name' => $property?->name,
            'old_value' => $old, 'new_value' => $new,
            'changed_by' => $request->user()->id, 'changed_by_name' => $request->user()->name,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function adminOnly(Request $request): void
    {
        abort_unless($request->user()->is_admin, 403, 'Only the platform admin can see or change the money settings.');
    }
}
