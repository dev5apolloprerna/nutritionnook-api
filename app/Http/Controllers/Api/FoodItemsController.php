<?php

namespace App\Http\Controllers\Api;

use App\Models\FoodItem;
use App\Models\Category;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\CuisineType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Tag;

class FoodItemsController extends Controller
{
public function listFoodItems()
{
    $user = auth()->user();

    $data = \DB::table('food_dishes as fd')
        ->select(
            'fd.*',
            \DB::raw("CONCAT('https://api.nutritionnook.net/', fd.image) AS image"),
            'ct.title as cuisine' // ðŸ‘ˆ cuisine_type ka title
        )
        ->leftJoin('cuisine_type as ct', 'fd.cuisine_type_id', '=', 'ct.id') // ðŸ‘ˆ join added
        ->where('fd.chef_id', $user->id)
        ->get();

    if ($data->isNotEmpty()) {
        return CommonHelper::apiResponse(200, true, 'FoodItems list fetched successfully!', $data);
    } else {
        return CommonHelper::apiResponse(404, false, 'Failed to find food items', []);
    }
}




    public function addFoodItems(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name'            => 'required|string',
        'image'           => 'required|image',
        'price'           => 'required|numeric',
        // 'category_id'     => 'required|exists:categories,id',
        'category_id' => 'required|string',
        'preparation_time' => 'required',
        'quantity'        => 'required',
        // 'cuisine' => 'required'
        'cuisine_type_id' => 'required|exists:cuisine_type,id',
        'is_get_now_or_get_later' => 'required|in:get_now,get_later,both',
        'tags' => 'required',
    ]);

    if ($validator->fails()) {
        return CommonHelper::apiResponse(422, false, 'Validation error', $validator->errors());
    }

    // âœ… Check Foreign Keys Are Not Soft Deleted
    if (!\App\Helpers\CommonHelper::checkForeignKeyActive(\App\Models\Category::class, $request->category_id)) {
        return CommonHelper::apiResponse(422, false, 'Selected category has been deleted.', null);
    }

    $authUser = $request->user(); // Authenticated User

    $imagePath = null;
    if ($request->hasFile('image')) {
        $image = $request->file('image');
        $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
        $image->move(public_path('images'), $imageName);
        $imagePath = 'public/images/' . $imageName;
    }
    
    $chefCommission = DB::table('chefs')
        ->where('id', $authUser->id)
        ->value('commission');

    if(!$chefCommission){
        $chefCommission = 10; // default commission
    }
    
    $basePrice = $request->price;
    $commissionAmount = ($basePrice * $chefCommission) / 100;
    $finalSellingPrice = round($basePrice + $commissionAmount);

    // âœ… Prepare & Store Data
    $data = [
        'chef_id'             => $authUser->id,
        'name'                => $request->name,
        'description'         => $request->description,
        'image'               => $imagePath,
        'base_price'          => $basePrice,
        'price'               => $finalSellingPrice,
        'category_id'         => $request->category_id,
        'preparation_time_id' => $request->preparation_time,
        'weight_option_id'    => $request->quantity,
        'ingredients'         => $request->ingredients,
        'allergy_warning'     => $request->allergy_warning,
        'is_active'           => 0,
        'spicy_level'         => $request->spice_level,
        // 'cuisine'    => $request->cuisine
        'cuisine_type_id'     => $request->cuisine_type_id,
        'is_get_now_or_get_later' => $request->is_get_now_or_get_later,
        'tags'                 => $request->tags,
    ];

    $foodItem = \App\Models\FoodItem::create($data);

    // âœ… Convert image to full URL in the response
    $foodItem->image = $foodItem->image ? url($foodItem->image) : null;
    if($foodItem){
        
        $foodItem->image = $foodItem->image ? url($foodItem->image) : null;

        // // âœ… Convert tags (ids) to array of titles
        // $tagIds = explode(',', $foodItem->tags);
        // $tags = \App\Models\Tag::whereIn('id', $tagIds)->pluck('title');

        // // Replace tags in response with titles
        // $foodItem->tags = $tags;


        return CommonHelper::apiResponse(201, true, 'FoodItem added successfully!', $foodItem);
    }else{
        return CommonHelper::apiResponse(500, false, 'failed to delete FoodItem', null);
    }
    
}


public function editFoodItems(Request $request)
{
    $validator = Validator::make($request->all(), [
        'id'              => 'required|exists:food_dishes,id',
        'name'            => 'required|string',
        'price'           => 'required|numeric',
        // 'category_id'     => 'required|exists:categories,id',
        'category_id' => 'required|string',
        'preparation_time' => 'required',
        'quantity'        => 'required',
        // 'cuisine'         => 'required',
        'cuisine_type_id'  => 'required|exists:cuisine_type,id',
        'is_get_now_or_get_later' => 'required|in:get_now,get_later,both',
        'tags'              => 'required',
    ]);

    if ($validator->fails()) {
        return CommonHelper::apiResponse(422, false, 'Validation error', $validator->errors());
    }

    $foodItem = \App\Models\FoodItem::where('id', $request->id)->first();

    if (!$foodItem) {
        return CommonHelper::apiResponse(404, false, 'Dish not found!', null);
    }

    // âœ… Check soft-deleted foreign keys
    if (!\App\Helpers\CommonHelper::checkForeignKeyActive(\App\Models\Category::class, $request->category_id)) {
        return CommonHelper::apiResponse(422, false, 'Selected category has been deleted.', null);
    }

    $authUser = $request->user();

    $imagePath = null;
    if ($request->hasFile('image')) {
        $image = $request->file('image');
        $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
        $image->move(public_path('images'), $imageName);
        $imagePath = 'public/images/' . $imageName;
    }
    
    $chefCommission = DB::table('chefs')
        ->where('id', $authUser->id)
        ->value('commission');

    if(!$chefCommission){
        $chefCommission = 10; // default commission
    }
    
   // Always calculate fresh selling price based on base_price
    $newBasePrice = $request->price;
    $newSellingPrice = round($newBasePrice + ($newBasePrice * $chefCommission) / 100);
    

    $data = [
        'chef_id'            => $authUser->id,
        'name'               => $request->name,
        'description'        => $request->description,
        'base_price'         => $newBasePrice,
        'price'              => $newSellingPrice,
        'category_id'        => $request->category_id,
        'preparation_time_id'=> $request->preparation_time,
        'weight_option_id'   => $request->quantity,
        'ingredients'        => $request->ingredients,
        'allergy_warning'    => $request->allergy_warning,
        'is_active'          => 0,
        'spicy_level'        => $request->spice_level,
        // 'cuisine'            => $request->cuisine
        'cuisine_type_id'    => $request->cuisine_type_id,
        'is_get_now_or_get_later' => $request->is_get_now_or_get_later,
        'tags'                => $request->tags,
    ];

    // âœ… Only update image if new one is uploaded
    if ($imagePath) {
        $data['image'] = $imagePath;
    }

    if ($foodItem->update($data)) {
        
        //  $tagIds = explode(',', $foodItem->tags);
        // $tags = \App\Models\Tag::whereIn('id', $tagIds)->pluck('title');

        // // Replace tags in response with titles
        // $foodItem->tags = $tags;
        
        return CommonHelper::apiResponse(200, true, 'FoodItem updated successfully!', $foodItem);
    } else {
        return CommonHelper::apiResponse(500, false, 'Failed to update FoodItem', null);
    }
}

    


    public function deleteFoodItems(Request $request)
{
    // Validate the request
    $validator = Validator::make($request->all(), [
        'id' => 'required|exists:food_dishes,id',
    ]);

    if ($validator->fails()) {
        return CommonHelper::apiResponse(422, false, 'Validation error', $validator->errors());
    }

    // Find the food item
    $foodItem = FoodItem::find($request->id);

    if (!$foodItem) {
        return CommonHelper::apiResponse(404, false, 'FoodItem not found!', null);
    }

    // âœ… Detach from pivot tables before deleting
    $foodItem->categories()->detach();
   
    if($foodItem->delete()){
        return CommonHelper::apiResponse(200, true, 'FoodItem deleted successfully!', null);
    }else{
        return CommonHelper::apiResponse(500, false, 'failed to delete FoodItem', null);
    }
    
}

