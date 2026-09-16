<?php

namespace App\Http\Controllers\Api;

use App\Models\UserAddress;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use App\Http\Controllers\Controller;

class UserAddressController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'full_address' => 'nullable|string',
            'pincode'      => 'nullable|string',
            'latitude'     => 'nullable|string',
            'longitude'    => 'nullable|string',
            'house_number'  => 'nullable|string',
            'floor'         => 'nullable|string',
            'building_name' => 'nullable|string',
            'tag'           => 'nullable|string', // restrict tags
        ]);

        $user = auth()->user();

        if ($user->role && in_array(strtolower($user->role->title), ['chef', 'chefs'])) {
            return CommonHelper::apiResponse(403, false, 'Chef is not allowed to add address.', null);
        }

         // Pehle check karo ki user ke addresses hai ya nahi
        $hasExisting = UserAddress::where('user_id', $user->id)->exists();
    
        if ($hasExisting) {
            // Sab purane addresses ka is_selected = 0 kar do
            UserAddress::where('user_id', $user->id)->update(['is_selected' => 0]);
        }


        $address = UserAddress::create([
            'user_id'      => auth()->id(),
            'full_address' => $request->full_address,
            'pincode'      => $request->pincode,
            // 'is_selected'  => !$hasExisting,
            'latitude'     => $request->latitude,
            'longitude'    => $request->longitude,
            'house_number'  => $request->house_number,
            'floor'         => $request->floor,
            'building_name' => $request->building_name,
            'tag'           => $request->tag, // default home
            'is_selected'   => 1,
        ]);

        return CommonHelper::apiResponse(200, true, 'Address saved successfully!', $address);
    }


    public function listUserAddresses()
    {
        $userId = auth()->id(); // Logged-in user ka ID

        $addresses = UserAddress::where('user_id', $userId)->get();

        return CommonHelper::apiResponse(200, true, 'User addresses fetched successfully!', $addresses);
    }

   public function update(Request $request)
{
    $request->validate([
        'id'            => 'required|exists:user_addresses,id',
        'full_address'  => 'nullable|string',
        'pincode'       => 'nullable|string',
        'is_selected'   => 'nullable|boolean',
        'latitude'      => 'nullable|string',
        'longitude'     => 'nullable|string',
        'house_number'  => 'nullable|string',
        'floor'         => 'nullable|string',
        'building_name' => 'nullable|string',
        'tag'           => 'nullable|string',
    ]);

    $user = auth()->user();

    // âœ… Check if user is a chef
    if ($user->role && in_array(strtolower($user->role->title), ['chef', 'chefs'])) {
        return CommonHelper::apiResponse(403, false, 'Chef is not allowed to update address.', null);
    }

    $address = UserAddress::where('id', $request->id)
        ->where('user_id', $user->id)
        ->firstOrFail();

    // âœ… Update address fields
    $address->full_address  = $request->full_address;
    $address->pincode       = $request->pincode;
    $address->latitude      = $request->latitude;
    $address->longitude     = $request->longitude;
    $address->house_number  = $request->house_number;
    $address->floor         = $request->floor;
    $address->building_name = $request->building_name;
    $address->tag           = $request->tag;

    // âœ… If user sets this as default (is_selected = true), then others ko false karo
    if ($request->filled('is_selected') && $request->is_selected) {
        UserAddress::where('user_id', $user->id)->update(['is_selected' => false]);
        $address->is_selected = true;
    }

    $address->save();

    // âœ… Response me sirf required keys
    $responseData = $address->only([
        'user_id',
        'full_address',
        'pincode',
        'latitude',
        'longitude',
        'house_number',
        'floor',
        'building_name',
        'tag',
        'is_selected',
        'updated_at',
        'created_at',
        'id',
    ]);

    return CommonHelper::apiResponse(200, true, 'Address updated successfully!', $responseData);
}


   public function delete(Request $request)
{
    $request->validate([
        'id' => 'required|exists:user_addresses,id',
    ]);

    $userId = auth()->id();

    $address = UserAddress::where('id', $request->id)
        ->where('user_id', $userId)
        ->first();

    if (!$address) {
        return CommonHelper::apiResponse(404, false, 'Address not found!', null);
    }

    $wasSelected = $address->is_selected; // check karo selected tha ya nahi
    $address->delete();

    if ($wasSelected) {
        // koi dusra address find karo (sabse latest le lete hai)
        $anotherAddress = UserAddress::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->first();

        if ($anotherAddress) {
            $anotherAddress->update(['is_selected' => 1]);
        }
    }

    return CommonHelper::apiResponse(200, true, 'Address deleted successfully!', null);
}


    public function selectAddress(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:user_addresses,id',
        ]);

        $user = auth()->user();

        // Check if address belongs to this user
        $address = UserAddress::where('id', $request->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$address) {
            return CommonHelper::apiResponse(404, false, 'Address not found!', null);
        }

        // Unselect all addresses of this user
        UserAddress::where('user_id', $user->id)->update(['is_selected' => false]);

        // Select the chosen one
        $address->is_selected = true;
        $address->save();

        return CommonHelper::apiResponse(200, true, 'Address selected successfully!', $address);
    }
    
   public function getSelectedAddress(Request $request)
{
    $user = auth()->user();


    $token = $request->bearerToken();

    if ($token) {
        $accessToken = PersonalAccessToken::findToken($token);

        if ($accessToken) {
            $user = $accessToken->tokenable; // 👈 logged-in user
        }
    }
    
    $defaultLat = env('DEFAULT_LAT', 22.9952);
    $defaultLng = env('DEFAULT_LNG', 72.6041);

    /**
     * ðŸ”¥ Case 1 â€” Guest user
     */
    if (!$user) {
        $responseData = [
            'id'            => null,
            'user_id'       => null,
            'full_address'  => 'Chandkheda, Ahmedabad, Gujarat 382424, India',
            'pincode'       => null,
            'latitude'      => $defaultLat,
            'longitude'     => $defaultLng,
            'house_number'  => null,
            'floor'         => null,
            'building_name' => null,
            'tag'           => 'default',
            'is_selected'   => true,
            'created_at'    => null,
            'updated_at'    => null,
        ];

        return CommonHelper::apiResponse(
            200,
            true,
            'Default address fetched successfully!',
            $responseData
        );
    }

    /**
     * ðŸ”¥ Case 2 â€” Logged-in user
     */
    $address = UserAddress::where('user_id', $user->id)
        ->where('is_selected', true)
        ->first();

    /**
     * ðŸ”¥ Case 3 â€” User logged in but no selected address
     * (fallback to default â€” production safe)
     */
    if (!$address) {
        $responseData = [
            'id'            => null,
            'user_id'       => $user->id,
            'full_address'  => 'Default Location',
            'pincode'       => null,
            'latitude'      => (float) $defaultLat,
            'longitude'     => (float) $defaultLng,
            'house_number'  => null,
            'floor'         => null,
            'building_name' => null,
            'tag'           => 'default',
            'is_selected'   => true,
            'created_at'    => null,
            'updated_at'    => null,
        ];

        return CommonHelper::apiResponse(
            200,
            true,
            'Default address used (no selected address found).',
            $responseData
        );
    }

    /**
     * ðŸ”¥ Case 4 â€” Real selected address
     */
    $responseData = [
        'id'            => $address->id,
        'user_id'       => $address->user_id,
        'full_address'  => $address->full_address,
        'pincode'       => $address->pincode,
        'latitude'      => (float) $address->latitude,
        'longitude'     => (float) $address->longitude,
        'house_number'  => $address->house_number,
        'floor'         => $address->floor,
        'building_name' => $address->building_name,
        'tag'           => $address->tag,
        'is_selected'   => (bool) $address->is_selected,
        'created_at'    => $address->created_at,
        'updated_at'    => $address->updated_at,
    ];

    return CommonHelper::apiResponse(
        200,
        true,
        'Selected address fetched successfully!',
        $responseData
    );
}

