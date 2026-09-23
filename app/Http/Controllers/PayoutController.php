<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
 use App\Services\FCMService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Services\ChefPayoutService;
use App\Helpers\CommonHelper;
use App\Models\Payout;
use App\Models\Chef;
use App\Models\Order;
use Carbon\Carbon;

class PayoutController extends Controller
{
    protected $payoutService;

    public function __construct(ChefPayoutService $payoutService)
    {
        $this->payoutService = $payoutService;
    }
    
   
public function markPaid(Request $request, $chef_id)
{
    // ૧. પોપઅપના ડેટાનું વેલિડેશન
    $request->validate([
        'payment_method' => 'required',
        'transaction_id' => 'required',
        'payout_amount'  => 'nullable|numeric|min:0',
    ]);

    DB::beginTransaction();
    try {
        $chef = Chef::findOrFail($chef_id);
        $commRate = $chef->commission ?? 0;

        $unpaidOrders = $this->payoutService->eligibleUnpaidOrders($chef_id);

        if ($unpaidOrders->isEmpty()) {
            return redirect()->back()->with('error', 'No pending orders found.');
        }

        $orderCount = $unpaidOrders->count();

        // Single source of truth (every item + quantity, 90/10 split).
        $breakdown = CommonHelper::chefPayoutBreakdown($unpaidOrders, $commRate);
        $totalDishPrice = $breakdown['dish_total'];
        $totalSecurity  = $breakdown['security'];
        $totalNetPayout = $breakdown['net_payout'];

        // Admin can override the computed net payout (e.g. rounding/bank adjustments); falls back to computed value.
        $finalPayoutAmount = $request->filled('payout_amount') ? (float) $request->payout_amount : $totalNetPayout;

        // ૨. Payout રેકોર્ડ બનાવો (નવી કોલમ સાથે) — same table + status the automated Razorpay payout uses,
        // so this chef isn't picked up again by the next auto-payout run for these same orders.
        $payout = Payout::create([
            'chef_id'           => $chef_id,
            'total_earning'     => $totalDishPrice,
            'commission_amount' => $totalSecurity,
            'payout_amount'     => $finalPayoutAmount,
            'order_count'       => $orderCount,
            'status'            => 'completed',
            'payment_method'    => $request->payment_method,
            'transaction_id'    => $request->transaction_id,
            'payout_month'      => now()->month,
            'payout_year'       => now()->year,
        ]);

        // Link + close out every order this payout covers (same as the automated payout flow).
        Order::whereIn('id', $unpaidOrders->pluck('id'))->update([
            'is_payout_completed' => 1,
            'payout_id'           => $payout->id,
        ]);

        DB::commit();

        // 🔥 નોટિફિકેશન અને ઈમેલ લોજિક
        try {
            $monthName = now()->format('F Y');
            $setting = DB::table('settings')->first();
            $logo = ($setting && $setting->logo) ? url('public/images/' . $setting->logo) : '';

            $notifyTitle = 'Payout Received! 💰';
            // મેસેજમાં ટ્રાન્ઝેક્શન આઈડી પણ બતાવી શકાય
            $notifyBody  = "₹" . number_format($totalNetPayout, 2) . " has been credited via {$request->payment_method}. Ref: {$request->transaction_id}";

            $notifyData = [
                'title'          => $notifyTitle,
                'body'           => $notifyBody,
                'type'           => 'payout_success',
                'payout_id'      => (string) $payout->id,
                'amount'         => (string) $totalNetPayout,
                'payment_method' => $request->payment_method, // NEW: Push માં મોકલ્યું
                'transaction_id' => $request->transaction_id, // NEW: Push માં મોકલ્યું
                'month'          => $monthName,
                'order_count'    => (string) $orderCount,
                'logo'           => $logo,
            ];

            // 1. FCM નોટિફિકેશન
            (new FCMService())->sendNotificationToUser(
                $chef_id,
                $notifyTitle,
                $notifyBody,
                $notifyData
            );

            // 2. ઈમેલ (વિગતો સાથે)
            if ($chef->email) {
                Mail::send('emails.payout-success', [
                    'name'           => $chef->name,
                    'amount'         => number_format($totalNetPayout, 2),
                    'method'         => $request->payment_method,
                    'transaction_id' => $request->transaction_id,
                    'month'          => $monthName,
                    'orderCount'     => $orderCount
                ], function ($message) use ($chef, $monthName) {
                    $message->to($chef->email)
                            ->subject("Payout Received: {$monthName} 💰");
                });
            }

        } catch (\Throwable $e) {
            Log::error("Notification Error: " . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Payment marked as paid and notification sent!');

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("Payout Failed: " . $e->getMessage());
        return redirect()->back()->with('error', 'Something went wrong while processing payout.');
    }
}

    public function index(Request $request)
    {
        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);

        $payouts = Payout::with('chef')
            ->when($request->has('month'), function ($q) use ($month, $year) {
                $q->where('payout_month', $month)->where('payout_year', $year);
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        $monthlyStats = [
            'total_earnings' => Payout::where('payout_month', $month)->where('payout_year', $year)->whereIn('status', ['success', 'completed'])->sum('total_earning'),
            'total_commission' => Payout::where('payout_month', $month)->where('payout_year', $year)->whereIn('status', ['success', 'completed'])->sum('commission_amount'),
            'total_paid' => Payout::where('payout_month', $month)->where('payout_year', $year)->whereIn('status', ['success', 'completed'])->sum('payout_amount'),
            'success_count' => Payout::where('payout_month', $month)->where('payout_year', $year)->whereIn('status', ['success', 'completed'])->count(),
        ];

        $availableMonths = Payout::selectRaw('DISTINCT payout_month, payout_year')
            ->orderByDesc('payout_year')->orderByDesc('payout_month')->get();

        return view('admin.payout.history', compact('payouts', 'monthlyStats', 'month', 'year', 'availableMonths'));
    }

    public function create()
{
    $chefs = Chef::where(function ($q) {
        $q->where('is_delete', 0)->orWhereNull('is_delete');
    })->get();

    $chefSummaries = [];
    foreach ($chefs as $chef) {
        $commRate = $chef->commission ?? 0; // દા.ત. 29%

        $unpaidOrders = $this->payoutService->eligibleUnpaidOrders($chef->id);

        // Single source of truth (every item + quantity, 90/10 split).
        $breakdown = CommonHelper::chefPayoutBreakdown($unpaidOrders, $commRate);
        $totalDishPrice    = $breakdown['dish_total'];
        $totalSecurityHeld = $breakdown['security'];
        $totalNetPayout    = $breakdown['net_payout'];

        if ($unpaidOrders->count() > 0) {
            $chefSummaries[] = [
                'id'                => $chef->id,
                'name'              => $chef->name,
                'commission_rate'   => $commRate,
                'dish_price'        => round($totalDishPrice, 2),
                'security_deposit'  => round($totalSecurityHeld, 2), // 10%
                'net_payout'        => round($totalNetPayout, 2),    // 90%
                'unpaid_orders'     => $unpaidOrders->count(),
                'bank_name'         => $chef->bank_name ?? 'N/A',
                'account_number'    => $chef->account_number ?? 'N/A',
                'has_bank_details'  => !empty($chef->account_number) && $chef->account_number !== 'N/A',
            ];
        }
    }

    $recentPayouts = Payout::with('chef')->orderByDesc('created_at')->limit(10)->get();
    $nextScheduledDate = Carbon::now()->addDays(7); // ટેમ્પરરી ડેટ

    return view('admin.payout.index', compact('chefSummaries', 'recentPayouts', 'nextScheduledDate'));
}

    public function run(Request $request)
    {
        // બલ્ક મેન્યુઅલ પેઆઉટ રન કરો
        $results = $this->payoutService->runBulkPayout();
        return redirect()->back()->with('success', "Manual payout marked as completed for {$results['processed']} chefs.");
    }

    public function chefPayoutSingle(Request $request, $chefId)
    {
        // સિંગલ શેફ માટે મેન્યુઅલ પેઆઉટ
        $result = $this->payoutService->processSingleChefPayout($chefId);

        if ($result['success']) {
            return redirect()->back()->with('success', "Payout successful! Amount: ₹{$result['payout_amount']} marked as paid.");
        }

        return redirect()->back()->with('error', "Failed: {$result['error']}");
    }
}