public function updateStock(Request $request, $dish_id)
{
    $validator = Validator::make($request->all(), [
        'in_stock' => 'required|boolean',
    ]);

    if ($validator->fails()) {
        return CommonHelper::apiResponse(422, false, 'Validation error', $validator->errors());
    }

    $foodItem = FoodItem::where('id', $dish_id)
        ->where('chef_id', auth()->id()) // ensure only the logged-in chef can update
        ->first();

    if (!$foodItem) {
        return CommonHelper::apiResponse(404, false, 'Dish not found!', null);
    }

    $foodItem->in_stock = $request->in_stock;
    $foodItem->save();

    return CommonHelper::apiResponse(200, true, 'Stock status updated successfully!', $foodItem);
}


public function updateRecommendation(Request $request, $dish_id)
{
    $validator = Validator::make($request->all(), [
        'is_recommended' => 'required|boolean',
    ]);

    if ($validator->fails()) {
        return CommonHelper::apiResponse(422, false, 'Validation error', $validator->errors());
    }

    $foodItem = FoodItem::where('id', $dish_id)
        ->where('chef_id', auth()->id()) // ensure only the logged-in chef can update
        ->first();

    if (!$foodItem) {
        return CommonHelper::apiResponse(404, false, 'Dish not found!', null);
    }

    $foodItem->is_recommended = $request->is_recommended;
    $foodItem->save();

    return CommonHelper::apiResponse(200, true, 'Recommendation status updated successfully!', $foodItem);
}

public function searchFoodItems(Request $request)
{
    $user = auth()->user();

    $search      = $request->query('search', '');
    $categoryId  = $request->query('category_id', 'all');
    $inStock     = $request->query('in_stock', null);
    $recommended = $request->query('is_recommended', null);
    $perPage     = $request->query('per_page', 10);

    $query = \DB::table('food_dishes as fd')
        ->select(
            'fd.*',
            \DB::raw("CONCAT('https://api.nutritionnook.net/', fd.image) AS image"),
            'c.title as category_name'
        )
        ->join('categories as c', 'fd.category_id', '=', 'c.id')
        // ->where('fd.in_stock','1')
        ->where('fd.chef_id', $user->id);

    // ✅ Search filter
    if (!empty($search)) {
        $query->where(function ($q) use ($search) {
            $q->where('fd.name', 'LIKE', "%{$search}%")
              ->orWhere('fd.description', 'LIKE', "%{$search}%");
        });
    }

    // ✅ Category filter
    if ($categoryId !== "all" && $categoryId != 0 && $categoryId != 8) {
        $query->where('fd.category_id', $categoryId);
    }

    // ✅ In-stock & Recommended filters
    if (!is_null($inStock) && !is_null($recommended)) {
        $query->where('fd.in_stock', (bool)$inStock)
              ->where('fd.is_recommended', (bool)$recommended);
    } elseif (!is_null($inStock)) {
        $query->where('fd.in_stock', (bool)$inStock);
    } elseif (!is_null($recommended)) {
        $query->where('fd.is_recommended', (bool)$recommended);
    }

    // ✅ PAGINATE
    $paginated = $query->paginate($perPage);

    // ✅ cuisines map
    $cuisines = \DB::table('cuisine_type')->pluck('title', 'id')->toArray();

    // ✅ Transform data
    $data = collect($paginated->items())->map(function ($item) use ($cuisines) {

        $ids = explode(',', $item->cuisine_type_id);
        $names = [];

        foreach ($ids as $id) {
            $id = trim($id);
            if (isset($cuisines[$id])) {
                $names[] = $cuisines[$id];
            }
        }

        $item->cuisine = implode(', ', $names);
        return $item;

    })->values();

    // ✅ Pagination structure
    $pagination = [
        "current_page"   => $paginated->currentPage(),
        "per_page"       => $paginated->perPage(),
        "total"          => $paginated->total(),
        "last_page"      => $paginated->lastPage(),
        "from"           => $paginated->firstItem(),
        "to"             => $paginated->lastItem(),
        "next_page_url"  => $paginated->nextPageUrl(),
        "prev_page_url"  => $paginated->previousPageUrl(),
    ];

    /**
     * =========================================
     * ✅ PRODUCTION-SAFE RESPONSE HANDLING
     * =========================================
     */

    // 🔴 No records in database at all
    if ($paginated->total() == 0) {
        return $this->apiResponseOne(
            404,
            false,
            'No food items found',
            [],
            $pagination
        );
    }

    // 🟡 Page exists but empty (page overflow)
    if ($data->isEmpty()) {
        return $this->apiResponseOne(
            200,
            true,
            'No more food items on this page',
            [],
            $pagination
        );
    }

    // 🟢 Normal success
    return $this->apiResponseOne(
        200,
        true,
        'Filtered FoodItems fetched successfully!',
        $data,
        $pagination
    );
}

