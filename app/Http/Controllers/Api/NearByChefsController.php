<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class NearByChefsController extends Controller
{

    public function getNearbyChefs(Request $request)
    {
        $user = Auth::user();

        // 1️⃣ Selected user address
        $token = $request->bearerToken();

        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);

            if ($accessToken) {
                $user = $accessToken->tokenable; // 👈 logged-in user
            }
        }
        $defaultLat = env('DEFAULT_LAT', 22.9952);
        $defaultLng = env('DEFAULT_LNG', 72.6041);
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

        // 2️⃣ Radius (KM)
        $radius = \App\Models\Setting::value('radius_km') ?? 10;

        // 3️⃣ Pagination
        $perPage = $request->input('per_page', 10);
        $page    = $request->input('page', 1);

        // 4️⃣ Cuisine filter
        $cuisineTypeId = $request->input('cuisine_type_id');

        
        // 5️⃣ Date → working day
        $day = null;
        if ($request->filled('date')) {
            $day = strtolower(\Carbon\Carbon::parse($request->date)->format('l'));
        }

        // 6️⃣ Nearby chefs query (STRICT radius filter)
        $chefsQuery = DB::table('chefs')
            ->select(
                'chefs.id',
                'chefs.name',
                'chefs.business_name',
                'chefs.kitchen_type',
                'chefs.kitchen_name',
                'chefs.profile_image',
                'chefs.city as location',
                'chefs.address',
                'chefs.available',
                'chefs.working_days',
                DB::raw("ROUND(6371 * acos(
                cos(radians($userLat)) *
                cos(radians(chefs.latitude)) *
                cos(radians(chefs.longitude) - radians($userLng)) +
                sin(radians($userLat)) *
                sin(radians(chefs.latitude))
            ), 1) AS distance")
            )
            ->having('distance', '<=', $radius)
            // ->where('available', 1)
            ->orderBy('distance', 'asc');

        // Filter by a matching dish only for a specific cuisine. Cuisine 1 means
        // "All", so chefs without dishes must not disappear from that listing.
        if ($cuisineTypeId && $cuisineTypeId != 1) {
            $chefsQuery->whereExists(function ($query) use ($cuisineTypeId) {
                $query->selectRaw('1')
                    ->from('food_dishes')
                    ->whereColumn('food_dishes.chef_id', 'chefs.id')
                    ->where('food_dishes.cuisine_type_id', $cuisineTypeId);
            });
        }

        // Only show chefs who work on the requested date. A chef with every day
        // selected matches this condition; this filter does not limit the result
        // to a fixed number of chefs.
        if ($day) {
            //$chefsQuery->whereJsonContains('chefs.working_days', $day);
            $chefsQuery->where(function ($query) use ($day) {
                $query->whereJsonContains('chefs.working_days', $day)
                    // Some existing records were JSON-encoded twice by the admin
                    // form. Keep them searchable until those rows are edited.
                    ->orWhereRaw(
                        'LOWER(JSON_UNQUOTE(chefs.working_days)) LIKE ?',
                        ['%"' . $day . '"%']
                    );
            });
        }

        // 7️⃣ Pagination
        $chefs = $chefsQuery->paginate($perPage, ['*'], 'page', $page);

        // 8️⃣ Favourite chefs

        if ($user) {
            $favourites = DB::table('favourite_chefs')
                ->where('user_id', $user->id)
                ->pluck('chef_id')
                ->toArray();
        } else {
            $favourites = [];
        }

        // 9️⃣ Transform response
        $chefs->getCollection()->transform(function ($chef) use ($favourites) {
            $chef->distance = $chef->distance . ' km';
            $chef->profile_image = $chef->profile_image ? asset($chef->profile_image) : null;
            $chef->working_days = $chef->working_days ? json_decode($chef->working_days, true) : [];
            $chef->is_fav = in_array($chef->id, $favourites);
            return $chef;
        });

        // 🔚 Final response
        if ($chefs->isEmpty()) {
            return CommonHelper::apiResponse(
                200,
                false,
                'No chefs available within ' . $radius . ' km.',
                []
            );
        }

        return CommonHelper::apiResponse(
            200,
            true,
            'Nearby chefs fetched successfully.',
            $chefs
        );
    }

    //  public function getNearbyChefs(Request $request)
    // {
    //     $user = Auth::user(); 
    //     $userAddress = DB::table('user_addresses')
    //         ->where('user_id', $user->id)
    //         ->where('is_selected', 1)
    //         ->first();

    //     if (!$userAddress) {
    //         return CommonHelper::apiResponse(404, false, 'Selected user address not found.', null);
    //     }
    //     $userLat = $userAddress->latitude;
    //     $userLng = $userAddress->longitude;

    //     $settings = \App\Models\Setting::select('radius_km')->first();
    //     $radius = $settings?->radius_km ?? 10;
    //     $perPage = $request->input('per_page', 10);
    //     $page    = $request->input('page', 1);

    //     $cuisineTypeId = $request->input('cuisine_type_id', null);

    //     $chefIds = null;
    //     if ($cuisineTypeId) {
    //         $chefIds = DB::table('food_dishes')
    //             ->where('cuisine_type_id', $cuisineTypeId)
    //             ->pluck('chef_id')
    //             ->toArray();
    //     }
    //     $day = null;
    //     if ($request->has('date')) {
    //         $day = strtolower(\Carbon\Carbon::parse($request->input('date'))->format('l'));
    //     }
    //     $chefsQuery = DB::table('chefs')
    //         ->select(
    //             'chefs.id',
    //             'chefs.name',
    //             'chefs.business_name',
    //             'chefs.kitchen_type',
    //             'chefs.kitchen_name',
    //             'chefs.profile_image',
    //             'chefs.city as location',
    //             'chefs.address as address',
    //             'chefs.working_days',
    //             DB::raw("ROUND(6371 * acos(
    //                 cos(radians($userLat)) *
    //                 cos(radians(chefs.latitude)) *
    //                 cos(radians(chefs.longitude) - radians($userLng)) +
    //                 sin(radians($userLat)) *
    //                 sin(radians(chefs.latitude))
    //             ), 1) AS distance")
    //         )
    //         ->having('distance', '<', $radius)
    //         ->orderBy('distance', 'asc');

    //     if ($chefIds) {
    //         $chefsQuery->whereIn('chefs.id', $chefIds);
    //     }


    //     if ($day) {
    //         $chefsQuery->whereJsonContains('chefs.working_days', $day);
    //     }

    //     $chefs = $chefsQuery->paginate($perPage, ['*'], 'page', $page);

    //     $favourites = DB::table('favourite_chefs')
    //         ->where('user_id', $user->id)
    //         ->pluck('chef_id')
    //         ->toArray();

    //     $chefs->getCollection()->transform(function ($chef) use ($favourites) {
    //         $chef->distance = $chef->distance . ' km';
    //         $chef->profile_image = !empty($chef->profile_image) ? asset($chef->profile_image) : null;
    //         $chef->working_days = !empty($chef->working_days) ? json_decode($chef->working_days, true) : [];
    //         $chef->is_fav = in_array($chef->id, $favourites); // ðŸ‘ˆ yaha set ho gaya true/false
    //         return $chef;
    //     });
    //     if ($chefs->isEmpty()) {
    //         return CommonHelper::apiResponse(404, false, 'No nearby chefs found.', []);
    //     }

    //     return CommonHelper::apiResponse(200, true, 'Nearby chefs fetched successfully.', $chefs);
    // }

    // public function getNearbyChefs(Request $request)
    // {
    //     $user = Auth::user();

    //     // 1. Get selected user address
    //     $userAddress = DB::table('user_addresses')
    //         ->where('user_id', $user->id)
    //         ->where('is_selected', 1)
    //         ->first();

    //     if (!$userAddress) {
    //         return CommonHelper::apiResponse(404, false, 'Selected user address not found.', null);
    //     }

    //     $userLat = $userAddress->latitude;
    //     $userLng = $userAddress->longitude;

    //     $settings = \App\Models\Setting::select('radius_km')->first();
    //     $radius = $settings?->radius_km ?? 10;

    //     // âœ… Pagination params
    //     $perPage = $request->input('per_page', 10);
    //     $page    = $request->input('page', 1);

    //     // âœ… Cuisine filter (optional)
    //     $cuisineTypeId = $request->input('cuisine_type_id', null);

    //     $chefIds = null;
    //     if ($cuisineTypeId) {
    //         $chefIds = DB::table('food_dishes')
    //             ->where('cuisine_type_id', $cuisineTypeId)
    //             ->pluck('chef_id')
    //             ->toArray();
    //     }

    //     // âœ… Step 1: Single date se day nikaalo (lowercase me)
    //     $day = null;
    //     if ($request->has('date')) {
    //         $day = strtolower(\Carbon\Carbon::parse($request->input('date'))->format('l')); 
    //         // Example: "monday", "tuesday"
    //     }

    //     // 2. Get nearby chefs with pagination + filter
    //     $chefsQuery = DB::table('chefs')
    //         ->select(
    //             'chefs.id',
    //             'chefs.name',
    //             'chefs.business_name',
    //             'chefs.kitchen_type',
    //             'chefs.kitchen_name',
    //             'chefs.profile_image',
    //             'chefs.city as location',
    //             'chefs.address as address',
    //             // 'chefs.working_days',
    //             DB::raw("ROUND(6371 * acos(
    //                 cos(radians($userLat)) *
    //                 cos(radians(chefs.latitude)) *
    //                 cos(radians(chefs.longitude) - radians($userLng)) +
    //                 sin(radians($userLat)) *
    //                 sin(radians(chefs.latitude))
    //             ), 1) AS distance")
    //         )
    //         ->having('distance', '<', $radius)
    //         ->orderBy('distance', 'asc');

    //     // Agar cuisine filter ho toh
    //     if ($chefIds) {
    //         $chefsQuery->whereIn('chefs.id', $chefIds);
    //     }

    //     // âœ… Step 2: Working days filter (only one date â†’ one day)
    //     if ($day) {
    //         $chefsQuery->whereJsonContains('chefs.working_days', $day);
    //     }

    //     $chefs = $chefsQuery->paginate($perPage, ['*'], 'page', $page);

    //     // âœ… Format distance + image
    //     $chefs->getCollection()->transform(function ($chef) {
    //         $chef->distance = $chef->distance . ' km';
    //         if (!empty($chef->profile_image)) {
    //             $chef->profile_image = asset($chef->profile_image);
    //         }

    //         if (!empty($chef->working_days)) {
    //             $chef->working_days = json_decode($chef->working_days, true);
    //         }

    //         return $chef;
    //     });

    //     // âœ… API response
    //     if ($chefs->isEmpty()) {
    //         return CommonHelper::apiResponse(404, false, 'No nearby chefs found.', []);
    //     }

    //     return CommonHelper::apiResponse(200, true, 'Nearby chefs fetched successfully.', $chefs);
    // }


    public function getPopularDishes(Request $request)
    {
        $user = Auth::user(); // âœ… token se user aayega

        // 1. Get selected user address
        $userAddress = DB::table('user_addresses')
            ->where('user_id', $user->id)
            ->where('is_selected', 1)
            ->first();

        if (!$userAddress) {
            return response()->json(['message' => 'No address found for this user'], 404);
        }

        // 2. Fetch random 10 dishes from nearby chefs (same pincode)
        $dishes = DB::table('food_dishes as fd')
            ->join('chefs as c', 'fd.chef_id', '=', 'c.id')
            ->where('fd.is_active', 1)
            ->where('c.pincode', $userAddress->pincode)
            ->select(
                'fd.id',
                'fd.name as dish_name',
                'fd.description',
                'fd.price',
                'fd.image as dish_image',
                'c.name as chef_name',
                'c.business_name',
                'c.address as chef_address'
            )
            ->inRandomOrder()
            ->limit(10)
            ->get();

        // 3. Response
        return response()->json([
            'user_address'  => $userAddress->full_address,
            'random_dishes' => $dishes,
        ], 200);
    }



    //  public function getNearbyChefs(Request $request)
    // {
    //     $user = Auth::user();

    //     // 1. Get selected user address
    //     $userAddress = DB::table('user_addresses')
    //         ->where('user_id', $user->id)
    //         ->where('is_selected', 1)
    //         ->first();

    //     if (!$userAddress) {
    //         return CommonHelper::apiResponse(404, false, 'Selected user address not found.', null);
    //     }

    //     $userLat = $userAddress->latitude;
    //     $userLng = $userAddress->longitude;

    //     $settings = \App\Models\Setting::select('radius_km')->first();
    //     $radius = $settings?->radius_km ?? 10;

    //     // âœ… Pagination params
    //     $perPage = $request->input('per_page', 10);
    //     $page    = $request->input('page', 1);

    //     // âœ… Cuisine filter (optional)
    //     $cuisineTypeId = $request->input('cuisine_type_id', null);

    //     // Agar cuisine_type_id diya gaya hai toh related chef_id nikaalo
    //     $chefIds = null;
    //     if ($cuisineTypeId) {
    //         $chefIds = DB::table('food_dishes')
    //             ->where('cuisine_type_id', $cuisineTypeId)
    //             ->pluck('chef_id')
    //             ->toArray();
    //     }

    //     // 2. Get nearby chefs with pagination + filter
    //     $chefsQuery = DB::table('chefs')
    //         ->select(
    //             'chefs.id',
    //             'chefs.name',
    //             'chefs.business_name',
    //             'chefs.kitchen_type',
    //             'chefs.kitchen_name',
    //             'chefs.profile_image',
    //             'chefs.city as location',
    //             'chefs.address as address',
    //             DB::raw("ROUND(6371 * acos(
    //                 cos(radians($userLat)) *
    //                 cos(radians(chefs.latitude)) *
    //                 cos(radians(chefs.longitude) - radians($userLng)) +
    //                 sin(radians($userLat)) *
    //                 sin(radians(chefs.latitude))
    //             ), 1) AS distance")
    //         )
    //         ->having('distance', '<', $radius)
    //         ->orderBy('distance', 'asc');

    //     // Agar cuisine filter ho toh uske hisaab se chefs filter karo
    //     if ($chefIds) {
    //         $chefsQuery->whereIn('chefs.id', $chefIds);
    //     }

    //     $chefs = $chefsQuery->paginate($perPage, ['*'], 'page', $page);

    //     // âœ… Format distance + image
    //     $chefs->getCollection()->transform(function ($chef) {
    //         $chef->distance = $chef->distance . ' km';
    //         if (!empty($chef->profile_image)) {
    //             $chef->profile_image = asset($chef->profile_image);
    //         }
    //         return $chef;
    //     });

    //     // âœ… API response
    //     if ($chefs->isEmpty()) {
    //         return CommonHelper::apiResponse(404, false, 'No nearby chefs found.', []);
    //     }

    //     return CommonHelper::apiResponse(200, true, 'Nearby chefs fetched successfully.', $chefs);
    // }



    // public function getNearbyChefs(Request $request)
    // {
    //     $user = Auth::user();

    //     // 1. Get selected user address
    //     $userAddress = DB::table('user_addresses')
    //         ->where('user_id', $user->id)
    //         ->where('is_selected', 1)
    //         ->first();

    //     if (!$userAddress) {
    //         return CommonHelper::apiResponse(404, false, 'Selected user address not found.', null);
    //     }

    //     $userLat = $userAddress->latitude;
    //     $userLng = $userAddress->longitude;

    //     $settings = \App\Models\Setting::select('radius_km')->first();
    //     $radius = $settings?->radius_km ?? 10;

    //     // âœ… Pagination params
    //     $perPage = $request->input('per_page', 10);
    //     $page    = $request->input('page', 1);

    //     // 2. Get nearby chefs with pagination
    //     $chefs = DB::table('chefs')
    //         ->select(
    //             'chefs.id',
    //             'chefs.name',
    //             'chefs.business_name',
    //             'chefs.kitchen_type',
    //             'chefs.kitchen_name',
    //             'chefs.profile_image',
    //             'chefs.city as location',
    //             'chefs.address as address',
    //             DB::raw("ROUND(6371 * acos(
    //                 cos(radians($userLat)) *
    //                 cos(radians(chefs.latitude)) *
    //                 cos(radians(chefs.longitude) - radians($userLng)) +
    //                 sin(radians($userLat)) *
    //                 sin(radians(chefs.latitude))
    //             ), 1) AS distance")
    //         )
    //         ->having('distance', '<', $radius)
    //         ->orderBy('distance', 'asc')
    //         ->paginate($perPage, ['*'], 'page', $page);

    //     // âœ… Format distance + image using transform (without breaking pagination meta)
    //     $chefs->getCollection()->transform(function ($chef) {
    //         $chef->distance = $chef->distance . ' km';
    //         if (!empty($chef->profile_image)) {
    //             $chef->profile_image = asset($chef->profile_image);
    //         }
    //         return $chef;
    //     });

    //     // âœ… API response with pagination
    //     if ($chefs->isEmpty()) {
    //         return CommonHelper::apiResponse(404, false, 'No nearby chefs found.', []);
    //     }

    //     return CommonHelper::apiResponse(200, true, 'Nearby chefs fetched successfully.', $chefs);
    // }






    // public function getNearbyChefs()
    // {
    //     $user = Auth::user();

    //     // 1. Get selected user address
    //     $userAddress = DB::table('user_addresses')
    //         ->where('user_id', $user->id)
    //         ->where('is_selected', 1)
    //         ->first();

    //     if (!$userAddress) {
    //         return CommonHelper::apiResponse(404, false, 'Selected user address not found.', null);
    //     }

    //     $userLat = $userAddress->latitude;
    //     $userLng = $userAddress->longitude;
    //     // $radius = 10;

    //     $settings = \App\Models\Setting::select('radius_km')->first();
    //     $radius = $settings?->radius_km ;

    //     // 2. Get chefs with user and food items
    //     // $chefs = DB::table('chef_addresses')
    //     //     ->join('users', 'chef_addresses.user_id', '=', 'users.id')
    //     //     ->select(
    //     //         'chef_addresses.*',
    //     //         'users.name as chef_name',
    //     //         'users.email',
    //     //         'users.phone_number',
    //     //         DB::raw("ROUND(6371 * acos(
    //     //             cos(radians($userLat)) *
    //     //             cos(radians(chef_addresses.latitude)) *
    //     //             cos(radians(chef_addresses.longitude) - radians($userLng)) +
    //     //             sin(radians($userLat)) *
    //     //             sin(radians(chef_addresses.latitude))
    //     //         ), 2) AS distance")
    //     //     )
    //     //     ->having('distance', '<', $radius)
    //     //     ->orderBy('distance', 'asc')
    //     //     ->get();


    //     $chefs = DB::table('chefs')
    //         ->select(
    //             'chefs.*',
    //             DB::raw("CAST(chefs.latitude AS DECIMAL(10,6)) as lat_decimal"),
    //             DB::raw("CAST(chefs.longitude AS DECIMAL(10,6)) as lng_decimal"),
    //             DB::raw("ROUND(6371 * acos(
    //                 cos(radians($userLat)) *
    //                 cos(radians(chefs.latitude)) *
    //                 cos(radians(chefs.longitude) - radians($userLng)) +
    //                 sin(radians($userLat)) *
    //                 sin(radians(chefs.latitude))
    //             ), 1) AS distance")
    //         )
    //         ->having('distance', '<', $radius)
    //         ->orderBy('distance', 'asc')
    //         ->paginate(10);

    //         // 4. Append "km" to distance values
    //         $chefs->getCollection()->transform(function ($chef) {
    //             $chef->distance = $chef->distance . ' km';
    //             return $chef;
    //     });

    //     // $chefs->transform(function ($chef) {
    //     //     $foodDishes = FoodDish::where('chef_id', $chef->user_id)
    //     //         ->with('category:id,title') // eager load category title
    //     //         ->get()
    //     //         ->map(function ($dish) {
    //     //             return [
    //     //                 'id' => $dish->id,
    //     //                 'chef_id' => $dish->chef_id,
    //     //                 'name' => $dish->name,
    //     //                 'description' => $dish->description,
    //     //                 'price' => $dish->price,
    //     //                 'image' => $dish->image,
    //     //                 'category_id' => $dish->category_id,
    //     //                 'category_title' => optional($dish->category)->title,
    //     //                 'spicy_level' => $dish->spicy_level,
    //     //                 'weight_option_id' => $dish->weight_option_id,
    //     //                 'preparation_time_id' => $dish->preparation_time_id,
    //     //                 'is_active' => $dish->is_active,
    //     //                 'availability_type' => $dish->availability_type,
    //     //                 'is_top_picks' => $dish->is_top_picks,
    //     //             ];
    //     //         });

    //     //     $chef->distance = $chef->distance . ' km';
    //     //     $chef->food_dishes = $foodDishes;

    //     //     return $chef;
    //     // });

    //     return CommonHelper::apiResponse(200, true, 'Nearby chefs fetched successfully.', [
    //         'user_address' => $userAddress,
    //         'nearby_chefs' => $chefs,
    //     ]);
    // }
}
