<?php

namespace App\Http\Controllers\Api;

use App\Models\FoodDish;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\CreateOrderRequest;
use App\Services\OrderService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function store(CreateOrderRequest $request)
    {
        $order = $this->orderService->create(
            $request->validated(),
            auth()->user()
        );

        return CommonHelper::apiResponse(201, true, 'Order created', [
            'order_id' => $order->id,
            'razorpay_order_id' => $order->razorpay_order_id, // may be null initially
            'amount' => $order->amount
        ]);
    }

// public function myOrders(Request $request)
// {
//     $userId = Auth::id(); // token se user id

//     $perPage = $request->input('per_page', 10);
//     $search  = $request->input('search', null);

//     // Orders query (sirf logged-in user ke orders)
//     $ordersQuery = DB::table('orders')->where('user_id', $userId);

//     // Optional search
//     if ($search) {
//         $ordersQuery->where(function ($q) use ($search) {
//             $q->where('date', 'like', "%$search%")
//               ->orWhereIn('chef_id', function ($sub) use ($search) {
//                   $sub->select('id')
//                       ->from('chefs')
//                       ->where('name', 'like', "%$search%");
//               })
//               ->orWhereIn('id', function ($sub) use ($search) {
//                   $sub->select('order_id')
//                       ->from('order_items')
//                       ->where('dish_name', 'like', "%$search%");
//               });
//         });
//     }

//     $orders = $ordersQuery->paginate($perPage);

//     // Transform response
//     $orders->getCollection()->transform(function ($order) {
//         // ✅ Chef details (sirf required fields)
//         $chef = DB::table('chefs')
//             ->select('id', 'name', 'business_name', 'kitchen_name', 'profile_image', 'address')
//             ->where('id', $order->chef_id)
//             ->first();
        
//         if ($chef) {
//             $chef->profile_image = !empty($chef->profile_image) 
//                 ? asset($chef->profile_image) 
//                 : null;
//         }


//         // ✅ Items decode from JSON
//         $itemsArr = json_decode($order->items, true);
//         $items = [];

//         if (is_array($itemsArr)) {
//             foreach ($itemsArr as $item) {
//                 $dish = DB::table('food_dishes')
//                     ->select('id', 'name', 'price', 'image')
//                     ->where('id', $item['id'])
//                     ->first();

//                 if ($dish) {
//                     $quantity    = $item['quantity'];
//                     $total_price = number_format($dish->price * $quantity, 2);

//                     $items[] = [
//                         'id'          => $dish->id,
//                         'name'        => $dish->name,
//                         'price'       => number_format($dish->price, 2),
//                         'quantity'    => $quantity,
//                         'total_price' => $total_price,
//                         'image'       => !empty($dish->image) ? asset($dish->image) : null,
//                     ];
//                 }
//             }
//         }

//         // ✅ Final response per order
//         return [
//             'chef'       => $chef,
//             'items'      => $items,
//             'amount'     => number_format($order->amount, 2),
//             'date'       => $order->date,
//             'status'     => $order->status,
//             'created_at' => $order->created_at,
//         ];
//     });

//     return CommonHelper::apiResponse(200, true, 'Orders fetched successfully!', $orders);
// }