public static function apiResponseOne($code, $success, $message, $data = [], $pagination = null)
{
    $response = [
        'code' => $code,
        'success' => $success,
        'message' => $message,
        'data' => $data,
    ];

    if ($pagination !== null) {
        $response['pagination'] = $pagination;
    }

    return response()->json($response, $code);
}
public function getTodayDishes(Request $request, $chefId)
{
    $today  = strtolower(\Carbon\Carbon::now()->format('l')); // e.g. "thursday"
    $noAvailability = false;
    $page    = $request->get('page', 1);
    $perPage = $request->get('per_page', 10);
    $categoryId = $request->get('category_id'); // âœ… optional filter
    $tagTitle = $request->get('tag'); // ✅ NEW
    // ✅ Tag ID find
    
    // ✅ Tag ID find
    $tagId = null;
    if (!empty($tagTitle)) {
        $tagData = \DB::table('tags')
            ->whereRaw('LOWER(title) = ?', [strtolower($tagTitle)])
            ->first();

        if ($tagData) {
            $tagId = $tagData->id;
        } 
    }

    $tagId = null;
    if (!empty($tagTitle)) {
        $tagData = \DB::table('tags')->where('title', $tagTitle)->first();
        if ($tagData) {
            $tagId = $tagData->id;
        }
    }

    $chef = \DB::table('chefs')
        ->select('id', 'working_days')
        ->where('id', $chefId)
        ->first();
        
    // dd($chef);

    if (!$chef) {
        return CommonHelper::apiResponse(404, false, 'Chef not found.', []);
    }

    // âœ… check today in working_days
    $days = json_decode($chef->working_days, true);

    // if (!is_array($days) || !in_array($today, $days)) {
    //     return CommonHelper::apiResponse(200, false, "Chef not available today ($today).", []);
    // }
    // ✅ NEW: handle zero working days only
    if (!is_array($days) || empty($days)) {
        $noAvailability = true;
    }
    
    // ✅ EXISTING FLOW (DO NOT CHANGE)
    elseif (!in_array($today, $days)) {
        return CommonHelper::apiResponse(200, false, "Chef not available today ($today).", []);
    }
    

    // âœ… Cuisine mapping
    $cuisines = \DB::table('cuisine_type')->pluck('title', 'id'); // [id => title]

    // âœ… food_dishes base query
    $query = \DB::table('food_dishes as f')
        ->select(
            'f.id',
            'f.name',
            'f.description',
            'f.price',
            'f.image',
            'f.spicy_level',
            'f.weight_option_id',
            'f.preparation_time_id',
            'f.ingredients',
            'f.allergy_warning',
            'f.cuisine_type_id',
            'f.is_get_now_or_get_later',
            'f.category_id',
            'f.in_stock',
            'f.tags'
        )
        ->where('f.chef_id', $chefId)
        // ->where('f.in_stock', 1)
        // ->where('f.tags',$tagId)
        ->whereIn('f.is_get_now_or_get_later', ['get_now', 'both']); 

    
    if (!empty($categoryId)) {
    $query->whereRaw('FIND_IN_SET(?, f.category_id)', [$categoryId]);
}
// ✅ 🔥 TAG FILTER (NEW)
    if (!empty($tagId)) {
        $query->whereRaw('FIND_IN_SET(?, f.tags)', [$tagId]);
    }
    
    $dishes = $query->orderBy('f.id', 'desc')
        ->paginate($perPage, ['*'], 'page', $page);
    
   
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
            "is_get_now_or_get_later" => $dish->is_get_now_or_get_later,
            "category_id"           => $dish->category_id,
            "in_stock" => $noAvailability ? 0 : $dish->in_stock
        ];
    });

    if ($dishes->isEmpty()) {
        return CommonHelper::apiResponse(404, false, 'No dishes found for today.', []);
    }

    return CommonHelper::apiResponse(200, true, "Today's dishes fetched successfully.", $dishes);
}



public function getLaterDishes(Request $request, $chefId)
{
    $today = strtolower(\Carbon\Carbon::now()->format('l')); // e.g. "thursday"

    $page       = $request->get('page', 1);
    $perPage    = $request->get('per_page', 10);
    $categoryId = $request->get('category_id'); // âœ… optional filter
    // $tag = $request->input('tag'); // seasonal / traditional
    // $tagId = 0;
    // $tagData = \DB::table('tags')->where('title',$tag)->first();
    // if($tagData){
    //     $tagId = $tagData->id;
    // }
    // âœ… Chef record fetch
    $chef = \DB::table('chefs')
        ->select('id', 'working_days','name')
        ->where('id', $chefId)
        ->first();
        
    // dd($chef);

    if (!$chef) {
        return CommonHelper::apiResponse(404, false, 'Chef not found.', []);
    }
    
    $tagTitle   = $request->get('tag'); // ✅ NEW
    
     $tagId = null;
    if (!empty($tagTitle)) {
        $tagData = \DB::table('tags')
            ->whereRaw('LOWER(title) = ?', [strtolower($tagTitle)])
            ->first();

        if ($tagData) {
            $tagId = $tagData->id;
        } else {
            return CommonHelper::apiResponse(200, false, 'Invalid tag provided.', []);
        }
    }
    
    
    // ✅ NEW: check if chef has zero availability
    $noAvailability = false;

    $days = json_decode($chef->working_days, true);

// 🔥 handle double encoded JSON
if (is_string($days)) {
    $days = json_decode($days, true);
}

if (!is_array($days) || empty($days)) {
    $noAvailability = true;
    $days = [];
}

    // ✅ 🔥 Normalize days (IMPORTANT FIX)
    $days = array_map(function ($day) {
        return strtolower(trim($day));
    }, $days);

    // ✅ Remove today properly
    $laterDays = array_values(array_diff($days, [$today]));

    // ✅ If no future days → no availability
    if (empty($laterDays)) {
        $noAvailability = true;
    }


    // âœ… Cuisine mapping
    $cuisines = \DB::table('cuisine_type')->pluck('title', 'id'); // [id => title]

    // âœ… food_dishes base query
    $query = \DB::table('food_dishes as f')
        ->select(
            'f.id',
            'f.name',
            'f.description',
            'f.price',
            'f.image',
            'f.spicy_level',
            'f.weight_option_id',
            'f.preparation_time_id',
            'f.ingredients',
            'f.allergy_warning',
            'f.cuisine_type_id',
            'f.is_get_now_or_get_later',
            'f.category_id',
            'f.in_stock',
            'f.tags' // debug
        )
        ->where('f.chef_id', $chefId)
        ->whereIn('f.is_get_now_or_get_later', ['get_later', 'both']);

    // âœ… apply category filter if passed
     if (!empty($categoryId)) {
    $query->whereRaw('FIND_IN_SET(?, f.category_id)', [$categoryId]);
}

// ✅ 🔥 TAG FILTER (MAIN)
    if (!empty($tagId)) {
        $query->whereRaw('FIND_IN_SET(?, f.tags)', [$tagId]);
    }


    // âœ… pagination
    $dishes = $query->orderBy('f.id', 'desc')
        ->paginate($perPage, ['*'], 'page', $page);

    // âœ… Transform dishes (without breaking pagination meta)
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
            "is_get_now_or_get_later" => $dish->is_get_now_or_get_later,
            "category_id" => $dish->category_id,
            'in_stock' => $noAvailability ? 0 : $dish->in_stock
        ];
    });

    if ($dishes->isEmpty()) {
        return CommonHelper::apiResponse(404, false, 'No later day dishes found.', []);
    }

    return CommonHelper::apiResponse(200, true, "Later day dishes fetched successfully.", $dishes);
}

