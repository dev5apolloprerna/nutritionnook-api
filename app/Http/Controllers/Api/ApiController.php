<?php

namespace App\Http\Controllers\Api;

use App\Models\ChefAddress;
use App\Http\Controllers\Api\NotificationController;
use App\Models\Chef;
use Mail;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\DB;
use App\Models\Payout;
use App\Models\ChefServingArea;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Support\Facades\Auth;
use App\Services\RefundService;
use App\Services\FCMService;
use App\Models\HomeScreen;
use Razorpay\Api\Api;
use App\Models\Order;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use PDF;

class ApiController extends Controller
{

    public function getSettings()
{
    $data = DB::table('settings')
        ->select(
            'email',
            'phone_number',
            'start_time',
            'end_time',
            'start_day',
            'end_day'
        )
        // ->whereNull('deleted_at')
        ->first();

    if ($data) {
        // Format time (10 AM - 8 PM)
        $working_hours = null;
        if ($data->start_time && $data->end_time) {
            $working_hours = date('g A', strtotime($data->start_time)) . ' - ' . date('g A', strtotime($data->end_time));
        }

        // Convert full day to short (Monday → Mon)
        $daysMap = [
            'Monday' => 'Mon',
            'Tuesday' => 'Tue',
            'Wednesday' => 'Wed',
            'Thursday' => 'Thu',
            'Friday' => 'Fri',
            'Saturday' => 'Sat',
            'Sunday' => 'Sun',
        ];

        $startDay = $daysMap[$data->start_day] ?? null;
        $endDay = $daysMap[$data->end_day] ?? null;

        $working_days = ($startDay && $endDay) ? "$startDay - $endDay" : null;

        $response = [
            'email' => $data->email,
            'phone_number' => $data->phone_number,
            'working_hours' => $working_hours,
            'working_days' => $working_days,
        ];
    } else {
        $response = null;
    }

    return CommonHelper::apiResponse(
        200,
        true,
        'Settings fetched successfully!',
        $response
    );
}

public function getPreparationTime($order_id)
{
    $order = DB::table('orders')
        ->where('id', $order_id)
        ->first();

    if (!$order) {
        return CommonHelper::apiResponse(
            404,
            false,
            'Order not found!',
            null
        );
    }

    $items = json_decode($order->items, true);

    if (empty($items)) {
        return CommonHelper::apiResponse(
            404,
            false,
            'No items found!',
            null
        );
    }

    // બધા food ids કાઢો
    $foodIds = array_column($items, 'id');

    $response = DB::table('food_dishes')
        ->whereIn('id', $foodIds)
        ->select('id', 'preparation_time_id')
        ->get();

    return CommonHelper::apiResponse(
        200,
        true,
        'Preparation time fetched successfully!',
        $response
    );
}
    
    public function listRazorpayGateway()
    {
    $data = DB::table('razorpay_gateway')
                ->select('id','api_key','created_at','updated_at','deleted_at')
                ->whereNull('deleted_at')
                ->first();

    return CommonHelper::apiResponse(
        200,
        true,
        'Razorpay gateway fetched successfully!',
        $data
    );
}

