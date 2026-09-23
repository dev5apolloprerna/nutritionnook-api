<?php

namespace App\Http\Controllers\Api;

use App\Models\FoodDish;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{

    public function search(Request $request)
    {
        $query   = $request->input('q');
        $page    = $request->get('page', 1);
        $perPage = $request->get('per_page', 10);
        // $today = strtolower(now()->format('l'));
        // 1️⃣ Logged-in user
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
        // // 2️⃣ Selected user address
        // $userAddress = DB::table('user_addresses')
        //     ->where('user_id', $user->id)
        //     ->where('is_selected', 1)
        //     ->first();

        // if (!$userAddress) {
        //     return CommonHelper::apiResponse(
        //         404,
        //         false,
        //         'Selected user address not found.',
        //         []
        //     );
        // }

        // $userLat = $userAddress->latitude;
        // $userLng = $userAddress->longitude;

        // 3️⃣ Radius (KM)
        $isGuest = !$user;
        $radius = \App\Models\Setting::value('radius_km') ?? 10;

        $applyAvailabilityFilters = function ($builder) {
            return $builder
                ->where('c.available', 1)
                ->where('c.is_verify', 1)
                ->whereNull('c.deleted_at')
                ->where('d.is_active', 1)
                ->where('d.in_stock', 1);
        };
        /**
         * 4️⃣ Global existence check (WITHOUT radius)
         */
        // $globalQuery = DB::table('food_dishes as d')
        //     ->join('chefs as c', 'c.id', '=', 'd.chef_id')
        //     ->where('c.available', 1)
        //     ->where(function ($q) use ($query) {
        //         $q->where('d.name', 'like', "%$query%")
        //             ->orWhere('c.name', 'like', "%$query%");
        //     });

        $globalQuery = $applyAvailabilityFilters(
            DB::table('food_dishes as d')
                ->join('chefs as c', 'c.id', '=', 'd.chef_id')
        );

        $cuisine = DB::table('cuisine_type')
            ->where('title', 'like', "%$query%")
            ->first();

        // if ($cuisine) {
        //     $globalQuery->orWhere(function ($q) use ($cuisine) {
        //         $q->where('d.cuisine_type_id', $cuisine->id)
        //             ->where('c.available', 1);
        //     });
        // }

        $globalQuery->where(function ($q) use ($query, $cuisine) {
            $q->where('d.name', 'like', "%$query%")
                ->orWhere('c.name', 'like', "%$query%");

            if ($cuisine) {
                $q->orWhere('d.cuisine_type_id', $cuisine->id);
            }
        });

        if (!$globalQuery->exists()) {
            return CommonHelper::apiResponse(
                404,
                false,
                'No results found.',
                []
            );
        }


        /**
         * 5️⃣ Main query WITH radius
         */
        // $dishQuery = DB::table('food_dishes as d')
        //     ->join('chefs as c', 'c.id', '=', 'd.chef_id')
        //     ->select(
        //         'd.id as dish_id',
        //         'd.name as dish_name',
        //         'd.image as dish_image',
        //         'c.id as chef_id',
        //         'c.name as chef_name',
        //         DB::raw("(
        //         6371 * acos(
        //             cos(radians($userLat)) *
        //             cos(radians(latitude)) *
        //             cos(radians(longitude) - radians($userLng)) +
        //             sin(radians($userLat)) *
        //             sin(radians(latitude))
        //         )
        //     ) AS distance")
        //     )
        //     // ->where('c.available', 1)
        //     // ->where('d.in_stock', 1) // 👈 Add this line to filter only in-stock items
        //     // ->whereJsonContains('c.working_days', $today)
        //     ->where(function ($q) use ($query, $cuisine) {
        $dishQuery = $applyAvailabilityFilters(
            DB::table('food_dishes as d')
                ->join('chefs as c', 'c.id', '=', 'd.chef_id')
                ->select(
                    'd.id as dish_id',
                    'd.name as dish_name',
                    'd.image as dish_image',
                    'c.id as chef_id',
                    'c.name as chef_name',
                    'c.profile_image as chef_image',
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
        )->where(function ($q) use ($query, $cuisine) {
                // 🔹 Dish or Chef name search
                $q->where(function ($sub) use ($query) {
                    $sub->where('d.name', 'like', "%$query%")
                        ->orWhere('c.name', 'like', "%$query%");
                });

                // 🔹 Cuisine search (OPTIONAL)
                if ($cuisine) {
                    $q->orWhere('d.cuisine_type_id', $cuisine->id);
                }
            })
            ->having('distance', '<=', $radius);


        // 6️⃣ Pagination
        $dishes = $dishQuery
            ->orderBy('distance', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        /**
         * 7️⃣ Dish exists but not in radius
         */
        if ($dishes->isEmpty()) {
            return CommonHelper::apiResponse(
                200,
                false,
                'These dishes are not available within ' . $radius . ' km of your location.',
                []
            );
        }

        // 8️⃣ Format response
        $dishes->getCollection()->transform(function ($row) {
            return [
                'id' => $row->dish_id,
                'name' => $row->dish_name,
                'image' => !empty($row->dish_image) ? asset($row->dish_image) : null,
                'chef' => [
                    'id' => $row->chef_id,
                    'name' => $row->chef_name,
                    'profile_image' => !empty($row->chef_image) ? asset($row->chef_image) : null,
                ],
            ];
        });

        return CommonHelper::apiResponse(
            200,
            true,
            'Dishes fetched successfully!',
            $dishes
        );
    }


    // public function search(Request $request)
    // {
    //     $query = $request->input('q');   // search keyword
    //     $page = $request->get('page', 1);
    //     $perPage = $request->get('per_page', 10);

    //     // ✅ Base query (dish + chef search)
    //     $dishQuery = \DB::table('food_dishes as d')
    //         ->join('chefs as c', 'c.id', '=', 'd.chef_id')
    //         ->select(
    //             'd.id as dish_id',
    //             'd.name as dish_name',
    //             'd.image as dish_image',
    //             'c.id as chef_id',
    //             'c.name as chef_name',
    //             'c.profile_image as chef_image',
    //             'c.available as availability'
    //         )
    //         ->where('c.available', 1)
    //         ->where(function($q) use ($query) {
    //             $q->where('d.name', 'like', "%$query%")
    //               ->orWhere('c.name', 'like', "%$query%");
    //         });

    //     // ✅ Check cuisine_type match
    //     $cuisine = \DB::table('cuisine_type')
    //         ->where('title', 'like', "%$query%")
    //         ->first();

    //     if ($cuisine) {
    //         // $dishQuery->orWhere('d.cuisine_type_id', $cuisine->id);
    //         $dishQuery->orWhere(function($q) use ($cuisine) {
    //         $q->where('d.cuisine_type_id', $cuisine->id)
    //           ->where('c.available', 1); // ✅ phir se ensure kare available chef hi aaye
    //     });
    //     }

    //     // ✅ Execute with pagination
    //     $dishes = $dishQuery->orderBy('d.id', 'desc')
    //         ->paginate($perPage, ['*'], 'page', $page);

    //     // ✅ Format each row
    //     $dishes->getCollection()->transform(function ($row) {
    //         return [
    //             'id' => $row->dish_id,
    //             'name' => $row->dish_name,
    //             'image' => !empty($row->dish_image) ? asset($row->dish_image) : null,
    //             'chef' => [
    //                 'id' => $row->chef_id,
    //                 'name' => $row->chef_name,
    //                 'profile_image' => !empty($row->chef_image) ? asset($row->chef_image) : null,
    //             ],
    //             // 'availability' => $row->availability ? 'get_now' : 'get_later',
    //         ];
    //     });

    //     if ($dishes->isEmpty()) {
    //         return CommonHelper::apiResponse(404, false, 'No dishes found', []);
    //     }

    //     return CommonHelper::apiResponse(200, true, 'Dishes fetched successfully!', $dishes);
    // }



    //     public function search(Request $request)
    // {
    //     $search = $request->input('q'); // search text

    //     // 1. Chef Search (LIKE query)
    //     $chefs = \DB::table('chefs')
    //         ->where('name', 'LIKE', "%{$search}%")
    //         ->get();

    //     $chefResults = [];
    //     foreach ($chefs as $chef) {
    //         $dishes = \DB::table('food_dishes')
    //             ->where('chef_id', $chef->id)
    //             ->get();

    //         $chefResults[] = [
    //             'chef' => $chef,
    //             'food_dishes' => $dishes
    //         ];
    //     }

    //     // 2. Food Dishes Search (LIKE query)
    //     $foodDishes = \DB::table('food_dishes')
    //         ->where('name', 'LIKE', "%{$search}%")
    //         ->get();

    //     $dishResults = [];
    //     foreach ($foodDishes as $dish) {
    //         $chef = \DB::table('chefs')->where('id', $dish->chef_id)->first();

    //         $dishResults[] = [
    //             'id' => $dish->id,
    //             'name' => $dish->name,
    //             'image_url' => !empty($dish->image) ? asset($dish->image) : null,
    //             'chef' => [
    //                 'id' => $chef->id,
    //                 'name' => $chef->name
    //             ],
    //             'availability' => $dish->availability ?? 'get_now'
    //         ];
    //     }

    //     // 3. Cuisine Type Search (LIKE query)
    //     $cuisines = \DB::table('cuisine_type')
    //         ->where('title', 'LIKE', "%{$search}%")
    //         ->get();

    //     $cuisineResults = [];
    //     foreach ($cuisines as $cuisine) {
    //         $dishes = \DB::table('food_dishes')
    //             ->where('cuisine_type_id', $cuisine->id)
    //             ->get();

    //         if ($dishes->isEmpty()) {
    //             continue; // ⚡ skip instead of error
    //         }

    //         $dishWithChefs = [];
    //         foreach ($dishes as $dish) {
    //             $chef = \DB::table('chefs')->where('id', $dish->chef_id)->first();
    //             $dishWithChefs[] = [
    //                 'chef' => $chef,
    //                 'food_dish' => $dish
    //             ];
    //         }

    //         $cuisineResults[] = [
    //             'cuisine' => $cuisine,
    //             'food_dishes' => $dishWithChefs
    //         ];
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Found successfully.',
    //         'chefs' => $chefResults,
    //         'food_dishes' => $dishResults,
    //         'cuisine_type' => $cuisineResults
    //     ]);
    // }

    // public function search(Request $request)
    // {
    //     $search = $request->input('q'); // search text

    //     // 1. Chef Search (LIKE query)
    //     $chefs = \DB::table('chefs')
    //         ->where('name', 'LIKE', "%{$search}%")
    //         ->get();

    //     $chefResults = [];
    //     foreach ($chefs as $chef) {
    //         $dishes = \DB::table('food_dishes')
    //             ->where('chef_id', $chef->id)
    //             ->get();

    //         $chefResults[] = [
    //             'chef' => $chef,
    //             'food_dishes' => $dishes
    //         ];
    //     }

    //     // 2. Food Dishes Search (LIKE query)
    //     $foodDishes = \DB::table('food_dishes')
    //         ->where('name', 'LIKE', "%{$search}%")
    //         ->get();

    //     $dishResults = [];
    //     foreach ($foodDishes as $dish) {
    //         $chef = \DB::table('chefs')->where('id', $dish->chef_id)->first();
    //         $dishResults[] = [
    //             'chef' => $chef,
    //             'food_dish' => $dish
    //         ];
    //     }

    //     // 3. Cuisine Type Search (LIKE query)
    //     $cuisines = \DB::table('cuisine_type')
    //         ->where('title', 'LIKE', "%{$search}%")
    //         ->get();

    //     $cuisineResults = [];
    //     foreach ($cuisines as $cuisine) {
    //         $dishes = \DB::table('food_dishes')
    //             ->where('cuisine_type_id', $cuisine->id)
    //             ->get();

    //         if ($dishes->isEmpty()) {
    //             continue; // ⚡ skip instead of error
    //         }

    //         $dishWithChefs = [];
    //         foreach ($dishes as $dish) {
    //             $chef = \DB::table('chefs')->where('id', $dish->chef_id)->first();
    //             $dishWithChefs[] = [
    //                 'chef' => $chef,
    //                 'food_dish' => $dish
    //             ];
    //         }

    //         $cuisineResults[] = [
    //             'cuisine' => $cuisine,
    //             'food_dishes' => $dishWithChefs
    //         ];
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Found successfully.',
    //         'chefs' => $chefResults,
    //         'food_dishes' => $dishResults,
    //         'cuisine_type' => $cuisineResults
    //     ]);
    // }




    public function unifiedSearch(Request $request)
    {
        $user       = auth()->user();
        $search     = $request->query('search', '');
        $categoryId = $request->query('category_id', 'all');
        $inStock    = $request->query('in_stock', null);
        $recommended = $request->query('is_recommended', null);
        $exactSearch = $request->input('q', $search); // fallback for exact search

        // =========================
        // 1. Global Food Dish Search (like previous searchFoodDishes)
        // =========================
        $foodDishesQuery = DB::table('food_dishes as fd')
            ->join('chefs as c', 'fd.chef_id', '=', 'c.id')
            ->select(
                'fd.*',
                DB::raw("CONCAT('http://thinkdream.in/nutrition-food/', fd.image) AS image"),
                'c.name as chef_name'
            );

        if (!empty($search)) {
            $foodDishesQuery->where(function ($q) use ($search) {
                $q->where('fd.name', 'LIKE', "%{$search}%")
                    ->orWhere('fd.description', 'LIKE', "%{$search}%")
                    ->orWhere('c.name', 'LIKE', "%{$search}%");
            });
        }

        $foodDishes = $foodDishesQuery->get();

        // =========================
        // 2. Logged-in User’s Food Items (like previous searchFoodItems)
        // =========================
        $foodItemsQuery = DB::table('food_dishes as fd')
            ->select(
                'fd.*',
                DB::raw("CONCAT('http://thinkdream.in/nutrition-food/', fd.image) AS image"),
                'c.title as category_name'
            )
            ->join('categories as c', 'fd.category_id', '=', 'c.id')
            ->where('fd.chef_id', $user->id);

        if (!empty($search)) {
            $foodItemsQuery->where(function ($q) use ($search) {
                $q->where('fd.name', 'LIKE', "%{$search}%")
                    ->orWhere('fd.description', 'LIKE', "%{$search}%");
            });
        }

        if ($categoryId !== "all" && $categoryId != 0 && $categoryId != 8) {
            $foodItemsQuery->where('fd.category_id', $categoryId);
        }

        if (!is_null($inStock)) {
            $foodItemsQuery->where('fd.in_stock', (bool)$inStock);
        }

        if (!is_null($recommended)) {
            $foodItemsQuery->where('fd.is_recommended', (bool)$recommended);
        }

        $foodItems = $foodItemsQuery->get();

        // =========================
        // 3. Exact Match Search (like previous search)
        // =========================

        // Chefs
        $chefs = DB::table('chefs')->where('name', '=', $exactSearch)->get();
        $chefResults = [];
        foreach ($chefs as $chef) {
            $dishes = DB::table('food_dishes')->where('chef_id', $chef->id)->get();
            $chefResults[] = [
                'chef' => $chef,
                'food_dishes' => $dishes
            ];
        }

        // Food Dishes
        $foodDishesExact = DB::table('food_dishes')->where('name', '=', $exactSearch)->get();
        $dishResults = [];
        foreach ($foodDishesExact as $dish) {
            $chef = DB::table('chefs')->where('id', $dish->chef_id)->first();
            $dishResults[] = [
                'chef' => $chef,
                'food_dish' => $dish
            ];
        }

        // Cuisine
        $cuisines = DB::table('cuisine_type')->where('title', '=', $exactSearch)->get();
        $cuisineResults = [];
        foreach ($cuisines as $cuisine) {
            $dishes = DB::table('food_dishes')->where('cuisine_type_id', $cuisine->id)->get();

            if ($dishes->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cuisine found but no food dishes linked'
                ], 404);
            }

            $dishWithChefs = [];
            foreach ($dishes as $dish) {
                $chef = DB::table('chefs')->where('id', $dish->chef_id)->first();
                $dishWithChefs[] = [
                    'chef' => $chef,
                    'food_dish' => $dish
                ];
            }

            $cuisineResults[] = [
                'cuisine' => $cuisine,
                'food_dishes' => $dishWithChefs
            ];
        }

        // =========================
        // Final Unified Response
        // =========================
        return response()->json([
            'success' => true,
            'message' => 'Search results fetched successfully!',
            'global_food_dishes' => $foodDishes,
            'user_food_items'    => $foodItems,
            'chefs'              => $chefResults,
            'food_dishes_exact'  => $dishResults,
            'cuisine_type'       => $cuisineResults
        ]);
    }



    //     public function search(Request $request)
    //     {
    //     $search = $request->input('q'); // search text

    //     // 1. Chef Search
    //     $chefs = \DB::table('chefs')
    //         ->where('name', 'like', "%{$search}%")
    //         ->get();

    //     // un chefs ke food_dishes fetch karo
    //     $chefResults = [];
    //     foreach ($chefs as $chef) {
    //         $dishes = \DB::table('food_dishes')
    //             ->where('chef_id', $chef->id)
    //             ->get();

    //         $chefResults[] = [
    //             'chef' => $chef,
    //             'food_dishes' => $dishes
    //         ];
    //     }

    //     // 2. Food Dishes Search
    //     $foodDishes = \DB::table('food_dishes')
    //         ->where('name', 'like', "%{$search}%")
    //         ->get();

    //     $dishResults = [];
    //     foreach ($foodDishes as $dish) {
    //         $chef = \DB::table('chefs')->where('id', $dish->chef_id)->first();
    //         $dishResults[] = [
    //             'food_dish' => $dish,
    //             'chef' => $chef
    //         ];
    //     }

    //     // 3. Cuisine Type Search
    //     $cuisines = \DB::table('cuisine_type')
    //         ->where('title', 'like', "%{$search}%")
    //         ->get();

    //     $cuisineResults = [];
    //     foreach ($cuisines as $cuisine) {
    //         $dishes = \DB::table('food_dishes')
    //             ->where('cuisine_type_id', $cuisine->id)
    //             ->get();

    //         $dishWithChefs = [];
    //         foreach ($dishes as $dish) {
    //             $chef = \DB::table('chefs')->where('id', $dish->chef_id)->first();
    //             $dishWithChefs[] = [
    //                 'food_dish' => $dish,
    //                 'chef' => $chef
    //             ];
    //         }

    //         $cuisineResults[] = [
    //             'cuisine' => $cuisine,
    //             'food_dishes' => $dishWithChefs
    //         ];
    //     }

    //     return response()->json([
    //         'chefs' => $chefResults,
    //         'food_dishes' => $dishResults,
    //         'cuisine_type' => $cuisineResults
    //     ]);
    // }


}