public function preOrderRestaurants(Request $request)
{
    $today = strtolower(now()->format('l'));

    $dishes = DB::table('chefs')
        ->select(
            'id',
            'name',
            'image',
            'rating',
            'is_pre_order',
            'working_days'
        )
        ->where('is_pre_order', 1)
        ->whereJsonContains('working_days', $today)
        ->orderBy('id', 'desc')
        ->get();

    return CommonHelper::apiResponse(
        200,
        true,
        "Later day dishes fetched successfully.",
        $dishes
    );
}



  public function listVariousFoodDishes()
{
    // Agar user kaam me use nahi ho raha to hata sakte ho
    $user = auth()->user();

     $data = Tag::where('status', 'active')->get();

    if ($data->isNotEmpty()) {
        return CommonHelper::apiResponse(
            200,
            true,
            'Various Food Dishes list fetched successfully!',
            $data
        );
    }

    return CommonHelper::apiResponse(
        200,
        true,
        'No food dishes found.',
        []
    );
}
// public function getLatestFoodByTag(Request $request, $tagId)
// {
//     $categoryId = $request->input('category_id'); 

//     $query = \DB::table('food_dishes')
//         ->whereRaw("FIND_IN_SET(?, tags)", [$tagId]);

//     if (!empty($categoryId)) {
//         $query->where('category_id', $categoryId); 
//     }

//     $foodItems = $query->orderBy('id', 'desc')
//         ->limit(5)
//         ->get([
//             'id', 'name', 'price', 'spicy_level', 'chef_id',
//             'description', 'cuisine_type_id', 'image', 'category_id'
//         ]);

//     if ($foodItems->isEmpty()) {
//         return CommonHelper::apiResponse(
//             200,
//             true,
//             'No dishes found for this tag and category',
//             []
//         );
//     }

//     $chefIds = $foodItems->pluck('chef_id')->unique();
//     $cuisineTypeIds = $foodItems->pluck('cuisine_type_id')->unique();

//     $chefs = \DB::table('chefs')
//         ->whereIn('id', $chefIds)
//         ->get(['id', 'name','profile_image']);

//     $ratings = \DB::table('ratings')
//         ->select('chef_id', \DB::raw('AVG(rating) as avg_rating'))
//         ->whereIn('chef_id', $chefIds)
//         ->groupBy('chef_id')
//         ->get()
//         ->keyBy('chef_id');

//     $cuisineTypes = \DB::table('cuisine_type')
//         ->whereIn('id', $cuisineTypeIds)
//         ->get(['id', 'title'])
//         ->keyBy('id');

//     $foodItems->transform(function ($item) use ($chefs, $ratings, $cuisineTypes) {
//         $chef = $chefs->firstWhere('id', $item->chef_id);
//         $avgRating = $ratings[$item->chef_id]->avg_rating ?? null;

//         $item->chef = [
//             'id'     => $chef->id ?? null,
//             'name'   => $chef->name ?? null,
//             'rating' => $avgRating ? number_format($avgRating, 1) : null,
//             'profile_image' => !empty($chef->profile_image) 
//                                 ? asset($chef->profile_image) 
//                                 : null
//         ];

//         $item->description = $item->description 
//             ? (strlen($item->description) > 20 
//                 ? substr($item->description, 0, 20) . '...' 
//                 : $item->description) 
//             : null;

//         $item->cuisine = $cuisineTypes[$item->cuisine_type_id]->title ?? null;

//         // âœ… food image
//         $item->image = !empty($item->image) ? asset($item->image) : null;

//         unset($item->chef_id, $item->cuisine_type_id);
//         return $item;
//     });

//     return CommonHelper::apiResponse(
//         200,
//         true,
//         'Latest dishes fetched successfully!',
//         $foodItems
//     );
// }


