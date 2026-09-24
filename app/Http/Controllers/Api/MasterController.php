<?php

namespace App\Http\Controllers\Api;

use App\Models\Role;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\Coupon;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;
use App\Models\CuisineType;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;


class MasterController extends Controller
{
    public function addRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors()
            ], 422);
        }

        $role = Role::create($request->all());

        return CommonHelper::apiResponse(201, true, 'Role added successfully!', $role);
    }

    public function listRole()
    {
        // $data = ExamBoard::all();

        $data = Role::whereNull('deleted_at')->get();

        return CommonHelper::apiResponse(200, true, 'Role fetched successfully!', $data);
    }

    public function editRole(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'id'     => 'required|exists:roles,id',
            'title'  => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        // Handle validation errors
        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'success' => false,
                'message' => 'Validation error',
                'data'    => $validator->errors()
            ], 422);
        }

        // Fetch and update exam board
        $role = Role::find($request->id);
        $role->update([
            'title'  => $request->title,
            'status' => $request->status,
        ]);

        return CommonHelper::apiResponse(200, true, 'Role updated successfully!', $role);
    }

    public function deleteRole(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors(),
            ], 422);
        }

        // Find and soft delete the role
        $role = Role::find($request->id);
        $role->delete(); // Soft delete

        // return response()->json([
        //     'status' => 200,
        //     'success' => true,
        //     'message' => 'Role deleted successfully!',
        //     'data' => null,
        // ]);
        return CommonHelper::apiResponse(200, true, 'Role deleted successfully!', null);
    }
    
    public function addCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors()
            ], 422);
        }

        $category = Category::create($request->all());

        return CommonHelper::apiResponse(201, true, 'Category added successfully!', $category);
    }

    public function listCategory()
{
    $data = Category::whereNull('deleted_at')->get();

    // Append full path to image
    $data->transform(function ($item) {
        if ($item->image) {
            $item->image = url('public/images/' . $item->image); 
            // this will generate http://thinkdream.in/food/public/images/xxxx.png
        }
        return $item;
    });

    return CommonHelper::apiResponse(200, true, 'Category fetched successfully!', $data);
}


    public function editCategory(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'id'     => 'required|exists:categories,id',
            'title'  => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        // Handle validation errors
        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'success' => false,
                'message' => 'Validation error',
                'data'    => $validator->errors()
            ], 422);
        }

        // Fetch and update exam board
        $category = Category::find($request->id);
        $category->update([
            'title'  => $request->title,
            'status' => $request->status,
        ]);

        return CommonHelper::apiResponse(200, true, 'Category updated successfully!', $category);
    }

    public function deleteCategory(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors(),
            ], 422);
        }

        // Find and soft delete the role
        $category = Category::find($request->id);
        $category->delete(); // Soft delete

        // return response()->json([
        //     'status' => 200,
        //     'success' => true,
        //     'message' => 'Role deleted successfully!',
        //     'data' => null,
        // ]);
        return CommonHelper::apiResponse(200, true, 'Category deleted successfully!', null);
    }
    
    // public function addCuisineType(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'title' => 'required|string',
    //         'status' => 'required|in:active,inactive',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => 422,
    //             'success' => false,
    //             'message' => 'Validation error',
    //             'data' => $validator->errors()
    //         ], 422);
    //     }

    //     $cuisineType = CuisineType::create($request->all());

    //     return CommonHelper::apiResponse(201, true, 'CuisineType added successfully!', $cuisineType);
    // }
    
public function listCuisinChefType()
{
 $data = CuisineType::whereNull('deleted_at')->get();

    // Image ka full path add karna
    $data->transform(function ($item) {
        if (!empty($item->image)) {
            $item->image = asset($item->image);
        } 
        return $item;
    });

    return CommonHelper::apiResponse(200, true, 'Cuisine types found!', $data);   
}

public function listChefType(Request $request)
{
    // Cuisine types are master/reference data (a fixed taxonomy), not something
    // that should vary by the customer's location or nearby chef availability —
    // always return every active cuisine type, regardless of geo/location.
    $data = CuisineType::whereNull('deleted_at')->get();

    // Image full path
    $data->transform(function ($item) {
        $item->image = $item->image ? asset($item->image) : null;
        return $item;
    });

    return CommonHelper::apiResponse(
        200,
        true,
        'Cuisine types found!',
        $data
    );
}


public function listTags()
{
    $data = Tag::whereNull('deleted_at')->get();


    return CommonHelper::apiResponse(200, true, 'Tags found!', $data);
}

    // public function editCuisineType(Request $request)
    // {
    //     // Validate request data
    //     $validator = Validator::make($request->all(), [
    //         'id'     => 'required|exists:cuisine_type,id',
    //         'title'  => 'required|string',
    //         'status' => 'required|in:active,inactive',
    //     ]);

    //     // Handle validation errors
    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status'  => 422,
    //             'success' => false,
    //             'message' => 'Validation error',
    //             'data'    => $validator->errors()
    //         ], 422);
    //     }

    //     // Fetch and update exam board
    //     $cuisineType = CuisineType::find($request->id);
    //     $cuisineType->update([
    //         'title'  => $request->title,
    //         'status' => $request->status,
    //     ]);

    //     return CommonHelper::apiResponse(200, true, 'CuisineType updated successfully!', $cuisineType);
    // }

    // public function deleteCuisineType(Request $request)
    // {
    //     // Validate the request
    //     $validator = Validator::make($request->all(), [
    //         'id' => 'required|exists:cuisine_type,id',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => 422,
    //             'success' => false,
    //             'message' => 'Validation error',
    //             'data' => $validator->errors(),
    //         ], 422);
    //     }

    //     // Find and soft delete the role
    //     $cuisineType = CuisineType::find($request->id);
    //     $cuisineType->delete(); // Soft delete

    //     // return response()->json([
    //     //     'status' => 200,
    //     //     'success' => true,
    //     //     'message' => 'Role deleted successfully!',
    //     //     'data' => null,
    //     // ]);
    //     return CommonHelper::apiResponse(200, true, 'CuisineType deleted successfully!', null);
    // }

    public function listCouponCode()
    {
        // $data = ExamBoard::all();

        $data = Coupon::whereNull('deleted_at')->get();

        return CommonHelper::apiResponse(200, true, 'Coupon fetched successfully!', $data);
    }
    
  public function getSettings()
{
    $data = \DB::table('settings')
        ->select('platform_fee')
        ->first();

    if (!$data) {
        return CommonHelper::apiResponse(404, false, 'Settings not found!', null);
    }

    return CommonHelper::apiResponse(200, true, 'Platform fee fetched successfully!', $data);
}
public function getAvailableDates()
{
    $dates = [];
    $today = Carbon::today();

    // Get Later starts tomorrow: return the next seven dates and never today.
    for ($i = 1; $i <= 7; $i++) {
        $date = $today->copy()->addDays($i);
        $dates[] = [
            'id'    => $date->toDateString(),   // e.g. 2025-09-05
            'label' => $date->format('D, M j')  // e.g. Fri, Sep 5
        ];
    }

    return CommonHelper::apiResponse(200, true, 'Upcoming dates found!', $dates);
}



}
