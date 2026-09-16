<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Rating;
use App\Helpers\CommonHelper;


class RatingController extends Controller
{
    public function addRating(Request $request)
{
    // ✅ Validate input
    $request->validate([
        'chef_id'       => 'required|integer',
        'order_id'      => 'required|integer|exists:orders,id',
        'food_items_id' => 'required|array',
        'rating'        => 'required|numeric|min:1|max:5'
    ]);

    // ✅ Current logged in user
    $user = auth()->user();
    if (!$user) {
        return CommonHelper::apiResponse(401, false, 'Unauthorized!', null);
    }

    // ✅ Verify order belongs to this user & chef
    $order = Order::where('id', $request->order_id)
        ->where('chef_id', $request->chef_id)
        ->where('user_id', $user->id)
        ->first();

    if (!$order) {
        return CommonHelper::apiResponse(400, false, 'Invalid order, chef, or user!', null);
    }

    // ✅ Rating create or update (upsert)
    $rating = Rating::updateOrCreate(
        [
            'order_id' => $request->order_id,
            'chef_id'  => $request->chef_id,
            'user_id'  => $user->id,
        ],
        [
            'food_items_id' => $request->food_items_id, // JSON column me array directly store hoga
            'rating'        => $request->rating,
        ]
    );

    return CommonHelper::apiResponse(200, true, 'Rating submitted successfully!', [
        'rating_id' => $rating->id,
        'is_updated' => $rating->wasRecentlyCreated ? false : true, // ✅ agar update hua toh true milega
    ]);
}

//     public function addRating(Request $request)
// {
//     // ✅ Validate input
//     $request->validate([
//         'chef_id'       => 'required|integer',
//         'order_id'      => 'required|integer|exists:orders,id',
//         'food_items_id' => 'required|array',
//         'rating'        => 'required|numeric|min:1|max:5'
//     ]);

//     // ✅ Current logged in user
//     $user = auth()->user();
//     if (!$user) {
//         return CommonHelper::apiResponse(401, false, 'Unauthorized!', null);
//     }

//     // ✅ Verify order belongs to this user & chef
//     $order = Order::where('id', $request->order_id)
//         ->where('chef_id', $request->chef_id)
//         ->where('user_id', $user->id)
//         ->first();

//     if (!$order) {
//         return CommonHelper::apiResponse(400, false, 'Invalid order, chef, or user!', null);
//     }

//     // ✅ Rating create
//     $rating = Rating::create([
//         'order_id'      => $request->order_id,
//         'chef_id'       => $request->chef_id,
//         'user_id'       => $user->id,
//         'food_items_id' => $request->food_items_id, // JSON column me array directly store hoga
//         'rating'        => $request->rating,
        
//     ]);

//     return CommonHelper::apiResponse(200, true, 'Rating submitted successfully!', [
//         'rating_id' => $rating->id,
//     ]);
// }


}