public function getLatestFoodByTag(Request $request, $tagId)
{
    // 1️⃣ Logged-in user
    $user = Auth::user();
    $today = strtolower(now()->format('l'));
    $isGuest = !$user;
    $token = $request->bearerToken();

    if ($token) {
        $accessToken = PersonalAccessToken::findToken($token);

        if ($accessToken) {
            $user = $accessToken->tokenable; // 👈 logged-in user
        }
    }
    $defaultLat = env('DEFAULT_LAT', 22.9952);
$defaultLng = env('DEFAULT_LNG', 72.6041);
    // 2️⃣ Selected user address
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

    // 3️⃣ Radius (KM)
    $radius = \App\Models\Setting::value('radius_km') ?? 10;

    // 4️⃣ Request inputs
    $cuisineTypeId = $request->input('cuisine_type_id');
    $date          = $request->input('date'); // yyyy-mm-dd

    // 5️⃣ Date → day
    $dayName = null;
    if (!empty($date)) {
        try {
            $dayName = strtolower(\Carbon\Carbon::parse($date)->format('l'));
        } catch (\Exception $e) {
            return CommonHelper::apiResponse(
                400,
                false,
                'Invalid date format',
                []
            );
        }
    }

    /**
     * 6️⃣ Find nearby chefs (within radius)
     */
    $nearbyChefIds = DB::table('chefs')
        ->select(
            'chefs.id',
            DB::raw("(
                6371 * acos(
                    cos(radians($userLat)) *
                    cos(radians(chefs.latitude)) *
                    cos(radians(chefs.longitude) - radians($userLng)) +
                    sin(radians($userLat)) *
                    sin(radians(chefs.latitude))
                )
            ) AS distance")
        )
        ->having('distance', '<=', $radius)
        ->whereExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('food_dishes')
                ->whereColumn('food_dishes.chef_id', 'chefs.id');
                // ->where('food_dishes.in_stock', 1);
        })
        // ->where('available', 1)
        // ->whereJsonContains('working_days', $today)
        ->pluck('id')
        ->toArray();

    if (empty($nearbyChefIds)) {
        return CommonHelper::apiResponse(
            200,
            true,
            'No dishes available within ' . $radius . ' km.',
            []
        );
    }

    /**
     * 7️⃣ Food dishes query (tag + radius)
     */
    $query = DB::table('food_dishes')
        ->whereRaw("FIND_IN_SET(?, tags)", [$tagId])
        ->whereIn('chef_id', $nearbyChefIds);
        // ->where('in_stock', 1);  // 👈 Add this line to filter only in-stock items;

    // 👉 Cuisine filter (skip if cuisine_type_id = 1)
    if (!empty($cuisineTypeId) && $cuisineTypeId != 1) {
        $query->where('cuisine_type_id', $cuisineTypeId);
    }

    $foodItems = $query
        ->orderBy('id', 'desc')
        ->get([
            'id',
            'name',
            'price',
            'spicy_level',
            'chef_id',
            'description',
            'cuisine_type_id',
            'image',
            'in_stock'
        ]);

    if ($foodItems->isEmpty()) {
        return CommonHelper::apiResponse(
            200,
            true,
            'No dishes found for this tag and filters',
            []
        );
    }

    /**
     * 8️⃣ Chef & cuisine data
     */
    $chefIds        = $foodItems->pluck('chef_id')->unique();
    $cuisineTypeIds = $foodItems->pluck('cuisine_type_id')->unique();

    $chefs = DB::table('chefs')
        ->whereIn('id', $chefIds)
        ->get([
            'id',
            'name',
            'profile_image',
            'working_days',
            'fssai_license_number'
        ]);

    // 👉 Filter chefs by working day
    if ($dayName) {
        $chefs = $chefs->filter(function ($chef) use ($dayName) {
            $days = json_decode($chef->working_days, true);
            return is_array($days)
                && in_array($dayName, array_map('strtolower', $days));
        });
    }

    $validChefIds = $chefs->pluck('id')->toArray();

    if ($dayName && empty($validChefIds)) {
        return CommonHelper::apiResponse(
            200,
            true,
            'No dishes found for this tag and date',
            []
        );
    }

    // 👉 Filter food by valid chefs
    if ($dayName) {
        $foodItems = $foodItems
            ->whereIn('chef_id', $validChefIds)
            ->values();
    }

    if ($foodItems->isEmpty()) {
        return CommonHelper::apiResponse(
            200,
            true,
            'No dishes found for this tag and date',
            []
        );
    }

    /**
     * 9️⃣ Ratings & cuisine types
     */
    $ratings = DB::table('ratings')
        ->select('chef_id', DB::raw('AVG(rating) as avg_rating'))
        ->whereIn('chef_id', $chefIds)
        ->groupBy('chef_id')
        ->get()
        ->keyBy('chef_id');

    $cuisineTypes = DB::table('cuisine_type')
        ->whereIn('id', $cuisineTypeIds)
        ->get(['id', 'title'])
        ->keyBy('id');

    /**
     * 🔟 Final transform
     */
    $foodItems->transform(function ($item) use ($chefs, $ratings, $cuisineTypes) {
        $chef = $chefs->firstWhere('id', $item->chef_id);
        $avgRating = $ratings[$item->chef_id]->avg_rating ?? null;

        $item->chef = [
            'id'     => $chef->id ?? null,
            'name'   => $chef->name ?? null,
            'rating' => $avgRating ? number_format($avgRating, 1) : null,
            'profile_image' => !empty($chef->profile_image)
                ? asset($chef->profile_image)
                : null,
            'fssai_license_number' => $chef->fssai_license_number ?? null
        ];

        $item->description = $item->description
            ? (strlen($item->description) > 20
                ? substr($item->description, 0, 20) . '...'
                : $item->description)
            : null;

        $item->cuisine = $cuisineTypes[$item->cuisine_type_id]->title ?? null;
        $item->image   = $item->image ? asset($item->image) : null;

        unset($item->chef_id, $item->cuisine_type_id);
        return $item;
    });

    return CommonHelper::apiResponse(
        200,
        true,
        'Latest dishes fetched successfully!',
        $foodItems
    );
}


// public function getLatestFoodByTag(Request $request, $tagId)
// {
//     $cuisineTypeId = $request->input('cuisine_type_id'); 
//     $date          = $request->input('date'); // yyyy-mm-dd format

//     // ðŸ”¹ Date se day nikaalna (lowercase me)
//     $dayName = null;
//     if (!empty($date)) {
//         try {
//             $dayName = strtolower(\Carbon\Carbon::parse($date)->format('l')); 
//             // e.g. "thursday"
//         } catch (\Exception $e) {
//             return CommonHelper::apiResponse(
//                 400,
//                 false,
//                 'Invalid date format',
//                 []
//             );
//         }
//     }

//     $query = \DB::table('food_dishes')
//         ->whereRaw("FIND_IN_SET(?, tags)", [$tagId]);

//     // ðŸ”¹ Cuisine type filter
//     if (!empty($cuisineTypeId) && $cuisineTypeId != 1) {
//         $query->where('cuisine_type_id', $cuisineTypeId); 
//     }

//     $foodItems = $query->orderBy('id', 'desc')
//         ->limit(5)
//         ->get([
//             'id', 'name', 'price', 'spicy_level', 'chef_id',
//             'description', 'cuisine_type_id', 'image'
//         ]);

//     if ($foodItems->isEmpty()) {
//         return CommonHelper::apiResponse(
//             200,
//             true,
//             'No dishes found for this tag and cuisine type',
//             []
//         );
//     }

//     $chefIds        = $foodItems->pluck('chef_id')->unique();
//     $cuisineTypeIds = $foodItems->pluck('cuisine_type_id')->unique();

//     // ðŸ”¹ Chef fetch
//     $chefs = \DB::table('chefs')
//         ->whereIn('id', $chefIds)
//         ->get(['id', 'name','profile_image','working_days','fssai_license_number']);

//     // ðŸ”¹ Agar date diya hai toh filter chefs by working_days
//     if ($dayName) {
//         $chefs = $chefs->filter(function ($chef) use ($dayName) {
//             $days = json_decode($chef->working_days, true);
//             if (is_array($days)) {
//                 return in_array($dayName, array_map('strtolower', $days));
//             }
//             return false;
//         });
//     }

//     $validChefIds = $chefs->pluck('id')->toArray();

//     if ($dayName && empty($validChefIds)) {
//         return CommonHelper::apiResponse(
//             200,
//             true,
//             'No dishes found for this tag and date',
//             []
//         );
//     }

//     // ðŸ”¹ Filter foodItems only with valid chefs
//     if ($dayName) {
//         $foodItems = $foodItems->filter(function ($item) use ($validChefIds) {
//             return in_array($item->chef_id, $validChefIds);
//         })->values();
//     }

//     if ($foodItems->isEmpty()) {
//         return CommonHelper::apiResponse(
//             200,
//             true,
//             'No dishes found for this tag and date',
//             []
//         );
//     }

//     $ratings = \DB::table('ratings')
//         ->select('chef_id', \DB::raw('AVG(rating) as avg_rating'))
//         ->whereIn('chef_id', $chefIds)
//         ->groupBy('chef_id')
//         ->get()
//         ->keyBy('chef_id');

//     $cuisineTypes = \DB::table('cuisine_type')
//         ->whereIn('id', $cuisineTypeIds)
//         ->get(['id', 'title'])
//         ->keyBy('id');