public function myOrders(Request $request)
{
    $userId = Auth::id(); // token se user id

    $perPage = $request->input('per_page', 10);
    $search  = $request->input('search', null);

    // Orders query (sirf logged-in user ke orders)
    $ordersQuery = DB::table('orders')->where('user_id', $userId)->where('payment_status', '!=', 'pending')->orderBy('id', 'desc');

    if ($search) {
        $ordersQuery->where(function ($q) use ($search, $userId) {
           if (is_numeric($search)) {
    $q->orWhereRaw('CAST(id AS CHAR) LIKE ?', ["%{$search}%"]);
}
            // ✅ Chef name match (sirf user ke orders ke chefs)
            $q->orWhereIn('chef_id', function ($sub) use ($search, $userId) {
                $sub->select('id')
                    ->from('chefs')
                    ->where('name', 'like', "%$search%")
                    ->whereIn('id', function ($sq) use ($userId) {
                        $sq->select('chef_id')
                           ->from('orders')
                           ->where('user_id', $userId);
                    });
            });

            // ✅ Food dishes name match OR cuisine type title match
            $q->orWhere(function ($subQuery) use ($search, $userId) {
                $userOrders = DB::table('orders')
                    ->where('user_id', $userId)
                    ->pluck('items');
            
                $dishIds = [];
                foreach ($userOrders as $itemsJson) {
                    $itemsArr = json_decode($itemsJson, true);
                    if (is_array($itemsArr)) {
                        foreach ($itemsArr as $item) {
                            $dishIds[] = $item['id'];
                        }
                    }
                }
            
                if (!empty($dishIds)) {
                    // ✅ Pehle dishes check karo
                    $matchingDishIds = DB::table('food_dishes')
                        ->whereIn('id', $dishIds)
                        ->where('name', 'like', "%$search%")
                        ->pluck('id');
            
                    // ✅ Cuisine type check karo (dish ke through)
                    $matchingCuisineDishIds = DB::table('food_dishes as fd')
                        ->join('cuisine_type as ct', 'fd.cuisine_type_id', '=', 'ct.id')
                        ->whereIn('fd.id', $dishIds)
                        ->where('ct.title', 'like', "%$search%")
                        ->pluck('fd.id');
            
                    // ✅ merge kar do dono result
                    $finalDishIds = $matchingDishIds->merge($matchingCuisineDishIds)->unique();
            
                    if ($finalDishIds->isNotEmpty()) {
                        $subQuery->where(function ($qq) use ($finalDishIds) {
                            foreach ($finalDishIds as $dishId) {
                                $qq->orWhere('items', 'like', '%"id":' . $dishId . '%');
                            }
                        });
                    }
                }
            });

        });
    }

    $orders = $ordersQuery->paginate($perPage);

    $orders->getCollection()->transform(function ($order) {
    // ✅ Chef details
    $chef = DB::table('chefs')
        ->select('id', 'name', 'business_name', 'kitchen_name', 'profile_image', 'address')
        ->where('id', $order->chef_id)
        ->first();

    if ($chef) {
        $chef->profile_image = !empty($chef->profile_image) 
            ? asset($chef->profile_image) 
            : null;
    }

    // ✅ Items
    $itemsArr = json_decode($order->items, true);
    $items = [];

    if (is_array($itemsArr)) {
        foreach ($itemsArr as $item) {
            $dish = DB::table('food_dishes')
                ->select('id', 'name', 'price', 'image','preparation_time_id')
                ->where('id', $item['id'])
                ->first();

            if ($dish) {
                $quantity    = $item['quantity'];
                $total_price = number_format($dish->price * $quantity, 2);

                $items[] = [
                    'id'          => $dish->id,
                    'name'        => $dish->name,
                    'price'       => number_format($dish->price, 2),
                    'quantity'    => $quantity,
                    'total_price' => $total_price,
                    'image'       => !empty($dish->image) ? asset($dish->image) : null,
                    'preparation_time_id' => $order->status === 'preparing' 
                        ? $dish->preparation_time_id 
                        : null,
                ];
            }
        }
    }

    // ✅ Review fetch (independent)
    $reviewRow = DB::table('reviews')
        ->select('review_text')
        ->where('order_id', $order->id)
        ->first();

    $reviewText = $reviewRow ? $reviewRow->review_text : null;

    // ✅ Rating fetch (independent)
    $ratingRow = DB::table('ratings')
        ->select('rating')
        ->where('order_id', $order->id)
        ->first();

    $rating = $ratingRow ? (float) $ratingRow->rating : null;

    return [
        'id'         => $order->id,
        'chef'       => $chef,
        'items'      => $items,
        'amount'     => number_format($order->amount,2),
        'date'       => $order->date,
        'status'     => $order->status,
        'created_at' => $order->created_at,
        'review'     => $reviewText,   // ✅ agar review table me ho
        'rating'     => $rating,       // ✅ agar rating table me ho
        'rejected_by' => $order->rejected_by,
        'accepted_at' => $order->accepted_at
    ];
});



    return CommonHelper::apiResponse(200, true, 'Orders fetched successfully!', $orders);
}

