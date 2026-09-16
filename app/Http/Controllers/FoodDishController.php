<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Chef;
use App\Models\FoodDish;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\CuisineType;
use App\Models\Tag;

class FoodDishController extends Controller
{
    public function create(Request $request)
    {
        $chef_id = $request->query('chef_id');
        $categories = Category::where('status', 'active')->pluck('title', 'id');
         $cuisines = CuisineType::where('status', 'active')->pluck('title', 'id');
         $tags = Tag::pluck('title', 'id');

        return view('admin.food_dishes.form', compact('chef_id', 'categories','cuisines','tags'));
    }


public function store(Request $request)
{
    $request->validate([
        'chef_id'           => 'required|exists:chefs,id',
        'name'              => 'required|string|max:255',
        'price'             => 'required|numeric',
        'spicy_level'       => 'required',
        'quantity'          => 'required|numeric',
        'unit'              => 'required|string',
        'prep_minutes'      => 'nullable|integer',
        'prep_seconds'      => 'nullable|integer',
        'description'       => 'required|string',
        'images'            => 'required',
        'images.*'          => 'image|mimes:jpeg,png,jpg,gif',
        // 'is_active'         => 'boolean',
        'category_id'       => 'required|array|min:1',
        'category_id.*'     => 'exists:categories,id',
        'cuisine_type_id'   => 'required|exists:cuisine_type,id',
        'ingredients'       => 'nullable|string',
        'allergy_warning'   => 'nullable|string',
        'availability'      => 'nullable|string',
        'tag_id'            => 'nullable|array',
    ]);

    $data = $request->only([
        'chef_id','name','price','spicy_level','description',
        'ingredients','allergy_warning','cuisine_type_id'
    ]);

    // ✅ Quantity + Unit combine
    $data['weight_option_id'] = $request->quantity . $request->unit;

    // ✅ Preparation time (e.g. 15min 30sec)
    if ($request->prep_minutes || $request->prep_seconds) {
        $min = $request->prep_minutes ? $request->prep_minutes . "min" : "";
        $sec = $request->prep_seconds ? $request->prep_seconds . "sec" : "";
        $data['preparation_time_id'] = trim($min . " " . $sec);
    }

    // ✅ Categories (comma separated string)
    $data['category_id'] = implode(',', $request->category_id);

    // ✅ Tags (comma separated string)
    if ($request->filled('tag_id')) {
        $data['tags'] = implode(',', $request->tag_id);
    }
    
    // ✅ Availability mapping
    if ($request->availability == 'now') {
        $data['is_get_now_or_get_later'] = 'get_now';
    } elseif ($request->availability == 'later') {
        $data['is_get_now_or_get_later'] = 'get_later';
    } else {
        $data['is_get_now_or_get_later'] = 'both';
    }

    // ✅ Images Upload
    $images = [];
    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $image) {
            $imageName = time() . '_' . uniqid() . '_' . preg_replace('/\s+/', '_', $image->getClientOriginalName());
            $image->move(public_path('images'), $imageName);
            $images[] = 'public/images/' . $imageName;
        }
    }
    $data['image'] = count($images) === 1 ? $images[0] : json_encode($images, JSON_UNESCAPED_SLASHES);

    FoodDish::create($data);

    return redirect()->route('chefs.details', $request->chef_id)->with('success', 'Dish created successfully.');
}