//     $foodItems->transform(function ($item) use ($chefs, $ratings, $cuisineTypes) {
//         $chef = $chefs->firstWhere('id', $item->chef_id);
//         $avgRating = $ratings[$item->chef_id]->avg_rating ?? null;

//         $item->chef = [
//             'id'     => $chef->id ?? null,
//             'name'   => $chef->name ?? null,
//             'rating' => $avgRating ? number_format($avgRating, 1) : null,
//             'profile_image' => !empty($chef->profile_image) 
//                                 ? asset($chef->profile_image) 
//                                 : null,
//             'fssai_license_number' => $chef->fssai_license_number ?? null
//         ];

//         $item->description = $item->description 
//             ? (strlen($item->description) > 20 
//                 ? substr($item->description, 0, 20) . '...' 
//                 : $item->description) 
//             : null;

//         $item->cuisine = $cuisineTypes[$item->cuisine_type_id]->title ?? null;
//         $item->image   = !empty($item->image) ? asset($item->image) : null;

//         unset($item->chef_id, $item->cuisine_type_id);
//         return $item;
//     });

//     return CommonHelper::apiResponse(
//         200,
//         true,
//         'Latest dishes fetched successfully!',
//         $foodItems
//     );
// }

public function getAllFoodByTag(Request $request, $tagId)
{
    $page          = (int) $request->get('page', 1);
    $perPage       = (int) $request->get('per_page', 10);
    $cuisineTypeId = $request->input('cuisine_type_id');
    $date          = $request->input('date');

    $defaultLat = env('DEFAULT_LAT', 22.9952);
    $defaultLng = env('DEFAULT_LNG', 72.6041);

    // =============================
    // 🔐 USER / GUEST HANDLING
    // =============================
    $user = Auth::user();
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

    // =============================
    // 📅 DAY LOGIC
    // =============================
    $dayName = strtolower(now()->format('l'));

    if (!empty($date)) {
        try {
            $dayName = strtolower(\Carbon\Carbon::parse($date)->format('l'));
        } catch (\Exception $e) {
            return CommonHelper::apiResponse(400, false, 'Invalid date format', []);
        }
    }

    // =============================
    // 📏 RADIUS
    // =============================
    $radius = \App\Models\Setting::value('radius_km') ?? 10;

    // =============================
    // 🍽️ BASE QUERY (JOIN CHEFS)
    // =============================
$query = DB::table('food_dishes as fd')
    ->join('chefs as c', 'c.id', '=', 'fd.chef_id')
    ->selectRaw(
    'fd.*,
     c.name as chef_name,
     c.profile_image as chef_profile,
     c.fssai_license_number,
     c.latitude,
     c.longitude,
     (
        6371 * acos(
            cos(radians(?)) *
            cos(radians(c.latitude)) *
            cos(radians(c.longitude) - radians(?)) +
            sin(radians(?)) *
            sin(radians(c.latitude))
        )
     ) AS distance',
    [$userLat, $userLng, $userLat]
)

    // ✅ TAG FILTER
    ->whereRaw("FIND_IN_SET(?, fd.tags)", [$tagId])

    // 🔥 BULLETPROOF STOCK FILTER
    // ->whereRaw('IFNULL(fd.in_stock, 0) = 1')

    // ✅ CHEF CONDITIONS
    // ->where('c.available', 1)
    ->where('c.is_verify', 1)

    // ✅ working days only if provided
    ->when(!empty($dayName), function ($q) use ($dayName) {
        // $q->whereJsonContains('c.working_days', $dayName);
    })

    // ✅ RADIUS FILTER
    ->having('distance', '<=', $radius)

    // ✅ IMPORTANT for HAVING stability
    ->orderBy('distance');

    // =============================
    // 🍛 CUISINE FILTER
    // =============================
    if (!empty($cuisineTypeId) && $cuisineTypeId != 1) {
        $query->where('fd.cuisine_type_id', $cuisineTypeId);
    }

    $query->orderBy('fd.id', 'desc');

    // =============================
    // 📄 PAGINATION
    // =============================
    $foodItems = $query->paginate($perPage, ['*'], 'page', $page);

    if ($foodItems->isEmpty()) {
        return CommonHelper::apiResponse(
            200,
            true,
            'No dishes found for this tag',
            []
        );
    }

    // =============================
    // ⭐ RATINGS
    // =============================
    $chefIds = $foodItems->pluck('chef_id')->unique();

    $ratings = DB::table('ratings')
        ->select('chef_id', DB::raw('AVG(rating) as avg_rating'))
        ->whereIn('chef_id', $chefIds)
        ->groupBy('chef_id')
        ->get()
        ->keyBy('chef_id');

    // =============================
    // ❤️ FAVOURITES
    // =============================
    if (!$user) {
        $favourites = [];
    } else {
        $favourites = DB::table('favourite_chefs')
            ->where('user_id', $user->id)
            ->pluck('chef_id')
            ->toArray();
    }

    // =============================
    // 🔄 TRANSFORM
    // =============================
    $foodItems->getCollection()->transform(function ($item) use ($ratings, $favourites) {

        $avgRating = $ratings[$item->chef_id]->avg_rating ?? null;

        return [
            "id"          => $item->id,
            "name"        => $item->name,
            "price"       => $item->price,
            "spicy_level" => $item->spicy_level,
            "in_stock"    => $item->in_stock,
            "description" => $item->description
                ? (strlen($item->description) > 20
                    ? substr($item->description, 0, 20) . '...'
                    : $item->description)
                : null,
            "image"  => !empty($item->image) ? asset($item->image) : null,

            "chef" => [
                'id'            => $item->chef_id,
                'name'          => $item->chef_name,
                'rating'        => $avgRating ? number_format($avgRating, 1) : null,
                'profile_image' => !empty($item->chef_profile) ? asset($item->chef_profile) : null,
                'fssai_license_number' => $item->fssai_license_number,
                'is_fav'        => in_array($item->chef_id, $favourites),
                'distance_km'   => round($item->distance, 2),
            ]
        ];
    });

    return CommonHelper::apiResponse(
        200,
        true,
        'All dishes fetched successfully!',
        $foodItems
    );
}


//south indian and north indian chef
// public function getLatestChefByCuisine(Request $request, $cuisineTypeId)
// {
//     $date = $request->input('date'); // yyyy-mm-dd format

//     // ðŸ”¹ Date se day nikaalna (lowercase me)
//     $dayName = null;
//     if (!empty($date)) {
//         try {
//             $dayName = strtolower(\Carbon\Carbon::parse($date)->format('l')); 
//         } catch (\Exception $e) {
//             return CommonHelper::apiResponse(
//                 400,
//                 false,
//                 'Invalid date format',
//                 null
//             );
//         }
//     }