    public function getPayoutTransactions(Request $request)
{
    $chefId = Auth::id();
    
    $query = Payout::where('chef_id', $chefId);

    /* ==========================
       STATUS FILTER
    ========================== */
    if ($request->filled('payment_status')) {
        $query->where('status', $request->payment_status);
    }

    /* ==========================
       DATE FILTER (created_at)
    ========================== */
    if ($request->filled('from_date') && $request->filled('to_date')) {
        $query->whereBetween(
            DB::raw('DATE(created_at)'),
            [$request->from_date, $request->to_date]
        );
    }
    /* ==========================
       PAGINATION
    ========================== */
    $transactions = $query
        ->latest()
        ->paginate(10)
        ->appends($request->query()); // keeps filters during pagination

    return CommonHelper::apiResponse(
        200,
        true,
        'Payout transactions fetched successfully',
        $transactions
    );
}


/**
 * 6. Get Payout Transaction Details by ID
 * Ensures the chef can only see their own transactions
 */
public function getPayoutDetail($id)
{
    $chefId = Auth::id();

    $payout = Payout::where('chef_id', $chefId)
        ->where('id', $id)
        ->first();

    if (!$payout) {
        return CommonHelper::apiResponse(
            404,
            false,
            'Transaction not found or unauthorized',
            null
        );
    }

    return CommonHelper::apiResponse(
        200,
        true,
        'Transaction details fetched successfully',
        $payout
    );
}
    public function appVersion()
{
    // Assuming single active settings row
    $setting = DB::table('settings')
        ->select(
            'android_latest_version',
            'android_build_number',
            'android_store_url',
            'android_release_notes',
            'ios_latest_version',
            'ios_build_number',
            'ios_store_url',
            'ios_release_notes'
        )
        ->first();

    if (!$setting) {
        return CommonHelper::apiResponse(
            404,
            false,
            'App version settings not found.',
            []
        );
    }

    $data = [
        'android' => [
            'latestVersion' => $setting->android_latest_version,
            'buildNumber'   => (int) $setting->android_build_number,
            'storeUrl'      => $setting->android_store_url,
            'releaseNotes'  => $setting->android_release_notes,
        ],
        'ios' => [
            'latestVersion' => $setting->ios_latest_version,
            'buildNumber'   => (int) $setting->ios_build_number,
            'storeUrl'      => $setting->ios_store_url,
            'releaseNotes'  => $setting->ios_release_notes,
        ]
    ];

    return CommonHelper::apiResponse(
        200,
        true,
        'App version fetched successfully.',
        $data
    );
}

public function updateBufferStatusByOrderId($order_id)
{
    \Log::info('CRON START: update-buffer-status');

    $time = \Carbon\Carbon::now()->subMinutes(2);
    \Log::info('Checking orders created before', ['time' => $time]);

    $orders = Order::with('user')
        ->where('is_buffer', 0)
        ->where('status', '!=', 'rejected')
        ->where('created_at', '<=', $time)
        ->where('id', $order_id)
        ->get();

    \Log::info('Orders fetched for processing', [
        'total_orders' => $orders->count()
    ]);

    $updated = 0;

    foreach ($orders as $order) {

        \Log::info('Processing order', [
            'order_id' => $order->id,
            'chef_id' => $order->chef_id
        ]);

        try {

            /* ---------- UPDATE BUFFER ---------- */
            \Log::info('Updating buffer status', [
                'order_id' => $order->id
            ]);

            $order->is_buffer = 1;
            $order->save();

            \Log::info('Buffer status updated successfully', [
                'order_id' => $order->id
            ]);

            $updated++;

            /* ---------- CUSTOMER ---------- */

            $user = $order->user;
            $customerName = $user->name ?? 'Customer';

            \Log::info('Customer info fetched', [
                'order_id' => $order->id,
                'customer_name' => $customerName
            ]);

            /* ---------- ITEMS (JSON COLUMN) ---------- */

            $items = is_array($order->items) ? $order->items : json_decode($order->items, true);
            $items = $items ?? [];

            \Log::info('Order items parsed', [
                'order_id' => $order->id,
                'items_count' => count($items)
            ]);

            $foodImage = null;

            if (!empty($items)) {

                $firstItemId = $items[0]['id'] ?? null;

                \Log::info('Fetching food image', [
                    'order_id' => $order->id,
                    'dish_id' => $firstItemId
                ]);

                if ($firstItemId) {

                    $dishImage = DB::table('food_dishes')
                        ->where('id', $firstItemId)
                        ->value('image');

                    if ($dishImage) {
                        $foodImage = url($dishImage);

                        \Log::info('Food image found', [
                            'order_id' => $order->id,
                            'image_url' => $foodImage
                        ]);
                    } else {
                        \Log::warning('Food image not found', [
                            'order_id' => $order->id,
                            'dish_id' => $firstItemId
                        ]);
                    }
                }
            }

            /* ---------- LOGO ---------- */

            \Log::info('Fetching application logo');

            $setting = DB::table('settings')->first();

            $logo = ($setting && $setting->logo)
                ? url('public/images/' . $setting->logo)
                : '';

            /* ---------- ORDER TYPE ---------- */

            $orderDate = \Carbon\Carbon::parse($order->date);

            $orderType = $orderDate->isToday()
                ? 'today'
                : ($orderDate->isFuture() ? 'preorder' : 'past');

            \Log::info('Order type determined', [
                'order_id' => $order->id,
                'order_type' => $orderType
            ]);

            /* ---------- PUSH NOTIFICATION ---------- */

            if ($order->payment_status == 'received') {
                
              
                \Log::info('Payment received. Sending push notification', [
                    'order_id' => $order->id
                ]);

                $title = 'New order recieved тЬЕ';
                $body  = "You have received a new order from {$customerName}";

                $data = [
                    'title' => $title,
                    'body' => $body,
                    'type' => 'payment_received',
                    'orderId' => (string) $order->id,
                    'customerName' => $customerName,
                    'amount' => (string) $order->amount,
                    'items' => (string) count($items),
                    'logo' => $logo,
                    'food_image' => $foodImage ?? '',
                    'order_type' => $orderType
                ];

                \Log::info('Sending push notification to chef', [
                    'order_id' => $order->id,
                    'chef_id' => $order->chef_id
                ]);

                $fcmService = new FCMService();

                $result = $fcmService->sendNotificationToUser(
                    $order->chef_id,
                    $title,
                    $body,
                    $data
                );

                \Log::info('FCM Notification result', [
                    'order_id' => $order->id,
                    'result' => $result
                ]);

            } else {

                \Log::info('Push notification skipped because payment not received', [
                    'order_id' => $order->id,
                    'payment_status' => $order->payment_status
                ]);
            }

        } catch (\Throwable $e) {

            \Log::error('Order processing failed', [
                'order_id' => $order->id,
                'error_message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
        }
    }

    \Log::info('CRON FINISHED', [
        'total_orders_updated' => $updated
    ]);

    return response()->json([
        'success' => true,
        'updated_records' => $updated
    ]);
}

public function generateInvoice(Request $request)
{
    $user = Auth::user();
    

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    $request->validate([
        'order_id' => 'required|exists:orders,id'
    ]);

    $order = Order::where('id', $request->order_id)
        // ->where('user_id', $user->id)
        ->first();

    if (!$order) {
        return response()->json([
            'status' => false,
            'message' => 'Order not found'
        ], 404);
    }

    /* ---------- ITEMS ---------- */
    $items = is_string($order->items)
        ? json_decode($order->items, true)
        : $order->items;

    $items = is_array($items) ? $items : [];

    /* ---------- CHEF DETAILS ---------- */
    $chef = \App\Models\Chef::select('name','email','phone_number','fssai_license_number')
        ->where('id', $order->chef_id)
        ->first();

    /* ---------- GST ---------- */
    $gstPercent = (float) str_replace('%', '', DB::table('settings')->value('gst') ?? 0);

    $subtotal = collect($items)->sum('total');
    $gstAmount = ($subtotal * $gstPercent) / 100;

    /* ---------- COUPON ---------- */
    $coupon = null;
    if ($order->coupon_id) {
        $coupon = DB::table('coupons')->where('id', $order->coupon_id)->first();
    }
    $setting = DB::table('settings')->first();
            $platformFee = $setting->platform_fee ?? 0; // જે ટેબલમાં તમારી ફી સેવ છ
    /* ---------- PDF DATA ---------- */
    $data = [
        'order'      => $order,
        'items'      => $items,
        'user'       => $user,
        'chef'       => $chef,
        'subtotal'   => $subtotal,
        'gstPercent' => $gstPercent,
        'gstAmount'  => $gstAmount,
        'coupon'     => $coupon,
        'date'       => \Carbon\Carbon::parse($order->created_at)->format('d M Y'),
        'platformFee' => $platformFee
    ];

    $pdf = \PDF::loadView('pdf.invoice', $data);

    $fileName = 'invoice_order_' . $order->id . '.pdf';
    $path = public_path('invoices');

    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }

    $pdf->save($path . '/' . $fileName);
    
    $data = [
        'invoice_url' => url('public/invoices/' . $fileName)
    ];

    return CommonHelper::apiResponse(
        200,
        true,
        'payment list generated successfully.',
        $data
    );
}

/**
 * Payout runs happen on the 7th and 22nd of every month:
 *  - orders placed 1st-15th  -> paid on the 22nd of that same month
 *  - orders placed 16th-31st -> paid on the 7th of the NEXT month
 * Based on each order's own date, not "today", so the shown date reflects
 * when the order was actually placed rather than when the chef opens the tab.
 *
 * IMPORTANT: a scheduled run that has already passed while the order is still
 * unpaid (a missed / backlogged payout) is rolled forward to the next
 * upcoming run — the "Next Payout" date must always be a real future date,
 * never a stale past one. The unpaid amount itself is never dropped: those
 * orders stay `is_payout_completed = 0`, so they remain in `next_payout_amount`
 * and get swept into whichever run actually pays them.
 *
 * Returns the EARLIEST (future) payout date among the given unpaid orders.
 * Falls back to "the next scheduled run from today" when there are no unpaid
 * orders, so the field is never null/missing.
 */
private function computeNextPayoutDate($unpaidOrders, \Carbon\Carbon $now): \Carbon\Carbon
{
    $today = $now->copy()->startOfDay();

    // The next scheduled payout run (7th / 22nd) on or after today.
    $nextRun = function () use ($today) {
        if ($today->day < 7)  return $today->copy()->day(7);
        if ($today->day < 22) return $today->copy()->day(22);
        return $today->copy()->day(7)->addMonth();
    };

    $dates = [];
    foreach ($unpaidOrders as $order) {
        $orderDate = Carbon::parse($order->date);

        $scheduled = $orderDate->day <= 15
            ? Carbon::create($orderDate->year, $orderDate->month, 22)->startOfDay()
            : Carbon::create($orderDate->year, $orderDate->month, 7)->addMonth()->startOfDay();

        // Missed run -> carry the balance to the next upcoming run.
        if ($scheduled->lt($today)) {
            $scheduled = $nextRun();
        }

        $dates[] = $scheduled;
    }

    if (!empty($dates)) {
        return min($dates);
    }

    return $nextRun();
}

// public function downloadEarningOverviewPdf(Request $request)
// generateEarningsPDF

public function downloadEarningOverviewPdf(Request $request)
{
    $chef = $request->user();
    if (!$chef) {
        return CommonHelper::apiResponse(401, false, 'Unauthorized', []);
    }

    $chefId   = $chef->id;
    $fromDate = $request->from_date;
    $toDate   = $request->to_date;
    $commRate = $chef->commission ?? 0;
    $now = Carbon::now();

    // Chef net payout (90% of menu base price, 10% security) for DELIVERED
    // orders in a window — same basis as the admin Payout page, the actual
    // payout, and the earnings-overview API. Counts every item + quantity.
    $periodPayout = function ($start, $end) use ($chefId, $commRate) {
        $orders = Order::where('chef_id', $chefId)
            ->where('status', 'delivered')
            ->whereBetween('date', [$start, $end])
            ->get();
        return CommonHelper::chefPayoutBreakdown($orders, $commRate)['net_payout'];
    };

    // Today, Week, Month Metrics
    $todayData = ['earnings' => $periodPayout(Carbon::today()->startOfDay(), Carbon::today()->endOfDay())];
    $weekData  = ['earnings' => $periodPayout(Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek())];
    $monthData = ['earnings' => $periodPayout(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth())];

    // Custom Date Range (Past Earning)
    $pastEarning = 0;
    if ($fromDate && $toDate) {
        try {
            $pastEarning = $periodPayout(
                Carbon::parse($fromDate)->startOfDay(),
                Carbon::parse($toDate)->endOfDay()
            );
        } catch (\Exception $e) { $pastEarning = 0; }
    }

    // ========== ૩. પેઆઉટ મેટ્રિક્સ (Pending & Next Payout) ==========
    $pendingPayout = Payout::where('chef_id', $chefId)->where('status', 'processing')->sum('payout_amount');

    // Next Payout Amount (unpaid orders)
    $unpaidOrders = Order::where('chef_id', $chefId)
        ->where('status', 'delivered')
        ->where('is_payout_completed', 0)
        ->get();
    $unpaidData = CommonHelper::chefPayoutBreakdown($unpaidOrders, $commRate);

    $nextPayoutAmount = $unpaidData['net_payout'];
    $platformSecurity = $unpaidData['security']; // This is your 10% security

    $nextPayoutDate = $this->computeNextPayoutDate($unpaidOrders, $now);

    // ========== ૪. PDF DATA & GENERATION ==========
    $pdfData = [
        'chef'                => $chef,
        'today'               => number_format($todayData['earnings'], 2, '.', ''),
        'week'                => number_format($weekData['earnings'], 2, '.', ''),
        'month'               => number_format($monthData['earnings'], 2, '.', ''),
        'past_earning'        => number_format($pastEarning, 2, '.', ''),
        'pending_payout'      => number_format($pendingPayout, 2, '.', ''),
        'next_payout_date'    => $nextPayoutDate->format('d M Y'),
        'next_payout_amount'  => number_format($nextPayoutAmount, 2, '.', ''),
        'platform_commission' => number_format($platformSecurity, 2, '.', ''), // Showing 10% Security
        'fromDate'            => $fromDate,
        'toDate'              => $toDate,
        'generated_at'        => now()->format('d M Y'),
    ];

    $pdf = \PDF::loadView('invoices.earning-overview', $pdfData);
    $fileName = 'earning_overview_' . time() . '.pdf';
    $path = public_path('invoices');
    if (!file_exists($path)) { mkdir($path, 0777, true); }
    $pdf->save($path . '/' . $fileName);

    // ========== ૫. API RESPONSE ==========
    return CommonHelper::apiResponse(200, true, 'Earning overview fetched successfully', [
        'today'              => (float) $todayData['earnings'],
        'week'               => (float) $weekData['earnings'],
        'month'              => (float) $monthData['earnings'],
        'past_earning'       => (float) $pastEarning,
        'pending_payout'     => (float) $pendingPayout,
        'next_payout_date'   => $nextPayoutDate->format('d M Y'),
        'next_payout_amount' => (float) $nextPayoutAmount,
        'from_date'          => $fromDate,
        'to_date'            => $toDate,
        'pdf_url'            => url('public/invoices/' . $fileName),
    ]);
}

// public function overviewDetails(Request $request)
// {
//     $chef = $request->user();
//     if (!$chef) {
//         return CommonHelper::apiResponse(401, false, 'Unauthorized', []);
//     }

//     $chefId = $chef->id;
//     $now = Carbon::now();

//     $baseQuery = Order::where('chef_id', $chefId)
//         ->where('status', 'delivered')
//         ->where('payment_status', 'received');
        
   

//     // ગણતરી: Security Deposit ₹24.90 છે, તો પેઆઉટ (90%) = 24.90 * 9 = 224.10
//     $getChefEarning = function($query) {
//         return (float) $query->sum(DB::raw("security_deposit * 9"));
//     };

//     $today = $getChefEarning((clone $baseQuery)->whereDate('created_at', Carbon::today()));

//     $weekStart = $now->copy()->startOfWeek();
//     if ($weekStart->month != $now->month) { $weekStart = $now->copy()->startOfMonth(); }
//     $week = $getChefEarning((clone $baseQuery)->whereBetween('created_at', [$weekStart, $now->copy()->endOfWeek()]));

//     $month = $getChefEarning((clone $baseQuery)->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]));

//     $year = $getChefEarning((clone $baseQuery)->whereYear('created_at', $now->year));

//     $unpaidOrdersQuery = Order::where('chef_id', $chefId)
//         ->where('status', 'delivered')
//         ->where('is_payout_completed', 0);

//     $securityDeposit = (float) (clone $unpaidOrdersQuery)->sum('security_deposit');
//     $nextPayoutAmount = $getChefEarning(clone $unpaidOrdersQuery);
    
//     $now = Carbon::now();

//     if ($now->day < 7) {
//         $nextPayoutDate = Carbon::create($now->year, $now->month, 7);
//     } elseif ($now->day < 22) {
//         $nextPayoutDate = Carbon::create($now->year, $now->month, 22);
//     } else {
//         $nextPayoutDate = Carbon::create($now->year, $now->month, 7)->addMonth();
//     }

//     return CommonHelper::apiResponse(200, true, 'Overview details fetched successfully', [
//         'today'               => number_format($today, 2, '.', ''),
//         'week'                => number_format($week, 2, '.', ''),
//         'month'               => number_format($month, 2, '.', ''),
//         'year'                => number_format($year, 2, '.', ''),
//         'pending_payout'      => number_format(Payout::where('chef_id', $chefId)->where('status', 'processing')->sum('payout_amount'), 2, '.', ''),
//         'next_payout_date'    => $nextPayoutDate->format('d M Y'),
//         'next_payout_amount'  => number_format($nextPayoutAmount, 2, '.', ''), 
//         'platform_commission' => number_format($securityDeposit, 2, '.', '')  
//     ]);
// }

public function overviewDetails(Request $request)
{
    $chef = $request->user();
    if (!$chef) {
        return CommonHelper::apiResponse(401, false, 'Unauthorized', []);
    }

    $chefId = $chef->id;
    $commRate = $chef->commission ?? 0;
    $now = Carbon::now();

    $fromDate = $request->from_date;
    $toDate   = $request->to_date;

    // Chef's net payout (90% of the menu base price, 10% held as security) for
    // DELIVERED orders in a date window — the SAME basis as the admin Payout
    // page and the actual bank payout. Uses CommonHelper::chefPayoutBreakdown
    // so every item + quantity in the order is counted.
    $periodPayout = function ($start, $end) use ($chefId, $commRate) {
        $orders = Order::where('chef_id', $chefId)
            ->where('status', 'delivered')
            ->whereBetween('date', [$start, $end])
            ->get();
        return CommonHelper::chefPayoutBreakdown($orders, $commRate)['net_payout'];
    };

    // Today / Week / Month always reflect the live current period.
    $today = $periodPayout(Carbon::today()->startOfDay(), Carbon::today()->endOfDay());
    $week  = $periodPayout($now->copy()->startOfWeek(), $now->copy()->endOfWeek());
    $month = $periodPayout($now->copy()->startOfMonth(), $now->copy()->endOfMonth());

    // Year card: a custom range (from_date + to_date) overrides it; otherwise
    // the current calendar year.
    if ($fromDate && $toDate) {
        try {
            $year = $periodPayout(
                Carbon::parse($fromDate)->startOfDay(),
                Carbon::parse($toDate)->endOfDay()
            );
        } catch (\Exception $e) {
            $year = $periodPayout($now->copy()->startOfYear(), $now->copy()->endOfYear());
        }
    } else {
        $year = $periodPayout($now->copy()->startOfYear(), $now->copy()->endOfYear());
    }

    // Pending payout = unpaid delivered orders (all-time), same breakdown.
    $unpaidOrders = Order::where('chef_id', $chefId)
        ->where('status', 'delivered')
        ->where('is_payout_completed', 0)
        ->get();
    $pending = CommonHelper::chefPayoutBreakdown($unpaidOrders, $commRate);

    $nextPayoutDate = $this->computeNextPayoutDate($unpaidOrders, $now);

    return CommonHelper::apiResponse(200, true, 'Overview details fetched successfully', [
        // The chef app parses these 4 as strict ints (`json['x'] as int?`) —
        // they MUST be bare JSON integers, not number_format()'d strings.
        'today'               => (int) round($today),
        'week'                => (int) round($week),
        'month'               => (int) round($month),
        'year'                => (int) round($year),
        'pending_payout'      => number_format(Payout::where('chef_id', $chefId)->where('status', 'processing')->sum('payout_amount'), 2, '.', ''),
        'next_payout_date'    => $nextPayoutDate->format('d M Y'),
        'next_payout_amount'  => number_format($pending['net_payout'], 2, '.', ''),
        'platform_commission' => number_format($pending['security'], 2, '.', ''),
    ]);
}
    
public function customerDeleteAccount(Request $request)
{
    // ЁЯФР Bearer token se logged-in user
    $user = $request->user();

    if (!$user) {
        return CommonHelper::apiResponse(
            401,
            false,
            'Unauthorized',
            []
        );
    }

    // тЭМ Already deleted check
    if ($user->is_delete == 1) {
        return CommonHelper::apiResponse(
            400,
            false,
            'Account already deleted',
            []
        );
    }

    // ЁЯЧС Soft delete
    $user->is_delete  = 1;
    // $user->deleted_at = now();
    $user->save();

    // ЁЯФС Current token revoke
    if ($request->user()->currentAccessToken()) {
        $request->user()->currentAccessToken()->delete();
    }

    return CommonHelper::apiResponse(
        200,
        true,
        'User account deleted successfully',
        []
    );
}
   public function chefDeleteAccount(Request $request)
{
    // ЁЯФР Bearer token se logged-in chef
    $chef = $request->user();

    if (!$chef || !$chef instanceof Chef) {
        return CommonHelper::apiResponse(
            401,
            false,
            'Chef not found or unauthorized',
            []
        );
    }

    // тЭМ Already deleted check
    if ($chef->is_delete == 1) {
        return CommonHelper::apiResponse(
            400,
            false,
            'Chef account already deleted',
            []
        );
    }

    // ЁЯЧС Soft delete
    $chef->is_delete  = 1;
    // $chef->deleted_at = now();
    $chef->save();

    // ЁЯФС Current token revoke
    if ($request->user()->currentAccessToken()) {
        $request->user()->currentAccessToken()->delete();
    }

    return CommonHelper::apiResponse(
        200,
        true,
        'Chef account deleted successfully',
        []
    );
}
    

   public function getAllergies()
{
    // Fetch allergies from database where status is active
    $allergies = \App\Models\Allergy::where('status', 'active')
                        ->orderBy('title', 'asc')
                        ->get();
    
    // Transform the data to match the original response format
    $options = $allergies->map(function($allergy) {
        return [
            'name' => strtolower($allergy->title) // Convert to lowercase to match original format
        ];
    })->toArray();
    
    // If no allergies found, you can either return empty array or default options
    // Uncomment below if you want to keep default options as fallback
    // if (empty($options)) {
    //     $options = [
    //         ['name' => 'dairy'],
    //         ['name' => 'nuts'],
    //         ['name' => 'gluten'],
    //         ['name' => 'soy'],
    //         ['name' => 'none'],
    //         ['name' => 'other'],
    //     ];
    // }
    
    return CommonHelper::apiResponse(200, true, 'Allergies fetched successfully', $options);
}

    public function getHomeScreens()
    {
        $items = HomeScreen::all()->map(function ($item) {
            return [
                'id'           => $item->id,
                'title'        => $item->title,
                'subtext'      => $item->subtext,
                'button_title' => $item->button_title,
                'image'        => $item->image ? asset('/' . $item->image) : null,
            ];
        });

        // return response()->json([
        //     'status' => true,
        //     'data'   => $items
        // ]);

        return CommonHelper::apiResponse(200, true, 'Home Screen details fetched successfully', $items);
    }
    public function aboutUs()
    {
        $page = DB::table('pages')->where('title', 'about us')->first();

        if (!$page) {
            return CommonHelper::apiResponse(404, false, 'Terms & Conditions not found', []);
        }

        // Decode HTML entities (&amp; -> &, &nbsp; -> space, etc.)
        $content = html_entity_decode($page->description);

        // Remove HTML tags
        $content = strip_tags($content);

        // Remove new lines
        $content = preg_replace("/\r\n|\r|\n/", " ", $content);

        // Remove tabs
        $content = preg_replace("/\t+/", " ", $content);

        // Collapse multiple spaces into one
        $content = preg_replace('/\s{2,}/', ' ', $content);

        return CommonHelper::apiResponse(200, true, 'Terms & Conditions fetched successfully', [
            'title'   => $page->title,
            'content' => trim($content)
        ]);
    }
    public function termsAndConditions()
    {
        $page = DB::table('pages')->where('title', 'Terms and Condition')->first();

        if (!$page) {
            return CommonHelper::apiResponse(404, false, 'Terms & Conditions not found', []);
        }

        // Decode HTML entities (&amp; -> &, &nbsp; -> space, etc.)
        $content = html_entity_decode($page->description);

        // Remove HTML tags
        $content = strip_tags($content);

        // Remove new lines
        $content = preg_replace("/\r\n|\r|\n/", " ", $content);

        // Remove tabs
        $content = preg_replace("/\t+/", " ", $content);

        // Collapse multiple spaces into one
        $content = preg_replace('/\s{2,}/', ' ', $content);

        return CommonHelper::apiResponse(200, true, 'Terms & Conditions fetched successfully', [
            'title'   => $page->title,
            'content' => trim($content)
        ]);
    }



    public function privacyPolicy()
    {
        $page = DB::table('pages')->where('title', 'Privacy-Policy')->first();
        // $page = DB::table('pages')->where('title', 'Terms and Condition')->first();

        if (!$page) {
            return CommonHelper::apiResponse(404, false, 'Terms & Conditions not found', []);
        }

        // Decode HTML entities (&amp; -> &, &nbsp; -> space, etc.)
        $content = html_entity_decode($page->description);

        // Remove HTML tags
        $content = strip_tags($content);

        // Remove new lines
        $content = preg_replace("/\r\n|\r|\n/", " ", $content);

        // Remove tabs
        $content = preg_replace("/\t+/", " ", $content);

        // Collapse multiple spaces into one
        $content = preg_replace('/\s{2,}/', ' ', $content);

        return CommonHelper::apiResponse(200, true, 'Terms & Conditions fetched successfully', [
            'title'   => $page->title,
            'content' => trim($content)
        ]);
    }


    public function updateProfile(Request $request)
    {
        $chef = auth()->user(); // logged in chef
        
        
        if (empty($chef)) {
            return CommonHelper::apiResponse(401, false, 'Unauthorized access', []);
        }

        $validator = Validator::make($request->all(), [
            'name'            => 'sometimes|string|max:255',
            'business_name'   => 'sometimes|string|max:255',
            'email'           => 'sometimes|email|unique:chefs,email,' . $chef->id,
            'phone_number'    => 'sometimes|max:15|unique:chefs,phone_number,' . $chef->id,
            'dob'             => 'sometimes|date',
            'gender'          => 'sometimes|in:male,female,other',
            'kitchen_name'    => 'sometimes|string|max:255',
            'address'         => 'sometimes|string',
            'about_chef '     => 'sometimes|string',
            'city '     => 'sometimes|string',
            'opening_time'    => 'sometimes|string',
            'closing_time'    => 'sometimes|string',

            // new fields
            'shop_plot_number' => 'sometimes|max:255',
            'pincode' => 'sometimes|max:255',
            'floor'           => 'sometimes|string|max:50',
            'commission'           => 'sometimes|max:50',
            'building_name'   => 'sometimes|string|max:255',
            'latitude'        => 'sometimes|numeric|between:-90,90',
            'longitude'       => 'sometimes|numeric|between:-180,180',
            'working_days'    => 'sometimes|array', // e.g. ["monday","tuesday"]
            'working_days.*'  => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',

            'cover_image'     => 'sometimes|image|mimes:jpeg,png,jpg,gif',
            'profile_image'   => 'sometimes|image|mimes:jpeg,png,jpg,gif',
            'is_pre_order' => 'sometimes|in:0,1,true,false'
        ]);

        if ($validator->fails()) {
            return CommonHelper::apiResponse(422, false, $validator->errors()->first(), []);
        }


        // $chef->update($request->only([
        //     'name',
        //     'business_name',
        //     'email',
        //     'phone_number',
        //     'dob',
        //     'gender',
        //     'kitchen_name',
        //     'address',
        //     'about_chef',
        //     'city',
        //     'opening_time',
        //     'closing_time',
        //     'shop_plot_number',
        //     'floor',
        //     'building_name',
        //     'latitude',
        //     'longitude',
        //     'working_days',
        // ]));

        $data = $request->only([
            'name',
            'business_name',
            'email',
            'phone_number',
            'dob',
            'gender',
            'kitchen_name',
            'address',
            'about_chef',
            'city',
            'opening_time',
            'closing_time',
            'shop_plot_number',
            'floor',
            'building_name',
            'latitude',
            'longitude',
            'working_days',
            'is_pre_order',
            'pincode',
            'commission'
        ]);

        // тЬЕ Handle cover image upload
        if ($request->hasFile('cover_image')) {
            $image      = $request->file('cover_image');
            $imageName  = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images'), $imageName);

            // path store hoga "public/images/filename.ext"
            $data['cover_image'] = 'public/images/' . $imageName;
        }

        if ($request->hasFile('profile_image')) {
            $image      = $request->file('profile_image');
            $imageName  = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images'), $imageName);

            // path store hoga "public/images/filename.ext"
            $data['profile_image'] = 'public/images/' . $imageName;
        }
        
        if (!empty($data['opening_time'])) {
            try {
                $data['opening_time'] = \Carbon\Carbon::parse($data['opening_time'])->format('h:i A');
            } catch (\Exception $e) {}
        }
    
        if (!empty($data['closing_time'])) {
            try {
                $data['closing_time'] = \Carbon\Carbon::parse($data['closing_time'])->format('h:i A');
            } catch (\Exception $e) {}
        }
        
        if ($request->has('working_days')) {
        $days = $request->input('working_days');
        
        // જો એપમાંથી working_days[0]=monday ફોર્મેટમાં ડેટા આવે 
        // તો લારાવેલ તેને એરે ગણશે. આપણે તેને clean array બનાવીએ.
        $cleanDays = is_array($days) ? array_values($days) : [$days];
        $data['working_days'] = $cleanDays;
        }

        // тЬЕ Update chef
        $chef->update($data);

        $chef->refresh();

        // тЬЕ Asset path ke sath image return karo
        if (!empty($chef->cover_image)) {
            $chef->cover_image = asset($chef->cover_image);
        }
        if (!empty($chef->profile_image)) {
            $chef->profile_image = asset($chef->profile_image);
        }


        return CommonHelper::apiResponse(200, true, 'Profile updated successfully', $chef);
    }

    public function deleteAccount()
    {
        $chef = auth()->user();

        if (empty($chef)) {
            return CommonHelper::apiResponse(401, false, 'Unauthorized access', []);
        }

        $chef->is_delete = true;
        $chef->save();

        return CommonHelper::apiResponse(200, true, 'Account deleted successfully', []);
    }

    public function sendNotification(Request $request)
    {
        $request->validate([
            'chef_id'       => 'required|exists:users,id',
            'order_id'      => 'required|string',
            'customer_name' => 'required|string',
            'amount'        => 'required|string',
            'items'         => 'required|string', // e.g. "Pizza x2, Pasta x1"
        ]);

        $chef = DB::table('users')->where('id', $request->chef_id)->first();

        if (!$chef || !$chef->fcm_token) {
            return CommonHelper::apiResponse(404, false, 'Chef does not have an FCM token', []);
        }

        // Firebase server key
        $SERVER_API_KEY = env('FIREBASE_SERVER_KEY');

        // Build payload
        $payload = [
            "to" => $chef->fcm_token,
            "priority" => "high",
            "notification" => [
                "title" => "New Order Received ЁЯН╜я╕П",
                "body"  => "You have a new order from {$request->customer_name}",
                "sound" => "default"
            ],
            "data" => [
                "type"          => "new_order",
                "orderId"       => $request->order_id,
                "customerName"  => $request->customer_name,
                "amount"        => $request->amount,
                "items"         => $request->items,
                // "click_action"  => "FLUTTER_NOTIFICATION_CLICK"
            ]
        ];

        // Send to Firebase
        $response = Http::withHeaders([
            'Authorization' => 'key=' . $SERVER_API_KEY,
            'Content-Type'  => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', $payload);

        return CommonHelper::apiResponse(200, true, 'Notification sent successfully!', [
            'firebase_response' => $response->json()
        ]);
    }
    //  public function listChefOrders(Request $request)
    // {
    //     $chefId = auth()->id();

    //     // Get pagination params (with defaults)
    //     $perPage = $request->get('per_page', 20);
    //     $page    = $request->get('page', 1);

    //     $query = DB::table('orders as o')
    //         ->join('users as u', 'o.user_id', '=', 'u.id')
    //         ->select(
    //             'o.id',
    //             'o.user_id',
    //             'u.name as customer_name',
    //             'o.items',
    //             'o.amount',
    //             'o.date',
    //             'o.status',
    //             'o.created_at'
    //         )
    //         ->where('o.chef_id', $chefId);

    //     // тЬЕ Apply unified search filter
    //     if ($request->filled('search')) {
    //         $search = $request->search;

    //         $query->where(function ($q) use ($search) {
    //             // Search in customer name
    //             $q->where('u.name', 'like', '%' . $search . '%')

    //               // Search in item name inside JSON column
    //               ->orWhere('o.items', 'like', '%"name":"' . $search . '%')

    //               // Search by amount (numeric check)
    //               ->orWhere(function ($q2) use ($search) {
    //                   if (is_numeric($search)) {
    //                       $q2->where('o.amount', $search);
    //                   }
    //               })

    //               // Search by date (YYYY-MM-DD format)
    //               ->orWhere(function ($q3) use ($search) {
    //                   if (strtotime($search)) {
    //                       $q3->whereDate('o.date', $search);
    //                   }
    //               });
    //         });
    //     }

    //     // тЬЕ Apply status filter (new, accepted, preparing, ready, delivered, rejected)
    //     if ($request->filled('status')) {
    //         $query->where('o.status', $request->status);
    //     }

    //     // Execute with pagination
    //     $orders = $query->orderBy('o.created_at', 'desc')
    //         ->paginate($perPage, ['*'], 'page', $page);

    //     // Decode items JSON
    //     $orders->getCollection()->transform(function ($order) {
    //         $order->items = json_decode($order->items, true);
    //         return $order;
    //     });

    //     if ($orders->isEmpty()) {
    //         return CommonHelper::apiResponse(404, false, 'No orders found', []);
    //     }

    //     return CommonHelper::apiResponse(200, true, 'Orders fetched successfully!', $orders);
    // }

    public function listChefOrders(Request $request)
    {
        $chefId = auth()->id();

        // Get pagination params (with defaults)
        $perPage = $request->get('per_page', 20);
        $page    = $request->get('page', 1);
        
        $tomorrow = Carbon::tomorrow();
        $orderType = $request->get('order_type');
        $dateType = $request->get('date_type', 'today');
        $today = Carbon::today();
        // dd($orderType);
        $query = DB::table('orders as o')
    ->join('users as u', 'o.user_id', '=', 'u.id')
    ->select(
        'o.id',
        'o.user_id',
        'u.name as customer_name',
        'o.items',
        'o.amount',
        'o.date',
        'o.status',
        'o.created_at',
        'o.updated_at',
        'o.rejected_by',
        'o.name',
        'o.price',
        'o.description',
        'o.image'
    )
    ->where('o.chef_id', $chefId)
    ->where('is_buffer', 1)
    ->where('payment_status','received')
    ->when($dateType === 'today', function ($q) use ($today) {
        $q->whereDate('o.date', $today);
    })
    ->when($dateType === 'tomorrow', function ($q) use ($tomorrow) {
        $q->whereDate('o.date', $tomorrow);
    })
    ->when($dateType == 'preorder', function ($q) {
        $q->whereDate('o.date', '>', Carbon::today());
    })
    ->when($dateType === 'all', function ($q) {
        // no date filter
    });
        if ($request->filled('search')) {
            $search = $request->search;
            
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('o.id', $search);
                    return;
                }
                $q->where('u.name', 'like', '%' . $search . '%')
                    ->orWhere('o.items', 'like', '%"name":"' . $search . '%')
                    ->orWhere(function ($sub) use ($search) {
                        if (is_numeric($search)) {
                            $sub->where('o.amount', $search);
                        }
                    })
                    ->orWhere(function ($sub) use ($search) {
                        if (strtotime($search)) {
                            $sub->whereDate('o.date', $search);
                        }
                    });
            });
        }

        // тЬЕ Status filter
        if ($request->filled('status')) {
            $query->where('o.status', $request->status);
        }

        // Execute with pagination
        $orders = $query->orderBy('o.created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // ------- TRANSFORM RESULT ------- //
        // $orders->getCollection()->transform(function ($order) {

        //     // Decode items JSON from orders table
        //     $itemsArr = json_decode($order->items, true);
        //     $items = [];

        //     if (is_array($itemsArr)) {
        //         foreach ($itemsArr as $item) {

        //             // Fetch dish details including preparation_time_id
        //             $dish = DB::table('food_dishes')
        //                 ->select('id', 'name', 'price', 'image', 'preparation_time_id')
        //                 ->where('id', $item['id'])
        //                 ->first();

        //             if ($dish) {
        //                 $quantity = $item['quantity'];
        //                 $total_price = number_format($dish->price * $quantity, 2);

        //                 $items[] = [
        //                     'id'          => $dish->id,
        //                     'name'        => $dish->name,
        //                     'price'       => number_format($dish->price, 2),
        //                     'quantity'    => $quantity,
        //                     'total_price' => $total_price,
        //                     'image'       => $dish->image ? asset($dish->image) : null,

        //                     // ЁЯСЙ preparation_time_id only when status = preparing
        //                     'preparation_time_id' => $order->status === 'preparing'
        //                         ? $dish->preparation_time_id
        //                         : null,
        //                 ];
        //             }
        //         }
        //     }

        //     // Replace items with transformed items
        //     $order->items = $items;

        //     return $order;
        // });
        
        
        // Pre-fetch every dish referenced across this whole page in ONE query,
        // instead of one query per item per order (was N+1: ~60 extra queries
        // for a page of 20 orders x 3 items).
        $dishIds = $orders->getCollection()
            ->flatMap(function ($order) {
                $itemsArr = json_decode($order->items, true);
                return is_array($itemsArr) ? array_column($itemsArr, 'id') : [];
            })
            ->unique()
            ->values();

        $dishesById = DB::table('food_dishes')
            ->select('id', 'name', 'price', 'image', 'preparation_time_id')
            ->whereIn('id', $dishIds)
            ->get()
            ->keyBy('id');

        $orders->getCollection()->transform(function ($order) use ($dishesById) {

            // Make amount string format
            $order->amount = number_format((float)$order->amount, 2, '.', '');

            // Decode items JSON from orders table
            $itemsArr = json_decode($order->items, true);
            $items = [];

            if (is_array($itemsArr)) {
                foreach ($itemsArr as $item) {

                    $dish = $dishesById->get($item['id']);

                    if ($dish) {
                        $quantity = $item['quantity'];
                        $total_price = number_format($dish->price * $quantity, 2, '.', '');

                        $items[] = [
                            'id'          => $dish->id,
                            'name'        => $dish->name,
                            'price'       => number_format($dish->price, 2, '.', ''),
                            'quantity'    => $quantity,
                            'total_price' => $total_price,
                            'image'       => $dish->image ? asset($dish->image) : null,
                            'preparation_time_id' => $order->status === 'preparing'
                                ? $dish->preparation_time_id
                                : null,
                        ];
                    }
                }
            }

            $order->items = $items;

            return $order;
        });


        if ($orders->isEmpty()) {
            return CommonHelper::apiResponse(404, false, 'No orders found', []);
        }

        return CommonHelper::apiResponse(200, true, 'Orders fetched successfully!', $orders);
    }




   public function acceptOrder($orderId)
{
    $chefId = auth()->id();

    $order = DB::table('orders')->where('id', $orderId)->first();
    if (!$order) {
        return CommonHelper::apiResponse(404, false, 'Order not found', []);
    }
    if ($order->chef_id != $chefId) {
        return CommonHelper::apiResponse(403, false, 'Unauthorized', []);
    }

    $newStatus = 'accepted';

    DB::table('orders')->where('id', $orderId)->update([
        'status'     => $newStatus,
        'updated_at' => now(),
        'accepted_at' => now()
    ]);

    DB::table('order_status_histories')->updateOrInsert(
        ['order_id' => $orderId, 'chef_id' => $chefId],
        ['status' => $newStatus, 'updated_at' => now(), 'created_at' => now()]
    );

    /* ---------- PUSH NOTIFICATION TO USER ---------- */
    try {
        $user = DB::table('users')->where('id', $order->user_id)->first();
        $customerName = $user->name ?? 'Customer';

        $items = is_array($order->items) ? $order->items : json_decode($order->items, true);
        $items = $items ?? [];

        $foodImage = '';
        if (!empty($items)) {
            $firstItemId = $items[0]['id'] ?? null;
            if ($firstItemId) {
                $dishImage = DB::table('food_dishes')->where('id', $firstItemId)->value('image');
                if ($dishImage) {
                    $foodImage = url($dishImage);
                }
            }
        }

        $setting = DB::table('settings')->first();
        $logo = ($setting && $setting->logo) ? url('public/images/' . $setting->logo) : '';

        $title = 'Order Accepted';
        $body  = "Your order #{$orderId} has been accepted by the chef";
        $orderDate = Carbon::parse($order->date);

        $orderType = $orderDate->isToday()
            ? 'today'
            : ($orderDate->isFuture() ? 'preorder' : 'past');
        $data = [
            'title' => $title,
            'body'  => $body,
            'type'  => 'order_accepted',
            'orderId' => (string) $orderId,
            'customerName' => $customerName,
            'amount' => (string) $order->amount,
            'items'  => (string) count($items),
            'logo'   => $logo,
            'food_image' => $foodImage,
            'order_type' => $orderType,
            // 'order_type' => 
            // 'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ];

        $fcmService = new FCMService();
        $result = $fcmService->sendNotificationToUser($order->user_id, $title, $body, $data);

        \Log::info('Accept Order Push Sent', [
            'order_id' => $orderId,
            'result' => $result,
            'data' => $data
        ]);

    } catch (\Throwable $e) {
        \Log::warning('Accept order push failed', [
            'order_id' => $orderId,
            'error' => $e->getMessage()
        ]);
    }
    
    // ========== ЁЯУз EMAIL SENDING AFTER ORDER ACCEPTED ==========
    try {
        // Get user details for email
        $user = DB::table('users')->where('id', $order->user_id)->first();
        
        // Get chef details
        $chef = DB::table('chefs')->where('id', $chefId)->first();
        $updatedAddress = "";
        // Decode items if needed
        $items = is_array($order->items) ? $order->items : json_decode($order->items, true);
        // $updatedAddressData = \DB::table('user_addresses')->where('user_id',$user->id)->first();
        
        // if(isset($updatedAddress)){
        //     $updatedAddress = $updatedAddressData->full_address;
        // }else{
        //     $updatedAddress = $user->address;
        // }
        $updatedAddress = $chef->address;
        // Send email to customer
        if (!empty($user->email)) {
            Mail::send('emails.order_accepted', [
                'user' => $user,
                'chef' => $chef,
                'order_id' => $orderId,
                'items' => $items,
                'amount' => $order->amount,
                'order_date' => $order->date,
                'accept_time' => now()->format('d M Y, h:i A'),
                'address' =>$updatedAddress,
            ], function($message) use ($user, $orderId) {
                $message->to($user->email)
                        ->subject('Order Accepted #' . $orderId . ' - Food App');
            });
        }
        
        // Optional: Send email to chef as confirmation
        if (!empty($chef->email)) {
            Mail::send('emails.order_accepted_chef', [
                'chef' => $chef,
                'user' => $user,
                'order_id' => $orderId,
                'items' => $items,
                'amount' => $order->amount,
                'order_date' => $order->date,
                'accept_time' => now()->format('d M Y, h:i A')
            ], function($message) use ($chef, $orderId) {
                $message->to($chef->email)
                        ->subject('Order Acceptance Confirmation #' . $orderId . ' - Food App');
            });
        }
        
    } catch (\Exception $e) {
        // Log email error but don't affect response
        \Log::error('Order acceptance email failed: ' . $e->getMessage());
    }
    // ========== ЁЯУз EMAIL SENDING END ==========


    return CommonHelper::apiResponse(200, true, 'Order accepted successfully', []);
}

// public function rejectOrder(Request $request, $orderId)
// {
      
//     $chefId = auth()->id();
   
//     $order = DB::table('orders')->where('id', $orderId)->first();
//     if (!$order) {
//         return CommonHelper::apiResponse(404, false, 'Order not found', []);
//     }

//     if ($order->chef_id != $chefId) {
//         return CommonHelper::apiResponse(403, false, 'You are not allowed to access this order', []);
//     }

//     /* ================= DEFAULT VALUES (OLD FLOW) ================= */

//     $refundPercentage = 0;
//     $diffValue = 0;
//     $diffType = '';
//     $refundMessage = '';

//     $hasRefundLogic = $request->has('is_pre_order');
    
//     /* ================= NEW : REFUND CALCULATION (OPTIONAL) ================= */
    
//     if ($hasRefundLogic) {

//         $now = now();
//         $orderDate = Carbon::parse($order->date);
//         $isPreOrder = (int) $request->is_pre_order;

//         /* ---------- INSTANT ORDER ---------- */
//         if ($isPreOrder === 0) {

//             $diffSeconds = $orderDate->diffInSeconds($now);
//             $diffValue = $diffSeconds;
//             $diffType = 'seconds';

//             if ($diffSeconds <= 120) {
//                 $refundPercentage = 100;
//                 $refundMessage = 'Order rejected within 2 minutes. 100% refund applicable.';
//             } else {
//                 $refundPercentage = 0;
//                 $refundMessage = 'Order preparation has started. Refund not applicable.';
//             }
//         }

//         /* ---------- PRE ORDER ---------- */
//         if ($isPreOrder === 1) {

//             $diffHours = $now->diffInHours($orderDate, false);
//             $diffValue = $diffHours;
//             $diffType = 'hours';

//             if ($diffHours >= 48) {
//                 $refundPercentage = 100;
//                 $refundMessage = 'Order rejected before 48 hours. 100% refund applicable.';
//             } elseif ($diffHours >= 24) {
//                 $refundPercentage = 50;
//                 $refundMessage = 'Order rejected between 24тАУ48 hours. 50% refund applicable.';
//             } else {
//                 $refundPercentage = 0;
//                 $refundMessage = 'Order rejected within 24 hours. Refund not applicable.';
//             }
//         }
//     }

//     /* ================= EXISTING STATUS UPDATE ================= */

//     $newStatus = 'rejected';

//     DB::table('orders')->where('id', $orderId)->update([
//         'status'            => $newStatus,
//         'refund_percentage' => $refundPercentage,
//         'updated_at'        => now(),
//     ]);

//     DB::table('order_status_histories')->updateOrInsert(
//         ['order_id' => $orderId, 'chef_id' => $chefId],
//         ['status' => $newStatus, 'updated_at' => now(), 'created_at' => now()]
//     );

//     /* ---------- PUSH NOTIFICATION (ALWAYS SEND) ---------- */
//     try {
//         $user = DB::table('users')->where('id', $order->user_id)->first();
//         $customerName = $user->name ?? 'Customer';

//         $items = is_array($order->items) ? $order->items : json_decode($order->items, true);
//         $items = $items ?? [];

//         $foodImage = '';
//         if (!empty($items)) {
//             $firstItemId = $items[0]['id'] ?? null;
//             if ($firstItemId) {
//                 $dishImage = DB::table('food_dishes')->where('id', $firstItemId)->value('image');
//                 if ($dishImage) {
//                     $foodImage = url($dishImage);
//                 }
//             }
//         }

//         $setting = DB::table('settings')->first();
//         $logo = ($setting && $setting->logo) ? url('public/images/' . $setting->logo) : '';

//         $title = 'Order Rejected тЭМ';
//         $body  = "Your order #{$orderId} has been rejected by the chef";

//         $data = [
//             'title'             => $title,
//             'body'              => $body,
//             'type'              => 'order_rejected',
//             'orderId'           => (string) $orderId,
//             'customerName'      => $customerName,
//             'amount'            => (string) $order->amount,
//             'items'             => (string) count($items),
//             'refund_percentage' => (string) $refundPercentage,
//             'logo'              => $logo,
//             'food_image'        => $foodImage,
//             'click_action'      => 'FLUTTER_NOTIFICATION_CLICK',
//         ];

//         $fcmService = new FCMService();
//         $fcmService->sendNotificationToUser(
//             $order->user_id,
//             $title,
//             $body,
//             $data
//         );

//     } catch (\Throwable $e) {
//         \Log::warning('Reject order push failed', [
//             'order_id' => $orderId,
//             'error'    => $e->getMessage()
//         ]);
//     }

//     /* ================= FINAL RESPONSE ================= */

//     // OLD FLOW RESPONSE
//     if (!$hasRefundLogic) {
//         return CommonHelper::apiResponse(200, true, 'Order rejected successfully', []);
//     }

//     // NEW FLOW RESPONSE
//     return CommonHelper::apiResponse(200, true, 'Order rejected successfully', [
//         'refund_percentage' => $refundPercentage,
//         'diff_value'        => number_format($diffValue,2),
//         'diff_type'         => $diffType,
//         'message'           => $refundMessage,
//     ]);
// }

public function rejectOrder(Request $request, $orderId)
    {
        $authUser = auth()->user();
        $authUserId = auth()->id();

        
        $order = DB::table('orders')->where('id', $orderId)->first();
        if (!$order) {
            return CommonHelper::apiResponse(404, false, 'Order not found', []);
        }
        
        
        $isChef = $authUser instanceof Chef;
        $isUser = isset($authUser->role) && $authUser->role->title === 'coustomer';
        

        if (
            ($isChef && $order->chef_id != $authUserId) ||
            ($isUser && $order->user_id != $authUserId)
        ) {
            return CommonHelper::apiResponse(403, false, 'Not allowed', []);
        }

        $refundPercentage = 0;
        $refundMessage = '';

        if ($request->has('is_pre_order')) {
            $now = now();
            $orderDate = Carbon::parse($order->date);
            $isPreOrder = (int) $request->is_pre_order;

            if ($isPreOrder === 0) {
                $diffSeconds = $orderDate->diffInSeconds($now);
                if ($diffSeconds <= 120) {
                    $refundPercentage = 100;
                    $refundMessage = '100% refund applicable.';
                }
            } else {
                $diffHours = $now->diffInHours($orderDate, false);
                if ($diffHours >= 48) {
                    $refundPercentage = 100;
                    $refundMessage = '100% refund applicable.';
                } elseif ($diffHours >= 24) {
                    $refundPercentage = 50;
                    $refundMessage = '50% refund applicable.';
                }
            }
        }

        DB::table('orders')->where('id', $orderId)->update([
            'status' => 'rejected',
            'refund_percentage' => $refundPercentage,
            'updated_at' => now(),
        ]);

        $user = DB::table('users')->where('id', $order->user_id)->first();
        $customerName = $user->name ?? 'Customer';

        $items = is_array($order->items)
            ? $order->items
            : json_decode($order->items, true);
        $items = $items ?? [];

        $foodImage = '';
        if (!empty($items)) {
            $firstItemId = $items[0]['id'] ?? null;
            if ($firstItemId) {
                $dishImage = DB::table('food_dishes')
                    ->where('id', $firstItemId)
                    ->value('image');
                if ($dishImage) {
                    $foodImage = url($dishImage);
                }
            }
        }

        $setting = DB::table('settings')->first();
        $logo = ($setting && $setting->logo)
            ? url('public/images/' . $setting->logo)
            : '';

        try {
            if ($isChef) {
                $notifyTitle = 'Order Rejected';
                $notifyBody  = "Your order #{$orderId} has been rejected";
                if ($refundPercentage > 0) {
                    $notifyBody .= "\n{$refundMessage}";
                }

                $notifyData = [
                    'title' => $notifyTitle,
                    'body'  => $notifyBody,
                    'type'  => 'order_rejected',
                    'orderId' => (string) $orderId,
                    'customerName' => $customerName,
                    'amount' => (string) $order->amount,
                    'items' => (string) count($items),
                    'refund_percentage' => (string) $refundPercentage,
                    'logo' => $logo,
                    'food_image' => $foodImage,
                ];

                (new FCMService())->sendNotificationToUser(
                    $order->user_id,
                    $notifyTitle,
                    $notifyBody,
                    $notifyData
                );

                \Log::info('RejectOrderNotification sent to customer', [
                    'order_id' => $orderId,
                    'user_id' => $order->user_id,
                    'title' => $notifyTitle,
                ]);
                
                DB::table('orders')->where('id', $orderId)->update([
                    'rejected_by' => 'chef',
                    'updated_at' => now(),
                ]);
            }

            if ($isUser && $order->is_buffer == 1) {
                $notifyTitle = 'Order Canceled';
                $notifyBody  = "Order #{$orderId} has been canceled by the customer";

                $notifyData = [
                    'title' => $notifyTitle,
                    'body'  => $notifyBody,
                    'type'  => 'order_canceled_by_customer',
                    'orderId' => (string) $orderId,
                    'customerName' => $customerName,
                    'amount' => (string) $order->amount,
                    'items' => (string) count($items),
                    'refund_percentage' => (string) $refundPercentage,
                    'logo' => $logo,
                    'food_image' => $foodImage,
                ];

                (new FCMService())->sendNotificationToUser(
                    $order->chef_id,
                    $notifyTitle,
                    $notifyBody,
                    $notifyData
                );

                \Log::info('CancelOrderNotification sent to chef', [
                    'order_id' => $orderId,
                    'chef_id' => $order->chef_id,
                    'title' => $notifyTitle,
                ]);
                
                DB::table('orders')->where('id', $orderId)->update([
                    'rejected_by' => 'customer',
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            \Log::error('FCM Error in rejectOrder', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
        }
        
        // ========== ЁЯУз EMAIL SENDING AFTER ORDER REJECTION ==========
    try {
        // Get user and chef details
        $user = DB::table('users')->where('id', $order->user_id)->first();
        $chef = DB::table('chefs')->where('id', $order->chef_id)->first();
        
        // Decode items
        $items = is_array($order->items) ? $order->items : json_decode($order->items, true);
        
        // Send email based on who rejected
        
       
        if ($isChef) {
            // Chef rejected - Send email to customer
            if (!empty($user->email)) {
                Mail::send('emails.order_rejected_by_chef', [
                    'user' => $user,
                    'chef' => $chef,
                    'order_id' => $orderId,
                    'items' => $items,
                    'amount' => $order->amount,
                    'order_date' => $order->date,
                    'rejection_time' => now()->format('d M Y, h:i A'),
                    'refund_percentage' => $refundPercentage,
                    'refund_message' => $refundMessage,
                    'reason' => $request->reason ?? 'Not specified'
                ], function($message) use ($user, $orderId) {
                    $message->to($user->email)
                            ->subject('Order Rejected #' . $orderId . ' - Food App');
                });
            }
            
            // Also send email to chef as confirmation
            if (!empty($chef->email)) {
                Mail::send('emails.order_rejection_confirmation_chef', [
                    'chef' => $chef,
                    'user' => $user,
                    'order_id' => $orderId,
                    'items' => $items,
                    'amount' => $order->amount,
                    'order_date' => $order->date,
                    'rejection_time' => now()->format('d M Y, h:i A'),
                    'refund_percentage' => $refundPercentage
                ], function($message) use ($chef, $orderId) {
                    $message->to($chef->email)
                            ->subject('Rejection Confirmation #' . $orderId . ' - Food App');
                });
            }
        }
        
       if ($isUser) {

    // ── Step 1: Force 100% refund for customer cancellation ──
    $refundPercentage = 100;
    $refundMessage = 'Your refund has been successfully initiated and will be credited within 5-7 business days.';

    // ── Step 2: Email to Chef (Customer canceled order) ──
    try {
        if ($chef && !empty($chef->email)) {

            // Mail::send('emails.order_canceled_by_customer', [
            //     'chef' => $chef,
            //     'user' => $user,
            //     'order_id' => $orderId,
            //     'items' => $items ?? [],
            //     'amount' => $order->amount ?? 0,
            //     'order_date' => $order->date ?? null,
            //     'cancellation_time' => now()->format('d M Y, h:i A'),
            //     'refund_percentage' => $refundPercentage,
            //     'reason' => $request->reason ?? 'Not specified'
            // ], function ($message) use ($chef, $orderId) {
            //     $message->to($chef->email)
            //             ->subject("Order Canceled by Customer #{$orderId}");
            // });

            // \Log::info('Cancel email sent to chef', [
            //     'order_id' => $orderId,
            //     'chef_email' => $chef->email
            // ]);

        } else {
            \Log::warning('Chef email missing', [
                'order_id' => $orderId,
                'chef' => $chef
            ]);
        }

    } catch (\Throwable $e) {
        \Log::error('Chef cancel email failed', [
            'order_id' => $orderId,
            'error' => $e->getMessage()
        ]);
    }

    // ── Step 3: Email to Customer (Confirmation) ──
    try {
        if ($user && !empty($user->email)) {

            Mail::send('emails.order_cancellation_confirmation_customer', [
                'user' => $user,
                'chef' => $chef,
                'order_id' => $orderId,
                'items' => $items ?? [],
                'amount' => $order->amount ?? 0,
                'order_date' => $order->date ?? null,
                'cancellation_time' => now()->format('d M Y, h:i A'),
                'refund_percentage' => $refundPercentage,
                'refund_message' => $refundMessage
            ], function ($message) use ($user, $orderId) {
                $message->to($user->email)
                        ->subject("Order Cancellation Confirmation #{$orderId}");
            });

            \Log::info('Cancellation confirmation email sent to customer', [
                'order_id' => $orderId,
                'user_email' => $user->email
            ]);

        } else {
            \Log::warning('Customer email missing', [
                'order_id' => $orderId,
                'user' => $user
            ]);
        }

    } catch (\Throwable $e) {
        \Log::error('Customer cancellation email failed', [
            'order_id' => $orderId,
            'error' => $e->getMessage()
        ]);
    }

    // ── Step 4: Trigger Refund (IMPORTANT) ──
    try {
        $eloquentOrder = \App\Models\Order::find($orderId);

        if ($eloquentOrder) {
            $this->processRefund(
                $eloquentOrder,
                $user,
                $refundPercentage,
                $refundMessage
            );
        } else {
            \Log::warning('Refund skipped: Order not found', [
                'order_id' => $orderId
            ]);
        }

    } catch (\Throwable $e) {
        \Log::error('Refund trigger failed', [
            'order_id' => $orderId,
            'error' => $e->getMessage()
        ]);
    }
}
        
    } catch (\Exception $e) {
        // Log email error but don't affect response
        \Log::error('Order rejection email failed: ' . $e->getMessage());
    }
    // ========== ЁЯУз EMAIL SENDING END ==========

        return CommonHelper::apiResponse(200, true, 'Order rejected successfully', [
            'refund_percentage' => $refundPercentage,
            'message' => $refundMessage,
        ]);
    }



private function calculateRefundPercentage($order)
{
    $now = now();

    /* ---------- INSTANT ORDER ---------- */
    if ((int)$order->is_pre_order === 0) {

        $seconds = $now->diffInSeconds(
            \Carbon\Carbon::parse($order->date)
        );

        return $seconds <= 120 ? 100 : 0;
    }

    /* ---------- PRE ORDER ---------- */
    if ((int)$order->is_pre_order === 1) {

        $hoursLeft = $now->diffInHours(
            \Carbon\Carbon::parse($order->date),
            false
        );

        if ($hoursLeft >= 48) return 100;
        if ($hoursLeft >= 24) return 50;
        return 0;
    }

    return 0;
}

private function processRefund(Order $order, $user, int $refundPercentage, string $refundMessage): void
    {
        try {
            // Re-fetch fresh Eloquent model (service expects Eloquent, not DB result)
            $freshOrder = Order::findOrFail($order->id);
            $refund     = (new RefundService())->refundOrder($freshOrder);

            \Log::info('Refund processed successfully', [
                'order_id'          => $order->id,
                'refund_id'         => $refund->id,
                'refund_amount'     => $refund->refund_amount,
                'refund_percentage' => $refundPercentage,
            ]);

            // ── Refund push to customer ──────────────────────────────────────
            try {
                $refundTitle = 'Refund Initiated';
                $refundBody  = "Your {$refundPercentage}% refund of ₹{$refund->refund_amount} for order #{$order->id} is being processed. It will reflect in 5-7 business days.";

                (new FCMService())->sendNotificationToUser(
                    $order->user_id,
                    $refundTitle,
                    $refundBody,
                    [
                        'title'             => $refundTitle,
                        'body'              => $refundBody,
                        'type'              => 'refund_initiated',
                        'orderId'           => (string) $order->id,
                        'refund_percentage' => (string) $refundPercentage,
                        'refund_amount'     => (string) $refund->refund_amount,
                    ]
                );
            } catch (\Throwable $e) {
                \Log::warning('Refund push notification failed', [
                    'order_id' => $order->id,
                    'error'    => $e->getMessage(),
                ]);
            }

            // // ── Refund email to customer ─────────────────────────────────────
            // try {
            //     if ($user && !empty($user->email)) {
            //         Mail::send('emails.refund_initiated', [
            //             'name'              => $user->name ?? 'Customer',
            //             'orderId'           => $order->id,
            //             'refund_percentage' => $refundPercentage,
            //             'refund_amount'     => $refund->refund_amount,
            //             'refund_message'    => $refundMessage,
            //         ], function ($message) use ($user, $order) {
            //             $message->to($user->email)
            //                     ->subject("Refund Initiated for Order #{$order->id}");
            //         });
            //     }
            // } catch (\Throwable $e) {
            //     \Log::error('Refund email failed', [
            //         'order_id' => $order->id,
            //         'error'    => $e->getMessage(),
            //     ]);
            // }

        } catch (\Throwable $e) {
            // Refund failure must NOT block the API response — order is already rejected
            \Log::error('Refund processing failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

   

// 20/02/2026
// public function createOrder(Request $request)
// {
    
//     /* ---------- VALIDATION ---------- */
//     $validated = $request->validate([
//         'chef_id' => 'required|exists:chefs,id',
//         'items' => 'required|array|min:1',
//         'items.*.id' => 'required|exists:food_dishes,id',
//         'items.*.quantity' => 'required|integer|min:1',
//         'coupon_id' => 'nullable|exists:coupons,id',
//         'date' => 'nullable|date',
//     ]);

//     $user   = auth()->user();
//     $userId = $user->id;
//     $now    = now();
    
//     /* ---------- FETCH DISH DATA (SINGLE QUERY) ---------- */
//     $dishIds = array_column($validated['items'], 'id');

//     $dishes = DB::table('food_dishes')
//         ->whereIn('id', $dishIds)
//         ->select('id', 'chef_id', 'name', 'price', 'in_stock')
//         ->get()
//         ->keyBy('id');

//     /* ---------- STOCK + CHEF VALIDATION (SINGLE LOOP) ---------- */
//     $subtotal = 0;
//     $items    = [];
//     $chefIds  = [];

//     foreach ($validated['items'] as $item) {
//         $food = $dishes[$item['id']] ?? null;
//         $foodName = $food && $food->name ? $food->name : 'Unknown';
//         if (!$food || !$food->in_stock) {
//             return CommonHelper::apiResponse(
//     400,
//     false,
//     "Sorry, food item {$foodName} is out of stock.",
//     null
// );
//         }

//         $chefIds[$food->chef_id] = true;

//         $lineTotal = $food->price * $item['quantity'];
//         $subtotal += $lineTotal;

//         $items[] = [
//             'id'       => $food->id,
//             'name'     => $food->name,
//             'price'    => $food->price,
//             'quantity' => $item['quantity'],
//             'total'    => $lineTotal,
//         ];
//     }

//     /* ---------- CHEF AVAILABILITY (SINGLE EXISTS QUERY) ---------- */
//     $chefUnavailable = DB::table('chefs')
//         ->whereIn('id', array_keys($chefIds))
//         ->where('available', '!=', 1)
//         ->exists();

//     if ($chefUnavailable) {
//         return CommonHelper::apiResponse(
//             400,
//             false,
//             "Sorry, the selected chef is currently unavailable.",
//             null
//         );
//     }

//     /* ---------- GST ---------- */
//     static $gstPercent = null; // ЁЯФе request-level cache
//     $gstPercent ??= (float) str_replace('%', '', DB::table('settings')->value('gst') ?? 0);

//     $gstAmount   = ($subtotal * $gstPercent) / 100;
//     $grossAmount = $subtotal + $gstAmount;

//     /* ---------- COUPON ---------- */
//     $discount = 0;
//     $couponId = null;

//     if (!empty($validated['coupon_id'])) {

//         $coupon = DB::table('coupons')
//             ->where('id', $validated['coupon_id'])
//             ->where('is_active', 1)
//             ->first();

//         if (!$coupon) {
//             return response()->json(['status' => false, 'message' => 'Invalid coupon'], 400);
//         }

//         if (($coupon->starts_at && $now->lt($coupon->starts_at)) ||
//             ($coupon->expires_at && $now->gt($coupon->expires_at))) {
//             return response()->json(['status' => false, 'message' => 'Coupon not valid'], 400);
//         }

//         if ($grossAmount < (float) $coupon->min_order_value) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'Minimum order amount should be тВ╣' . $coupon->min_order_value
//             ], 400);
//         }

//         if ($coupon->usage_limit_per_user) {
//             $used = DB::table('coupon_usages')
//                 ->where('coupon_id', $coupon->id)
//                 ->where('user_id', $userId)
//                 ->count();

//             if ($used >= $coupon->usage_limit_per_user) {
//                 return response()->json(['status' => false, 'message' => 'Coupon already used'], 400);
//             }
//         }

//         if ($coupon->type === 'percentage') {
//             $discount = ($subtotal * $coupon->value) / 100;
//             if ($coupon->max_discount_amount) {
//                 $discount = min($discount, $coupon->max_discount_amount);
//             }
//         } else {
//             $discount = (float) $coupon->value;
//         }

//         $discount = min($discount, $grossAmount);
//         $couponId = $coupon->id;
//     }

//     /* ---------- FINAL ---------- */
//     $finalAmount = (int) round(($subtotal - $discount) + $gstAmount);

//     /* ---------- ORDER INSERT (FAST) ---------- */
//     $orderId = DB::table('orders')->insertGetId([
//         'chef_id'         => $validated['chef_id'],
//         'user_id'         => $userId,
//         'items'           => json_encode($items),
//         'amount'          => $finalAmount,
//         'date'            => $validated['date'] ?? now()->toDateString(),
//         'status'          => 'new',
//         'payment_status'  => 'Pending',
//         'coupon_id'       => $couponId,
//         'is_buffer'       => 0,
//         'discount_amount' => (int) round($discount),
//         'created_at'      => now(),
//         'updated_at'      => now(),
//     ]);

//     /* ---------- RAZORPAY (LAST тАФ SLOW CALL) ---------- */
//     $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
//     // dd($api);
//     $razorOrder = $api->order->create([
//         'receipt'  => 'order_' . $orderId,
//         'amount'   => $finalAmount * 100,
//         'currency' => 'INR',
//     ]);

//     DB::table('orders')->where('id', $orderId)
//         ->update(['razorpay_order_id' => $razorOrder['id']]);

//     /* ---------- FCM (NON-BLOCKING TRY) ---------- */
    

//     return CommonHelper::apiResponse(201, true, 'Order created successfully', [
//         'order_id' => $orderId,
//         'razorpay_order_id' => $razorOrder['id'],
//         'subtotal' => number_format($subtotal, 2),
//         'gst' => number_format($gstAmount, 2),
//         'discount' => (int) round($discount),
//         'final_amount' => $finalAmount,
//     ]);
// }

public function createOrder(Request $request) {

    DB::beginTransaction();

    try {

        $validated = $request->validate([
            'chef_id' => 'required|exists:chefs,id',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:food_dishes,id',
            'items.*.quantity' => 'required|integer|min:1',
            'coupon_id' => 'nullable|exists:coupons,id',
            'date' => 'nullable|date',
        ]);

        $user   = auth()->user();
        $userId = $user->id;
        $now    = now();
        $full_address = "";

        $orderDate = isset($validated['date'])
            ? \Carbon\Carbon::parse($validated['date'])->toDateString()
            : now()->toDateString();

        $isFutureOrder = $orderDate > now()->toDateString();

        if ($isFutureOrder) {

            $chefPreOrderAllowed = DB::table('chefs')
                ->where('id', $validated['chef_id'])
                ->value('is_pre_order');

            if (!$chefPreOrderAllowed) {
                DB::rollBack();
                return CommonHelper::apiResponse(
                    400,
                    false,
                    "Sorry, this chef is currently not accepting pre-orders.",
                    null
                );
            }
        }else{
           $chefCheck = DB::table('chefs')
            ->where('id', $validated['chef_id'])
            ->select('opening_time', 'closing_time')
            ->first();
        
        // 1. કરંટ ટાઈમ (IST)
        $now = now('Asia/Kolkata');
        $currentTime = $now->format('H:i');
        
        // 2. ઓપનિંગ અને ક્લોઝિંગ ટાઈમ ને પ્રોપર ફોર્મેટમાં લાવો
        $start = \Carbon\Carbon::createFromFormat('H:i', \Carbon\Carbon::parse($chefCheck->opening_time)->format('H:i'));
        $end = \Carbon\Carbon::createFromFormat('H:i', \Carbon\Carbon::parse($chefCheck->closing_time)->format('H:i'));
        $check = \Carbon\Carbon::createFromFormat('H:i', $currentTime);
        
        $isOpen = false;
        
        if ($end->lessThan($start)) {
            // કિસ્સો: 04:00 PM થી 12:00 AM અથવા 02:00 AM (Overnight)
            // જો ચેક કરવાનો સમય Start થી રાત્રે 11:59 સુધી હોય અથવા રાત્રે 12 થી End સુધી હોય
            if ($check->greaterThanOrEqualTo($start) || $check->lessThanOrEqualTo($end)) {
                $isOpen = true;
            }
        } else {
            // કિસ્સો: નોર્મલ દિવસ (e.g. 10:00 AM to 06:00 PM)
            if ($check->betweenIncluded($start, $end)) {
                $isOpen = true;
            }
        }
        
        if (!$isOpen) {
            DB::rollBack();
            // ટાઈમ ફોર્મેટિંગ ગ્રાહકને બતાવવા માટે (12-hour format with AM/PM)
            $displayStart = \Carbon\Carbon::parse($chefCheck->opening_time)->format('h:i A');
            $displayEnd = \Carbon\Carbon::parse($chefCheck->closing_time)->format('h:i A');
        
            return CommonHelper::apiResponse(
                400,
                false,
                "Sorry, the chef is currently closed. Orders are accepted between {$displayStart} to {$displayEnd}.",
                null
            );
        }    
                    
            
        }

        $userAddress = DB::table('user_addresses')
            ->where('user_id',$user->id)
            ->where('is_selected',1)
            ->first();

        if(isset($userAddress)){
            $full_address = $userAddress->full_address;
        }

        /* ---------- FETCH CHEF COMMISSION ---------- */

        $chefCommission = DB::table('chefs')
            ->where('id', $validated['chef_id'])
            ->value('commission') ?? 0;

        /* ---------- FETCH DISH DATA ---------- */

        $dishIds = array_column($validated['items'], 'id');

        $dishes = DB::table('food_dishes')
            ->whereIn('id', $dishIds)
            ->select(
                'id',
                'chef_id',
                'name',
                'price',
                'image',
                'description',
                'in_stock',
                'base_price'
            )
            ->get()
            ->keyBy('id');

        $subtotal = 0;
        $items    = [];
        $chefIds  = [];
        $totalSecurityDeposit = 0;

        // snapshot variables
        $orderDishName = null;
        $orderDishPrice = null;
        $orderDishImage = null;
        $orderDishDescription = null;
         $platformFee = \DB::table('settings')
            ->value('platform_fee') ?? 0;
        foreach ($validated['items'] as $item) {

            $food = $dishes[$item['id']] ?? null;
            $foodName = $food && $food->name ? $food->name : 'Unknown';

            if (!$food || !$food->in_stock) {
                DB::rollBack();
                return CommonHelper::apiResponse(
                    400,
                    false,
                    "Sorry, food item {$foodName} is out of stock.",
                    null
                );
            }

            $chefIds[$food->chef_id] = true;

            // snapshot values
            $orderDishName = $food->name;
            
            $orderDishImage = $food->image ?? null;
            $orderDishDescription = $food->description ?? null;

            // $commissionAmount = ($food->price * $chefCommission) / 100;
            // $priceWithCommission = $food->price + $commissionAmount;
            $price = (float) $food->price;
            $basePrice = (float) $food->base_price; // Chef ркирлА ркорлВрк│ ркХрк┐ркВркоркд
            $orderDishPrice = $food->price;
            
            $lineTotal = $orderDishPrice * $item['quantity'];
            
            $lineSecurity = ($basePrice * 0.10) * $item['quantity'];
            $totalSecurityDeposit += $lineSecurity;
            
            $subtotal += $lineTotal;

            $items[] = [
                'id'       => $food->id,
                'name'     => $food->name,
                'price'    => round($price),
                'quantity' => $item['quantity'],
                'total'    => round($lineTotal),
            ];
            
            
        }

        /* ---------- CHEF AVAILABILITY ---------- */

        $chefUnavailable = DB::table('chefs')
            ->whereIn('id', array_keys($chefIds))
            ->where('available', '!=', 1)
            ->exists();

        // if ($chefUnavailable) {
        //     DB::rollBack();
        //     return CommonHelper::apiResponse(
        //         400,
        //         false,
        //         "Sorry, the selected chef is currently unavailable.",
        //         null
        //     );
        // }

        /* ---------- GST ---------- */

        static $gstPercent = null;

        $gstPercent ??= (float) str_replace('%', '', DB::table('settings')->value('gst') ?? 0);

        $gstAmount   = ($subtotal * $gstPercent) / 100;
        $grossAmount = $subtotal + $gstAmount;

        /* ---------- COUPON ---------- */

        $discount = 0;
        $couponId = null;

        if (!empty($validated['coupon_id'])) {

            $coupon = DB::table('coupons')
                ->where('id', $validated['coupon_id'])
                ->where('is_active', 1)
                ->first();

            if (!$coupon) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Invalid coupon'], 400);
            }

            if (($coupon->starts_at && $now->lt($coupon->starts_at)) ||
                ($coupon->expires_at && $now->gt($coupon->expires_at))) {

                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Coupon not valid'], 400);
            }

            if ($grossAmount < (float) $coupon->min_order_value) {

                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Minimum order amount should be тВ╣' . $coupon->min_order_value
                ], 400);
            }

            if ($coupon->usage_limit_per_user) {

                $used = DB::table('coupon_usages')
                    ->where('coupon_id', $coupon->id)
                    ->where('user_id', $userId)
                    ->count();

                if ($used >= $coupon->usage_limit_per_user) {

                    DB::rollBack();
                    return response()->json(['status' => false, 'message' => 'Coupon already used'], 400);
                }
            }

            if ($coupon->type === 'percentage') {

                $discount = ($subtotal * $coupon->value) / 100;

                if ($coupon->max_discount_amount) {
                    $discount = min($discount, $coupon->max_discount_amount);
                }

            } else {

                $discount = (float) $coupon->value;
            }

            $discount = min($discount, $grossAmount);
            $couponId = $coupon->id;
        }
        // dd($platformFee);
        /* ---------- FINAL ---------- */

        // $finalAmount = (int) round(($subtotal - $discount) + $gstAmount);
    //   $finalAmount = (int) round(
    //         (float)$subtotal + (float)$gstAmount + (float)$platformFee - (float)$discount
    //     );
    
    $subtotal    = round($subtotal, 2);
$gstAmount   = round($gstAmount, 2);
$platformFee = round($platformFee, 2);
$discount    = round($discount, 2);
     $finalAmount = 
    (float)$subtotal + (float)$gstAmount + (float)$platformFee - (float)$discount;

// Step 1: ensure only 2 decimals
$finalAmount = round($finalAmount, 2);

// Step 2 + 3: convert to paise (integer)
$finalAmountPaise = (int) round($finalAmount * 100);
       
        /* ---------- ORDER INSERT ---------- */

        $orderId = DB::table('orders')->insertGetId([
            'chef_id'         => $validated['chef_id'],
            'user_id'         => $userId,
            'items'           => json_encode($items),
            'amount'          => $finalAmount,
            'date'            => $validated['date'] ?? now()->toDateString(),
            'status'          => 'new',
            'payment_status'  => 'Pending',
            'coupon_id'       => $couponId,
            'is_buffer'       => 0,
            'discount_amount' => (int) round($discount),
            'created_at'      => now(),
            'updated_at'      => now(),
            'address'         => $full_address,

            // NEW SNAPSHOT COLUMNS$gstAmount
            'name'            => $orderDishName,
            'price'           => $orderDishPrice,
            'image'           => $orderDishImage,
            'description'     => $orderDishDescription,
            'platform_fee'    => $platformFee,
            'calculated_gst'  => $gstAmount,
            'security_deposit' => number_format($totalSecurityDeposit, 2)
        ]);

        /* ---------- RAZORPAY ---------- */

        $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

        $razorOrder = $api->order->create([
            'receipt'  => 'order_' . $orderId,
            'amount'   => (int) $finalAmountPaise,
            'currency' => 'INR',
        ]);

        DB::table('orders')->where('id', $orderId)
            ->update(['razorpay_order_id' => $razorOrder['id']]);

        DB::commit();

        return CommonHelper::apiResponse(201, true, 'Order created successfully', [
            'order_id' => $orderId,
            'razorpay_order_id' => $razorOrder['id'],
            'subtotal' => number_format($subtotal, 2),
            'gst' => number_format($gstAmount, 2),
            'discount' => (int) round($discount),
            'final_amount' => (int) $finalAmountPaise,
        ]);

    } catch (\Throwable $e) {

        DB::rollBack();

        return CommonHelper::apiResponse(
            500,
            false,
            'Something went wrong while creating order.',
            ['error' => $e->getMessage()]
        );
    }
}


// public function verifyPayment(Request $request)
// {
//     $request->validate([
//         'razorpay_payment_id' => 'required|string',
//         'razorpay_order_id' => 'required|string',
//         'razorpay_signature' => 'required|string',
//         'order_id' => 'required|integer'
//     ]);

//     $generatedSignature = hash_hmac(
//         'sha256',
//         $request->razorpay_order_id . "|" . $request->razorpay_payment_id,
//         env('RAZORPAY_SECRET')
//     );

//     if ($generatedSignature !== $request->razorpay_signature) {
//         return CommonHelper::apiResponse(400, false, 'Payment verification failed', []);
//     }

//     $order = Order::find($request->order_id);
//     if (!$order) {
//         return CommonHelper::apiResponse(404, false, 'Order not found', []);
//     }

//     /* ---------- UPDATE ORDER ---------- */
//     $order->payment_status = 'received';
//     $order->razorpay_payment_id = $request->razorpay_payment_id;
//     $order->paid_at = now();
//     $order->save();

//     /* ---------- SAVE COUPON USAGE (ONLY NOW) ---------- */
//      if ($order->coupon_id && $order->discount_amount > 0) {

//         DB::table('coupon_usages')->insert([
//             'coupon_id' => $order->coupon_id,
//             'user_id' => $order->user_id,
//             'order_id' => $order->id,
//             'discount_amount' => $order->discount_amount,
//             'created_at' => now(),
//             'updated_at' => now()
//         ]);

//         DB::table('coupons')
//             ->where('id', $order->coupon_id)
//             ->increment('usage_count');
//     }

//     return CommonHelper::apiResponse(200, true, 'Payment verified successfully', [
//         'order_id' => $order->id,
//         'payment_status' => 'received'
//     ]);
// }



public function verifyPayment(Request $request)
{
    /* ---------- VALIDATION ---------- */
    $request->validate([
        'razorpay_payment_id' => 'required|string',
        'razorpay_order_id'   => 'required|string',
        'razorpay_signature'  => 'required|string',
        'order_id'            => 'required|integer',
    ]);

    /* ---------- VERIFY SIGNATURE ---------- */
    $generatedSignature = hash_hmac(
        'sha256',
        $request->razorpay_order_id . "|" . $request->razorpay_payment_id,
        env('RAZORPAY_SECRET')
    );

    if ($generatedSignature !== $request->razorpay_signature) {
        return CommonHelper::apiResponse(400, false, 'Payment verification failed', []);
    }

    /* ---------- FIND ORDER ---------- */
    $order = Order::with('user')->find($request->order_id);
    if (!$order) {
        return CommonHelper::apiResponse(404, false, 'Order not found', []);
    }
    
    // dd($order->date);
    
  
    
   

    /* ---------- FETCH PAYMENT METHOD ---------- */
    try {
        $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
        $payment = $api->payment->fetch($request->razorpay_payment_id);
        $paymentMethod = $payment->method ?? null;
    } catch (\Throwable $e) {
        $paymentMethod = null;
    }
    
    // $platformFee = \DB::table('settings')
    //     ->value('platform_fee') ?? 0;
        
    // $originalAmount = $order->amount; // existing order amount
    // $finalAmount = $originalAmount + $platformFee;

    /* ---------- UPDATE ORDER ---------- */
    $order->payment_status      = 'received';
    $order->payment_method      = $paymentMethod;
    $order->razorpay_order_id   = $request->razorpay_order_id;
    $order->razorpay_payment_id = $request->razorpay_payment_id;
    // $order->amount              = $finalAmount;
    $order->paid_at             = now();
    $order->created_at          = now();
    $order->save();

    /* ---------- SAVE COUPON USAGE ---------- */
    if ($order->coupon_id && $order->discount_amount > 0) {
        DB::table('coupon_usages')->insert([
            'coupon_id'        => $order->coupon_id,
            'user_id'          => $order->user_id,
            'order_id'         => $order->id,
            'discount_amount' => $order->discount_amount,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        DB::table('coupons')
            ->where('id', $order->coupon_id)
            ->increment('usage_count');
    }

    /* ---------- PUSH NOTIFICATION TO CHEF ---------- */
    try {
        $user = $order->user;
        $customerName = $user->name ?? 'Customer';

        // Safe items handling
        $items = is_array($order->items) ? $order->items : json_decode($order->items, true);
        $items = $items ?? [];

        $foodImage = null; // single key

        if (!empty($items)) {
            // get first item only
            $firstItemId = $items[0]['id'] ?? null;

            if ($firstItemId) {
                $dishImage = DB::table('food_dishes')->where('id', $firstItemId)->value('image');
                if ($dishImage) {
                    $foodImage = url($dishImage);
                }
            }
        }

//         // Logo
//         $setting = DB::table('settings')->first();
//         $logo = ($setting && $setting->logo) ? url('public/images/' . $setting->logo) : '';

//         // Notification payload
//         $title = 'New order recieved тЬЕ';
//         $body  = "You have received a new order from {$customerName}";
//         //   $orderDate = Carbon::parse($order->date);

//   $orderDate = Carbon::parse($order->date);

// $orderType = $orderDate->isToday()
//     ? 'today'
//     : ($orderDate->isFuture() ? 'preorder' : 'past');
            
//         // dd($orderType);
//         $data = [
//             'title' => $title,
//             'body' => $body,
//             'type'         => 'payment_received',
//             'orderId'      => (string) $order->id,
//             'customerName' => $customerName,
//             'amount'       => (string) $order->amount,
//             'items'        => (string) count($items),
//             'logo'         => $logo,
//             'food_image'   => $foodImage ?? '',
//             'order_type' => $orderType
//             // 'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
//         ];

//         $fcmService = new FCMService();
//         $result = $fcmService->sendNotificationToUser($order->chef_id, $title, $body, $data);

//         // Log FCM result
//         \Log::info('FCM Result', ['order_id' => $order->id, 'result' => $result]);

    } catch (\Throwable $e) {
        \Log::warning('Chef payment push failed', [
            'order_id' => $order->id,
            'error'    => $e->getMessage(),
        ]);
    }

    /* ---------- API RESPONSE ---------- */
    return CommonHelper::apiResponse(200, true, 'Payment verified successfully', [
        'order_id'       => $order->id,
        'payment_status' => 'received',
        'payment_method' => $paymentMethod,
    ]);
}




    public function getGst()
    {
        $gstValue = DB::table('settings')->value('gst') ?? '0';

        
        return CommonHelper::apiResponse(200, true, 'GST Fecthed successfully!', [
        'success' => true,
            'gst' => rtrim($gstValue, '%') . '%'  // ensure output always includes %
        ]);
    }
    
    public function getradius()
    {
        $radiusValue = DB::table('settings')->value('radius_km') ?? '0';

        
        return CommonHelper::apiResponse(200, true, 'Radius Fecthed successfully!', [
        'success' => true,
            'radius' => rtrim($radiusValue, 'km') . 'km'  // ensure output always includes %
        ]);
    }

public function paymentList(Request $request)
{
    $user = auth()->user();
    
    
    if (!$user) {
        return CommonHelper::apiResponse(401, false, 'Unauthorized user! Please login again.', []);
    }

    // Find Chef
    $chef = DB::table('chefs')->where('id', $user->id)->first();
    if (!$chef) {
        return CommonHelper::apiResponse(404, false, 'Chef not found for this user!', []);
    }

    /* тЬЕ ONLY ADDED */
    $fromDate = $request->from_date;
    $toDate   = $request->to_date;
    $paymentStatus = $request->payment_status;

    // Pagination Params
    $perPage = $request->get('per_page', 20);
    $page    = $request->get('page', 1);

    $query = DB::table('orders')
        ->where('chef_id', $chef->id)
        // ->whereNotNull('razorpay_payment_id')
        ->where('status','delivered')
        // ->whereNotNull('paid_at')
        ->where('updated_at', '<=', Carbon::now()->subHour())
        ->select(
            'id',
            DB::raw("FORMAT(amount, 2) as amount"),
            'razorpay_payment_id as payment_id',
            'payment_status',
            'paid_at',
            'status',
            'created_at'
        );

    /* ============================
       тЬЕ DATE FILTER (GET PARAMS)
    ============================= */
    if ($fromDate && $toDate) {
        $query->whereBetween(
            DB::raw('DATE(created_at)'),
            [$fromDate, $toDate]
        );
    }
    // No date filter applied: show all of the chef's settled transactions,
    // newest first (already sorted by paid_at DESC below), paginated —
    // consistent with chef/payouts and chef/analytics, which also don't
    // restrict to "today" when no filter is given. Previously this defaulted
    // to whereDate('date', Carbon::today()), which silently hid every
    // transaction older than today until the chef manually opened the
    // filter sheet and picked a date range.
    
    if ($paymentStatus && $paymentStatus !== 'all') {
        $query->where('payment_status', $paymentStatus);
    }

    // Sort list by most recent payment
    $orders = $query
        ->orderBy('paid_at', 'DESC')
        ->paginate($perPage, ['*'], 'page', $page);

    if ($orders->isEmpty()) {
        return CommonHelper::apiResponse(404, false, 'No payment records found', []);
    }

    return CommonHelper::apiResponse(200, true, 'Payment success list fetched!', $orders);
}


public function generatePaymentPdf(Request $request)
{
    $user = auth()->user();
    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized user! Please login again.'
        ], 401);
    }

    // Find Chef
    $chef = DB::table('chefs')->where('id', $user->id)->first();
    if (!$chef) {
        return response()->json([
            'status' => false,
            'message' => 'Chef not found for this user!'
        ], 404);
    }

    /* ---------- DATE FILTER ---------- */
    $fromDate = $request->from_date;
    $toDate   = $request->to_date;
    $paymentStatus = $request->payment_status;

     $query = DB::table('orders')
        ->where('chef_id', $chef->id)
        // ->whereNotNull('razorpay_payment_id')
        ->where('status','delivered')
        // ->whereNotNull('paid_at')
        ->where('updated_at', '<=', Carbon::now()->subHour())
        ->select(
            'id',
            'amount',
            'razorpay_payment_id as payment_id',
            'payment_status',
            'paid_at',
            'status',
            'created_at'
        );

    if ($fromDate && $toDate) {
        $query->whereBetween(
            DB::raw('DATE(created_at)'),
            [$fromDate, $toDate]
        );
    } else {
        $query->whereDate('date', Carbon::today());
    }
    
     if ($paymentStatus && $paymentStatus !== 'all') {
        $query->where('payment_status', $paymentStatus);
    }

    $payments = $query
        ->orderBy('paid_at', 'DESC')
        ->get();

    if ($payments->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'No payment records found'
        ], 404);
    }

    /* ---------- PDF DATA ---------- */
    $data = [
        'chef'      => $chef,
        'payments'  => $payments,
        'fromDate'  => $fromDate,
        'toDate'    => $toDate,
        'generated' => Carbon::now()->format('d M Y h:i A')
    ];

    $pdf = PDF::loadView('payment-list', $data);

    /* ---------- SAVE PDF ---------- */
    $fileName = 'payment_list_' . Carbon::now()->format('Ymd_His') . '.pdf';
    $path = public_path('invoices');

    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }

    $pdf->save($path . '/' . $fileName);
    
    $data = [
        'invoice_url' => url('public/invoices/' . $fileName)
    ];

    return CommonHelper::apiResponse(
        200,
        true,
        'payment list invoice generated successfully.',
        $data
    );

}






public function paymentDetails(Request $request, $order_id)
{
    $user = auth()->user();

    if (!$user) {
        return CommonHelper::apiResponse(401, false, 'Unauthorized user! Please login again.', []);
    }

    // Find chef
    $chef = DB::table('chefs')
        ->where('id', $user->id)
        ->first();

    if (!$chef) {
        return CommonHelper::apiResponse(404, false, 'Chef not found for this user!', []);
    }

    // Fetch single order for this chef
    $order = DB::table('orders')
        ->where('chef_id', $chef->id)
        ->where('id', $order_id)
        ->select(
            'id',
            'items',
            'amount',
            'date',
            'payment_status',
            'razorpay_payment_id',
            'razorpay_order_id',
            'payment_method',
            'paid_at'
        )
        ->first();

    if (!$order) {
        return CommonHelper::apiResponse(404, false, 'Transaction not found!', []);
    }

    $itemsData = json_decode($order->items, true) ?: [];

    $transaction = [
        'order_id'        => (string)$order->id,
        'amount'          => number_format($order->amount, 2),
        'date'            => $order->date,
        'status'          => $order->payment_status ?? 'Pending',
        'payment_id'      => $order->razorpay_payment_id ?? '',
        'razorpay_order_id' => $order->razorpay_order_id ?? '',
        'payment_method'  => $order->payment_method ?? '',
        'paid_at'         => $order->paid_at ?? '',
        'items'           => $itemsData
    ];

    return CommonHelper::apiResponse(200, true, 'Transaction Details Fetched Successfully!', [
        'success'     => true,
        'chef_id'     => (string)$chef->id,
        'transaction' => $transaction
    ]);
}


protected function firstValidationError($validator)
{
    return CommonHelper::apiResponse(
        422,
        false,
        $validator->errors()->first(),
        []
    );
}

public function chefsRegister(Request $request)
    {
        
        $step = (int) $request->step;

        switch ($step) {

            /* =====================================================
             | STEP 1
             ===================================================== */
            case 1:

                $validator = Validator::make($request->all(), [
                    'name'         => 'required|string|max:255',
                    'email'        => 'required|email|unique:chefs,email',
                    'phone_number' => 'required|string|max:15|unique:chefs,phone_number',
                    'password'     => 'nullable|min:6',
                    'temp_uuid'    => 'nullable|string'
                ], [
                    'name.required' => 'Name is required.',
                    'email.required' => 'Email is required.',
                    'email.email' => 'Please enter a valid email.',
                    'email.unique' => 'Email already exists. Please login or use another email.',
                    'phone_number.required' => 'Phone number is required.',
                    'phone_number.unique' => 'Phone number already exists. Please login or use another number.',
                    'password.min' => 'Password must be at least 6 characters.'
                ]);
                
                if ($validator->fails()) {
                    return $this->firstValidationError($validator);
                }
                
                $data = $validator->validated();
               

                if (!empty($data['password'])) {
                    $data['password'] = Hash::make($data['password']);
                }
                
                
                /* ---------- CHECK EMAIL EXISTS ---------- */
                if (DB::table('chefs')->where('email', $data['email'])->exists()) {
                    return CommonHelper::apiResponse(
                        422,
                        false,
                        'Email already exists. Please login or use another email.',
                        []
                    );
                }
            
                /* ---------- CHECK PHONE NUMBER EXISTS ---------- */
                if (DB::table('chefs')->where('phone_number', $data['phone_number'])->exists()) {
                    return CommonHelper::apiResponse(
                        422,
                        false,
                        'Phone number already exists. Please login or use another number.',
                        []
                    );
                }

                /* Existing registration (email change allowed) */
                if (!empty($data['temp_uuid'])) {

                    $temp = DB::table('chef_registrations_temp')
                              ->where('temp_uuid', $data['temp_uuid'])
                              ->first();

                    if (!$temp) {
                        return CommonHelper::apiResponse(422, false, 'Invalid registration token', []);
                    }

                    DB::table('chef_registrations_temp')
                        ->where('id', $temp->id)
                        ->update([
                            'step1'      => json_encode($data),
                            'email'      => $data['email'],
                            'updated_at' => now()
                        ]);

                    $tempUuid = $temp->temp_uuid;

                } else {

                    /* New registration */
                    $tempUuid = (string) Str::uuid();

                    DB::table('chef_registrations_temp')->insert([
                        'temp_uuid'  => $tempUuid,
                        'email'      => $data['email'],
                        'step1'      => json_encode($data),
                        'created_at'=> now(),
                        'updated_at'=> now()
                    ]);
                }

                return CommonHelper::apiResponse(200, true, 'Step 1 completed', [
                    'temp_uuid' => $tempUuid,
                    'next_step' => 2
                ]);


            /* =====================================================
             | STEP 2
             ===================================================== */
            case 2:

                $data = $request->validate([
                    'temp_uuid'       => 'required|string',
                    'shop_plot_number'=> 'required|string',
                    'floor'           => 'nullable|string',
                    'building_name'   => 'required|string',
                    'pincode'         => 'required|string',
                    'address'         => 'required|string',
                    'latitude'        => 'required|numeric',
                    'longitude'       => 'required|numeric',
                    'delivery_radius' => 'required|integer',
                    'city'            => 'required'
                ]);

                $temp = $this->getTempByUuid($data['temp_uuid']);
                if (!$temp) return $this->invalidToken();

                DB::table('chef_registrations_temp')
                    ->where('id', $temp->id)
                    ->update([
                        'step2'      => json_encode($data),
                        'updated_at' => now()
                    ]);

                return CommonHelper::apiResponse(200, true, 'Step 2 completed', [
                    'temp_uuid' => $data['temp_uuid'],
                    'next_step' => 3
                ]);


            /* =====================================================
             | STEP 3
             ===================================================== */
            case 3:
                
                $data = $request->validate([
                    'temp_uuid'          => 'required|string',
                    'profile_image'      => 'nullable|required|image|mimes:jpeg,png,jpg',
                    'cuisine_speciality' => 'required|array',
                    'preference_tags'    => 'required|array',
                    'kitchen_name'       => 'required'
                ]);
                
                

                $temp = $this->getTempByUuid($data['temp_uuid']);
                if (!$temp) return $this->invalidToken();

                /* Upload image */
                $file = $request->file('profile_image');
                $fileName = time().'_profile_'.uniqid().'.'.$file->getClientOriginalExtension();
                $file->move(public_path('images'), $fileName);

                $step3 = [
                    'profile_image'      => 'public/images/'.$fileName,
                    'cuisine_speciality' => implode(',', $data['cuisine_speciality']),
                    'preference_tags'    => implode(',', $data['preference_tags']),
                    'kitchen_name' => $request->kitchen_name
                ];

                DB::table('chef_registrations_temp')
                    ->where('id', $temp->id)
                    ->update([
                        'step3'      => json_encode($step3),
                        'updated_at' => now()
                    ]);

                return CommonHelper::apiResponse(200, true, 'Step 3 completed', [
                    'temp_uuid' => $data['temp_uuid'],
                    'next_step' => 4
                ]);


            /* =====================================================
             | STEP 4 тАФ FINAL SUBMIT
             ===================================================== */
            case 4:
            
                $data = $request->validate([
                    'temp_uuid'                         => 'required|string',
                    'kitchen_assessment_photographs'    => 'required|array',
                    'kitchen_assessment_photographs.*'  => 'image|mimes:jpeg,png,jpg',
                    'fscai_certificate'                 => 'nullable|array',
                    'fscai_certificate.*'               => 'mimes:jpeg,png,jpg,pdf'
                ]);
            
                $temp = $this->getTempByUuid($data['temp_uuid']);
                if (!$temp) return $this->invalidToken();
            
                /* ---------- Upload kitchen photos ---------- */
                 $kitchenPhotos = [];
                if ($request->hasFile('kitchen_assessment_photographs')) {
                    foreach ($request->file('kitchen_assessment_photographs') as $file) {
                        $fileName = time().'_kitchen_'.uniqid().'.'.$file->getClientOriginalExtension();
                        $file->move(public_path('images/kitchen_photos'), $fileName);
                        $kitchenPhotos[] = 'public/images/kitchen_photos/' . $fileName;
                    }
                }
            
                /* ---------- Upload FSCI certificates ---------- */
                $certificates = [];
    if ($request->hasFile('fscai_certificate')) {
        foreach ($request->file('fscai_certificate') as $file) {
            $name = time().'_fscai_'.uniqid().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('documents'), $name);
            $certificates[] = 'public/documents/'.$name;
        }
    }
            
                DB::table('chef_registrations_temp')
                    ->where('id', $temp->id)
                    ->update([
                        'step4' => json_encode([
                            'kitchen_assessment_photographs' => json_encode($kitchenPhotos),
                            'fscai_certificate'              => json_encode($certificates)
                        ]),
                        'updated_at' => now()
                    ]);
            
                /* ---------- Merge all steps ---------- */
                $final = array_merge(
                    json_decode($temp->step1, true),
                    json_decode($temp->step2, true),
                    json_decode($temp->step3, true),
                    json_decode(DB::table('chef_registrations_temp')->where('id', $temp->id)->value('step4'), true)
                );
                
               
            
                /* тЭМ REMOVE temp_uuid (VERY IMPORTANT) */
                unset($final['temp_uuid']);
            
                $final['created_at'] = now();
                $final['updated_at'] = now();
            
                /* ---------- Insert into chefs table ---------- */
                $chefId = DB::table('chefs')->insertGetId($final);
                
                /* ---------- Fetch stored chef data ---------- */
                $chef = Chef::select(
                            'id',
                            'name',
                            'email',
                            'phone_number',
                            'shop_plot_number',
                            'floor',
                            'building_name',
                            'pincode',
                            'address',
                            'latitude',
                            'longitude',
                            'delivery_radius',
                            'profile_image',
                            'cuisine_speciality',
                            'preference_tags',
                            'kitchen_assessment_photographs',
                            'fscai_certificate',
                            'created_at'
                        )->find($chefId);
                
                /* ---------- Create token ---------- */
                $token = $chef->createToken('chef_api_token')->plainTextToken;
                
                /* ---------- Cleanup temp data ---------- */
                DB::table('chef_registrations_temp')
                    ->where('id', $temp->id)
                    ->delete();
                
                /* ---------- FINAL RESPONSE ---------- */
                return CommonHelper::apiResponse(200, true, 'Chef Registration Completed ЁЯОЙ', [
                    'chef_id' => $chefId,
                    'token'   => $token,
                    'chef'    => $chef
                ]);



            default:
                return CommonHelper::apiResponse(422, false, 'Invalid step', []);
        }
    }

    /* ================= HELPER METHODS ================= */

    private function getTempByUuid($uuid)
    {
        return DB::table('chef_registrations_temp')
                 ->where('temp_uuid', $uuid)
                 ->first();
    }

    private function invalidToken()
    {
        return CommonHelper::apiResponse(422, false, 'No registration in progress', []);
    }







public function checkVerificationStatus(Request $request)
{
    $chefId = $request->user()->id;  // Authenticated Chef ID

    $chef = \App\Models\Chef::find($chefId);

    if (!$chef) {
        return CommonHelper::apiResponse(404, false, 'Chef not found', []);
    }

    return CommonHelper::apiResponse(200, true, 'Verification status fetched', [
        'chef_id'   => $chef->id,
        'is_verify' => (int)$chef->is_verify
    ]);
}




    // public function verifyPayment(Request $request)
    // {
    //     $request->validate([
    //         'razorpay_payment_id' => 'required|string',
    //         'razorpay_order_id' => 'required|string',
    //         'razorpay_signature' => 'required|string',
    //         'order_id' => 'required|integer',
    //         'payment_method' => 'nullable|string'
    //     ]);

    //     $generated_signature = hash_hmac(
    //         'sha256',
    //         $request->razorpay_order_id . "|" . $request->razorpay_payment_id,
    //         env('RAZORPAY_SECRET')
    //     );

    //     if ($generated_signature !== $request->razorpay_signature) {
    //         return CommonHelper::apiResponse(400, false, 'Payment verification failed', []);
    //     }

    //     $order = Order::find($request->order_id);
    //     $payment = json_decode($order->payment, true);

    //     $payment['payment_id'] = $request->razorpay_payment_id;
    //     $payment['method'] = $request->payment_method ?? 'Unknown'; // тнР Save method
    //     $payment['status'] = 'Paid';
    //     $payment['paidAt'] = now()->toISOString();

    //     $order->payment = json_encode($payment);
    //     $order->status = 'accepted';
    //     $order->update();

    //     return CommonHelper::apiResponse(200, true, 'Payment verified successfully!', []);
    // }


    /**
     * Helper: Send push notification via Firebase
     */
    private function sendPushNotification($fcmToken, $notification, $data = [])
    {
        $url = "https://fcm.googleapis.com/fcm/send";

        $payload = [
            "to" => $fcmToken,
            "priority" => "high",
            "notification" => $notification,
            "data" => $data
        ];

        $headers = [
            "Authorization: key=" . env('FIREBASE_SERVER_KEY'),
            "Content-Type: application/json"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }





    public function listFoodDishes()
    {
        $data = DB::table('food_dishes as fd')
            ->join('chefs as ch', 'fd.chef_id', '=', 'ch.id')
            ->leftJoin('category_food_dishes as cfd', 'fd.id', '=', 'cfd.food_dish_id')
            ->leftJoin('categories as cat', 'cfd.category_id', '=', 'cat.id')
            ->select(
                'fd.id',
                'fd.name',
                'fd.description',
                DB::raw("CONCAT('https://api.nutritionnook.net/', fd.image) AS image"),
                'ch.name as chef_name',
                DB::raw("GROUP_CONCAT(cat.title SEPARATOR ', ') as category_names") // тЬЕ multiple categories
            )
            ->groupBy('fd.id', 'fd.name', 'fd.description', 'fd.image', 'ch.name')
            ->get();

        if ($data->isNotEmpty()) {
            return CommonHelper::apiResponse(200, true, 'Food Dishes fetched successfully!', $data);
        } else {
            return CommonHelper::apiResponse(404, false, 'No food dishes found', []);
        }
    }
    
public function searchFoodDishes(Request $request)
{
    $search  = $request->query('search', '');
    $page    = $request->get('page', 1);
    $perPage = $request->get('per_page', 10);

    // 1я╕ПтГг Logged-in user
    $user = Auth::user();

    // 2я╕ПтГг Selected user address
    $userAddress = DB::table('user_addresses')
        ->where('user_id', $user->id)
        ->where('is_selected', 1)
        ->first();

    if (!$userAddress) {
        return CommonHelper::apiResponse(
            404,
            false,
            'Selected user address not found. Please select your delivery address.',
            []
        );
    }

    $userLat = $userAddress->latitude;
    $userLng = $userAddress->longitude;

    // 3я╕ПтГг Radius (KM)
    $radius = \App\Models\Setting::value('radius_km') ?? 10;

    /**
     * 4я╕ПтГг Check dish existence globally (without radius)
     */
    $globalDishQuery = DB::table('food_dishes as fd')
        ->join('chefs as c', 'fd.chef_id', '=', 'c.id');

    if (!empty($search)) {
        $globalDishQuery->where(function ($q) use ($search) {
            $q->where('fd.name', 'LIKE', "%{$search}%")
              ->orWhere('fd.description', 'LIKE', "%{$search}%")
              ->orWhere('c.name', 'LIKE', "%{$search}%");
        });
    }

    $globalExists = $globalDishQuery->exists();

    // тЭМ Dish does not exist anywhere
    if (!$globalExists) {
        return CommonHelper::apiResponse(
            404,
            false,
            'No matching food dishes found.',
            []
        );
    }

    /**
     * 5я╕ПтГг Main query with radius logic
     */
    $query = DB::table('food_dishes as fd')
        ->join('chefs as c', 'fd.chef_id', '=', 'c.id')
        ->select(
            'fd.*',
            DB::raw("CONCAT('https://api.nutritionnook.net/', fd.image) AS image"),
            'c.name as chef_name',
            DB::raw("(
                6371 * acos(
                    cos(radians($userLat)) *
                    cos(radians(c.latitude)) *
                    cos(radians(c.longitude) - radians($userLng)) +
                    sin(radians($userLat)) *
                    sin(radians(c.latitude))
                )
            ) AS distance")
        )
        ->having('distance', '<=', $radius);

    if (!empty($search)) {
        $query->where(function ($q) use ($search) {
            $q->where('fd.name', 'LIKE', "%{$search}%")
              ->orWhere('fd.description', 'LIKE', "%{$search}%")
              ->orWhere('c.name', 'LIKE', "%{$search}%");
        });
    }

    /**
     * 6я╕ПтГг Pagination
     */
    $data = $query
        ->orderBy('distance', 'asc')
        ->paginate($perPage, ['*'], 'page', $page);

    // тЭМ Dish exists but chef not available in radius
    if ($data->isEmpty()) {
        return CommonHelper::apiResponse(
            200,
            false,
            "This dish is currently not available within {$radius} km of your location.",
            []
        );
    }

    // тЬЕ Success
    return CommonHelper::apiResponse(
        200,
        true,
        'Filtered food dishes fetched successfully!',
        $data
    );
}



    // public function searchFoodDishes(Request $request)
    // {
    //     $search = $request->query('search', '');

    //     $query = DB::table('food_dishes as fd')
    //         ->join('chefs as c', 'fd.chef_id', '=', 'c.id')
    //         ->select(
    //             'fd.*',
    //             DB::raw("CONCAT('http://thinkdream.in/nutrition-food/', fd.image) AS image"),
    //             'c.name as chef_name'
    //         );

    //     // тЬЕ Search filter
    //     if (!empty($search)) {
    //         $query->where(function ($q) use ($search) {
    //             $q->where('fd.name', 'LIKE', "%{$search}%")
    //                 ->orWhere('fd.description', 'LIKE', "%{$search}%")
    //                 ->orWhere('c.name', 'LIKE', "%{$search}%"); // search by chef name
    //         });
    //     }

    //     $data = $query->get();

    //     if ($data->isNotEmpty()) {
    //         return CommonHelper::apiResponse(200, true, 'Filtered Food Dishes fetched successfully!', $data);
    //     } else {
    //         return CommonHelper::apiResponse(404, false, 'No matching food dishes found', []);
    //     }
    // }

    public function updateOrderStatus(Request $request, $orderId)
    {
        
        
        $chefId = auth()->id();

        // Validate input
        $request->validate([
            'status' => 'required',
        ]);
        
      

        // Map action тЖТ status
        $statusMap = [
            'mark_ready'      => 'ready',
            'mark_completed'  => 'completed',
            'mark_delivered'  => 'delivered',
        ];

        $newStatus = $request->status;

        // Check order ownership
        $order = DB::table('orders')
            ->where('id', $orderId)
            ->where('chef_id', $chefId)
            ->first();
            
        
        if (!$order) {
            return CommonHelper::apiResponse(404, false, 'Order not found', []);
        }

        // Update status
        DB::table('orders')
            ->where('id', $orderId)
            ->update([
                'status' => $newStatus,
                'updated_at' => now(),
            ]);

        // Check latest history record for this order + chef
        $lastHistory = DB::table('order_status_histories')
            ->where('order_id', $orderId)
            ->where('chef_id', $chefId)
            ->first();

        if (!$lastHistory || $lastHistory->status !== $newStatus) {
            // Insert new history only if status changed
            DB::table('order_status_histories')->insert([
                'order_id'   => $orderId,
                'chef_id'    => $chefId,
                'status'     => $newStatus,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        try {
        
        $user = DB::table('users')->where('id', $order->user_id)->first();
        
        $customerName = $user->name ?? 'Customer';

        $items = is_array($order->items) ? $order->items : json_decode($order->items, true);
        $items = $items ?? [];

        $foodImage = '';
        if (!empty($items)) {
            $firstItemId = $items[0]['id'] ?? null;
            if ($firstItemId) {
                $dishImage = DB::table('food_dishes')->where('id', $firstItemId)->value('image');
                if ($dishImage) {
                    $foodImage = url($dishImage);
                }
            }
        }

        $setting = DB::table('settings')->first();
        $logo = ($setting && $setting->logo) ? url('public/images/' . $setting->logo) : '';
        
        // Dynamic notification based on status
        $notificationMap = [
        'ready' => [
            'title' => 'Order Ready',
            'body'  => "Your Order #{$orderId} is ready for pickup",
            'type'  => 'order_ready',
        ],
        'completed' => [
            'title' => 'Order Completed',
            'body'  => "Your order #{$orderId} has been completed successfully.",
            'type'  => 'order_completed',
        ],
        'delivered' => [
            'title' => 'Order Delivered',
            'body'  => "Your order #{$orderId} has been delivered. Enjoy your meal!",
            'type'  => 'order_delivered',
        ],
    ];
    
    

    // Fallback if unknown status
    $notification = $notificationMap[$newStatus] ?? [
        'title' => 'Order Update',
        'body'  => "Your order #{$orderId} status has been updated.",
        'type'  => 'order_update',
    ];
    
    $title = $notification['title'];
    $body  = $notification['body'];
    $type  = $notification['type'];
    


        // $body  = "Your order #{$orderId} has been accepted by the chef";
        $orderDate = Carbon::parse($order->date);

$orderType = $orderDate->isToday()
    ? 'today'
    : ($orderDate->isFuture() ? 'preorder' : 'past');
    
        $data = [
            'title' => $title,
            'body'  => $body,
            // 'type'  => 'order_accepted',
            'orderId' => (string) $orderId,
            'customerName' => $customerName,
            'amount' => (string) $order->amount,
            'items'  => (string) count($items),
            'logo'   => $logo,
            'food_image' => $foodImage,
            'order_type' => $orderType,
            // 'order_type' => 
            // 'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ];

        $fcmService = new FCMService();
        $result = $fcmService->sendNotificationToUser($order->user_id, $title, $body, $data);
        
     
        // Send email to customer
        if ($newStatus == 'delivered') {
            try {
                
                 if (!empty($user->email)) {

            // ---------- ITEMS ----------
            $items = is_string($order->items)
                ? json_decode($order->items, true)
                : $order->items;

            $items = is_array($items) ? $items : [];

            // ---------- CHEF DETAILS ----------
            $chef = \App\Models\Chef::select('name','email','phone_number','fssai_license_number')
                ->where('id', $order->chef_id)
                ->first();

            // ---------- GST ----------
            $gstPercent = (float) str_replace('%', '', DB::table('settings')->value('gst') ?? 0);

            $subtotal = collect($items)->sum('total');
            $gstAmount = ($subtotal * $gstPercent) / 100;

            // ---------- COUPON ----------
            $coupon = null;
            if ($order->coupon_id) {
                $coupon = DB::table('coupons')->where('id', $order->coupon_id)->first();
            }
            
            $setting = DB::table('settings')->first();
            $platformFee = $setting->platform_fee ?? 0; // જે ટેબલમાં તમારી ફી સેવ છે

            // ---------- PDF DATA ----------
            $pdfData = [
                'order'      => $order,
                'items'      => $items,
                'user'       => $user,
                'chef'       => $chef,
                'subtotal'   => $subtotal,
                'gstPercent' => $gstPercent,
                'gstAmount'  => $gstAmount,
                'coupon'     => $coupon,
                'platformFee' => $platformFee,
                
                'date'       => \Carbon\Carbon::parse($order->created_at)->format('d M Y'),
            ];

            // ✅ USE YOUR WORKING VIEW
            $pdf = \PDF::loadView('pdf.invoice', $pdfData);

            // Optional: Save file
            $fileName = 'invoice_order_' . $order->id . '.pdf';

            // ---------- EMAIL ----------
            \Mail::send('emails.order-delivered', [
                'customerName'   => $user->name ?? 'Customer',
                    'orderId'        => $order->id,
                    'amount'         => $order->amount,        // Final amount from DB
                    'items'          => $items,
                    'subtotal'       => $subtotal,
                    'gstPercent'     => $gstPercent,
                    'gstAmount'      => $gstAmount,
                    'platformFee'    => $platformFee,
                    'discountAmount' => $order->discount_amount ?? 0 // Discount amount ઉમેર્યું
            ], function ($message) use ($user, $order, $pdf, $fileName) {

                $message->to($user->email)
                    ->subject("Order #{$order->id} Delivered - Invoice Attached")
                    ->attachData(
                        $pdf->output(),
                        $fileName,
                        ['mime' => 'application/pdf']
                    );
            });

            \Log::info("✅ Delivery Email with Invoice sent to: " . $user->email);
        }
            
            } catch (\Throwable $e) {
                dd($e->getMessage());
                \Log::warning('Order status mail failed', [
                    'order_id' => $orderId,
                    'error' => $e->getMessage()
                ]);
            
            }
        }

        \Log::info('Accept Order Push Sent', [
            'order_id' => $orderId,
            'result' => $result,
            'data' => $data
        ]);

    } catch (\Throwable $e) {
        dd($e->getMessage());
        \Log::warning('Accept order push failed', [
            'order_id' => $orderId,
            'error' => $e->getMessage()
        ]);
    }

        return CommonHelper::apiResponse(200, true, "Order marked as {$newStatus} successfully!", [
            'order_id' => $orderId,
            'new_status' => $newStatus,
        ]);
    }

    public function updateCustomer(Request $request)
    {
        $userId = Auth::id(); // token se user id milega

        // тЬЕ Validation
        $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => 'required|email|unique:users,email,' . $userId,
            'dob'    => 'nullable|date',
            'gender' => 'nullable|in:male,female,other,prefer not to disclose',
        ]);

        // тЬЕ Update user
        DB::table('users')->where('id', $userId)->update([
            'name'   => $request->name,
            'email'  => $request->email,
            'dob'    => $request->dob,
            'gender' => $request->gender,
            'updated_at' => now(),
        ]);

        // тЬЕ Updated record fetch karke return karo
        $user = DB::table('users')
            ->select('id', 'name', 'email', 'dob', 'gender')
            ->where('id', $userId)
            ->first();

        // if ($user && !empty($user->image)) {
        //     $user->image = asset('/images/' . ltrim($user->image, '/'));
        // }

        return CommonHelper::apiResponse(200, true, 'Customer details updated successfully!', $user);
    }
    
    public function sendPreOrderReminders()
    {
        $today = Carbon::today();

        $upcomingOrders = DB::table('orders')
            ->whereNotNull('date')
            ->whereDate('date', '>', $today)
            ->where('payment_status','received')
            ->where('is_buffer', '1')
            // ->whereIn('status', )
            ->where(function ($query) use ($today) {
                $query->whereNull('last_push_sent_at')
                    ->orWhereDate('last_push_sent_at', '<', $today);
            })
            ->get();
        

        $sentCount = 0;
        $skippedCount = 0;
        $fcmService = new FCMService();
        $setting = DB::table('settings')->first();
        $logo = ($setting && $setting->logo) ? url('public/images/' . $setting->logo) : '';

        foreach ($upcomingOrders as $order) {
            $orderDate = Carbon::parse($order->date)->startOfDay();
            $daysLeft = (int) $today->diffInDays($orderDate, false);

            if ($daysLeft <= 0) {
                $skippedCount++;
                continue;
            }

            if ($daysLeft == 1) {
                $notifyTitle = 'Order Tomorrow!';
                $notifyBody  = "Order #{$order->id} is scheduled for tomorrow ({$orderDate->format('d M Y')}). Get ready!";
            } else {
                $notifyTitle = "Upcoming Order - {$daysLeft} Days Left";
                $notifyBody  = "Order #{$order->id} is coming up in {$daysLeft} days ({$orderDate->format('d M Y')}). Please prepare accordingly.";
            }

            $notifyData = [
                'title'      => $notifyTitle,
                'body'       => $notifyBody,
                'type'       => 'pre_order_reminder',
                'order_id'   => (string) $order->id,
                'order_date' => $orderDate->format('Y-m-d'),
                'days_left'  => (string) $daysLeft,
                'logo'       => $logo,
                'order_type' => 'preorder'
            ];

            try {
                $fcmService->sendNotificationToUser(
                    $order->chef_id,
                    $notifyTitle,
                    $notifyBody,
                    $notifyData
                );

                DB::table('orders')
                    ->where('id', $order->id)
                    ->update(['last_push_sent_at' => now()]);

                $sentCount++;
                
                $chef = DB::table('chefs')->where('id', $order->chef_id)->first();

                    if ($chef) {
                        Mail::send('emails.preorder-reminder', [
                            'name' => $chef->name ?? 'Chef',
                            'orderId' => $order->id,
                            'orderDate' => $orderDate->format('d M Y'),
                            'daysLeft' => $daysLeft
                        ], function ($message) use ($user, $order) {
    
                            $message->to($user->email)
                                ->subject("Upcoming Order Reminder #{$order->id}");
    
                        });
                }

            } catch (\Throwable $e) {
                $skippedCount++;
                \Illuminate\Support\Facades\Log::error("PreOrderReminder error for Order #{$order->id}: " . $e->getMessage());
            }
        }

        return response()->json([
            'status'  => true,
            'message' => "Pre-order reminders processed. Sent: {$sentCount}, Skipped: {$skippedCount}",
            'sent'    => $sentCount,
            'skipped' => $skippedCount,
        ]);
    }
}