public function getSelectedAddressWithChef(Request $request, $chef_id)
{
    $user = auth()->user();

    $token = $request->bearerToken();

    if ($token) {
        $accessToken = PersonalAccessToken::findToken($token);

        if ($accessToken) {
            $user = $accessToken->tokenable;
        }
    }

    /**
     * 🔥 Chef Fetch
     */
    $chef = \App\Models\Chef::find($chef_id);

    if (!$chef) {
        return CommonHelper::apiResponse(
            404,
            false,
            'Chef not found!',
            null
        );
    }

    /**
     * 🔥 Chef Details
     */
    $chefData = [
        'chef_id'        => $chef->id,
        'chef_name'      => $chef->name,
        'kitchen_name'   => $chef->kitchen_name,
        'full_address'   => $chef->address,
        'pincode'        => $chef->pincode,
        'latitude'       => (float) $chef->latitude,
        'longitude'      => (float) $chef->longitude,
        'floor'          => $chef->floor,
        'building_name'  => $chef->building_name,
        'city'           => $chef->city,
        'phone_number'   => $chef->phone_number,
    ];

    $defaultLat = env('DEFAULT_LAT', 22.9952);
    $defaultLng = env('DEFAULT_LNG', 72.6041);

    /**
     * 🔥 CASE 1:
     * Guest User
     */
    if (!$user) {

        $responseData = [
            'id'            => null,
            'user_id'       => null,
            'full_address'  => 'Chandkheda, Ahmedabad, Gujarat 382424, India',
            'pincode'       => null,
            'latitude'      => (float) $defaultLat,
            'longitude'     => (float) $defaultLng,
            'house_number'  => null,
            'floor'         => null,
            'building_name' => null,
            'tag'           => 'default',
            'is_selected'   => true,
            'created_at'    => null,
            'updated_at'    => null,

            // 🔥 Chef Details
            'chef_details'  => $chefData,
        ];

        return CommonHelper::apiResponse(
            200,
            true,
            'Default address fetched successfully!',
            $responseData
        );
    }

    /**
     * 🔥 CASE 4:
     * Real Selected Address + Chef Details
     */
    $responseData = [
        'id'            => $chef->id,
        // 'user_id'       => $address->user_id,
        'full_address'  => $chef->address,
        'pincode'       => $chef->pincode,
        'latitude'      => (float) $chef->latitude,
        'longitude'     => (float) $chef->longitude,
        // 'house_number'  => $address->house_number,
        'floor'         => $chef->floor,
        'building_name' => $chef->building_name
    ];

    return CommonHelper::apiResponse(
        200,
        true,
        'Selected address with chef fetched successfully!',
        $responseData
    );
}


}