//     // ðŸ”¹ Cuisine type ke hisaab se food fetch karo (sirf chef_id chahiye)
//     $foodItems = \DB::table('food_dishes')
//         ->where('cuisine_type_id', $cuisineTypeId)
//         ->get(['chef_id']);

//     if ($foodItems->isEmpty()) {
//         return CommonHelper::apiResponse(
//             200,
//             true,
//             'No chefs found for this cuisine type',
//             null
//         );
//     }

//     $chefIds = $foodItems->pluck('chef_id')->unique();

//     // ðŸ”¹ Chef fetch (city + profile_image)
//     $chefs = \DB::table('chefs')
//         ->whereIn('id', $chefIds)
//         ->get(['id', 'name', 'city', 'profile_image', 'working_days']);

//     // ðŸ”¹ Agar date diya hai toh filter chefs by working_days
//     if ($dayName) {
//         $chefs = $chefs->filter(function ($chef) use ($dayName) {
//             $days = json_decode($chef->working_days, true);
//             if (is_array($days)) {
//                 return in_array($dayName, array_map('strtolower', $days));
//             }
//             return false;
//         });
//     }
    
//     $chefs = $chefs->take(5);

//     // ðŸ”¹ Agar filter ke baad chefs empty ho toh null
//     if ($chefs->isEmpty()) {
//         return CommonHelper::apiResponse(
//             200,
//             true,
//             'No chefs found for this cuisine type and date',
//             null
//         );
//     }

//     // ðŸ”¹ Ratings fetch
//     $ratings = \DB::table('ratings')
//         ->select('chef_id', \DB::raw('AVG(rating) as avg_rating'))
//         ->whereIn('chef_id', $chefIds)
//         ->groupBy('chef_id')
//         ->get()
//         ->keyBy('chef_id');

//      $favChefIds = [];
//         if ($userId) {
//             $favChefIds = \DB::table('favourite_chefs')
//                 ->where('user_id', $userId)
//                 ->whereIn('chef_id', $chefIds)
//                 ->pluck('chef_id')
//                 ->toArray();
//         }
//     // ðŸ”¹ Final response (sirf chefs ka data)
//     $chefData = $chefs->map(function ($chef) use ($ratings) {
//         $avgRating = $ratings[$chef->id]->avg_rating ?? null;

//         return [
//             'id'            => $chef->id,
//             'name'          => $chef->name,
//             'city'          => $chef->city,
//             'rating'        => $avgRating ? number_format($avgRating, 1) : null,
//             'profile_image' => !empty($chef->profile_image) 
//                                 ? asset($chef->profile_image) 
//                                 : null,
//                                 'is_fav'        => in_array($chef->id, $favChefIds) ? true : false
//         ];
//     })->values();

//     return CommonHelper::apiResponse(
//         200,
//         true,
//         'Chefs fetched successfully!',
//         $chefData
//     );
// }
// public function getLatestChefByCuisine(Request $request, $cuisineTypeId)
// {
//     $date = $request->input('date'); // yyyy-mm-dd format

//     // ðŸ”¹ Token se user_id le lo
//     $userId = auth()->id(); // ya auth()->user()->id
// // dd($userId);
//     // ðŸ”¹ Date se day nikaalna (lowercase me)
//     $dayName = null;
//     if (!empty($date)) {
//         try {
//             $dayName = strtolower(\Carbon\Carbon::parse($date)->format('l')); 
//         } catch (\Exception $e) {
//             return CommonHelper::apiResponse(
//                 400,
//                 false,
//                 'Invalid date format',
//                 null
//             );
//         }
//     }

//     // ðŸ”¹ Cuisine type ke hisaab se food fetch karo (sirf chef_id chahiye)
//     $foodItems = \DB::table('food_dishes')
//         ->where('cuisine_type_id', $cuisineTypeId)
//         ->get(['chef_id']);

//     if ($foodItems->isEmpty()) {
//         return CommonHelper::apiResponse(
//             200,
//             true,
//             'No chefs found for this cuisine type',
//             null
//         );
//     }

//     $chefIds = $foodItems->pluck('chef_id')->unique();

//     // ðŸ”¹ Chef fetch (city + profile_image)
//     $chefs = \DB::table('chefs')
//         ->whereIn('id', $chefIds)
//         ->get(['id', 'name', 'city', 'profile_image', 'working_days']);

//     // ðŸ”¹ Agar date diya hai toh filter chefs by working_days
//     if ($dayName) {
//         $chefs = $chefs->filter(function ($chef) use ($dayName) {
//             $days = json_decode($chef->working_days, true);
//             if (is_array($days)) {
//                 return in_array($dayName, array_map('strtolower', $days));
//             }
//             return false;
//         });
//     }

//     // ðŸ”¹ Agar filter ke baad chefs empty ho toh null
//     if ($chefs->isEmpty()) {
//         return CommonHelper::apiResponse(
//             200,
//             true,
//             'No chefs found for this cuisine type and date',
//             null
//         );
//     }

//     // ðŸ”¹ Ratings fetch
//     $ratings = \DB::table('ratings')
//         ->select('chef_id', \DB::raw('AVG(rating) as avg_rating'))
//         ->whereIn('chef_id', $chefIds)
//         ->groupBy('chef_id')
//         ->get()
//         ->keyBy('chef_id');

//     // ðŸ”¹ Favourite chefs fetch based on token user_id
//     $favChefIds = [];
//     if ($userId) {
//         $favChefIds = \DB::table('favourite_chefs')
//             ->where('user_id', $userId)
//             ->whereIn('chef_id', $chefIds)
//             ->pluck('chef_id')
//             ->toArray();
//     }

//     // ðŸ”¹ Final response (sirf chefs ka data)
//     $chefData = $chefs->map(function ($chef) use ($ratings, $favChefIds) {
//         $avgRating = $ratings[$chef->id]->avg_rating ?? null;

//         return [
//             'id'            => $chef->id,
//             'name'          => $chef->name,
//             'city'          => $chef->city,
//             'rating'        => $avgRating ? number_format($avgRating, 1) : null,
//             'profile_image' => !empty($chef->profile_image) ? asset($chef->profile_image) : null,
//             'is_fav'        => in_array($chef->id, $favChefIds)
//         ];
//     })->values();

