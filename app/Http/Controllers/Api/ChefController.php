<?php

namespace App\Http\Controllers\Api;

use App\Models\FoodDish;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ChefController extends Controller
{

public function analytics(Request $request)
{
    $chef = auth()->user();

    if (!$chef) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    $type = $request->query('type', 'daily');
    $fromDate = $request->query('from_date');
    $toDate   = $request->query('to_date');

    $from = $fromDate ? Carbon::parse($fromDate)->startOfDay() : null;
    $to   = $toDate   ? Carbon::parse($toDate)->endOfDay()   : null;

    /**
     * =========================
     * BASE ORDERS QUERY
     * =========================
     */
    $orders = DB::table('orders')
        ->where('chef_id', $chef->id)
        ->where('payment_status', 'received')
        ->where('status', 'delivered');

    if ($from && $to) {
        $orders->whereBetween('created_at', [$from, $to]);
    }

    /**
     * =========================
     * 1️⃣ EARNINGS TREND
     * =========================
     */

    if ($type === 'daily') {

        if (!$from || !$to) {
            $from = now()->startOfWeek();
            $to   = now()->endOfWeek();
            $orders->whereBetween('created_at', [$from, $to]);
        }

        $raw = (clone $orders)
            ->selectRaw("
                DAYOFWEEK(created_at) as sort_key,
                DAYNAME(created_at) as label,
                SUM(amount) as amount
            ")
            ->groupByRaw("sort_key, label")
            ->get()
            ->keyBy('sort_key');
            
        // ✅ SHORT DAY LABELS
        $dayLabels = [1 => 'Sun', 2 => 'Mon', 3 => 'Tue', 4 => 'Wed', 5 => 'Thu', 6 => 'Fri', 7 => 'Sat'];


        $earningsTrend = collect(range(2, 7))
            ->merge([1]) // Move Sunday to last
            ->map(function ($day) use ($raw, $dayLabels) {
                return [
                    'label'  => $dayLabels[$day],
                    'amount' => (int) ($raw[$day]->amount ?? 0)
                ];
            });

    } elseif ($type === 'weekly') {

        $month = ($from ?? now())->month;
        $year  = ($from ?? now())->year;

        $raw = DB::query()
            ->fromSub(function ($q) use ($orders) {
                $q->from($orders, 'o')
                  ->selectRaw("
                      FLOOR((DAYOFMONTH(o.created_at) - 1) / 7) + 1 AS week_number,
                      o.amount
                  ");
            }, 't')
            ->selectRaw("week_number, SUM(amount) as amount")
            ->groupBy('week_number')
            ->get()
            ->keyBy('week_number');

        $weeksInMonth = ceil(Carbon::create($year, $month)->daysInMonth / 7);

        $earningsTrend = collect(range(1, $weeksInMonth))->map(function ($week) use ($raw) {
            return [
                'label'  => "Week $week",
                'amount' => (int) ($raw[$week]->amount ?? 0)
            ];
        });

    } else { // monthly

        $year = ($from ?? now())->year;

        $raw = (clone $orders)
            ->selectRaw("
                MONTH(created_at) as month_number,
                SUM(amount) as amount
            ")
            ->groupBy('month_number')
            ->get()
            ->keyBy('month_number');

        $earningsTrend = collect(range(1, 12))->map(function ($month) use ($raw) {
            return [
                'label'  => Carbon::create()->month($month)->format('M'),
                'amount' => (int) ($raw[$month]->amount ?? 0)
            ];
        });
    }

    /**
     * =========================
     * 2️⃣ TOP SELLING ITEMS
     * =========================
     */
    $itemsData = (clone $orders)->pluck('items');

    $dishQty = [];
    $totalQty = 0;

    foreach ($itemsData as $itemsJson) {
        $items = json_decode($itemsJson, true);
        if (!is_array($items)) continue;

        foreach ($items as $item) {
            if (!isset($item['id'], $item['quantity'])) continue;

            $dishQty[$item['id']] = ($dishQty[$item['id']] ?? 0) + $item['quantity'];
            $totalQty += $item['quantity'];
        }
    }

    $dishNames = DB::table('food_dishes')
        ->whereIn('id', array_keys($dishQty))
        ->pluck('name', 'id');

    $topItems = collect($dishQty)
        ->sortDesc()
        ->take(6)
        ->map(function ($qty, $dishId) use ($totalQty, $dishNames) {
            return [
                'name' => $dishNames[$dishId] ?? 'Unknown',
                'percentage' => $totalQty > 0
                    ? number_format(($qty / $totalQty) * 100, 1, '.', '')
                    : '0.0'
            ];
        })
        ->values();

    /**
     * =========================
     * 3️⃣ SUMMARY
     * =========================
     */
    $totalOrders = (clone $orders)->count();
    $totalRevenue = (clone $orders)->sum('amount');

    $summary = [
        'averagePerOrder' => $totalOrders > 0 ? round($totalRevenue / $totalOrders) : 0,
        'topSellingItem' => $topItems->first()['name'] ?? null
    ];

    if ($type === 'daily') {
        $summary['bestEarningDay'] = $earningsTrend->sortByDesc('amount')->first()['label'];
    } elseif ($type === 'weekly') {
        $summary['bestEarningWeek'] = $earningsTrend->sortByDesc('amount')->first()['label'];
    } else {
        $summary['bestEarningMonth'] = $earningsTrend->sortByDesc('amount')->first()['label'];
    }

    return CommonHelper::apiResponse(
        200,
        true,
        'Analytics fetched successfully',
        [
            'earningsTrend' => $earningsTrend->values(),
            'topItems'      => $topItems,
            'summary'       => $summary
        ]
    );
}


public function todayChef(Request $request)
{
    $user = Auth::user();
    $today = strtolower(now()->format('l'));
    $defaultLat = env('DEFAULT_LAT', 22.9952);
    $defaultLng = env('DEFAULT_LNG', 72.6041);
    $isGuest = !$user;
   
    $token = $request->bearerToken();
    
    if ($token) {
        $accessToken = PersonalAccessToken::findToken($token);

        if ($accessToken) {
            $user = $accessToken->tokenable; // 👈 logged-in user
        }
    }
    
    
   if (!$user) {
    // Guest user → set default coordinates
    $userLat = $defaultLat;
    $userLng = $defaultLng;
} else {

    // 1️⃣ Selected user address
    $userAddress = DB::table('user_addresses')
        ->where('user_id', $user->id)
        ->where('is_selected', 1)
        ->first();
        
    
    if (!$userAddress) {
        // If address not found also fallback to default
        $userLat = $defaultLat;
        $userLng = $defaultLng;
    } else {
        $userLat = $userAddress->latitude ?? $defaultLat;
        $userLng = $userAddress->longitude ?? $defaultLng;
    }
}

    // 2️⃣ Radius
    $radius = \App\Models\Setting::value('radius_km') ?? 10;

    // 3️⃣ Pagination inputs
    $perPage = (int) $request->input('per_page', 10);
    $page    = (int) $request->input('page', 1);
    $offset  = ($page - 1) * $perPage;

    // dd(\DB::table('chefs')->first());
  
    // 4️⃣ Base query (REUSED)
    $baseQuery = DB::table('chefs')
        ->select(
            'id',
            'name',
            'business_name',
            'kitchen_name',
            'cover_image',
            'profile_image',
            'about_chef',
            'available',
            'city',
            'address',
            'pincode',
            'phone_number',
            'shop_plot_number',
            'floor',
            'building_name',
            'fssai_license_number',
            'latitude',
            'longitude',
            'working_days',
            DB::raw("(
                6371 * acos(
                    cos(radians($userLat)) *
                    cos(radians(latitude)) *
                    cos(radians(longitude) - radians($userLng)) +
                    sin(radians($userLat)) *
                    sin(radians(latitude))
                )
            ) AS distance")
        )
        ->where('available', 1)
        ->where('is_verify',1)
        ->whereJsonContains('working_days', $today)
        ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('food_dishes')
                    ->whereColumn('food_dishes.chef_id', 'chefs.id')
                    ->whereIn('food_dishes.is_get_now_or_get_later', ['get_now', 'both']);
                    
                if (!empty($tag)) {
                    $query->where('food_dishes.tags', $tag);
                }
            })
        // ->whereExists(function ($query) use ($tag) {
        //     $query->select(DB::raw(1))
        //         ->from('food_dishes')
        //         ->whereColumn('food_dishes.chef_id', 'chefs.id');
        
        //     // 👉 Apply tag filter only if tag is passed
        //     if (!empty($tagId)) {
        //         $query->where('food_dishes.tags', $tagId);
        //     }
        // })
        ->having('distance', '<=', $radius);
        
    // 5️⃣ Total count
    $total = $baseQuery->count();
   

    if ($total === 0) {
        return CommonHelper::apiResponse(
            200,
            false,
            'No chefs available within ' . $radius . ' km.',
            []
        );
    }

    // 6️⃣ Paginated data
    $chefs = $baseQuery
        ->orderBy('distance')
        ->limit($perPage)
        ->offset($offset)
        ->get();
        if (!$user) {
            $favourites = [];
        }else{
            $favourites = DB::table('favourite_chefs')
            ->where('user_id', $user->id)
            ->pluck('chef_id')
            ->toArray();
        }
        
    $chefs->transform(function ($chef) use ($favourites) {

    $chef->cover_image = $chef->cover_image
        ? url($chef->cover_image)
        : null;

    $chef->profile_image = $chef->profile_image
        ? url($chef->profile_image)
        : null;

    $chef->is_fav = in_array($chef->id, $favourites);

    return $chef;
});

    
    

    return CommonHelper::apiResponse(
        200,
        true,
        'Chefs fetched successfully.',
        [
            'data' => $chefs,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => (int) ceil($total / $perPage),
            ]
        ]
    );
}


public function preOrderRestaurants(Request $request)
{
    $user = Auth::user();
    $today = strtolower(now()->format('l'));
    $defaultLat = env('DEFAULT_LAT', 22.9952);
$defaultLng = env('DEFAULT_LNG', 72.6041);
    // 1️⃣ Selected user address
    $isGuest = !$user;
    $token = $request->bearerToken();

    if ($token) {
        $accessToken = PersonalAccessToken::findToken($token);

        if ($accessToken) {
            $user = $accessToken->tokenable; // 👈 logged-in user
        }
    }
   if (!$user) {
    $userLat = $defaultLat;
    $userLng = $defaultLng;
} else {

    $userAddress = DB::table('user_addresses')
        ->where('user_id', $user->id)
        ->where('is_selected', 1)
        ->first();

    if (!$userAddress) {
        $userLat = $defaultLat;
        $userLng = $defaultLng;
    } else {
        $userLat = $userAddress->latitude ?? $defaultLat;
        $userLng = $userAddress->longitude ?? $defaultLng;
    }
}

    // 2️⃣ Radius
    $radius = \App\Models\Setting::value('radius_km') ?? 10;

    // 3️⃣ Today
    $today = strtolower(now()->format('l'));

    // 4️⃣ Pagination inputs
    $perPage = (int) $request->input('per_page', 10);
    $page    = (int) $request->input('page', 1);
    $offset  = ($page - 1) * $perPage;

    // 5️⃣ Base query
    \DB::enableQueryLog();
    $baseQuery = DB::table('chefs')
        ->select(
            'id',
            'name',
            'business_name',
            'kitchen_name',
            'cover_image',
            'profile_image',
            'about_chef',
            'available',
            'city',
            'address',
            'pincode',
            'phone_number',
            'shop_plot_number',
            'floor',
            'building_name',
            'fssai_license_number',
            'latitude',
            'longitude',
            'working_days',
            DB::raw("(
                6371 * acos(
                    cos(radians($userLat)) *
                    cos(radians(latitude)) *
                    cos(radians(longitude) - radians($userLng)) +
                    sin(radians($userLat)) *
                    sin(radians(latitude))
                )
            ) AS distance")
        )
        ->where('is_pre_order', "1")
        ->where('is_verify',1)
        // ->where('available', 1)
        // ->whereJsonContains('working_days', $today)
        ->whereExists(function ($query) {
        $query->select(DB::raw(1))
            ->from('food_dishes')
            ->whereColumn('food_dishes.chef_id', 'chefs.id')
            ->whereIn('food_dishes.is_get_now_or_get_later', ['get_later', 'both']);
    })
        // ->whereJsonContains('working_days', $today)
        ->having('distance', '<=', $radius);
    
    // 6️⃣ Total count
    $total = $baseQuery->count();
   
    if ($total === 0) {
        return CommonHelper::apiResponse(
            200,
            false,
            'No pre-order restaurants available within ' . $radius . ' km.',
            []
        );
    }

    // 7️⃣ Paginated data
    $chefs = $baseQuery
        ->orderBy('distance')
        ->limit($perPage)
        ->offset($offset)
        ->get();
        
        if(!$user){
             $favourites = [];
        }else{
             $favourites = DB::table('favourite_chefs')
        ->where('user_id', $user->id)
        ->pluck('chef_id')
        ->toArray();
        }
        
        $chefs->transform(function ($chef) use ($favourites) {
        $chef->cover_image = $chef->cover_image
            ? url($chef->cover_image)
            : null;

        $chef->profile_image = $chef->profile_image
            ? url($chef->profile_image)
            : null;
         $chef->is_fav = in_array($chef->id, $favourites);

        return $chef;
    });

    return CommonHelper::apiResponse(
        200,
        true,
        'Later day dishes fetched successfully.',
        [
            'data' => $chefs,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => (int) ceil($total / $perPage),
            ]
        ]
    );
}

