<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\RestaurantType;
use Illuminate\Http\Request;

class RestaurantTypeController extends Controller
{
    public function list()
    {
        $restaurants = RestaurantType::orderBy('created_at', 'desc')->get();
        return view('admin.restaurants.list', compact('restaurants'));
    }

    public function create()
    {
        return view('admin.restaurants.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        RestaurantType::create($request->only(['title', 'status']));
        return redirect()->route('restaurants.index')->with('success', 'Restaurant Type added successfully!');
    }

    public function edit($id)
    {
        $restaurant = RestaurantType::findOrFail($id);
        return view('admin.restaurants.form', compact('restaurant'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        $restaurant = RestaurantType::findOrFail($id);
        $restaurant->update($request->only(['title', 'status']));
        return redirect()->route('restaurants.index')->with('success', 'Restaurant Type updated successfully!');
    }

    public function destroy($id)
    {
        $restaurant = RestaurantType::findOrFail($id);
        $restaurant->delete();
        return redirect()->route('restaurants.index')->with('success', 'Restaurant Type deleted successfully!');
    }
}
