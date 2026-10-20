<?php

namespace App\Http\Controllers\API\Admin\Money;

use App\Http\Controllers\BaseController;
use App\Models\User;
use App\Models\VendorPayoutAccount;
use App\Services\Money\PayoutAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Where a vendor wants to be paid. A vendor sees and changes only their own account; the admin can open any vendor's.
 * Every change is kept in a history, and the admin is told when an account that was already in use is changed.
 */
class PayoutAccountController extends BaseController
{
    public function __construct(private readonly PayoutAccountService $accounts) {}

    public function show(Request $request): JsonResponse
    {
        $vendor  = $this->vendor($request);
        $account = VendorPayoutAccount::where('user_id', $vendor->id)->first();

        return $this->sendSuccess([
            'vendor'  => ['id' => $vendor->id, 'name' => $vendor->name],
            'account' => $account?->details(),
            'history' => DB::table('vendor_payout_account_changes')->where('user_id', $vendor->id)->orderByDesc('id')->limit(10)->get(['changed_by_name', 'created_at'])->map(fn ($c) => ['changed_by_name' => $c->changed_by_name, 'created_at' => \App\Services\Money\MoneyRules::local($c->created_at)]),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);

        // first the kind of each answer (text, not a list): the tidying below needs text
        $request->validate([
            'method' => ['required', 'string', 'max:20'], 'holder_name' => ['required', 'string', 'max:200'], 'account_number' => ['required', 'string', 'max:80'],
            'bank_name' => ['nullable', 'string', 'max:200'], 'branch_name' => ['nullable', 'string', 'max:200'], 'routing_number' => ['nullable', 'string', 'max:40'],
        ]);

        $bank   = $request->input('method') === 'bank';
        $wallet = in_array($request->input('method'), ['bkash', 'nagad', 'rocket'], true);

        $request->merge(['account_number' => PayoutAccountService::clean($request->all())['account_number']]);

        $data = $request->validate([
            'method'         => ['required', Rule::in(VendorPayoutAccount::METHODS)],
            'holder_name'    => ['required', 'string', 'min:2', 'max:120'],
            'account_number' => ['required', 'string', $wallet ? 'regex:/^01[3-9][0-9]{8}$/' : 'regex:/^[0-9A-Za-z]{5,30}$/'],
            'bank_name'      => [$bank ? 'required' : 'nullable', 'string', 'max:120'],
            'branch_name'    => [$bank ? 'required' : 'nullable', 'string', 'max:120'],
            'routing_number' => ['nullable', 'regex:/^[0-9]{9}$/'],
        ], [
            'account_number.regex' => $wallet ? 'Write the 11-digit mobile number of the wallet, like 01712345678.' : 'The account number can only have letters and numbers (5 to 30 characters).',
            'routing_number.regex' => 'A routing number has 9 digits.',
        ]);

        $account = $this->accounts->save($vendor, $data, $request->user());

        return $this->sendSuccess(['account' => $account->details()], 'Your payout account was saved.');
    }

    /** A vendor works on their own account; the admin names the vendor */
    private function vendor(Request $request): User
    {
        $user = $request->user();

        if ($user->is_admin) {
            $request->validate(['vendor_id' => ['required', 'integer', 'exists:users,id']]);

            return User::findOrFail((int) $request->input('vendor_id'));
        }

        abort_unless($user->is_merchant, 403, 'Only a property owner has a payout account.');

        return $user;
    }
}