public function pastOrders(Request $request)
{
    $chefId = auth()->id();

    // Pagination
    $perPage = $request->get('per_page', 20);
    $page    = $request->get('page', 1);

    // Filters
    $fromDate = $request->get('from_date');
    $toDate   = $request->get('to_date');
    $status   = $request->get('status');

    // Past order statuses
    $pastStatuses = ['delivered', 'cancelled', 'rejected', 'failed','preparing','new','accepted'];
    $incompleteStatuses = ['new', 'accepted', 'preparing', 'ready'];

    /* ============================
       BASE QUERY (PAST + TODAY FILTERED)
    ============================= */
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
            'o.updated_at'
        )
        ->where('o.chef_id', $chefId)
        ->where('o.payment_status', 'received')
        ->whereIn('o.status', $pastStatuses)

        // ✅ UPDATED LOGIC HERE
        ->where(function ($q) {
            $q->whereDate('o.date', '<', Carbon::today())
              ->orWhere(function ($sub) {
                  $sub->whereDate('o.date', Carbon::today())
                      ->whereIn('o.status', ['delivered', 'rejected']);
              });
        });

    /* ============================
       DATE FILTER (FROM → TO)
    ============================= */
    if ($fromDate && $toDate) {
        $query->whereBetween(
            DB::raw('DATE(o.created_at)'),
            [$fromDate, $toDate]
        );
    }

    /* ============================
       STATUS FILTER
    ============================= */
    if ($status && $status !== 'all') {
        if ($status === 'incomplete') {
            $query->whereIn('o.status', $incompleteStatuses);
        } else {
            $query->where('o.status', $status);
        }
    }

    /* ============================
       SEARCH FILTER
    ============================= */
    if ($request->filled('search')) {
        $search = $request->search;

        $query->where(function ($q) use ($search) {

            if (is_numeric($search)) {
                $q->orWhereRaw('CAST(o.id AS CHAR) LIKE ?', ["%{$search}%"]);
            }

            // Customer name
            $q->orWhere('u.name', 'like', '%' . $search . '%');

            // Dish name (items JSON)
            $q->orWhere('o.items', 'like', '%"name":"' . $search . '%');

            // Amount
            if (is_numeric($search)) {
                $q->orWhere('o.amount', $search);
            }

            // Order date
            if (strtotime($search)) {
                $q->orWhereDate('o.date', $search);
            }
        });
    }

    /* ============================
       EXECUTE PAGINATION
    ============================= */
    $orders = $query
        ->orderBy('o.created_at', 'desc')
        ->paginate($perPage, ['*'], 'page', $page);

    /* ============================
       TRANSFORM
    ============================= */
    $orders->getCollection()->transform(function ($order) {

        // Format amount
        $order->amount = number_format((float)$order->amount, 2, '.', '');
        // $order->amount = round((float) $order->amount);

        // Decode items JSON
        $itemsArr = json_decode($order->items, true);
        $items = [];

        if (is_array($itemsArr)) {
            foreach ($itemsArr as $item) {

                $dish = DB::table('food_dishes')
                    ->select('id', 'name', 'price', 'image', 'preparation_time_id')
                    ->where('id', $item['id'])
                    ->first();

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
                        'preparation_time_id' => null,
                    ];
                }
            }
        }

        $order->items = $items;

        return $order;
    });

    if ($orders->isEmpty()) {
        return CommonHelper::apiResponse(404, false, 'No past orders found', []);
    }

    return CommonHelper::apiResponse(
        200,
        true,
        'Past orders fetched successfully!',
        $orders
    );
}




// public function orderDetail(Request $request, $id)
// {
//     /* ----------------------------------
//      | Order Fetch WITH Refund Relation
//      ---------------------------------- */
//     $order = \App\Models\Order::with('refund')->find($id);

//     if (!$order) {
//         return CommonHelper::apiResponse(404, false, 'Order not found.', null);
//     }

//     /* ----------------------------------
//      | Items Decode
//      ---------------------------------- */
//     $itemsArr = $order->items;
//     $items = [];

//     if (is_array($itemsArr)) {
//         foreach ($itemsArr as $item) {

//             $dish = DB::table('food_dishes')
//                 ->select('id', 'name', 'price', 'image', 'preparation_time_id')
//                 ->where('id', $item['id'])
//                 ->first();

//             if ($dish) {
//                 $items[] = [
//                     'id'          => $dish->id,
//                     'name'        => $dish->name,
//                     'price'       => number_format($dish->price, 2),
//                     'quantity'    => $item['quantity'],
//                     'total_price' => number_format($dish->price * $item['quantity'], 2),
//                     'image'       => $dish->image ? asset($dish->image) : null,
//                     'preparation_time_id' =>
//                         $order->status === 'preparing'
//                             ? $dish->preparation_time_id
//                             : null,
//                 ];
//             }
//         }
//     }

