<?php

namespace App\Http\Controllers\Api;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CouponController extends Controller
{

public function listCoupons(Request $request)
{
    $now = Carbon::now();

    $coupons = \DB::table('coupons')
        ->where('is_active', 1)

        // Remove expired coupons
        ->where(function ($query) use ($now) {
            $query->whereNull('expires_at')
                  ->orWhere('expires_at', '>=', $now);
        })

        // Remove already applied coupons
        ->whereNotIn('id', function ($query) {
            $query->select('coupon_id')
                ->from('orders')
                ->whereNotNull('coupon_id');
        })

        ->orderBy('id', 'desc')
        ->get();

    if ($coupons->isEmpty()) {
        return CommonHelper::apiResponse(200, true, 'No coupons found', []);
    }

    $data = $coupons->map(function ($coupon) {

        // Label logic
        $label = '';
        if ($coupon->type === 'percentage') {
            $label = $coupon->value . '% OFF';
        } elseif ($coupon->type === 'fixed') {
            if (str_contains(strtolower($coupon->description), 'shipping')) {
                $label = 'FREE SHIPPING';
            } else {
                $label = '₹' . intval($coupon->value) . ' OFF';
            }
        }

        // Title logic
        $title = "Save ₹" . intval($coupon->value) . " on this order!";

        // Description logic
        $desc = $coupon->description;
        if ($coupon->min_order_value > 0) {
            $desc .= " Minimum order: ₹" . intval($coupon->min_order_value) . ".";
        }
        if ($coupon->max_discount_amount) {
            $desc .= " Maximum discount: ₹" . intval($coupon->max_discount_amount) . ".";
        }

        return [
            'id'                  => $coupon->id,
            'code'                => $coupon->code,
            'label'               => $label,
            'title'               => $title,
            'action_text'         => 'APPLY',
            'description'         => $desc,
            'min_order_value'     => $coupon->min_order_value,
            'max_discount_amount' => $coupon->max_discount_amount,
            'validity'            => [
                'starts_at'  => $coupon->starts_at,
                'expires_at' => $coupon->expires_at,
            ],
        ];
    });

    return CommonHelper::apiResponse(200, true, 'Coupons fetched successfully', $data);
}




// public function applyCoupon(Request $request)
// {
//     $request->validate([
//         'order_id' => 'required|string',
//         'order_amount' => 'required|numeric|min:1',
//         'coupon_code' => 'required|string'
//     ]);

//     // Token se user nikalna
//     $user = Auth::user();
//     if (!$user) {
//         return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
//     }
//     $userId = $user->id;

//     $coupon = \DB::table('coupons')
//         ->where('code', $request->coupon_code)
//         ->first();

//     if (!$coupon) {
//         return response()->json(['status' => false, 'message' => 'Invalid coupon code']);
//     }

//     // ✅ baaki saara code same rahega, bas yahan use karo:
//     $userUsage = \DB::table('coupon_usages')
//         ->where('coupon_id', $coupon->id)
//         ->where('user_id', $userId)
//         ->count();

//     if ($coupon->usage_limit_per_user && $userUsage >= $coupon->usage_limit_per_user) {
//         return response()->json(['status' => false, 'message' => 'You have already used this coupon']);
//     }

//     // Discount Calculation
//     $discount = 0;
//     if ($coupon->type == 'percentage') {
//         $discount = ($request->order_amount * $coupon->value) / 100;
//         if ($coupon->max_discount_amount) {
//             $discount = min($discount, $coupon->max_discount_amount);
//         }
//     } else {
//         $discount = $coupon->value;
//     }

//     $finalAmount = max(0, $request->order_amount - $discount);

//     // Save usage
//     \DB::table('coupon_usages')->insert([
//         'coupon_id' => $coupon->id,
//         'user_id'   => $userId,
//         'order_id'  => $request->order_id,
//         'discount_amount' => $discount,
//         'created_at' => now(),
//         'updated_at' => now(),
//     ]);

//     // Update usage count
//     \DB::table('coupons')
//         ->where('id', $coupon->id)
//         ->increment('usage_count');

//     return response()->json([
//         'status' => true,
//         'message' => 'Coupon applied successfully',
//         'discount' => $discount,
//         'final_amount' => $finalAmount
//     ]);
// }


// public function applyCoupon(Request $request)
// {
//     $request->validate([
//         'order_amount' => 'required|numeric',
//         'coupon_code'  => 'required|string'
//     ]);

//     $user = Auth::user();
//     if (!$user) {
//         return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
//     }
//     $userId = $user->id;

//     $coupon = \DB::table('coupons')->where('code', $request->coupon_code)->first();

//     if (!$coupon) {
//         return response()->json(['status' => false, 'message' => 'Invalid coupon code']);
//     }

//     // User usage check
//     $userUsage = \DB::table('coupon_usages')
//         ->where('coupon_id', $coupon->id)
//         ->where('user_id', $userId)
//         ->count();

//     if ($coupon->usage_limit_per_user && $userUsage >= $coupon->usage_limit_per_user) {
//         return response()->json(['status' => false, 'message' => 'You have already used this coupon']);
//     }

//     // Discount calculation
//     $discount = 0;
//     if ($coupon->type == 'percentage') {
//         $discount = ($request->order_amount * $coupon->value) / 100;
//         if ($coupon->max_discount_amount) {
//             $discount = min($discount, $coupon->max_discount_amount);
//         }
//     } else {
//         $discount = $coupon->value;
//     }

//     $finalAmount = max(0, $request->order_amount - $discount);

//     // ❌ Is stage par DB me save mat karo
//     // ✅ Sirf response do
//     return response()->json([
//         'status' => true,
//         'message' => 'Coupon applied successfully',
//         'discount' => $discount,
//         'final_amount' => $finalAmount,
//         'coupon_id' => $coupon->id
//     ]);
// }

public function applyCoupon(Request $request)
{
    $request->validate([
        'order_amount' => 'required|numeric|min:1',
        'coupon_code'  => 'required|string'
    ]);

    $user = Auth::user();
    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    $userId      = $user->id;
    $orderAmount = (float) $request->order_amount;
    $now         = now();

    /* ---------- FETCH COUPON ---------- */
    $coupon = DB::table('coupons')
        ->where('code', $request->coupon_code)
        ->where('is_active', 1)
        ->first();

    if (!$coupon) {
        return response()->json([
            'status' => false,
            'message' => 'Invalid coupon code'
        ]);
    }

    /* ---------- DATE VALIDATION ---------- */
    if ($coupon->starts_at && $now->lt($coupon->starts_at)) {
        return response()->json([
            'status' => false,
            'message' => 'Coupon is not active yet'
        ]);
    }

    if ($coupon->expires_at && $now->gt($coupon->expires_at)) {
        return response()->json([
            'status' => false,
            'message' => 'Coupon has expired'
        ]);
    }

    /* ---------- MIN ORDER VALUE CHECK ---------- */
    if ($orderAmount < (float) $coupon->min_order_value) {
        return response()->json([
            'status' => false,
            'message' => 'Minimum order amount should be ₹' . $coupon->min_order_value
        ]);
    }

    /* ---------- USER USAGE LIMIT ---------- */
    if ($coupon->usage_limit_per_user) {
        $userUsage = DB::table('coupon_usages')
            ->where('coupon_id', $coupon->id)
            ->where('user_id', $userId)
            ->count();

        if ($userUsage >= $coupon->usage_limit_per_user) {
            return response()->json([
                'status' => false,
                'message' => 'You have already used this coupon'
            ]);
        }
    }

    /* ---------- DISCOUNT CALCULATION ---------- */
    $discount = 0;

    if ($coupon->type === 'percentage') {

        // percentage discount
        $discount = ($orderAmount * (float) $coupon->value) / 100;

        // max discount cap
        if (!is_null($coupon->max_discount_amount)) {
            $discount = min($discount, (float) $coupon->max_discount_amount);
        }

    } elseif ($coupon->type === 'fixed') {

        // fixed discount
        $discount = (float) $coupon->value;
    }

    // safety check
    $discount    = min($discount, $orderAmount);
    $finalAmount = $orderAmount - $discount;

    /* ---------- RESPONSE ONLY (NO DB SAVE) ---------- */
    return response()->json([
        'status'        => true,
        'message'       => 'Coupon applied successfully',
        'coupon_id'     => $coupon->id,
        'coupon_type'   => $coupon->type,
        'discount'      => (int) round($discount),
        'final_amount'  => (int) round($finalAmount)
    ]);
}


}