<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Category;
use App\Models\FoodItem;
use App\Models\MealTime;
use App\Models\Preference;
use App\Models\CuisineType;
use Illuminate\Http\Request;

class FoodItemController extends Controller
{
    public function index()
    {
        $foodItems = FoodItem::with(['user', 'categories', 'cuisineTypes'])->orderBy('created_at', 'desc')->get();
        return view('admin.food-items.list', compact('foodItems'));
    }

    public function create()
    {
        $foodItem = null;
        // $users = User::where('user_role', 'chef')->get();
        $users = User::whereHas('role', function ($query) {
            $query->whereRaw('LOWER(title) LIKE ?', ['%chef%']);
        })->get();
        $categories = Category::whereNull('deleted_at')->get();
        $cuisineTypes = CuisineType::whereNull('deleted_at')->get();
        $preferences = Preference::whereNull('deleted_at')->get();
        $mealTimes = MealTime::whereNull('deleted_at')->get();
        return view('admin.food-items.form', compact('foodItem', 'users', 'categories', 'cuisineTypes', 'preferences', 'mealTimes'));
    }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'user_id' => 'required|exists:users,id',
    //         'name' => 'required|string',
    //         'description' => 'required|string',
    //         'image' => 'required|image',
    //         'price' => 'required|numeric',
    //         'discount' => 'nullable|numeric',
    //         'availability' => 'required|boolean',
    //         'category_ids' => 'required|array',
    //         'cuisine_type_ids' => 'required|array',
    //         'preference_ids' => 'required|array',
    //         'meal_time_ids' => 'required|array',
    //     ]);

    //     $imagePath = null;
    //     if ($request->hasFile('image')) {
    //         $image = $request->file('image');
    //         $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
    //         $image->move(public_path('images'), $imageName);
    //         $imagePath = 'images/' . $imageName;
    //     }

    //     $foodItem = FoodItem::create([
    //         'user_id' => $request->user_id,
    //         'name' => $request->name,
    //         'description' => $request->description,
    //         'image' => $imagePath,
    //         'price' => $request->price,
    //         'discount' => $request->discount,
    //         'availability' => $request->availability,
    //     ]);

    //     $foodItem->categories()->sync($request->category_ids);
    //     $foodItem->cuisineTypes()->sync($request->cuisine_type_ids);
    //     $foodItem->preferences()->sync($request->preference_ids);
    //     $foodItem->mealTimes()->sync($request->meal_time_ids);

    //     return redirect()->route('food-items.index')->with('success', 'Food item created successfully.');
    // }
    
    public function store(Request $request)
    {
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'name' => 'required|string',
        'description' => 'nullable|string',
        'image' => 'required|image',
        'price' => 'required|numeric',
        'category_id' => 'required|exists:categories,id',
        'preparation_time_id' => 'required|exists:preparation_times,id',
        'weight_option_id' => 'required|exists:weight_options,id',
        'ingredients' => 'nullable|string',
        'allergy_warning' => 'nullable|string',
        'spicy_level' => 'nullable|integer',
        'cuisine_type_id' => 'required|exists:cuisine_type,id',
        'is_get_now_or_get_later' => 'required|in:get_now,get_later,both',
        'tags' => 'nullable|string', // ids as comma separated
    ]);

    // Image upload
    $imagePath = null;
    if ($request->hasFile('image')) {
        $image = $request->file('image');
        $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
        $image->move(public_path('images'), $imageName);
        $imagePath = 'public/images/' . $imageName;
    }

    $foodItem = FoodItem::create([
        'chef_id' => $request->user_id,
        'name' => $request->name,
        'description' => $request->description,
        'image' => $imagePath,
        'price' => $request->price,
        'category_id' => $request->category_id,
        'preparation_time_id' => $request->preparation_time_id,
        'weight_option_id' => $request->weight_option_id,
        'ingredients' => $request->ingredients,
        'allergy_warning' => $request->allergy_warning,
        'is_active' => 0,
        'spicy_level' => $request->spicy_level,
        'cuisine_type_id' => $request->cuisine_type_id,
        'is_get_now_or_get_later' => $request->is_get_now_or_get_later,
        'tags' => $request->tags,
    ]);

    return redirect()->route('food-items.index')->with('success', 'Food item created successfully.');
}

    public function edit($id)
    {
        $foodItem = FoodItem::findOrFail($id);
        // $users = User::where('user_role', 'chef')->get();
        $users = User::whereHas('role', function ($query) {
            $query->whereRaw('LOWER(title) LIKE ?', ['%chef%']);
        })->get();
        $categories = Category::whereNull('deleted_at')->get();
        $cuisineTypes = CuisineType::whereNull('deleted_at')->get();
        $preferences = Preference::whereNull('deleted_at')->get();
        $mealTimes = MealTime::whereNull('deleted_at')->get();
        return view('admin.food-items.form', compact('foodItem', 'users', 'categories', 'cuisineTypes', 'preferences', 'mealTimes'));
    }

    public function update(Request $request, FoodItem $foodItem)
    {
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'name' => 'required|string',
        'description' => 'nullable|string',
        'image' => 'nullable|image',
        'price' => 'required|numeric',
        'category_id' => 'required|exists:categories,id',
        'preparation_time_id' => 'required|exists:preparation_times,id',
        'weight_option_id' => 'required|exists:weight_options,id',
        'ingredients' => 'nullable|string',
        'allergy_warning' => 'nullable|string',
        'spicy_level' => 'nullable|integer',
        'cuisine_type_id' => 'required|exists:cuisine_type,id',
        'is_get_now_or_get_later' => 'required|in:get_now,get_later,both',
        'tags' => 'nullable|string',
    ]);

    if ($request->hasFile('image')) {
        $image = $request->file('image');
        $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
        $image->move(public_path('images'), $imageName);
        $foodItem->image = 'public/images/' . $imageName;
    }

    $foodItem->update([
        'chef_id' => $request->user_id,
        'name' => $request->name,
        'description' => $request->description,
        'price' => $request->price,
        'category_id' => $request->category_id,
        'preparation_time_id' => $request->preparation_time_id,
        'weight_option_id' => $request->weight_option_id,
        'ingredients' => $request->ingredients,
        'allergy_warning' => $request->allergy_warning,
        'spicy_level' => $request->spicy_level,
        'cuisine_type_id' => $request->cuisine_type_id,
        'is_get_now_or_get_later' => $request->is_get_now_or_get_later,
        'tags' => $request->tags,
    ]);

    return redirect()->route('food-items.index')->with('success', 'Food item updated successfully.');
}


    public function destroy(FoodItem $foodItem)
    {
        $foodItem->delete();
        return redirect()->route('food-items.index')->with('success', 'Food item deleted successfully.');
    }
}
