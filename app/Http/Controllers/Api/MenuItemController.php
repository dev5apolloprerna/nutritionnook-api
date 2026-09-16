<?php

namespace App\Http\Controllers\Api;

use App\Models\MenuItem;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use App\Http\Controllers\Controller;

class MenuItemController extends Controller
{
   public function index()
    {
        // $items = MenuItem::all();
        $items = MenuItem::whereNull('deleted_at')->where('status', 'active')->get();
        // return response()->json($items);
        return CommonHelper::apiResponse(201, true, 'Menu Items Fetched successfully!', $items);
    }
}