// public function getChefById($id)
// {
//     $chef = \DB::table('chefs')->where('id', $id)->first();

//     if (!$chef) {
//         return CommonHelper::apiResponse(404, false, 'Chef not found.', null);
//     }

//     return CommonHelper::apiResponse(200, true, 'Chef fetched successfully.', $chef);
// }

public function getChefById(Request $request,$id)
{
    $user = Auth::user();    
    $defaultLat = env('DEFAULT_LAT', 22.9952);
    $defaultLng = env('DEFAULT_LNG', 72.6041);
    
    $token = $request->bearerToken();
    
    if ($token) {
        $accessToken = PersonalAccessToken::findToken($token);

        if ($accessToken) {
            $user = $accessToken->tokenable; // 👈 logged-in user
        }
    }
    
   
   if (!$user) {
    $userLat = $defaultLat;
    $userLng = $defaultLng;
} else {

    $userAddress = DB::table('user_addresses')
        ->where('user_id', $user->id)
        ->where('is_selected', 1)
        ->first();
        
    
    if (!$userAddress) {
        $userLat = $defaultLat;
        $userLng = $defaultLng;
    } else {
        $userLat = $userAddress->latitude ?? $defaultLat;
        $userLng = $userAddress->longitude ?? $defaultLng;
    }
}



    // Chef data from table
    $chef = \DB::table('chefs')
        ->select(
            'id',
            'name',
            'business_name',
            'kitchen_name',
            'cover_image',
            'profile_image',
            'about_chef',
            'available',
            'city',
            'address',
            'pincode',
            'phone_number',
            'shop_plot_number',
            'floor',
            'building_name',
            'fssai_license_number',
            'latitude',
            'longitude',
            'working_days',
            'is_pre_order',
            'opening_time',
            'closing_time'
        )
        ->where('id', $id)
        // ->where('available','1')
        ->first();
        
        
      
    // ✅ Rating ka average nikalna
    $avgRating = \DB::table('ratings')
        ->where('chef_id', $id)
        ->avg('rating');

    // ✅ Review ka total count nikalna
    $reviewCount = \DB::table('reviews')
        ->where('chef_id', $id)
        ->count();

    // Convert to array for extra keys
    $chef = (array) $chef;
    
     $chef['cover_image']   = !empty($chef['cover_image']) ? asset($chef['cover_image']) : null;
    $chef['profile_image'] = !empty($chef['profile_image']) ? asset($chef['profile_image']) : null;



    // ✅ Average rating & review count
    $chef['average_rating'] = number_format($avgRating, 1);
    $chef['total_reviews']  = $reviewCount;

    // ✅ Latitude/Longitude fix
    $chef['latitude']  = isset($chef['latitude']) ? (float) number_format($chef['latitude'], 5, '.', '') : null;
    $chef['longitude'] = isset($chef['longitude']) ? (float) number_format($chef['longitude'], 5, '.', '') : null;
    $chef['opening_time'] = $chef['opening_time'];
    $chef['closing_time'] = $chef['closing_time'];
    
    
    // ✅ Get Now check
    $today = strtolower(\Carbon\Carbon::now()->format('l')); // e.g. "friday"
    $workingDays = !empty($chef['working_days']) ? json_decode($chef['working_days'], true) : [];
    
   $decodedDays = json_decode($chef['working_days'], true);

// 🔥 Check if still string → decode again
if (is_string($decodedDays)) {
    $decodedDays = json_decode($decodedDays, true);
}

if (is_array($decodedDays) && count($decodedDays) > 0) {
    $workingDays = $decodedDays;
    $chef['zero_working_days'] = false;
} else {
    $workingDays = [];
    $chef['zero_working_days'] = true;
}
    $chef['get_now'] = (is_array($workingDays) && in_array($today, $workingDays));
    
    
    // $chef['get_later'] = 

    // ✅ Get Later Days (Mon–Sun)
    $daysMap = [
        'monday', 'tuesday', 'wednesday', 'thursday', 'friday','saturday','sunday'
    ];

    $getLaterDays = [];
    foreach ($daysMap as $day) {
        $getLaterDays[] = [
            'label' => ucfirst($day),
            'take_order' => (is_array($workingDays) && in_array($day, $workingDays)) ? 1 : 0
        ];
    }

    $chef['get_later_days'] = $getLaterDays;
    $chef['DeliveryOutOfRange'] = 1;
    
    // ✅ Distance calculation (agar user ke paas selected address hai)
    if ($userLat && $chef['latitude'] && $chef['longitude']) {
        // $userLat = $userLat->latitude;
        // $userLng = $userAddress->longitude;

        $distance = 6371 * acos(
            cos(deg2rad($userLat)) *
            cos(deg2rad($chef['latitude'])) *
            cos(deg2rad($chef['longitude']) - deg2rad($userLng)) +
            sin(deg2rad($userLat)) *
            sin(deg2rad($chef['latitude']))
        );
        
        $chef['distance'] = round($distance, 1) . ' km';
        if($distance > 10) {
            $chef['DeliveryOutOfRange'] = 1;
        }else{
            $chef['DeliveryOutOfRange'] = 0;
        }
        
    } else {
        $chef['distance'] = null;
        // $chef['DeliveryOutOfRange'] = 1;
    }

    // ✅ Available Categories
$categoriesRaw = DB::table('food_dishes')
    ->where('chef_id', $id)
    ->pluck('category_id')
    ->toArray();

$categoryIds = [];

foreach ($categoriesRaw as $value) {
    $ids = explode(',', $value);   // split "2,4,5"
    $categoryIds = array_merge($categoryIds, $ids);
}

$categoryIds = array_unique(array_filter($categoryIds));


// 4️⃣ Fetch categories safely
$categories = [];
if (!empty($categoryIds)) {
    $categories = DB::table('categories')
        ->select('id', 'title', 'image')
        ->whereIn('id', $categoryIds) // ✅ now array
        ->get()
        ->map(function ($cat) {
            return [
                'id'    => $cat->id,
                'title' => $cat->title,
                'image' => !empty($cat->image)
                    ? asset('/images/' . $cat->image)
                    : null
            ];
        })
        ->toArray();
}

// 5️⃣ Attach to chef response
$chef['available_category'] = $categories;

        if (!$user) {
            $isFav = false;
        }else{
             $isFav = DB::table('favourite_chefs')
        ->where('user_id', $user->id)
        ->where('chef_id', $id)
        ->exists();
        }
     

    $chef['is_fav'] = $isFav;
    

    // Agar aapko working_days response me nahi chahiye
    unset($chef['working_days']);
    
    // ✅ Check if chef has any "get_later" dishes
    $isGetLater = DB::table('food_dishes')
        ->where('chef_id', $id)
        ->whereIn('is_get_now_or_get_later', ['get_later','both'])
        ->exists();
    
    // ✅ Force boolean (true / false only)
    $chef['is_get_later'] = $isGetLater ? true : false;
    // dd($chef);
    return CommonHelper::apiResponse(200, true, 'Chef fetched successfully.', $chef);
}