//     return CommonHelper::apiResponse(
//         200,
//         true,
//         'Chefs fetched successfully!',
//         $chefData
//     );
// }
//18/02/2026
public function getLatestChefByCuisine(Request $request, $cuisineTypeId)
{
    $date   = $request->input('date');
    $radius = $request->input('radius', 10); // KM
    $today = strtolower(now()->format('l'));
    $user = auth()->user();
    $token = $request->bearerToken();

    if ($token) {
        $accessToken = PersonalAccessToken::findToken($token);

        if ($accessToken) {
            $user = $accessToken->tokenable; // 👈 logged-in user
        }
    }
    $isGuest = !$user;
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

    // 🔹 Date → Day name
    $dayName = null;
    if (!empty($date)) {
        try {
            $dayName = strtolower(\Carbon\Carbon::parse($date)->format('l'));
        } catch (\Exception $e) {
            return CommonHelper::apiResponse(400, false, 'Invalid date format', null);
        }
    }

    // 🔹 Fetch chef IDs by cuisine
    $chefIds = \DB::table('food_dishes')
        ->where('cuisine_type_id', $cuisineTypeId)
        ->pluck('chef_id')
        ->unique()
        ->values();

    if ($chefIds->isEmpty()) {
        return CommonHelper::apiResponse(
            200,
            true,
            'No chefs found for this cuisine type',
            null
        );
    }

    // 🔹 Chef query with distance calculation
    $chefs = \DB::table('chefs')
        ->whereIn('id', $chefIds)
        ->select(
            'id',
            'name',
            'city',
            'profile_image',
            'working_days',
            'latitude',
            'longitude',
            'available'
        )
        ->selectRaw("
            (6371 * acos(
                cos(radians(?))
                * cos(radians(latitude))
                * cos(radians(longitude) - radians(?))
                + sin(radians(?))
                * sin(radians(latitude))
            )) AS distance
        ", [$userLat, $userLng, $userLat])
        ->having('distance', '<=', $radius)
        // ->whereJsonContains('working_days', $today)
        ->whereExists(function ($query) {
        $query->select(DB::raw(1))
            ->from('food_dishes')
            ->whereColumn('food_dishes.chef_id', 'chefs.id');
            // ->where('food_dishes.in_stock', 1);
    })
    // ->where('available', 1)
        ->orderBy('distance')
        ->get();

    // 🔹 Filter by working day
    if ($dayName) {
        $chefs = $chefs->filter(function ($chef) use ($dayName) {
            $days = json_decode($chef->working_days, true);
            return is_array($days) && in_array($dayName, array_map('strtolower', $days));
        })->values();
    }

    if ($chefs->isEmpty()) {
        return CommonHelper::apiResponse(
            200,
            true,
            'No chefs found for this cuisine type and filters',
            null
        );
    }

    // 🔹 Ratings
    $ratings = \DB::table('ratings')
        ->select('chef_id', \DB::raw('AVG(rating) as avg_rating'))
        ->whereIn('chef_id', $chefIds)
        ->groupBy('chef_id')
        ->get()
        ->keyBy('chef_id');

    // 🔹 Favourite chefs
    
    if(isset($user)){
    $favChefIds = \DB::table('favourite_chefs')
        ->where('user_id', $user->id)
        ->whereIn('chef_id', $chefIds)
        ->pluck('chef_id')
        ->toArray();
    }else{
        $favChefIds = [];
    }

    // 🔹 Final response
    $chefData = $chefs->map(function ($chef) use ($ratings, $favChefIds) {
        return [
            'id'            => $chef->id,
            'name'          => $chef->name,
            'city'          => $chef->city,
            'rating'        => isset($ratings[$chef->id])
                                ? number_format($ratings[$chef->id]->avg_rating, 1)
                                : null,
            'distance_km'   => round($chef->distance, 2),
            'profile_image' => $chef->profile_image ? asset($chef->profile_image) : null,
            'is_fav'        => in_array($chef->id, $favChefIds),
            'available'     => $chef->available
        ];
    })->values();

    return CommonHelper::apiResponse(
        200,
        true,
        'Chefs fetched successfully!',
        $chefData
    );
}





public function getAllChefByCuisine(Request $request, $cuisineTypeId)
{
    $page    = $request->get('page', 1);
    $perPage = $request->get('per_page', 10);
    $date    = $request->input('date'); 

    // ðŸ”¹ Token se user_id lo
    $userId = auth()->id(); // âœ… token se authenticated user ka id

    // ðŸ”¹ Date se day nikaalna (lowercase me)
    $dayName = null;
    if (!empty($date)) {
        try {
            $dayName = strtolower(\Carbon\Carbon::parse($date)->format('l')); 
        } catch (\Exception $e) {
            return CommonHelper::apiResponse(400, false, 'Invalid date format', null);
        }
    }

    // ðŸ”¹ Cuisine type ke hisaab se food fetch karo (sirf chef_id chahiye)
    $chefIds = \DB::table('food_dishes')
        ->where('cuisine_type_id', $cuisineTypeId)
        ->pluck('chef_id')
        ->unique()
        ->toArray();

    if (empty($chefIds)) {
        return CommonHelper::apiResponse(200, true, 'No chefs found for this cuisine type', null);
    }

    // ðŸ”¹ Chef fetch (city + profile_image)
    $query = \DB::table('chefs')
        ->whereIn('id', $chefIds);

    $chefs = $query->paginate($perPage, ['id', 'name', 'city', 'profile_image', 'working_days','available'], 'page', $page);

    // ðŸ”¹ Agar date diya hai toh filter chefs by working_days
    if ($dayName) {
        $filtered = $chefs->getCollection()->filter(function ($chef) use ($dayName) {
            $days = json_decode($chef->working_days, true);
            if (is_array($days)) {
                return in_array($dayName, array_map('strtolower', $days));
            }
            return false;
        })->values();
        $chefs->setCollection($filtered);
    }

    if ($chefs->isEmpty()) {
        return CommonHelper::apiResponse(200, true, 'No chefs found for this cuisine type' . ($dayName ? ' and date' : ''), null);
    }

    // ðŸ”¹ Ratings fetch
    $ratings = \DB::table('ratings')
        ->select('chef_id', \DB::raw('AVG(rating) as avg_rating'))
        ->whereIn('chef_id', $chefIds)
        ->groupBy('chef_id')
        ->get()
        ->keyBy('chef_id');

    // ðŸ”¹ Favourite chefs fetch
    $favChefIds = [];
    if ($userId) {
        $favChefIds = \DB::table('favourite_chefs')
            ->where('user_id', $userId)
            ->whereIn('chef_id', $chefIds)
            ->pluck('chef_id')
            ->toArray();
    }
    
    // dd($chefs);

    // ðŸ”¹ Transform paginated collection
    $chefs->getCollection()->transform(function ($chef) use ($ratings, $favChefIds) {
        $avgRating = $ratings[$chef->id]->avg_rating ?? null;

        return [
            'id'            => $chef->id,
            'name'          => $chef->name,
            'city'          => $chef->city,
            'rating'        => $avgRating ? number_format($avgRating, 1) : null,
            'profile_image' => !empty($chef->profile_image) ? asset($chef->profile_image) : null,
            'is_fav'        => in_array($chef->id, $favChefIds),
            'available'   => $chef->available
        ];
    });

    return CommonHelper::apiResponse(200, true, 'Chefs fetched successfully!', $chefs);
}









}
