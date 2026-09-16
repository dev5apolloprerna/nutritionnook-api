<?php

namespace App\Services;

use Razorpay\Api\Api;
use App\Models\Chef;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\Payout;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ChefPayoutService
{
    protected $api;
    protected $keyId;
    protected $keySecret;
    protected $accountNumber;

    public function __construct()
    {
       $this->keyId = env('RAZORPAY_KEY');
$this->keySecret = env('RAZORPAY_SECRET');
$this->accountNumber = env('RAZORPAY_X_ACCOUNT_NUMBER');
        $this->api = new Api($this->keyId, $this->keySecret);
    }

    public function runBulkPayout(): array
    {
        $chefs = Chef::where(function ($q) {
                $q->where('is_delete', 0)->orWhereNull('is_delete');
            })
            ->get();

        $now = Carbon::now();
        $results = ['processed' => 0, 'skipped' => 0, 'failed' => 0, 'details' => []];

        foreach ($chefs as $chef) {
            $alreadyPaid = Payout::where('chef_id', $chef->id)
                ->whereIn('status', ['success', 'processing', 'completed'])
                ->where('payout_month', $now->month)
                ->where('payout_year', $now->year)
                ->exists();

            if ($alreadyPaid) {
                $results['skipped']++;
                $results['details'][] = "Chef #{$chef->id} ({$chef->name}): Skipped - already paid this period";
                Log::info("Payout skipped: Chef #{$chef->id} already paid for " . $now->format('F Y'));
                continue;
            }

            $unpaidOrders = Order::where('chef_id', $chef->id)
                ->where('status', 'delivered')
                ->where('is_payout_completed', 0)
                ->get();
                
            // સાચી ગણતરી: Base Price (Amount - Fee - GST)
            $totalBaseEarnings = $unpaidOrders->sum('amount') - ($unpaidOrders->sum('platform_fee') + $unpaidOrders->sum('calculated_gst'));

            // $totalEarnings = $unpaidOrders->sum('amount');

            if ($totalBaseEarnings <= 0) {
                $results['skipped']++;
                $results['details'][] = "Chef #{$chef->id} ({$chef->name}): Skipped - no net earnings";
                continue;
            }
            $result = $this->processIndividualPayout($chef, $totalBaseEarnings, $unpaidOrders);
            
            if ($result['success']) {
                $results['processed']++;
                $results['details'][] = "Chef #{$chef->id} ({$chef->name}): Processing - ├втАЪ┬╣{$result['payout_amount']}";
            } else {
                $results['failed']++;
                $results['details'][] = "Chef #{$chef->id} ({$chef->name}): Failed - {$result['error']}";
            }
        }

        return $results;
    }

    public function processSingleChefPayout(int $chefId): array
    {
        $chef = Chef::find($chefId);
        if (!$chef) {
            return ['success' => false, 'error' => 'Chef not found'];
        }

        if (empty($chef->account_number) || empty($chef->ifsc_code)) {
            return ['success' => false, 'error' => 'Chef bank details incomplete (account number or IFSC missing)'];
        }

        $unpaidOrders = Order::where('chef_id', $chef->id)
            ->where('status', 'delivered')
            ->where('is_payout_completed', 0)
            ->get();

        $totalBaseEarnings = $unpaidOrders->sum('amount') - ($unpaidOrders->sum('platform_fee') + $unpaidOrders->sum('calculated_gst'));

        if ($totalBaseEarnings <= 0) {
            return ['success' => false, 'error' => 'No pending earnings'];
        }

        return $this->processIndividualPayout($chef, $totalBaseEarnings, $unpaidOrders);
    }

    public function retryPayout(int $payoutId): array
    {
        $payout = Payout::find($payoutId);
        if (!$payout || $payout->status !== 'failed') {
            return ['success' => false, 'error' => 'Payout not found or not in failed status'];
        }

        $chef = Chef::find($payout->chef_id);
        if (!$chef) {
            return ['success' => false, 'error' => 'Chef not found'];
        }

        if (empty($chef->account_number) || empty($chef->ifsc_code)) {
            return ['success' => false, 'error' => 'Chef bank details incomplete'];
        }

        $amountInPaise = (int) round($payout->payout_amount * 100);

        DB::beginTransaction();
        try {
            $fundAccountId = $this->getOrCreateFundAccount($chef);

            $payoutResponse = $this->createRazorpayPayout($fundAccountId, $amountInPaise, [
                'retry_of' => (string) $payout->id,
                'chef_id' => (string) $chef->id,
                'month' => $payout->payout_month . '/' . $payout->payout_year,
            ]);

            $payout->update([
                'razorpay_payout_id' => $payoutResponse['id'],
                'razorpay_fund_account_id' => $fundAccountId,
                'status' => 'processing',
                'failure_reason' => null,
            ]);

            DB::commit();
            Log::info("RETRY SUCCESS: Payout #{$payout->id} for Chef #{$chef->id} - Razorpay ID: {$payoutResponse['id']}");
            return ['success' => true, 'payout_amount' => $payout->payout_amount];

        } catch (\Exception $e) {
            DB::rollBack();
            $payout->update(['failure_reason' => $e->getMessage()]);
            Log::error("RETRY FAILED: Payout #{$payout->id} | Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function processIndividualPayout($chef, $totalEarnings, $orders): array
    {
        if (empty($chef->account_number) || empty($chef->ifsc_code)) {
            Log::warning("PAYOUT SKIPPED: Chef #{$chef->id} has no bank details");

            $now = Carbon::now();
            Payout::create([
                'chef_id' => $chef->id,
                'total_earning' => $totalEarnings,
                'commission_amount' => 0,
                'payout_amount' => 0,
                'status' => 'failed',
                'payout_month' => $now->month,
                'payout_year' => $now->year,
                'order_count' => $orders->count(),
                'failure_reason' => 'Bank details missing (account_number or ifsc_code)',
            ]);

            return ['success' => false, 'error' => 'Bank details incomplete'];
        }

        $totalAmount = $orders->sum('amount');
        
       
        $totalFeesAndGst = $orders->sum('platform_fee') + $orders->sum('calculated_gst');
        
        // Base Price = ગ્રાહકે ભરેલા - (Platform Fee + GST) -> ₹249.00
        $chefBaseTotal = $totalAmount - $totalFeesAndGst;


        $totalSecurityDeposit = $orders->sum('security_deposit');
    
       
        $netPayout = $chefBaseTotal - $totalSecurityDeposit;
    
        $amountInPaise = (int) round($netPayout * 100);
        $now = Carbon::now();

        DB::beginTransaction();
        try {
            $fundAccountId = $this->getOrCreateFundAccount($chef);

            $payoutResponse = $this->createRazorpayPayout($fundAccountId, $amountInPaise, [
                'month' => $now->format('F Y'),
                'chef_id' => (string) $chef->id,
                'chef_name' => (string) $chef->name,
            ]);

            $payoutRecord = Payout::create([
            'chef_id' => $chef->id,
            'total_earning' => $chefBaseTotal,       // ₹249.00 (Base)
            'commission_amount' => $totalSecurityDeposit, // ₹24.90 (Security)
            'payout_amount' => $netPayout,           // ₹224.10 (Hand cash)
            'razorpay_payout_id' => $payoutResponse['id'],
            'razorpay_fund_account_id' => $fundAccountId,
            'status' => 'processing',
            'payout_month' => $now->month,
            'payout_year' => $now->year,
            'order_count' => $orders->count(),
        ]);

            Order::whereIn('id', $orders->pluck('id'))->update([
                'is_payout_completed' => 1,
                'payout_id' => $payoutRecord->id,
            ]);

            DB::commit();
            Log::info("PAYOUT INITIATED: ├втАЪ┬╣{$netPayout} for Chef #{$chef->id} | Razorpay ID: {$payoutResponse['id']}");
            return ['success' => true, 'payout_amount' => $netPayout];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("FAILED PAYOUT: Chef #{$chef->id} | Error: " . $e->getMessage());

            Payout::create([
                'chef_id' => $chef->id,
                'total_earning' => $totalEarnings,
                'commission_amount' => $totalSecurityDeposit,
                'payout_amount' => $netPayout,
                'razorpay_payout_id' => null,
                'status' => 'failed',
                'payout_month' => $now->month,
                'payout_year' => $now->year,
                'order_count' => $orders->count(),
                'failure_reason' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function getOrCreateFundAccount($chef): string
    {
        if (!empty($chef->razorpay_fund_account_id)) {
            return $chef->razorpay_fund_account_id;
        }

        $contactId = $chef->razorpay_contact_id;
        if (empty($contactId)) {
            $contactId = $this->createRazorpayContact($chef);
        }

        $fundAccountId = $this->createRazorpayFundAccount($chef, $contactId);

        $chef->update([
            'razorpay_contact_id' => $contactId,
            'razorpay_fund_account_id' => $fundAccountId,
        ]);

        return $fundAccountId;
    }

    protected function createRazorpayContact($chef): string
    {
        $response = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->post('https://api.razorpay.com/v1/contacts', [
                'name' => (string) ($chef->name ?: $chef->business_name),
                'email' => (string) $chef->email,
                'contact' => (string) $chef->phone_number,
                'type' => 'vendor',
                'reference_id' => 'chef_' . $chef->id,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to create Razorpay contact: ' . $response->body());
        }

        $contactId = $response->json('id');
        Log::info("Razorpay Contact created for Chef #{$chef->id}: {$contactId}");
        return $contactId;
    }

    protected function createRazorpayFundAccount($chef, string $contactId): string
    {
        $response = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->post('https://api.razorpay.com/v1/fund_accounts', [
                'contact_id' => $contactId,
                'account_type' => 'bank_account',
                'bank_account' => [
                    'name' => (string) ($chef->name ?: $chef->business_name),
                    'ifsc' => (string) $chef->ifsc_code,
                    'account_number' => (string) $chef->account_number,
                ],
            ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to create Razorpay fund account: ' . $response->body());
        }

        $fundAccountId = $response->json('id');
        Log::info("Razorpay Fund Account created for Chef #{$chef->id}: {$fundAccountId}");
        return $fundAccountId;
    }

    protected function createRazorpayPayout(string $fundAccountId, int $amountInPaise, array $notes = []): array
{
    try {
        $response = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->withHeaders([
                'X-Razorpay-Account' => $this->accountNumber, // ├░┼╕тАЭ┬е MUST
                'Content-Type' => 'application/json'
            ])
            ->post('https://api.razorpay.com/v1/payouts', [
                'account_number' => $this->accountNumber,
                'fund_account_id' => $fundAccountId,
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'mode' => 'IMPS', // or UPI / NEFT
                'purpose' => 'payout',
                'queue_if_low_balance' => true,
                'reference_id' => 'payout_' . time() . '_' . ($notes['chef_id'] ?? ''),
                'narration' => 'Nutrition Nook Payout',
                'notes' => $notes,
            ]);

        // Debug log (VERY IMPORTANT)
        \Log::info('Razorpay Payout Response', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        if (!$response->successful()) {
            throw new \Exception('Razorpay payout failed: ' . $response->body());
        }

        return $response->json();

    } catch (\Exception $e) {
        \Log::error('Razorpay Payout Error', [
            'message' => $e->getMessage()
        ]);

        throw $e;
    }
}

    public function getMonthlyReport(int $month, int $year): array
    {
        $payouts = Payout::with('chef')
            ->where('payout_month', $month)
            ->where('payout_year', $year)
            ->get();

        return [
            'month' => date('F', mktime(0, 0, 0, $month, 1)),
            'year' => $year,
            'total_payouts' => $payouts->count(),
            'total_amount' => $payouts->where('status', '!=', 'failed')->sum('payout_amount'),
            'total_commission' => $payouts->where('status', '!=', 'failed')->sum('commission_amount'),
            'successful' => $payouts->whereIn('status', ['success', 'processing'])->count(),
            'failed' => $payouts->where('status', 'failed')->count(),
            'payouts' => $payouts,
        ];
    }
}
