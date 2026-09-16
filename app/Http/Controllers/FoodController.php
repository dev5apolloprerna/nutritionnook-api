<?php

namespace App\Http\Controllers;

use App\Models\Food;
use Illuminate\Http\Request;

class FoodController extends Controller
{
    public function index()
    {
        $foods = Food::latest()->get();
        return view('admin.foods.list', compact('foods'));
    }

    public function create()
    {
        return view('admin.foods.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric',
            'discount_price' => 'nullable|numeric',
            'image' => 'required|image',
            'is_veg' => 'required|boolean',
            'spicy_level' => 'required|in:low,medium,high',
            'status' => 'required|in:active,inactive',
        ]);

        $data = $request->except('image');

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images'), $imageName);
            $data['image'] = 'images/' . $imageName;
        }

        Food::create($data);
        return redirect()->route('foods.index')->with('success', 'Food added successfully!');
    }

    public function edit(Food $food)
    {
        return view('admin.foods.form', compact('food'));
    }

    public function update(Request $request, Food $food)
    {
        $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric',
            'discount_price' => 'nullable|numeric',
            'image' => 'required|image',
            'is_veg' => 'required|boolean',
            'spicy_level' => 'required|in:low,medium,high',
            'status' => 'required|in:active,inactive',
        ]);

        $data = $request->except('image');

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images'), $imageName);
            $data['image'] = 'images/' . $imageName;
        }

        $food->update($data);
        return redirect()->route('foods.index')->with('success', 'Food updated successfully!');
    }

    public function destroy(Food $food)
    {
        $food->delete();
        return redirect()->route('foods.index')->with('success', 'Food deleted successfully!');
    }
}
