<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\Order;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function addReview(Request $request)
    {
        // ✅ Validate input
        $request->validate([
            'chef_id'       => 'required|integer',
            'order_id'      => 'required|integer|exists:orders,id',
            'food_items_id' => 'required|array',
            'review_text'   => 'required|string',
        ]);

        // ✅ Current logged in user
        $user = auth()->user();

        if (!$user) {
            return CommonHelper::apiResponse(401, false, 'Unauthorized!', null);
        }

        // ✅ Order verify kare (user aur chef dono match hone chahiye)
        $order = Order::where('id', $request->order_id)
            ->where('chef_id', $request->chef_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return CommonHelper::apiResponse(400, false, 'Invalid order, chef, or user!', null);
        }

        // ✅ Review create
        // $review = Review::create([
        //     'order_id'      => $request->order_id,
        //     'chef_id'       => $request->chef_id,
        //     'user_id'       => $user->id,
        //     'food_items_id' => $request->food_items_id,
        //     'review_text'   => $request->review_text,
        //     // 'is_review'     => 1,
        // ]);

        // return CommonHelper::apiResponse(200, true, 'Review submitted successfully!', [
        //     'review_id' => $review->id,
        // ]);
        
         $review = Review::updateOrCreate(
            [
                'order_id' => $request->order_id,
                'chef_id'  => $request->chef_id,
                'user_id'  => $user->id,
            ],
            [
                'food_items_id' => $request->food_items_id,
                'review_text'   => $request->review_text,
                // 'is_review'     => 1,
            ]
        );
    
        return CommonHelper::apiResponse(200, true, 'Review submitted successfully!', [
            'review_id'  => $review->id,
            'is_updated' => $review->wasRecentlyCreated ? false : true, // ✅ agar update hua toh true milega
        ]);
    }
    
// public function getChefReviews(Request $request, $chef_id)
// {
//     $perPage = $request->input('per_page', 10);
//     $page    = $request->input('page', 1);

//     // ✅ Average rating & total reviews
//     $summary = DB::table('reviews as r')
//         ->leftJoin('ratings as rt', function($join) {
//             $join->on('rt.order_id', '=', 'r.order_id')
//                  ->on('rt.user_id', '=', 'r.user_id')
//                  ->on('rt.chef_id', '=', 'r.chef_id');
//         })
//         ->where('r.chef_id', $chef_id)
//         ->select(
//             DB::raw('AVG(rt.rating) as average_rating'),
//             DB::raw('COUNT(r.id) as total_reviews')
//         )
//         ->first();

//     // ✅ Reviews with user info + pagination
//     $reviews = DB::table('reviews as r')
//         ->join('users as u', 'u.id', '=', 'r.user_id')
//         ->leftJoin('ratings as rt', function($join) {
//             $join->on('rt.order_id', '=', 'r.order_id')
//                  ->on('rt.user_id', '=', 'r.user_id')
//                  ->on('rt.chef_id', '=', 'r.chef_id');
//         })
//         ->where('r.chef_id', $chef_id)
//         ->orderBy('r.created_at', 'desc')
//         ->paginate($perPage, [
//             'r.id as review_id',
//             'u.id as user_id',
//             'u.name',
//             'u.image as profile_pic',
//             'rt.rating',
//             'r.review_text',
//             'r.created_at'
//         ], 'page', $page);

//     // ✅ Transform collection
//     $reviews->getCollection()->transform(function($item) {
//         return [
//             'review_id'   => $item->review_id,
//             'user'        => [
//                 'user_id'     => $item->user_id,
//                 'name'        => $item->name,
//                 'profile_pic' => !empty($item->profile_pic) ? asset('/images/' . $item->profile_pic) : null,
//             ],
//             'rating'      => (float) $item->rating,
//             'review_text' => $item->review_text,
//             'created_at'  => $item->created_at,
//         ];
//     });

//     // ✅ Response data
//     $data = [
//         'chef_id'        => (string) $chef_id,
//         'average_rating' => round($summary->average_rating, 1),
//         'total_reviews'  => $summary->total_reviews,
//         'reviews'        => $reviews, // Laravel paginator object ke sath
//     ];

//     return CommonHelper::apiResponse(200, true, 'Chef reviews fetched successfully!', $data);
// }

public function getChefReviews(Request $request, $chef_id)
{
    $perPage = $request->input('per_page', 10);
    $page    = $request->input('page', 1);
    $sortBy  = $request->input('sort_by'); 
    // default null hoga, tab koi sorting apply nahi hogi

    // ✅ Average rating & total reviews
    $summary = DB::table('reviews as r')
        ->leftJoin('ratings as rt', function($join) {
            $join->on('rt.order_id', '=', 'r.order_id')
                 ->on('rt.user_id', '=', 'r.user_id')
                 ->on('rt.chef_id', '=', 'r.chef_id');
        })
        ->where('r.chef_id', $chef_id)
        ->select(
            DB::raw('AVG(rt.rating) as average_rating'),
            DB::raw('COUNT(r.id) as total_reviews')
        )
        ->first();

    // ✅ Base query for reviews
    $reviewsQuery = DB::table('reviews as r')
        ->join('users as u', 'u.id', '=', 'r.user_id')
        ->leftJoin('ratings as rt', function($join) {
            $join->on('rt.order_id', '=', 'r.order_id')
                 ->on('rt.user_id', '=', 'r.user_id')
                 ->on('rt.chef_id', '=', 'r.chef_id');
        })
        ->where('r.chef_id', $chef_id);

    // ✅ Sorting logic (sirf tab chale jab sort_by diya ho)
    if ($sortBy === 'high_rating') {
        $reviewsQuery->orderBy('rt.rating', 'desc');
    } elseif ($sortBy === 'low_rating') {
        $reviewsQuery->orderBy('rt.rating', 'asc');
    } elseif ($sortBy === 'latest') {
        $today = now()->toDateString(); // yyyy-mm-dd
        $reviewsQuery->orderBy('r.created_at', 'desc');
    }
    // ✅ Pagination
    $reviews = $reviewsQuery->paginate($perPage, [
        'r.id as review_id',
        'u.id as user_id',
        'u.name',
        'u.image as profile_pic',
        'rt.rating',
        'r.review_text',
        'r.created_at'
    ], 'page', $page);

    // ✅ Transform collection
    $reviews->getCollection()->transform(function($item) {
        return [
            'review_id'   => $item->review_id,
            'user'        => [
                'user_id'     => $item->user_id,
                'name'        => $item->name,
                'profile_pic' => !empty($item->profile_pic) ? asset('/images/' . $item->profile_pic) : null,
            ],
            'rating'      => (float) $item->rating,
            'review_text' => $item->review_text,
            'created_at'  => $item->created_at,
        ];
    });

    // ✅ Response data
    $data = [
        'chef_id'        => (string) $chef_id,
         'average_rating' => number_format((float) $summary->average_rating, 1, '.', ''),
        'total_reviews'  => $summary->total_reviews,
        'reviews'        => $reviews,
    ];

    return CommonHelper::apiResponse(200, true, 'Chef reviews fetched successfully!', $data);
}



}