//     /* ----------------------------------
//      | User Details
//      ---------------------------------- */
//     $user = DB::table('users')
//         ->select('id', 'image', 'name', 'email', 'phone_number')
//         ->where('id', $order->user_id)
//         ->first();


//  $selectedAddress = DB::table('user_addresses')
//         ->where('full_address', 'like', '%' . $order->address . '%')
//         ->select(
//             'id',
//             'full_address',
//             'pincode',
//             'house_number',
//             'floor',
//             'building_name',
//             'tag',
//             'latitude',
//             'longitude'
//         )
//         ->first();
//     if ($user) {
//         $user->image = $user->image
//             ? asset('/images/' . $user->image)
//             : null;
//         $user->delivery_address = $order->address;
//         $user->selected_address = $selectedAddress;
        
//     }

//     /* ----------------------------------
//      | Chef Details
//      ---------------------------------- */
//     $chef = DB::table('chefs')
//         ->select('id', 'name', 'phone_number', 'business_name', 'kitchen_name', 'profile_image', 'address')
//         ->where('id', $order->chef_id)
//         ->first();

//     if ($chef) {
//         $chef->profile_image = $chef->profile_image
//             ? asset($chef->profile_image)
//             : null;
//     }

//     /* ----------------------------------
//      | Refund (RELATIONSHIP BASED)
//      ---------------------------------- */
//     $refundData = null;

//     if ($order->refund) {
//         $refundData = [
//             'id'                  => $order->refund->id,
//             'refund_amount'       => number_format($order->refund->refund_amount, 2, '.', ''),
//             'status'              => $order->refund->status, // initiated | processed | failed
//             'razorpay_refund_id'  => $order->refund->razorpay_refund_id,
//             'reason'              => $order->refund->reason,
//             'gateway_response'    => $order->refund->gateway_response,
//             'created_at'          => $order->refund->created_at,
//             'updated_at'          => $order->refund->updated_at,
//         ];
//     }

//     /* ----------------------------------
//      | Final Response
//      ---------------------------------- */
//     $data = [
//         'id'         => $order->id,
//         'items'      => $items,
//         'amount'     => number_format($order->amount, 2),
//         'date'       => $order->date,
//         'status'     => $order->status,
//         'created_at' => $order->created_at,
//         'updated_at' => $order->updated_at,

//         // Payment
//         'payment_status'       => $order->payment_status,
//         'payment_method'       => $order->payment_method,
//         'paid_at'              => $order->paid_at,
//         'razorpay_order_id'    => $order->razorpay_order_id,
//         'razorpay_payment_id'  => $order->razorpay_payment_id,
//         'rejected_by' =>    $order->rejected_by,
//         'accepted_at' =>    $order->accepted_at,

//         // Refund (CLEAN KEY)
//         'refund' => $refundData,

//         'user' => $user,
//         'chef' => $chef,
//     ];

//     return CommonHelper::apiResponse(
//         200,
//         true,
//         'Order detail fetched successfully.',
//         $data
//     );
// }

