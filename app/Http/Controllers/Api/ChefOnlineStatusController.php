<?php

namespace App\Http\Controllers\Api;

use App\Models\Role;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use App\Models\ChefOnlineStatus;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ChefOnlineStatusController extends Controller
{
    
public function getChefCategory()
{
    $user = auth()->user(); // logged-in chef
    $chefId = $user->id;

    $categories = \DB::table('categories')
        ->orderBy('id', 'desc')
        ->get()
        ->map(function ($category) {
            if (!empty($category->image)) {
                // âœ… Convert filename into full URL
                $category->image = asset('/images/' . $category->image);
            }
            return $category;
        });

    if ($categories->isNotEmpty()) {
        return CommonHelper::apiResponse(200, true, 'Chef Category found.', $categories);
    } else {
        return CommonHelper::apiResponse(404, false, 'Chef record not found.', []);
    }
}



  public function updateOpenStatus(Request $request)
{
    $user = Auth::user();

    // 1. Validate input
    $request->validate([
        'available' => 'required|boolean',
    ]);

    // 2. Get chef record
    $chef = \App\Models\Chef::find($user->id);

    if (!$chef) {
        return CommonHelper::apiResponse(404, false, 'Chef record not found.', []);
    }

    // 3. Check verification
    if ($chef->is_verify == 0) {
        return CommonHelper::apiResponse(
            403,
            false,
            'Your account is under review. Verification not completed.',
            []
        );
    }

    // 4. Update available status
    if($request->available == true) {
        $chef->available = 1;
    } else if($request->available == false) {
        $chef->available = 0;
    }
    
    $chef->save();

    return CommonHelper::apiResponse(
        200,
        true,
        'Chef online status updated successfully.',
        $chef
    );
}


public function getChefDetail()
{
    $user = Auth::user();

    // Orders assigned to this chef
    $ordersQuery = \App\Models\Chef::where('id', $user->id)->first();
    if(isset($ordersQuery)){
        return CommonHelper::apiResponse(200, true, 'Chef detail fetch successfully.',[
            'ordersCount'     => $ordersQuery->ordersCount,
            'earnings'        => $ordersQuery->earnings,
            'preparingDishes' => $ordersQuery->preparingDishes,
            'readyDishes'     => $ordersQuery->readyDishes,
            'deliveredDishes' => $ordersQuery->deliveredDishes,
            'tomorrowOrders'  => $ordersQuery->tomorrowOrders,
        ] );
    }else{
        return CommonHelper::apiResponse(404, false, 'Chef record not found.', []);
    }
    // return response()->json();
}

// public function getOrderDetail($id)
// {
//     $ordersQuery = \App\Models\Order::with(['user:id,image,name,email,phone_number,address'])->where('id', $id)->first();
//     if(isset($ordersQuery)){
//         return CommonHelper::apiResponse(200, true, 'order detail fetch successfully.',$ordersQuery);
//     }else{
//         return CommonHelper::apiResponse(404, false, 'order record not found.',[]);
//     }
// }


// public function getOrderDetail($id)
// {
//     $order = \App\Models\Order::with(['user:id,image,name,email,phone_number,address'])
//                 ->where('id', $id)
//                 ->first();

//     if (!$order) {
//         return CommonHelper::apiResponse(404, false, 'order record not found.', []);
//     }

//     // --------------------- Transform Items --------------------- //
//     $itemsArr = $order->items;
//     $finalItems = [];

//     if (is_array($itemsArr)) {
//         foreach ($itemsArr as $item) {

//             // Fetch dish details
//             $dish = DB::table('food_dishes')
//                 ->select('id', 'name', 'price', 'image', 'preparation_time_id')
//                 ->where('id', $item['id'])
//                 ->first();

//             if ($dish) {
//                 $quantity = $item['quantity'];
//                 $total_price = number_format($dish->price * $quantity, 2);

//                 $finalItems[] = [
//                     'id'          => $dish->id,
//                     'name'        => $dish->name,
//                     'price'       => number_format($dish->price, 2),
//                     'quantity'    => $quantity,
//                     'total_price' => $total_price,
//                     'image'       => $dish->image ? asset($dish->image) : null,

//                     // ðŸ‘‰ preparation_time_id only when status = preparing
//                     'preparation_time_id' => $order->status === 'preparing'
//                         ? $dish->preparation_time_id
//                         : null,
//                 ];
//             }
//         }
//     }

//     // Replace items
//     $order->items = $finalItems;

//     return CommonHelper::apiResponse(200, true, 'order detail fetch successfully.', $order);
// }


public function getOrderDetail($id)
{
    $order = \App\Models\Order::with([
            'user:id,image,name,email,phone_number',
            'refund'
        ])
        ->select(
            'id',
            'user_id',
            'items',
            DB::raw("FORMAT(amount, 2) as amount"),
            'status',
            'is_refunded',
            'refunded_at',
            'razorpay_order_id',
            'razorpay_payment_id',
            'payment_status',
            'payment_method',
            'paid_at',
            'date',
            'created_at',
            'updated_at',
            'rejected_by',
            'address'
        )
        ->where('payment_status','received')
        ->where('id', $id)
        ->first();

    if (!$order) {
        return CommonHelper::apiResponse(404, false, 'Order record not found.', []);
    }

    /* ---------------- Address ---------------- */
    $selectedAddress = DB::table('user_addresses')
        ->where('user_id', $order->user_id)
        ->where('is_selected', 1)
        ->select(
            'id','full_address','pincode','house_number',
            'floor','building_name','tag','latitude','longitude'
        )
        ->first();

    $user = DB::table('users')
        ->select('id','image','name','email','phone_number')
        ->where('id', $order->user_id)
        ->first();
        
    $selectedAddress = DB::table('user_addresses')
        ->where('full_address', 'like', '%' . $order->address . '%')
        ->select(
            'id','full_address','pincode','house_number',
            'floor','building_name','tag','latitude','longitude'
        )
        ->first();
        
    if ($order->user) {
        $order->user->selected_address = $selectedAddress;
    }

    /* ---------------- Items ---------------- */
    $itemsArr = $order->items;
    $finalItems = [];

    if (is_array($itemsArr)) {
        foreach ($itemsArr as $item) {

            $dish = DB::table('food_dishes')
                ->select('id','name','price','image','preparation_time_id')
                ->where('id', $item['id'])
                ->first();

            if ($dish) {
                $quantity = $item['quantity'];

                $finalItems[] = [
                    'id'          => $dish->id,
                    'name'        => $dish->name,
                    'price'       => number_format($dish->price, 2, '.', ''),
                    'quantity'    => $quantity,
                    'total_price' => number_format($dish->price * $quantity, 2, '.', ''),
                    'image'       => $dish->image ? asset($dish->image) : null,
                    'preparation_time_id' =>
                        $order->status === 'preparing'
                            ? $dish->preparation_time_id
                            : null,
                ];
            }
        }
    }

    $order->items = $finalItems;

    /* ---------------- Refund ---------------- */
    if ($order->refund) {
        $order->refund = [
            'id' => $order->refund->id,
            'refund_amount' => number_format($order->refund->refund_amount, 2, '.', ''),
            'status' => $order->refund->status,
            'razorpay_refund_id' => $order->refund->razorpay_refund_id,
            'reason' => $order->refund->reason,
            'gateway_response' => $order->refund->gateway_response,
            'created_at' => $order->refund->created_at,
            'updated_at' => $order->refund->updated_at,
        ];
    }

    /* -------------------------------------------------
     | ✅ NEW: CALCULATION (LIKE YOUR REFERENCE API)
     ------------------------------------------------- */

    $setting = DB::table('settings')->first();

    $subTotal = 0;

    if (is_array($itemsArr)) {
        foreach ($itemsArr as $item) {

            $dish = DB::table('food_dishes')
                ->select('price')
                ->where('id', $item['id'])
                ->first();

            if ($dish) {
                $subTotal += ((float)$dish->price * (int)$item['quantity']);
            }
        }
    }

    // ✅ Platform Fee
    $platformFee = (float) ($setting->platform_fee ?? 0);

    // ✅ GST %
    $gstPercent = 5;

    // ✅ GST Calculation (ONLY ON SUBTOTAL)
    $gstAmount = ($subTotal * $gstPercent) / 100;

    // ✅ FIX: amount is formatted string → convert properly
    $orderAmount = (float) str_replace(',', '', $order->amount);

    // ✅ Discount
    $discountAmount = ($subTotal + $platformFee + $gstAmount) - $orderAmount;
    $discountAmount = $discountAmount > 0 ? $discountAmount : 0;

    /* ---------------- ADD RESPONSE FIELDS ---------------- */

    $order->platform_fee    = (int) $platformFee;
    $order->gst_amount      = round($gstAmount);
    $order->discount_amount = (float) round($discountAmount);

    return CommonHelper::apiResponse(
        200,
        true,
        'Order detail fetched successfully.',
        $order
    );
}





}
