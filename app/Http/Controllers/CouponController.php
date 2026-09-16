<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;
use App\Models\FoodDish;
use App\Models\Chef;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::orderBy('created_at', 'desc')->get();
        return view('admin.coupons.list', compact('coupons'));
    }

    public function create()
    {
        $foodDishes = FoodDish::pluck('name', 'id');
        $chefs = Chef::pluck('kitchen_name', 'id');
        return view('admin.coupons.form', compact('foodDishes', 'chefs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'coupon_name' => 'required|string|unique:coupons,code',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:1',
            'min_order_value' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit_per_user' => 'nullable|integer',
            'usage_limit' => 'nullable|integer',
            // 'valid_hours' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,inactive',
            // 'food_dish_id' => 'nullable|exists:food_dishes,id',
            // 'chef_id' => 'nullable|exists:chefs,id',
            'description' => 'nullable|string'
        ]);

        Coupon::create([
            'code' => $request->coupon_name,
            'type' => $request->type,
            'value' => $request->value,
            'min_order_value' => $request->min_order_value,
            'max_discount_amount' => $request->max_discount_amount,
            'usage_limit_per_user' => $request->usage_limit_per_user,
            'usage_limit' => $request->usage_limit,
            // 'valid_hours' => $request->valid_hours,
            'starts_at' => $request->start_date,
            'expires_at' => $request->end_date,
            'is_active' => $request->status === 'active' ? 1 : 0,
            // 'food_dish_id' => $request->food_dish_id,
            // 'chef_id' => $request->chef_id,
            'description' => $request->description,
             'created_by' => auth()->id(),
        ]);

        return redirect()->route('coupons.index')->with('success', 'Coupon added successfully!');
    }

    public function edit($id)
    {
        $coupon = Coupon::findOrFail($id);
        $foodDishes = FoodDish::pluck('name', 'id');
        $chefs = Chef::pluck('kitchen_name', 'id');
        return view('admin.coupons.form', compact('coupon','foodDishes', 'chefs'));
    }
    
    public function show($id){
        
    }
    
    public function update(Request $request, $id)
{
    
   
    $request->validate([
        'coupon_name' => 'required|string|unique:coupons,code,' . $id,
        'type' => 'required|in:percentage,fixed',
        'value' => 'required|numeric|min:1',
        'min_order_value' => 'nullable|numeric|min:0',
        'max_discount_amount' => 'nullable|numeric|min:0',
        'usage_limit_per_user' => 'nullable|integer',
        'usage_limit' => 'nullable|integer',
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'status' => 'required|in:active,inactive',
        'description' => 'nullable|string',
        // 'created_by' => auth()->id(), // This line might also cause issues
    ]);

    $coupon = Coupon::findOrFail($id);
    
 
    $coupon->update([
        'code' => $request->coupon_name, // Changed from 'code' to 'coupon_name'
        'type' => $request->type,
        'value' => $request->value,
        'min_order_value' => $request->min_order_value,
        'max_discount_amount' => $request->max_discount_amount,
        'usage_limit_per_user' => $request->usage_limit_per_user,
        'usage_limit' => $request->usage_limit,
        'starts_at' => $request->start_date,
        'expires_at' => $request->end_date,
        'is_active' => $request->status === 'active' ? 1 : 0,
        'description' => $request->description,
    ]);

    return redirect()->route('coupons.index')->with('success', 'Coupon updated successfully!');
}


    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        return redirect()->route('coupons.index')->with('success', 'Coupon deleted successfully!');
    }

    public function getDishes($chef_id)
    {
        $dishes = FoodDish::where('chef_id', $chef_id)->pluck('name', 'id');
        return response()->json($dishes);
    }
}