public function orderDetail(Request $request, $id)
{
    /* ----------------------------------
     | Order Fetch WITH Refund Relation
     ---------------------------------- */
    $order = \App\Models\Order::with('refund')->find($id);

    if (!$order) {
        return CommonHelper::apiResponse(404, false, 'Order not found.', null);
    }

    /* ----------------------------------
     | Items Decode
     ---------------------------------- */
    $itemsArr = $order->items;
    $items = [];

    if (is_array($itemsArr)) {
        foreach ($itemsArr as $item) {

            $dish = DB::table('food_dishes')
                ->select('id', 'name', 'price', 'image', 'preparation_time_id')
                ->where('id', $item['id'])
                ->first();

            if ($dish) {
                $items[] = [
                    'id'          => $dish->id,
                    'name'        => $dish->name,
                    'price'       => number_format($dish->price,2),
                    'quantity'    => $item['quantity'],
                    'total_price' => number_format($dish->price * $item['quantity'], 2),
                    'image'       => $dish->image ? asset($dish->image) : null,
                    'preparation_time_id' =>
                        $order->status === 'preparing'
                            ? $dish->preparation_time_id
                            : null,
                ];
            }
        }
    }

    /* ----------------------------------
     | User Details
     ---------------------------------- */
    $user = DB::table('users')
        ->select('id', 'image', 'name', 'email', 'phone_number')
        ->where('id', $order->user_id)
        ->first();

    $selectedAddress = DB::table('user_addresses')
        ->where('full_address', 'like', '%' . $order->address . '%')
        ->select(
            'id',
            'full_address',
            'pincode',
            'house_number',
            'floor',
            'building_name',
            'tag',
            'latitude',
            'longitude'
        )
        ->first();

    if ($user) {
        $user->image = $user->image
            ? asset('/images/' . $user->image)
            : null;
        $user->delivery_address = $order->address;
        $user->selected_address = $selectedAddress;
    }

    /* ----------------------------------
     | Chef Details
     ---------------------------------- */
    $chef = DB::table('chefs')
        ->select('id', 'name', 'phone_number', 'business_name', 'kitchen_name', 'profile_image', 'address')
        ->where('id', $order->chef_id)
        ->first();

    if ($chef) {
        $chef->profile_image = $chef->profile_image
            ? asset($chef->profile_image)
            : null;
    }

    /* ----------------------------------
     | Refund (RELATIONSHIP BASED)
     ---------------------------------- */
    $refundData = null;

    if ($order->refund) {
        $refundData = [
            'id'                  => $order->refund->id,
            'refund_amount'       => number_format($order->refund->refund_amount, 2, '.', ''),
            'status'              => $order->refund->status,
            'razorpay_refund_id'  => $order->refund->razorpay_refund_id,
            'reason'              => $order->refund->reason,
            'gateway_response'    => $order->refund->gateway_response,
            'created_at'          => $order->refund->created_at,
            'updated_at'          => $order->refund->updated_at,
        ];
    }

    $setting = \DB::table('settings')->first();

    /* ----------------------------------
     | ✅ NEW: Discount Calculation FIX
     ---------------------------------- */
$subTotal = 0;

if (is_array($itemsArr)) {
    foreach ($itemsArr as $item) {

        $dish = DB::table('food_dishes')
            ->select('price')
            ->where('id', $item['id'])
            ->first();

        if ($dish) {
            $subTotal += ((float) $dish->price * (int) $item['quantity']);
        }
    }
}

// ✅ Platform Fee (keep if you really use fixed fee)
$platformFee = (float) ($setting->platform_fee ?? 0);

// ✅ GST % (IMPORTANT: match createOrder)
$gstPercent = 5; // 🔥 CHANGE THIS to your actual GST (example: 5%, 18%)

// ✅ GST Calculation
$gstAmount = ($subTotal * $gstPercent) / 100;

// ✅ Final Amount
$orderAmount = (float) ($order->amount ?? 0);
// $orderAmount = round((float) ($order->amount ?? 0), 2);

// ✅ Discount Calculation
$discountAmount = ($subTotal + $platformFee + $gstAmount) - $orderAmount;
$discountAmount = $discountAmount > 0 ? $discountAmount : 0;
    /* ----------------------------------
     | Final Response
     ---------------------------------- */
    $data = [
        'id'         => $order->id,
        'items'      => $items,
        'amount'     => number_format($order->amount,2),

        // ✅ FIXED FIELDS
        'platform_fee'    => $platformFee,
        'gst_amount'      => number_format($gstAmount,2),
        'discount_amount' => (float) number_format($discountAmount,2),

        'date'       => $order->date,
        'status'     => $order->status,
        'created_at' => $order->created_at,
        'updated_at' => $order->updated_at,

        // Payment
        'payment_status'       => $order->payment_status,
        'payment_method'       => $order->payment_method,
        'paid_at'              => $order->paid_at,
        'razorpay_order_id'    => $order->razorpay_order_id,
        'razorpay_payment_id'  => $order->razorpay_payment_id,
        'rejected_by' => $order->rejected_by,
        'accepted_at' => $order->accepted_at,

        // Refund
        'refund' => $refundData,

        'user' => $user,
        'chef' => $chef,
    ];

    return CommonHelper::apiResponse(
        200,
        true,
        'Order detail fetched successfully.',
        $data
    );
}
    
}