// public function getTopPicks(Request $request, $id)
// {
//     $noAvailability = false;
//     // Check if chef exists
//     $chef = DB::table('chefs')->where('id', $id)->first();
//     $tag = $request->input('tag'); // seasonal / traditional
//     // $tagId = 0;
//     // $tagData = \DB::table('tags')->where('title',$tag)->first();
//     // if($tagData){
//     //     $tagId = $tagData->id;
//     // }
//     // ✅ Tag input
//     $tagTitle = $request->input('tag'); // seasonal / traditional
    
//     // ✅ Tag ID find
//     $tagId = null;
//     if (!empty($tagTitle)) {
//         $tagData = DB::table('tags')
//             ->whereRaw('LOWER(title) = ?', [strtolower($tagTitle)])
//             ->first();

//         if ($tagData) {
//             $tagId = $tagData->id;
//         } 
//     }
//     if (!$chef) {
//         return CommonHelper::apiResponse(404, false, 'Chef not found.', []);
//     }

//     // Pagination params
//     $perPage = $request->input('per_page', 10);
//     $page    = $request->input('page', 1);

//     // 🔥 Build query first
//     $query = DB::table('food_dishes')
//         ->where('chef_id', $id)
//         // ->where('tags',$tagId)
//         ->where('is_recommended', 1);
//         // ->where('in_stock', 1);
        
