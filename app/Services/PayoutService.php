<?php

namespace App\Services;

use App\Models\{Wallet, Payout};
use DB;
use Log;
use Exception;

class PayoutService
{
    public function processChef($chef)
    {
        $wallet = $chef->wallet;

        if ($wallet->balance <= 0) return;

        $gross = $wallet->balance;
        $commission = $gross * 0.10;
        $net = $gross - $commission;

        DB::beginTransaction();

        try {
            $payout = Payout::create([
                'chef_id' => $chef->id,
                'gross_amount' => $gross,
                'commission_amount' => $commission,
                'net_amount' => $net,
                'status' => 'pending',
                'payout_date' => now(),
            ]);

            $razorpay = app(RazorpayPayoutService::class);
            $response = $razorpay->sendPayout(
                $chef->bankAccount->razorpay_fund_account_id,
                $net,
                'CHEF-'.$chef->id.'-'.$payout->id
            );

            $wallet->update(['balance' => 0]);

            $payout->update([
                'status' => 'processed',
                'razorpay_payout_id' => $response->id,
                'processed_at' => now(),
            ]);

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();

            $payout->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);
        }
    }

    /**
 * Register a Chef on Razorpay X and save their Fund Account ID
 */

}
