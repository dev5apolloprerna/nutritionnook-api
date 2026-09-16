<?php
// app/Http/Controllers/Admin/ReportController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Chef;
use App\Models\User;
use App\Models\FoodDish;
use App\Models\CuisineType;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Rating;
use Carbon\Carbon;
use DB;
use Excel;
use PDF;
use App\Exports\DynamicExport;
use App\Exports\ReportMultiSheetExport;

class ReportController extends Controller
{
    /**
     * Show all reports dashboard
     */
    public function index()
    {
        return view('reports.index');
    }

    /**
     * 1. Order & Revenue Reports
     */
    public function orderRevenueReports(Request $request)
    {
        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'report_type' => 'nullable|in:daily,weekly,monthly,custom'
        ]);

        $fromDate = $request->from_date ? Carbon::parse($request->from_date) : Carbon::now()->startOfMonth();
        $toDate = $request->to_date ? Carbon::parse($request->to_date)->endOfDay() : Carbon::now()->endOfDay();
        $reportType = $request->report_type ?? 'monthly';

        // Total Orders Report
        $ordersQuery = Order::whereBetween('created_at', [$fromDate, $toDate]);
        
        $totalOrders = $ordersQuery->count();
        $totalRevenue = $ordersQuery->sum('amount');
        $totalCommission = $ordersQuery->where('is_payout_completed', 1)->sum(DB::raw('amount * 0.1')); // Assuming 10% commission
        $totalRefunds = Order::where('is_refunded', 1)->whereBetween('refunded_at', [$fromDate, $toDate])->sum('amount');

        // Order Status Report
        $orderStatusData = Order::select('status', DB::raw('count(*) as count'), DB::raw('sum(amount) as revenue'))
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->groupBy('status')
            ->get();

        // Daily/Weekly/Monthly breakdown
        $dateFormat = match($reportType) {
            'daily' => '%Y-%m-%d',
            'weekly' => '%Y-%u',
            'monthly' => '%Y-%m',
            default => '%Y-%m-%d'
        };

        $orderTrends = Order::select(
                DB::raw("DATE_FORMAT(created_at, '$dateFormat') as period"),
                DB::raw('count(*) as total_orders'),
                DB::raw('sum(amount) as total_revenue'),
                DB::raw('sum(case when status = "cancelled" then 1 else 0 end) as cancelled_orders')
            )
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        // Refund/Cancellation Report
        $refundedOrders = Order::where('is_refunded', 1)
            ->whereBetween('refunded_at', [$fromDate, $toDate])
            ->with(['user', 'chef'])
            ->get();

        $cancelledOrders = Order::where('status', 'cancelled')
            ->whereBetween('updated_at', [$fromDate, $toDate])
            ->with(['user', 'chef'])
            ->get();

        if ($request->export == 'excel') {
            return $this->exportOrderRevenueExcel($orderTrends, $orderStatusData, $fromDate, $toDate);
        } elseif ($request->export == 'pdf') {
            return $this->exportOrderRevenuePDF($orderTrends, $orderStatusData, $fromDate, $toDate);
        }

        return view('reports.order-revenue', compact(
            'totalOrders', 'totalRevenue', 'totalCommission', 'totalRefunds',
            'orderStatusData', 'orderTrends', 'refundedOrders', 'cancelledOrders',
            'fromDate', 'toDate', 'reportType'
        ));
    }
    
    public function customerInsights(Request $request)
{
    $request->validate([
        'from_date' => 'nullable|date',
        'to_date' => 'nullable|date|after_or_equal:from_date'
    ]);

    $fromDate = $request->from_date ? Carbon::parse($request->from_date)->startOfDay() : Carbon::now()->startOfMonth();
    $toDate = $request->to_date ? Carbon::parse($request->to_date)->endOfDay() : Carbon::now()->endOfDay();

    try {
        // Get all customers (role_id = 5) with their orders
        $customerData = User::where('user_role', 5)
            ->where('is_delete', 0)
            ->with(['orders' => function($query) use ($fromDate, $toDate) {
                $query->whereBetween('created_at', [$fromDate, $toDate])
                      ->where('payment_status', 'received');
            }])
            ->get();

        // New vs Returning Customers with null checking
        $newCustomers = $customerData->filter(function($user) use ($fromDate, $toDate) {
            // Check if created_at exists and is a valid Carbon instance
            if ($user->created_at && $user->created_at instanceof \Carbon\Carbon) {
                return $user->created_at->between($fromDate, $toDate);
            }
            return false;
        })->count();

        $returningCustomers = $customerData->filter(function($user) use ($fromDate, $toDate) {
            // Check if user has orders in the period and created_at is valid
            $hasOrdersInPeriod = $user->orders->count() > 0;
            
            // Check if user was created before the period
            $wasExistingCustomer = $user->created_at && 
                                   $user->created_at instanceof \Carbon\Carbon && 
                                   $user->created_at->lt($fromDate);
            
            return $hasOrdersInPeriod && $wasExistingCustomer;
        })->count();

        $inactiveCustomers = $customerData->filter(function($user) {
            return $user->orders->count() == 0;
        })->count();

        // Total customers count
        $totalCustomers = $customerData->count();

        // Top Customers by spending with better null handling
        $topCustomers = User::select(
                'users.id',
                'users.name',
                'users.email',
                'users.phone_number',
                'users.image',
                'users.created_at as registered_date',
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('COALESCE(SUM(orders.amount), 0) as total_spent'),
                DB::raw('COALESCE(AVG(orders.amount), 0) as avg_order_value'),
                DB::raw('MAX(orders.created_at) as last_order_date')
            )
            ->leftJoin('orders', function($join) use ($fromDate, $toDate) {
                $join->on('users.id', '=', 'orders.user_id')
                     ->whereBetween('orders.created_at', [$fromDate, $toDate])
                     ->where('orders.payment_status', 'received');
            })
            ->where('users.user_role', 5)
            ->where('users.is_delete', 0)
            ->groupBy(
                'users.id', 
                'users.name', 
                'users.email', 
                'users.phone_number',
                'users.image',
                'users.created_at'
            )
            ->having('order_count', '>', 0)
            ->orderByDesc('total_spent')
            ->limit(20)
            ->get();

        // Format top customers data
        $topCustomers = $topCustomers->map(function($customer) {
            return (object)[
                'id' => $customer->id,
                'name' => $customer->name ?? 'N/A',
                'email' => $customer->email ?? 'N/A',
                'phone_number' => $customer->phone_number ?? 'N/A',
                'image' => $customer->image,
                'registered_date' => $customer->registered_date ? Carbon::parse($customer->registered_date)->format('d M Y') : 'N/A',
                'order_count' => (int) $customer->order_count,
                'total_spent' => (float) $customer->total_spent,
                'avg_order_value' => $customer->order_count > 0 
                    ? round($customer->total_spent / $customer->order_count, 2) 
                    : 0,
                'last_order_date' => $customer->last_order_date 
                    ? Carbon::parse($customer->last_order_date)->format('d M Y') 
                    : 'Never'
            ];
        });

        // Customer Rating Summary with null handling
        $customerRatings = User::select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('COUNT(DISTINCT ratings.id) as total_ratings_given'),
                DB::raw('COALESCE(ROUND(AVG(ratings.rating), 2), 0) as avg_rating_given'),
                DB::raw('SUM(CASE WHEN ratings.rating = 5 THEN 1 ELSE 0 END) as five_star_ratings'),
                DB::raw('SUM(CASE WHEN ratings.rating = 4 THEN 1 ELSE 0 END) as four_star_ratings'),
                DB::raw('SUM(CASE WHEN ratings.rating = 3 THEN 1 ELSE 0 END) as three_star_ratings'),
                DB::raw('SUM(CASE WHEN ratings.rating = 2 THEN 1 ELSE 0 END) as two_star_ratings'),
                DB::raw('SUM(CASE WHEN ratings.rating = 1 THEN 1 ELSE 0 END) as one_star_ratings')
            )
            ->leftJoin('ratings', 'users.id', '=', 'ratings.user_id')
            ->where('users.user_role', 5)
            ->where('users.is_delete', 0)
            ->whereBetween('ratings.created_at', [$fromDate, $toDate])
            ->groupBy('users.id', 'users.name', 'users.email')
            ->having('total_ratings_given', '>', 0)
            ->orderByDesc('avg_rating_given')
            ->get();

        // Format ratings data
        $customerRatings = $customerRatings->map(function($rating) {
            return (object)[
                'id' => $rating->id,
                'name' => $rating->name ?? 'N/A',
                'email' => $rating->email ?? 'N/A',
                'total_ratings_given' => (int) $rating->total_ratings_given,
                'avg_rating_given' => (float) $rating->avg_rating_given,
                'five_star_ratings' => (int) ($rating->five_star_ratings ?? 0),
                'four_star_ratings' => (int) ($rating->four_star_ratings ?? 0),
                'three_star_ratings' => (int) ($rating->three_star_ratings ?? 0),
                'two_star_ratings' => (int) ($rating->two_star_ratings ?? 0),
                'one_star_ratings' => (int) ($rating->one_star_ratings ?? 0)
            ];
        });

        // Customer Acquisition by Date with null handling
        $customerAcquisition = User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as new_customers')
            )
            ->where('user_role', 5)
            ->where('is_delete', 0)
            ->whereNotNull('created_at')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // Fill missing dates in acquisition data
        $acquisitionData = [];
        $period = new \DatePeriod($fromDate, new \DateInterval('P1D'), $toDate);
        $acquisitionByDate = $customerAcquisition->keyBy('date');
        
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $acquisitionData[] = (object)[
                'date' => $dateStr,
                'new_customers' => $acquisitionByDate[$dateStr]->new_customers ?? 0
            ];
        }

        // Customer Order Distribution with null handling
        $customersWithOrders = $customerData->filter(function($user) {
            return $user->orders->count() > 0;
        });

        $orderDistribution = [
            '1-2 orders' => $customersWithOrders->filter(function($user) { 
                $count = $user->orders->count();
                return $count >= 1 && $count <= 2; 
            })->count(),
            '3-5 orders' => $customersWithOrders->filter(function($user) { 
                $count = $user->orders->count();
                return $count >= 3 && $count <= 5; 
            })->count(),
            '6-10 orders' => $customersWithOrders->filter(function($user) { 
                $count = $user->orders->count();
                return $count >= 6 && $count <= 10; 
            })->count(),
            '10+ orders' => $customersWithOrders->filter(function($user) { 
                return $user->orders->count() > 10; 
            })->count(),
        ];

        // Summary statistics
        $summary = [
            'total_customers' => $totalCustomers,
            'new_customers' => $newCustomers,
            'returning_customers' => $returningCustomers,
            'inactive_customers' => $inactiveCustomers,
            'customers_with_orders' => $customersWithOrders->count(),
            'total_revenue' => $topCustomers->sum('total_spent'),
            'average_revenue_per_customer' => $customersWithOrders->count() > 0 
                ? round($topCustomers->sum('total_spent') / $customersWithOrders->count(), 2) 
                : 0
        ];

        // Export functionality
        if ($request->export == 'excel') {
            return $this->exportCustomerInsightsExcel($topCustomers, $customerRatings, $acquisitionData, $fromDate, $toDate);
        } elseif ($request->export == 'pdf') {
            return $this->exportCustomerInsightsPDF($topCustomers, $customerRatings, $acquisitionData, $fromDate, $toDate);
        }

        return view('reports.customer-insights', compact(
            'customerData',
            'summary',
            'topCustomers', 
            'customerRatings',
            'acquisitionData',
            'orderDistribution',
            'fromDate', 
            'toDate'
        ));

    } catch (\Exception $e) {
        \Log::error('Customer Insights Error: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ]);

        return back()->with('error', 'Failed to generate customer insights: ' . $e->getMessage());
    }
}
    
    public function chefPerformanceReports(Request $request)
    {
        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'chef_id' => 'nullable|exists:chefs,id'
        ]);

        $fromDate = $request->from_date ? Carbon::parse($request->from_date) : Carbon::now()->startOfMonth();
        $toDate = $request->to_date ? Carbon::parse($request->to_date)->endOfDay() : Carbon::now()->endOfDay();

        // Chef Earnings Report
        $chefEarnings = Chef::select(
                'chefs.id',
                'chefs.name',
                'chefs.business_name',
                'chefs.email',
                'chefs.phone_number',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('SUM(orders.amount) as gross_earnings'),
                DB::raw('SUM(orders.amount * chefs.commission / 100) as commission_amount'),
                DB::raw('SUM(orders.amount * (100 - chefs.commission) / 100) as net_earnings'),
                DB::raw('AVG(ratings.rating) as avg_rating')
            )
            ->leftJoin('orders', function($join) use ($fromDate, $toDate) {
                $join->on('chefs.id', '=', 'orders.chef_id')
                     ->whereBetween('orders.created_at', [$fromDate, $toDate])
                     ->where('orders.payment_status', 'received');
            })
            ->leftJoin('ratings', 'chefs.id', '=', 'ratings.chef_id')
            ->when($request->chef_id, function($query) use ($request) {
                return $query->where('chefs.id', $request->chef_id);
            })
            ->groupBy('chefs.id', 'chefs.name', 'chefs.business_name', 'chefs.email', 'chefs.phone_number')
            ->orderByDesc('gross_earnings')
            ->get();

        // Chef Acceptance Rate
        $chefAcceptance = Chef::select(
                'chefs.id',
                'chefs.name',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('SUM(CASE WHEN orders.status = "accepted" THEN 1 ELSE 0 END) as accepted_orders'),
                DB::raw('SUM(CASE WHEN orders.status = "rejected" THEN 1 ELSE 0 END) as rejected_orders'),
                DB::raw('ROUND(SUM(CASE WHEN orders.status = "accepted" THEN 1 ELSE 0 END) * 100.0 / COUNT(orders.id), 2) as acceptance_rate')
            )
            ->leftJoin('orders', function($join) use ($fromDate, $toDate) {
                $join->on('chefs.id', '=', 'orders.chef_id')
                     ->whereBetween('orders.created_at', [$fromDate, $toDate]);
            })
            ->whereNotNull('orders.id')
            ->groupBy('chefs.id', 'chefs.name')
            ->orderByDesc('acceptance_rate')
            ->get();

        // Top Selling Items
        $topItems = FoodDish::select(
                'food_dishes.id',
                'food_dishes.name',
                'chefs.name as chef_name',
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('SUM(orders.amount) as total_revenue'),
                'food_dishes.price'
            )
            ->join('chefs', 'food_dishes.chef_id', '=', 'chefs.id')
            ->join('orders', DB::raw('JSON_CONTAINS(orders.items, JSON_OBJECT("id", food_dishes.id))'), '=', DB::raw('1'))
            ->whereBetween('orders.created_at', [$fromDate, $toDate])
            ->groupBy('food_dishes.id', 'food_dishes.name', 'chefs.name', 'food_dishes.price')
            ->orderByDesc('order_count')
            ->limit(20)
            ->get();

        // Chef Rating Summary
        $chefRatings = Chef::select(
                'chefs.id',
                'chefs.name',
                DB::raw('COUNT(ratings.id) as total_ratings'),
                DB::raw('ROUND(AVG(ratings.rating), 2) as avg_rating'),
                DB::raw('SUM(CASE WHEN ratings.rating = 5 THEN 1 ELSE 0 END) as five_star'),
                DB::raw('SUM(CASE WHEN ratings.rating = 4 THEN 1 ELSE 0 END) as four_star'),
                DB::raw('SUM(CASE WHEN ratings.rating = 3 THEN 1 ELSE 0 END) as three_star'),
                DB::raw('SUM(CASE WHEN ratings.rating = 2 THEN 1 ELSE 0 END) as two_star'),
                DB::raw('SUM(CASE WHEN ratings.rating = 1 THEN 1 ELSE 0 END) as one_star')
            )
            ->leftJoin('ratings', 'chefs.id', '=', 'ratings.chef_id')
            ->groupBy('chefs.id', 'chefs.name')
            ->having('total_ratings', '>', 0)
            ->orderByDesc('avg_rating')
            ->get();

        if ($request->export == 'excel') {
            return $this->exportChefPerformanceExcel($chefEarnings, $chefAcceptance, $topItems, $fromDate, $toDate);
        } elseif ($request->export == 'pdf') {
            return $this->exportChefPerformancePDF($chefEarnings, $chefAcceptance, $topItems, $fromDate, $toDate);
        }

        return view('reports.chef-performance', compact(
            'chefEarnings', 'chefAcceptance', 'topItems', 'chefRatings',
            'fromDate', 'toDate'
        ));
    }