//     // ✅ IMPORTANT: apply filter at DB level
//     if ((int)$chef->is_pre_order === 0) {
//         $query->where('is_get_now_or_get_later', 'get_now');
//     }
    
//      // ✅ 🔥 TAG FILTER (MAIN ADDITION)
//     if (!empty($tagId)) {
//         $query->whereRaw('FIND_IN_SET(?, tags)', [$tagId]);
//     }

//     // ✅ Paginate AFTER filtering
//     $dishes = $query->paginate($perPage, ['*'], 'page', $page);

//     // Cuisine mapping
//     $cuisines = DB::table('cuisine_type')->pluck('title', 'id');

//     // ✅ Transform only (no filtering here)
//     $dishes->getCollection()->transform(function ($dish) use ($cuisines) {
//         return [
//             "id"                  => $dish->id,
//             "name"                => $dish->name,
//             "description"         => $dish->description,
//             "price"               => $dish->price,
//             "image"               => !empty($dish->image) ? asset($dish->image) : null,
//             "spicy_level"         => $dish->spicy_level,
//             "weight_option_id"    => $dish->weight_option_id,
//             "preparation_time_id" => $dish->preparation_time_id,
//             "ingredients"         => $dish->ingredients,
//             "allergy_warning"     => $dish->allergy_warning,
//             "cuisine"             => $cuisines[$dish->cuisine_type_id] ?? null,
//             "is_get_now_or_get_later" => $dish->is_get_now_or_get_later ?? null,
//             "in_stock"               => $dish->in_stock
//         ];
//     });

