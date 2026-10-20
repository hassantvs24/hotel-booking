<?php

namespace App\Services\Money;

use App\Models\User;
use App\Models\VendorPayoutAccount;
use App\Notifications\Admin\AdminNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Saves where a vendor wants to be paid, writes every change to the history, and tells the admin when an account
 * that was already in use is changed (a swapped account number is how payout fraud happens).
 */
class PayoutAccountService
{
    /** Tidy what was typed: no spaces in numbers, a mobile number written the same way every time */
    public static function clean(array $d): array
    {
        $method = (string) ($d['method'] ?? '');
        $number = preg_replace('/[\s\-]+/', '', (string) ($d['account_number'] ?? ''));

        if (in_array($method, ['bkash', 'nagad', 'rocket'], true)) {
            $number = preg_replace('/^(\+?88)/', '', $number);
        }

        return [
            'method'         => $method,
            'holder_name'    => trim((string) ($d['holder_name'] ?? '')),
            'account_number' => $number,
            'bank_name'      => $method === 'bank' ? (trim((string) ($d['bank_name'] ?? '')) ?: null) : null,
            'branch_name'    => $method === 'bank' ? (trim((string) ($d['branch_name'] ?? '')) ?: null) : null,
            'routing_number' => $method === 'bank' ? (preg_replace('/\s+/', '', (string) ($d['routing_number'] ?? '')) ?: null) : null,
        ];
    }

    public function save(User $vendor, array $data, User $by): VendorPayoutAccount
    {
        $new = self::clean($data);

        return DB::transaction(function () use ($vendor, $new, $by) {
            $account = VendorPayoutAccount::where('user_id', $vendor->id)->lockForUpdate()->first();
            $old     = $account?->details();
            $same    = $account && collect($new)->every(fn ($v, $k) => ($account->{$k} ?? null) === $v);

            if ($same) {
                return $account;   // nothing changed: no history line, no alarm
            }

            $account = $account ? tap($account)->update($new) : VendorPayoutAccount::create($new + ['user_id' => $vendor->id]);

            DB::table('vendor_payout_account_changes')->insert([
                'user_id' => $vendor->id, 'changed_by' => $by->id, 'changed_by_name' => $by->name,
                'old_values' => $old ? json_encode($old) : null, 'new_values' => json_encode($account->details()), 'created_at' => now(),
            ]);

            if ($old) {
                $this->tellAdmins($vendor, $by);
            }

            return $account;
        });
    }

    /** Hide most of an account number on screens that only need to recognise it */
    public static function mask(?string $number): string
    {
        $number = (string) $number;

        return strlen($number) <= 4 ? $number : str_repeat('•', strlen($number) - 4) . substr($number, -4);
    }

    private function tellAdmins(User $vendor, User $by): void
    {
        try {
            User::admins()->get()->each(fn ($admin) => $admin->notify(new AdminNotification(
                type: 'payout',
                title: 'Payout account changed',
                message: "{$vendor->name} changed the account their payouts go to" . ($by->id !== $vendor->id ? " (changed by {$by->name})" : '') . '. Check it before the next payout.',
                icon: 'bx-wallet',
                color: 'amber',
                extra: ['vendor_id' => $vendor->id],
                channel: 'admin',
            )));
        } catch (Throwable $e) {
            Log::warning('[Payout] Could not notify admins about an account change: ' . $e->getMessage());
        }
    }
}
