<?php

namespace App\Http\Controllers\Api;

use App\Models\Banner;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use App\Http\Controllers\Controller;

class BannerController extends Controller
{
    public function index()
    {
        $items = Banner ::where('status', 'active')->get();
        // return response()->json($items);
        return CommonHelper::apiResponse(201, true, 'Banners Fetched successfully!', $items);
    }
}
