<?php

namespace App\Http\Controllers\Api;

use App\Models\ChefAddress;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use App\Models\ChefServingArea;
use App\Http\Controllers\Controller;

class ChefAddressAndServingAreaController extends Controller
{
    public function storeMainAddress(Request $request)
    {
        $user = auth()->user();

        // Only allow users with role title as chef (case-insensitive)
        if (!$user->role || !in_array(strtolower($user->role->title), ['chef', 'chefs'])) {
            return CommonHelper::apiResponse(
                403,
                false,
                'You are not allowed to add an address. Please log in with a Chef account if you wish to add a Chef address.',
                null
            );
        }


        $request->validate([
            'full_address' => 'required|string',
            'pincode'      => 'required|string',
            'latitude'     => 'required|string',
            'longitude'    => 'required|string',
        ]);

        $address = ChefAddress::create([
            'user_id'      => auth()->id(),
            'full_address' => $request->full_address,
            'pincode'      => $request->pincode,
            'latitude'     => $request->latitude,
            'longitude'    => $request->longitude,
            // 'is_default'   => $request->is_default ?? false,
        ]);

        return CommonHelper::apiResponse(200, true, 'Chef Address Saved successfully!', $address);
    }

    // List Chef Addresses
    public function listChefAddresses()
    {
        $user = auth()->user();

        $addresses = ChefAddress::where('user_id', $user->id)->get();

        return CommonHelper::apiResponse(200, true, 'Chef Addresses fetched successfully!', $addresses);
    }

    // Update Chef Address
    public function updateChefAddress(Request $request)
    {
        $request->validate([
            'id'           => 'required|exists:chef_addresses,id',
            'full_address' => 'required|string',
            'pincode'      => 'required|string',
            'latitude'     => 'required|string',
            'longitude'    => 'required|string',
        ]);

        $user = auth()->user();

        if (!$user->role || !in_array(strtolower($user->role->title), ['chef', 'chefs'])) {
            return CommonHelper::apiResponse(
                403,
                false,
                'You are not allowed to update chef address. Please log in with a Chef account.',
                null
            );
        }

        $address = ChefAddress::where('id', $request->id)->where('user_id', $user->id)->first();

        if (!$address) {
            return CommonHelper::apiResponse(404, false, 'Chef address not found.', null);
        }

        $address->update([
            'full_address' => $request->full_address,
            'pincode'      => $request->pincode,
            'latitude'     => $request->latitude,
            'longitude'    => $request->longitude,
        ]);

        return CommonHelper::apiResponse(200, true, 'Chef Address updated successfully!', $address);
    }

    // Delete Chef Address
    public function deleteChefAddress(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:chef_addresses,id',
        ]);

        $user = auth()->user();

        if (!$user->role || !in_array(strtolower($user->role->title), ['chef', 'chefs'])) {
            return CommonHelper::apiResponse(
                403,
                false,
                'You are not authorized to delete this address. Only chefs can perform this action.',
                null
            );
        }

        $address = ChefAddress::where('id', $request->id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$address) {
            return CommonHelper::apiResponse(404, false, 'Address not found!', null);
        }

        $address->delete();

        return CommonHelper::apiResponse(200, true, 'Chef Address deleted successfully!', null);
    }



    public function storeServingAreas(Request $request)
    {
        $user = auth()->user();

        // Only allow users with role title as chef (case-insensitive)
        if (!$user->role || !in_array(strtolower($user->role->title), ['chef', 'chefs'])) {
            return CommonHelper::apiResponse(
                403,
                false,
                'You are not allowed to add a serving area. Please log in with a Chef account if you wish to add a Chef serving area.',
                null
            );
        }

        $request->validate([
            'area' => 'required|string',
            'pincode' => 'required|string',
            'latitude'     => 'required|string',
            'longitude'    => 'required|string',
        ]);

        $address = ChefServingArea::create([
            'user_id'      => auth()->id(),
            'area' => $request->area,
            'pincode'      => $request->pincode,
            'latitude'     => $request->latitude,
            'longitude'    => $request->longitude,
        ]);

        return CommonHelper::apiResponse(200, true, 'Chef Serving Area Saved successfully!', $address);
    }

    // List Serving Areas
    public function listServingAreas()
    {
        $user = auth()->user();

        $areas = ChefServingArea::where('user_id', $user->id)->get();

        return CommonHelper::apiResponse(200, true, 'Chef Serving Areas fetched successfully!', $areas);
    }

    // Update Serving Area
    public function updateServingArea(Request $request)
    {
        $request->validate([
            'id'      => 'required|exists:chef_serving_areas,id',
            'area'    => 'required|string',
            'pincode' => 'required|string',
            'latitude'     => 'required|string',
            'longitude'    => 'required|string',
        ]);

        $user = auth()->user();

        $servingArea = ChefServingArea::where('id', $request->id)->where('user_id', $user->id)->first();

        if (!$servingArea) {
            return CommonHelper::apiResponse(404, false, 'Serving area not found.', null);
        }

        $servingArea->update([
            'area'    => $request->area,
            'pincode' => $request->pincode,
            'latitude'     => $request->latitude,
            'longitude'    => $request->longitude,
        ]);

        return CommonHelper::apiResponse(200, true, 'Serving Area updated successfully!', $servingArea);
    }

    // Delete Serving Area
    public function deleteServingArea(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:chef_serving_areas,id',
        ]);

        $user = auth()->user();

        if (!$user->role || !in_array(strtolower($user->role->title), ['chef', 'chefs'])) {
            return CommonHelper::apiResponse(
                403,
                false,
                'You are not authorized to delete this address. Only chefs can perform this action.',
                null
            );
        }

        $servingArea = ChefServingArea::where('id', $request->id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$servingArea) {
            return CommonHelper::apiResponse(404, false, 'Address not found!', null);
        }

        $servingArea->delete();

        return CommonHelper::apiResponse(200, true, 'Serving Area deleted successfully!', null);
    }
}
