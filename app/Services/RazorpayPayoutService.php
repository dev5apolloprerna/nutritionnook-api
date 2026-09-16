<?php

namespace App\Services;

use Razorpay\Api\Api;

class RazorpayPayoutService
{
    protected Api $api;

    public function __construct()
    {
        $this->api = new Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );
    }

    public function getOrCreateFundAccount($chef)
    {
        if ($chef->fund_account_id) {
            return $chef->fund_account_id;
        }

        // Create Contact (dynamic handler)
        $contact = $this->api->contact->create([
            'name' => $chef->name,
            'type' => 'vendor',
        ]);

        // Create Fund Account
        $fund = $this->api->fundAccount->create([
            'contact_id' => $contact->id,
            'account_type' => 'bank_account',
            'bank_account' => [
                'name' => $chef->name,
                'ifsc' => $chef->ifsc_code,
                'account_number' => $chef->account_number,
            ],
        ]);

        $chef->update([
            'fund_account_id' => $fund->id
        ]);

        return $fund->id;
    }

    public function payout($fundAccountId, $amount, $reference)
    {
        return $this->api->payout->create([
            'account_number' => config('services.razorpay.account'),
            'fund_account_id' => $fundAccountId,
            'amount' => (int) ($amount * 100),
            'currency' => 'INR',
            'mode' => 'IMPS',
            'purpose' => 'payout',
            'reference_id' => $reference,
        ]);
    }
}