//     if ($dishes->isEmpty()) {
//         return CommonHelper::apiResponse(404, false, 'No recommended dishes found.', []);
//     }

//     return CommonHelper::apiResponse(200, true, 'Chefs top picks food fetched successfully.', $dishes);
// }

public function getTopPicks(Request $request, $id)
{
    // Check if chef exists
    $chef = DB::table('chefs')->where('id', $id)->first();

    if (!$chef) {
        return CommonHelper::apiResponse(404, false, 'Chef not found.', []);
    }

    // ✅ NEW: availability flag
    $noAvailability = false;

    // ✅ NEW: check working days
    $today = strtolower(\Carbon\Carbon::now()->format('l'));
    $days = json_decode($chef->working_days ?? null, true);

    if (!is_array($days) || !in_array($today, $days)) {
        $noAvailability = true;
    }

    // ✅ Tag input
    $tagTitle = $request->input('tag');

    // ✅ Tag ID find
    $tagId = null;
    if (!empty($tagTitle)) {
        $tagData = DB::table('tags')
            ->whereRaw('LOWER(title) = ?', [strtolower($tagTitle)])
            ->first();

        if ($tagData) {
            $tagId = $tagData->id;
        }
    }

    // Pagination params
    $perPage = $request->input('per_page', 10);
    $page    = $request->input('page', 1);

    // 🔥 Build query
    $query = DB::table('food_dishes')
        ->where('chef_id', $id)
        ->where('is_recommended', 1);

    // ✅ Pre-order condition
    if ((int)$chef->is_pre_order === 0) {
        // $query->where('is_get_now_or_get_later', 'get_now');
        $query->whereIn(
            'is_get_now_or_get_later',
            FoodDish::availabilityTypesFor(FoodDish::GET_NOW)
        );
    }

    // ✅ TAG FILTER
    if (!empty($tagId)) {
        $query->whereRaw('FIND_IN_SET(?, tags)', [$tagId]);
    }

    // ✅ Paginate
    $dishes = $query->paginate($perPage, ['*'], 'page', $page);

    // Cuisine mapping
    $cuisines = DB::table('cuisine_type')->pluck('title', 'id');

    // ✅ Transform with override
    $dishes->getCollection()->transform(function ($dish) use ($cuisines, $noAvailability) {
        return [
            "id"                  => $dish->id,
            "name"                => $dish->name,
            "description"         => $dish->description,
            "price"               => $dish->price,
            "image"               => !empty($dish->image) ? asset($dish->image) : null,
            "spicy_level"         => $dish->spicy_level,
            "weight_option_id"    => $dish->weight_option_id,
            "preparation_time_id" => $dish->preparation_time_id,
            "ingredients"         => $dish->ingredients,
            "allergy_warning"     => $dish->allergy_warning,
            "cuisine"             => $cuisines[$dish->cuisine_type_id] ?? null,
            "is_get_now_or_get_later" => $dish->is_get_now_or_get_later ?? null,
            "in_stock"            => $noAvailability ? 0 : $dish->in_stock
        ];
    });

    if ($dishes->isEmpty()) {
        return CommonHelper::apiResponse(404, false, 'No recommended dishes found.', []);
    }

    return CommonHelper::apiResponse(200, true, 'Chefs top picks food fetched successfully.', $dishes);
}
    