// $chefEarnings = Chef::select(
//         'chefs.id',
//         'chefs.name',
//         'chefs.business_name',
//         'chefs.email',
//         'chefs.phone_number',
//         'chefs.commission',
//         DB::raw('COUNT(orders.id) as total_orders'),
//         DB::raw('COALESCE(SUM(orders.amount), 0) as gross_earnings'),
//         DB::raw('COALESCE(SUM(orders.amount * IFNULL(chefs.commission, 10) / 100), 0) as commission_amount'),
//         DB::raw('COALESCE(SUM(orders.amount * (100 - IFNULL(chefs.commission, 10)) / 100), 0) as net_earnings'),
//         DB::raw('COALESCE(AVG(ratings.rating), 0) as avg_rating')
//     )
//     ->leftJoin('orders', function($join) use ($fromDate, $toDate) {
//         $join->on('chefs.id', '=', 'orders.chef_id')
//              ->whereBetween('orders.created_at', [$fromDate, $toDate])
//              ->where('orders.payment_status', 'received');
//     })
//     ->leftJoin('ratings', 'chefs.id', '=', 'ratings.chef_id')
//     ->when($request->chef_id, function($query) use ($request) {
//         return $query->where('chefs.id', $request->chef_id);
//     })
//     ->groupBy('chefs.id', 'chefs.name', 'chefs.business_name', 'chefs.email', 'chefs.phone_number', 'chefs.commission')
//     ->orderByDesc('gross_earnings')
//     ->get();

    /**
     * 3. Customer Insights Reports
     */
   

    /**
     * 4. Menu & Cuisine Insights
     */
    public function menuCuisineInsights(Request $request)
    {
        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date'
        ]);

        $fromDate = $request->from_date ? Carbon::parse($request->from_date) : Carbon::now()->startOfMonth();
        $toDate = $request->to_date ? Carbon::parse($request->to_date)->endOfDay() : Carbon::now()->endOfDay();

        // Most Ordered Cuisine Types
        $topCuisines = CuisineType::select(
                'cuisine_type.id',
                'cuisine_type.title',
                DB::raw('COUNT(DISTINCT orders.id) as order_count'),
                DB::raw('SUM(orders.amount) as revenue')
            )
            ->join('food_dishes', 'cuisine_type.id', '=', 'food_dishes.cuisine_type_id')
            ->join('orders', DB::raw('JSON_CONTAINS(orders.items, JSON_OBJECT("id", food_dishes.id))'), '=', DB::raw('1'))
            ->whereBetween('orders.created_at', [$fromDate, $toDate])
            ->groupBy('cuisine_type.id', 'cuisine_type.title')
            ->orderByDesc('order_count')
            ->get();

        // Most Ordered Dishes
        $mostOrderedDishes = FoodDish::select(
                'food_dishes.id',
                'food_dishes.name',
                'chefs.name as chef_name',
                'cuisine_type.title as cuisine',
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('SUM(orders.amount) as total_revenue'),
                'food_dishes.price'
            )
            ->join('chefs', 'food_dishes.chef_id', '=', 'chefs.id')
            ->join('cuisine_type', 'food_dishes.cuisine_type_id', '=', 'cuisine_type.id')
            ->join('orders', DB::raw('JSON_CONTAINS(orders.items, JSON_OBJECT("id", food_dishes.id))'), '=', DB::raw('1'))
            ->whereBetween('orders.created_at', [$fromDate, $toDate])
            ->groupBy('food_dishes.id', 'food_dishes.name', 'chefs.name', 'cuisine_type.title', 'food_dishes.price')
            ->orderByDesc('order_count')
            ->limit(30)
            ->get();

        // Out of Stock / Disabled Items
        $outOfStockItems = FoodDish::select(
                'food_dishes.*',
                'chefs.name as chef_name'
            )
            ->join('chefs', 'food_dishes.chef_id', '=', 'chefs.id')
            ->where('food_dishes.in_stock', 0)
            ->orWhere('food_dishes.is_active', 0)
            ->get();

        if ($request->export == 'excel') {
            return $this->exportMenuInsightsExcel($topCuisines, $mostOrderedDishes, $outOfStockItems, $fromDate, $toDate);
        } elseif ($request->export == 'pdf') {
            return $this->exportMenuInsightsPDF($topCuisines, $mostOrderedDishes, $outOfStockItems, $fromDate, $toDate);
        }

        return view('reports.menu-cuisine', compact(
            'topCuisines', 'mostOrderedDishes', 'outOfStockItems',
            'fromDate', 'toDate'
        ));
    }

    /**
     * 5. Platform Health / Analytics
     */
    public function platformHealth(Request $request)
    {
        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date'
        ]);

        $fromDate = $request->from_date ? Carbon::parse($request->from_date) : Carbon::now()->subDays(30);
        $toDate = $request->to_date ? Carbon::parse($request->to_date)->endOfDay() : Carbon::now()->endOfDay();

        // Daily Active Users (DAU)
        $dailyActiveUsers = User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(DISTINCT id) as new_users'),
                DB::raw('0 as active_users') // You'll need a user_activity table for accurate DAU
            )
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // Orders Trend (7/30 days)
        $ordersTrend = Order::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(amount) as revenue')
            )
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // Peak Ordering Time
        $peakHours = Order::select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(amount) as revenue')
            )
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('hour')
            ->get();

        // Coupon Usage Report
        $couponUsage = Coupon::select(
                'coupons.id',
                'coupons.code',
                'coupons.type',
                'coupons.value',
                DB::raw('COUNT(coupon_usages.id) as usage_count'),
                DB::raw('SUM(coupon_usages.discount_amount) as total_discount'),
                DB::raw('COUNT(DISTINCT coupon_usages.user_id) as unique_users')
            )
            ->leftJoin('coupon_usages', 'coupons.id', '=', 'coupon_usages.coupon_id')
            ->whereBetween('coupon_usages.created_at', [$fromDate, $toDate])
            ->groupBy('coupons.id', 'coupons.code', 'coupons.type', 'coupons.value')
            ->orderByDesc('usage_count')
            ->get();

        if ($request->export == 'excel') {
            return $this->exportPlatformHealthExcel($ordersTrend, $peakHours, $couponUsage, $fromDate, $toDate);
        } elseif ($request->export == 'pdf') {
            return $this->exportPlatformHealthPDF($ordersTrend, $peakHours, $couponUsage, $fromDate, $toDate);
        }

        return view('reports.platform-health', compact(
            'dailyActiveUsers', 'ordersTrend', 'peakHours', 'couponUsage',
            'fromDate', 'toDate'
        ));
    }

    // Export methods
    private function exportOrderRevenueExcel($orderTrends, $orderStatusData, $fromDate, $toDate)
    {
        $sheets = [
            new DynamicExport('order_trends', ['period', 'total_orders', 'total_revenue', 'cancelled_orders'], $orderTrends, 'Order Trends'),
            new DynamicExport('order_status', ['status', 'count', 'revenue'], $orderStatusData, 'Order Status'),
        ];

        return Excel::download(new ReportMultiSheetExport($sheets), $this->reportFileName('order-revenue', $fromDate, $toDate));
    }

    private function exportOrderRevenuePDF($orderTrends, $orderStatusData, $fromDate, $toDate)
    {
        $pdf = PDF::loadView('reports.pdf.order-revenue', compact('orderTrends', 'orderStatusData', 'fromDate', 'toDate'));
        return $pdf->download('order-revenue-report-'.now()->format('Y-m-d').'.pdf');
    }

    private function exportChefPerformanceExcel($chefEarnings, $chefAcceptance, $topItems, $fromDate, $toDate)
    {
        $sheets = [
            new DynamicExport('chef_earnings', ['id', 'name', 'business_name', 'email', 'phone_number', 'total_orders', 'gross_earnings', 'commission_amount', 'net_earnings', 'avg_rating'], $chefEarnings, 'Chef Earnings'),
            new DynamicExport('chef_acceptance', ['id', 'name', 'total_orders', 'accepted_orders', 'rejected_orders', 'acceptance_rate'], $chefAcceptance, 'Acceptance Rate'),
            new DynamicExport('top_items', ['id', 'name', 'chef_name', 'order_count', 'total_revenue', 'price'], $topItems, 'Top Selling Items'),
        ];

        return Excel::download(new ReportMultiSheetExport($sheets), $this->reportFileName('chef-performance', $fromDate, $toDate));
    }

    private function exportChefPerformancePDF($chefEarnings, $chefAcceptance, $topItems, $fromDate, $toDate)
    {
        $pdf = PDF::loadView('reports.pdf.chef-performance', compact('chefEarnings', 'chefAcceptance', 'topItems', 'fromDate', 'toDate'));
        return $pdf->download('chef-performance-report-'.now()->format('Y-m-d').'.pdf');
    }

    private function exportCustomerInsightsExcel($topCustomers, $customerRatings, $acquisitionData, $fromDate, $toDate)
    {
        $sheets = [
            new DynamicExport('top_customers', ['id', 'name', 'email', 'phone_number', 'registered_date', 'order_count', 'total_spent', 'avg_order_value', 'last_order_date'], $topCustomers, 'Top Customers'),
            new DynamicExport('customer_ratings', ['id', 'name', 'email', 'total_ratings_given', 'avg_rating_given', 'five_star_ratings', 'four_star_ratings', 'three_star_ratings', 'two_star_ratings', 'one_star_ratings'], $customerRatings, 'Customer Ratings'),
            new DynamicExport('acquisition', ['date', 'new_customers'], collect($acquisitionData), 'Customer Acquisition'),
        ];

        return Excel::download(new ReportMultiSheetExport($sheets), $this->reportFileName('customer-insights', $fromDate, $toDate));
    }

    private function exportCustomerInsightsPDF($topCustomers, $customerRatings, $acquisitionData, $fromDate, $toDate)
    {
        $pdf = PDF::loadView('reports.pdf.customer-insights', compact('topCustomers', 'customerRatings', 'acquisitionData', 'fromDate', 'toDate'));
        return $pdf->download('customer-insights-report-'.now()->format('Y-m-d').'.pdf');
    }

    private function exportMenuInsightsExcel($topCuisines, $mostOrderedDishes, $outOfStockItems, $fromDate, $toDate)
    {
        $sheets = [
            new DynamicExport('top_cuisines', ['id', 'title', 'order_count', 'revenue'], $topCuisines, 'Top Cuisines'),
            new DynamicExport('most_ordered_dishes', ['id', 'name', 'chef_name', 'cuisine', 'order_count', 'total_revenue', 'price'], $mostOrderedDishes, 'Most Ordered Dishes'),
            new DynamicExport('out_of_stock', ['id', 'name', 'chef_name', 'price', 'in_stock', 'is_active'], $outOfStockItems, 'Out of Stock'),
        ];

        return Excel::download(new ReportMultiSheetExport($sheets), $this->reportFileName('menu-cuisine', $fromDate, $toDate));
    }

    private function exportMenuInsightsPDF($topCuisines, $mostOrderedDishes, $outOfStockItems, $fromDate, $toDate)
    {
        $pdf = PDF::loadView('reports.pdf.menu-cuisine', compact('topCuisines', 'mostOrderedDishes', 'outOfStockItems', 'fromDate', 'toDate'));
        return $pdf->download('menu-cuisine-report-'.now()->format('Y-m-d').'.pdf');
    }

    private function exportPlatformHealthExcel($ordersTrend, $peakHours, $couponUsage, $fromDate, $toDate)
    {
        $sheets = [
            new DynamicExport('orders_trend', ['date', 'order_count', 'revenue'], $ordersTrend, 'Orders Trend'),
            new DynamicExport('peak_hours', ['hour', 'order_count', 'revenue'], $peakHours, 'Peak Hours'),
            new DynamicExport('coupon_usage', ['id', 'code', 'type', 'value', 'usage_count', 'total_discount', 'unique_users'], $couponUsage, 'Coupon Usage'),
        ];

        return Excel::download(new ReportMultiSheetExport($sheets), $this->reportFileName('platform-health', $fromDate, $toDate));
    }

    private function exportPlatformHealthPDF($ordersTrend, $peakHours, $couponUsage, $fromDate, $toDate)
    {
        $pdf = PDF::loadView('reports.pdf.platform-health', compact('ordersTrend', 'peakHours', 'couponUsage', 'fromDate', 'toDate'));
        return $pdf->download('platform-health-report-'.now()->format('Y-m-d').'.pdf');
    }

    private function reportFileName(string $slug, $fromDate, $toDate): string
    {
        return "{$slug}-report-{$fromDate->format('Y-m-d')}_to_{$toDate->format('Y-m-d')}.xlsx";
    }
}