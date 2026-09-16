<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\Category;
use Carbon\Carbon;
use App\Models\FoodItem;
use App\Models\MealTime;
use App\Models\Preference;
use App\Models\CuisineType;
use App\Models\FoodDish;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
{
    $today = now()->toDateString();
    $startOfWeek = Carbon::now()->startOfWeek();
    $endOfWeek = Carbon::now()->endOfWeek();

    // ========== 1. SUMMARY BOXES ==========

    // Today's Orders
    $todaysOrders = Order::whereDate('date', $today)->count();

    // Today's Revenue (Total amount paid by customers)
    $todaysRevenue = Order::whereDate('date', $today)->sum('amount');

    // --- PAYOUT LOGIC MATCHED COMMISSION ---
$todaysOrdersCollection = Order::whereDate('date', $today)
    ->where('status', 'delivered') // ફક્ત સક્સેસફુલ ઓર્ડર જ ગણવા
    ->get();

$todaysRevenue = 0;    // આમાં આપણે 90% (શેફનો ભાગ) + 10% (કમિશન) એટલે કે ટોટલ બેઝ પ્રાઈસ ગણીશું
$todaysCommission = 0; // આમાં ફક્ત 10% ગણીશું

foreach ($todaysOrdersCollection as $order) {
    // ૧. શેફનો કમિશન રેટ મેળવો
    $chef = DB::table('chefs')->where('id', $order->chef_id)->first();
    $commRate = $chef->commission ?? 0;

    // ૨. આઈટમની ID અને Base Price મેળવો
    $items = is_string($order->items) ? json_decode($order->items, true) : $order->items;
    $dishId = (is_array($items) && isset($items[0]['id'])) ? $items[0]['id'] : null;
    $dish = DB::table('food_dishes')->where('id', $dishId)->first();
    
    // ૩. અસલી બેઝ પ્રાઈસ (તારા પેઆઉટ ફંક્શન જેવું જ સેમ લોજિક)
    $actualBasePrice = ($dish && $dish->base_price > 0) 
        ? $dish->base_price 
        : ($order->price / (1 + ($commRate / 100)));

    // ૪. Today's Revenue = કુલ બેઝ પ્રાઈસ (100%)
    $todaysRevenue += $actualBasePrice;

    // ૫. Today's Commission = બેઝ પ્રાઈસના ૧૦% (Security)
    $todaysCommission += ($actualBasePrice * 0.10);
}

$todaysRevenue = round($todaysRevenue, 2);
$todaysCommission = round($todaysCommission, 2);


    // ========== 2. PREP TIME & DISH STATS ==========
    $ordersForStats = Order::select('items')->get();
    $allDishIds = [];
    $itemStats = [];

    foreach ($ordersForStats as $order) {
        $items = is_string($order->items) ? json_decode($order->items, true) : $order->items;
        if (!is_array($items)) continue;

        foreach ($items as $item) {
            $dishId = $item['id'] ?? null;
            if ($dishId) {
                $allDishIds[] = $dishId;
                if (!isset($itemStats[$dishId])) {
                    $itemStats[$dishId] = ['dish_id' => $dishId, 'total_quantity' => 0];
                }
                $itemStats[$dishId]['total_quantity'] += (int) ($item['quantity'] ?? 1);
            }
        }
    }

    $uniqueDishIds = array_unique($allDishIds);
    $dishPrepTimes = DB::table('food_dishes')
        ->whereIn('id', $uniqueDishIds)
        ->whereNotNull('preparation_time_id')
        ->pluck('preparation_time_id', 'id');

    $totalMinutes = 0;
    $countDishes = 0;
    foreach ($dishPrepTimes as $id => $prepTime) {
        $minutes = (int) filter_var($prepTime, FILTER_SANITIZE_NUMBER_INT);
        if ($minutes > 0) {
            $totalMinutes += $minutes;
            $countDishes++;
        }
    }
    $avgPrepTime = $countDishes > 0 ? round($totalMinutes / $countDishes) : 0;

    // ========== 3. CHARTS (Weekly) ==========
    $weeklyOrders = Order::selectRaw('DATE(date) as order_date, COUNT(*) as total_orders, SUM(amount) as revenue')
        ->whereBetween('date', [$startOfWeek, $endOfWeek])
        ->groupBy('order_date')
        ->get()
        ->keyBy('order_date');

    $chartOrders = [];
    $chartRevenue = [];
    for ($i = 0; $i < 7; $i++) {
        $dateKey = $startOfWeek->copy()->addDays($i)->toDateString();
        $chartOrders[] = $weeklyOrders->has($dateKey) ? (int) $weeklyOrders[$dateKey]->total_orders : 0;
        $chartRevenue[] = $weeklyOrders->has($dateKey) ? (float) $weeklyOrders[$dateKey]->revenue : 0;
    }

    // Cuisine Chart Logic
    $dishCuisineMap = DB::table('food_dishes')
        ->whereIn('id', $uniqueDishIds)
        ->whereNotNull('cuisine_type_id')
        ->where('cuisine_type_id', '>', 0)
        ->pluck('cuisine_type_id', 'id');

    $cuisineAgg = [];
    foreach ($dishCuisineMap as $dishId => $cuisineId) {
        $cuisineAgg[$cuisineId] = ($cuisineAgg[$cuisineId] ?? 0) + ($itemStats[$dishId]['total_quantity'] ?? 0);
    }

    $cuisineNames = DB::table('cuisine_type')->whereIn('id', array_keys($cuisineAgg))->pluck('title', 'id');
    $cuisineLabels = [];
    $cuisineData = [];
    $totalCuisineOrders = array_sum($cuisineAgg);
    arsort($cuisineAgg);

    foreach ($cuisineAgg as $cuisineId => $count) {
        $cuisineLabels[] = $cuisineNames[$cuisineId] ?? 'Other';
        $cuisineData[] = $totalCuisineOrders > 0 ? round(($count / $totalCuisineOrders) * 100, 1) : 0;
    }
    $cuisineColors = ['#FF6B35', '#F7C948', '#E53E3E', '#38B2AC', '#805AD5', '#DD6B20', '#3182CE'];

    // ========== 4. LISTINGS (Top 3) ==========

   // Top Chefs (Top 3)
$topChefs = DB::table('orders')
    ->join('chefs', 'chefs.id', '=', 'orders.chef_id')
    ->select(
        'chefs.id', 'chefs.name', 'chefs.business_name', 'chefs.profile_image',
        DB::raw('COUNT(orders.id) as total_orders'),
        DB::raw('SUM(orders.amount) as total_earnings') // અહીં ફરીથી 'total_earnings' કરી દો
    )
    ->groupBy('chefs.id', 'chefs.name', 'chefs.business_name', 'chefs.profile_image')
    ->orderByDesc('total_orders')
    ->limit(3)
    ->get();

    // Popular Dishes
    $topDishIds = collect($itemStats)->sortByDesc('total_quantity')->take(3)->pluck('dish_id')->toArray();
    $popularDishes = FoodDish::whereIn('id', $topDishIds)->get()->map(function ($dish) use ($itemStats) {
        return [
            'id' => $dish->id,
            'name' => $dish->name,
            'image' => $dish->image,
            'total_quantity' => $itemStats[$dish->id]['total_quantity'] ?? 0,
        ];
    })->sortByDesc('total_quantity')->values();

    // Latest Orders & Others
    $topCustomers = DB::table('orders')
        ->join('users', 'users.id', '=', 'orders.user_id')
        ->select('users.id', 'users.name', 'users.image', DB::raw('COUNT(orders.id) as total_orders'))
        ->groupBy('users.id', 'users.name', 'users.image')
        ->orderByDesc('total_orders')->limit(3)->get();

    $latestOrders = Order::select('id', 'status', 'amount', 'date', 'created_at')
        ->orderByDesc('created_at')->limit(3)->get();

    $allCustomers = DB::table('orders')
        ->join('users', 'users.id', '=', 'orders.user_id')
        ->select('users.id', 'users.name', 'users.image', 'users.created_at as user_created_at', DB::raw('COUNT(orders.id) as total_orders'))
        ->groupBy('users.id', 'users.name', 'users.image', 'users.created_at')
        ->orderByDesc('total_orders')->get();

    $topCustomer = $allCustomers->first();
    $regularCustomer = $allCustomers->first(fn($c) => $c->total_orders >= 5 && $c->total_orders < 15) ?? ($allCustomers->count() > 1 ? $allCustomers->skip(1)->first() : null);
    $newCustomer = DB::table('users')->select('id', 'name', 'image', 'created_at')->orderByDesc('created_at')->first();
    
    $issues = \App\Models\Issue::where('status','open')->count();
    $unresolvedOrders = Order::where('status', 'new')->count();

    return view('admin.dashboard', compact(
        'issues', 'todaysOrders', 'todaysRevenue', 'todaysCommission', 'avgPrepTime',
        'chartOrders', 'chartRevenue', 'cuisineLabels', 'cuisineData', 'cuisineColors',
        'topChefs', 'popularDishes', 'topCustomers', 'latestOrders', 'topCustomer',
        'regularCustomer', 'newCustomer', 'unresolvedOrders'
    ));
}
}