public function edit(FoodDish $foodDish)
{
    $categories = Category::where('status', 'active')->pluck('title', 'id');
    $selectedCategories = $foodDish->category_id ? explode(',', $foodDish->category_id) : [];
    $cuisines = CuisineType::where('status', 'active')->pluck('title', 'id');
    $tags = Tag::pluck('title', 'id');
    $selectedTags = $foodDish->tags ? explode(',', $foodDish->tags) : [];

    // 🔹 Quantity + Unit
    $quantity = $unit = null;
    if ($foodDish->weight_option_id) {
        preg_match('/(\d+)([a-zA-Z]+)/', $foodDish->weight_option_id, $matches);
        $quantity = $matches[1] ?? null;
        $unit = $matches[2] ?? null;
    }

    // 🔹 Preparation time
    $prep_minutes = $prep_seconds = null;
    if ($foodDish->preparation_time_id) {
        preg_match('/(?:(\d+)min)?\s*(?:(\d+)sec)?/', $foodDish->preparation_time_id, $matches);
        $prep_minutes = $matches[1] ?? null;
        $prep_seconds = $matches[2] ?? null;
    }

    return view('admin.food_dishes.form', [
        'dish' => $foodDish,
        'categories' => $categories,
        'selectedCategories' => $selectedCategories,
        'chef_id' => $foodDish->chef_id,
        'cuisines' => $cuisines,
        'tags' => $tags,
        'selectedTags' => $selectedTags,
        'quantity' => $quantity,
        'unit' => $unit,
        'prep_minutes' => $prep_minutes,
        'prep_seconds' => $prep_seconds,
    ]);
}


  
public function update(Request $request, FoodDish $foodDish)
{
    $request->validate([
        'name'              => 'required|string|max:255',
        'price'             => 'required|numeric',
        'spicy_level'       => 'required',
        'quantity'          => 'required|numeric',
        'unit'              => 'required|string',
        'prep_minutes'      => 'nullable|integer',
        'prep_seconds'      => 'nullable|integer',
        'description'       => 'required|string',
        'images.*'          => 'nullable|image|mimes:jpeg,png,jpg,gif',
        // 'is_active'         => 'boolean',
        'category_id'       => 'required|array|min:1',
        'category_id.*'     => 'exists:categories,id',
        'cuisine_type_id'   => 'required|exists:cuisine_type,id',
        'ingredients'       => 'nullable|string',
        'allergy_warning'   => 'nullable|string',
        'is_get_now_or_get_later' => 'required|in:get_now,get_later,both',
        'tag_id'            => 'nullable|array',
    ]);

    $data = $request->only([
        'name','price','spicy_level','description',
        'ingredients','allergy_warning','cuisine_type_id'
    ]);

    // ✅ Quantity + Unit
    $data['weight_option_id'] = $request->quantity . $request->unit;

    // ✅ Preparation time
    if ($request->prep_minutes || $request->prep_seconds) {
        $min = $request->prep_minutes ? $request->prep_minutes . "min" : "";
        $sec = $request->prep_seconds ? $request->prep_seconds . "sec" : "";
        $data['preparation_time_id'] = trim($min . " " . $sec);
    }

    // ✅ Categories
    $data['category_id'] = implode(',', $request->category_id);

    // ✅ Tags
    if ($request->filled('tag_id')) {
        $data['tags'] = implode(',', $request->tag_id);
    } else {
        $data['tags'] = null;
    }
    
    // ✅ Availability mapping
    $data['is_get_now_or_get_later'] = $request->is_get_now_or_get_later;



    // ✅ Existing Images
    $existingImages = [];
    if ($foodDish->image) {
        $decoded = json_decode($foodDish->image, true);
        $existingImages = json_last_error() === JSON_ERROR_NONE ? $decoded : [$foodDish->image];
    }

    // ✅ Delete selected images
    if ($request->filled('delete_images')) {
        $toDelete = json_decode($request->delete_images, true);
        foreach ($toDelete as $img) {
            if (file_exists(public_path(str_replace('public/', '', $img)))) {
                unlink(public_path(str_replace('public/', '', $img)));
            }
            $existingImages = array_filter($existingImages, fn($i) => $i !== $img);
        }
    }

    // ✅ Add new images
    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $image) {
            $imageName = time() . '_' . uniqid() . '_' . preg_replace('/\s+/', '_', $image->getClientOriginalName());
            $image->move(public_path('images'), $imageName);
            $existingImages[] = 'public/images/' . $imageName;
        }
    }

    $data['image'] = count($existingImages) === 1
        ? array_values($existingImages)[0]
        : json_encode(array_values($existingImages), JSON_UNESCAPED_SLASHES);

    $foodDish->update($data);

    return redirect()->route('chefs.details', $foodDish->chef_id)->with('success', 'Dish updated successfully.');
}


    public function destroy(FoodDish $foodDish)
    {
        if ($foodDish->image) {
            Storage::disk('public')->delete($foodDish->image);
        }

        $chefId = $foodDish->chef_id;

        $foodDish->categories()->detach();
        $foodDish->delete();

        return redirect()->route('chefs.details', $chefId)->with('success', 'Dish deleted successfully.');
    }

   public function toggleActive(Request $request, $id) {
    $dish = FoodDish::findOrFail($id);

    // in_stock toggle (0 <-> 1)
    $dish->in_stock = $dish->in_stock ? 0 : 1;
    $dish->save();

    return response()->json([
        'success' => true,
        'message' => $dish->in_stock ? 'Dish is now In Stock' : 'Dish is now Out of Stock',
        'new_status' => $dish->in_stock
    ]);
}

public function toggleRecommended(Request $request, $id)
{
    $dish = FoodDish::findOrFail($id);

    // Toggle recommended status
    $dish->is_recommended = !$dish->is_recommended;
    $dish->save();

    return response()->json([
        'success' => true,
        'message' => 'Dish recommendation status updated successfully.',
        'new_status' => $dish->is_recommended
    ]);
}



}
