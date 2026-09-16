<?php

namespace App\Http\Controllers\Api;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use App\Http\Controllers\Controller;

class RestaurantController extends Controller
{
    public function index()
    {
        $data = Restaurant::all();

        return CommonHelper::apiResponse(200, true, 'All Restaurant fetched successfully!', $data);
    }

    public function show($id)
    {
        $restaurant = Restaurant::with([
            'mealTimes.foods.preferences',
            'mealTimes.foods.variants',
            'mealTimes.foods.addons',
            'mealTimes.foods.offers',
            'categories',
            'cuisines',
            'preferences',
            'offers'
        ])->find($id);

        if (!$restaurant) {
            return response()->json(['message' => 'Restaurant not found'], 404);
        }

        // return response()->json($restaurant);
        return CommonHelper::apiResponse(200, true, 'Restaurant details fetched successfully!', $restaurant);
    }
}