public function getChefByTag(Request $request)
{
    $tagTitle = $request->tag;

    $user = Auth::user();    
    $defaultLat = env('DEFAULT_LAT', 22.9952);
    $defaultLng = env('DEFAULT_LNG', 72.6041);

    // ✅ Pagination params
    $page    = $request->get('page', 1);
    $perPage = $request->get('per_page', 10);

    $token = $request->bearerToken();
    
    if ($token) {
        $accessToken = PersonalAccessToken::findToken($token);
        if ($accessToken) {
            $user = $accessToken->tokenable;
        }
    }

    if (!$user) {
        $userLat = $defaultLat;
        $userLng = $defaultLng;
    } else {
        $userAddress = DB::table('user_addresses')
            ->where('user_id', $user->id)
            ->where('is_selected', 1)
            ->first();

        $userLat = $userAddress->latitude ?? $defaultLat;
        $userLng = $userAddress->longitude ?? $defaultLng;
    }

    // ✅ Tag fetch
    $tag = DB::table('tags')
        ->where('title', $tagTitle)
        ->first();

    if (!$tag) {
        return CommonHelper::apiResponse(404, false, 'Tag not found.', []);
    }

    $tagId = $tag->id;

    // ✅ Chef IDs from dishes
    $chefIds = DB::table('food_dishes')
        ->whereRaw("FIND_IN_SET(?, tags)", [$tagId])
        ->pluck('chef_id')
        ->unique()
        ->toArray();

    if (empty($chefIds)) {
        return CommonHelper::apiResponse(200, true, 'No chefs found.', []);
    }

    // ✅ Pagination
    $chefs = DB::table('chefs')
        ->whereIn('id', $chefIds)
        ->paginate($perPage, ['*'], 'page', $page);

    $result = [];

    foreach ($chefs->items() as $chef) {

        $chef = (array) $chef;

        // ✅ 🔥 NEW: check if chef has get_now dishes for this tag
        $hasGetNowDish = DB::table('food_dishes')
            ->where('chef_id', $chef['id'])
            ->whereRaw("FIND_IN_SET(?, tags)", [$tagId])
            ->whereIn('is_get_now_or_get_later', ['get_now', 'both'])
            ->exists();

        if ($hasGetNowDish) {
            $today = strtolower(\Carbon\Carbon::now()->format('l'));
            $days = json_decode($chef['working_days'] ?? null, true);

            // ❌ Skip chef if not working today
            if (!is_array($days) || !in_array($today, $days)) {
                continue;
            }
        }

        // ✅ Images
        $chef['cover_image']   = !empty($chef['cover_image']) ? asset($chef['cover_image']) : null;
        $chef['profile_image'] = !empty($chef['profile_image']) ? asset($chef['profile_image']) : null;

        // ✅ Ratings
        $avgRating = DB::table('ratings')->where('chef_id', $chef['id'])->avg('rating');
        $reviewCount = DB::table('reviews')->where('chef_id', $chef['id'])->count();

        $chef['average_rating'] = number_format($avgRating, 1);
        $chef['total_reviews']  = $reviewCount;

        // ✅ Distance calculation
        if ($userLat && $chef['latitude'] && $chef['longitude']) {
            $distance = 6371 * acos(
                cos(deg2rad($userLat)) *
                cos(deg2rad($chef['latitude'])) *
                cos(deg2rad($chef['longitude']) - deg2rad($userLng)) +
                sin(deg2rad($userLat)) *
                sin(deg2rad($chef['latitude']))
            );

            $chef['distance'] = round($distance, 1) . ' km';
            $chef['DeliveryOutOfRange'] = $distance > 10 ? 1 : 0;
        } else {
            $chef['distance'] = null;
            $chef['DeliveryOutOfRange'] = 1;
        }

        // ✅ Favourite
        if (!$user) {
            $chef['is_fav'] = false;
        } else {
            $chef['is_fav'] = DB::table('favourite_chefs')
                ->where('user_id', $user->id)
                ->where('chef_id', $chef['id'])
                ->exists();
        }

        $result[] = $chef;
    }

    return CommonHelper::apiResponse(200, true, 'Chefs fetched successfully.', [
        'data' => $result,
        'current_page' => $chefs->currentPage(),
        'last_page' => $chefs->lastPage(),
        'per_page' => $chefs->perPage(),
        'total' => $chefs->total(),
    ]);
}
    
    // public function getTopPicks(Request $request, $id)
    // {
    //     // Check if chef exists
    //     $chef = DB::table('chefs')->where('id', $id)->first();

    //     if (!$chef) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Chef not found'
    //         ], 404);
    //     }

    //     // Pagination setup
    //     $perPage = $request->input('per_page', 10);
    //     $page = $request->input('page', 1);
    //     $offset = ($page - 1) * $perPage;

    //     // Fetch recommended dishes
    //     $dishes = DB::table('food_dishes')
    //         ->where('chef_id', $id)
    //         ->where('is_recommended', 1)
    //         ->offset($offset)
    //         ->limit($perPage)
    //         ->get();

    //     // Attach cuisine name
    //     $result = $dishes->map(function ($dish) {
    //         $cuisine = DB::table('cuisine_type')->where('id', $dish->cuisine_type_id)->first();
    //         $dish->cuisine = $cuisine ? $cuisine->title : null;
    //         return $dish;
    //     });

    //     // return response()->json([
            
    //     // ]);
        
    //     return CommonHelper::apiResponse(200, true, 'chefs top picks food fetched successfully.', [
    //         'success' => true,
    //         'data' => $result
    //     ]);
    // }
    
    
    public function storeFavChef(Request $request)
{
    $request->validate([
        'chef_id' => 'required|exists:chefs,id',
    ]);

    $userId = Auth::id();

    // Check if already exists
    $exists = DB::table('favourite_chefs')
        ->where('user_id', $userId)
        ->where('chef_id', $request->chef_id)
        ->exists();

    if ($exists) {
        return CommonHelper::apiResponse(
            400,
            false,
            'Already added to favourites.',
            null
        );
    }

    DB::table('favourite_chefs')->insert([
        'user_id'    => $userId,
        'chef_id'    => $request->chef_id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return CommonHelper::apiResponse(
        200,
        true,
        'Chef added to favourites successfully.',
        null
    );
}

    /**
     * ✅ Remove from favourite
     */
    public function destroyFavChef($chefId)
    {
        $userId = Auth::id();

        DB::table('favourite_chefs')
            ->where('user_id', $userId)
            ->where('chef_id', $chefId)
            ->delete();
        
        return CommonHelper::apiResponse(
        200,
        true,
        'Chef removed from favourites successfully',
        null
    );
    }

    /**
     * ✅ Check isFav in chef profile
     */
    // public function check($chefId)
    // {
    //     $userId = Auth::id();

    //     $isFav = DB::table('favourite_chefs')
    //         ->where('user_id', $userId)
    //         ->where('chef_id', $chefId)
    //         ->exists();

    //     return response()->json([
    //         'status' => true,
    //         'chef_id' => $chefId,
    //         'isFav' => $isFav,
    //     ]);
    // }

    
}
