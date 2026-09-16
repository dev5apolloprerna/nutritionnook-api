<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use App\Services\FCMService; // Import the service
use App\Helpers\CommonHelper;
use DB;

class NotificationController extends Controller
{
    protected $fcmService;

    // Inject the service
    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    public function sendNotification(Request $request)
    {
        $request->validate([
            'chef_id' => 'required',
            'order_id' => 'required',
            'customer_name' => 'required',
        ]);

        $title = 'New Order Received 🍽️';
        $body = "You have a new order from {$request->customer_name}";
        $data = [
            'type' => 'new_order',
            'orderId' => $request->order_id,
            'customerName' => $request->customer_name,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ];

        $result = $this->fcmService->sendNotificationToUser($request->chef_id, $title, $body, $data);

        return CommonHelper::apiResponse(200, true, 'Process completed', $result);
    }
    
   
public function saveFcmToken(Request $request)
{
    $user = auth()->user();

    // 1. Validation
    $request->validate([
        'fcm_token' => 'required|string',
        'platform'  => 'required|string|in:android,ios,web', // Added validation for platform values
    ]);

    try {
        DB::beginTransaction();
        
        $now = now();
        $userId = $user->id;
        $fcmToken = $request->fcm_token;
        $platform = $request->platform;

        // 2. First, check if this token already exists for ANY user
        $existingToken = DB::table('user_devices')
            ->where('fcm_token', $fcmToken)
            ->first();

        if ($existingToken) {
            // If token exists for a different user, delete it first
            DB::table('user_devices')
                ->where('fcm_token', $fcmToken)
                ->delete();
        }

        // 3. Remove existing tokens for this user + platform combination
        DB::table('user_devices')
            ->where('user_id', $userId)
            ->where('platform', $platform)
            ->delete();

        // 4. Also remove any duplicate tokens for this user
        DB::table('user_devices')
            ->where('user_id', $userId)
            ->where('fcm_token', $fcmToken)
            ->delete();

        // 5. Insert fresh token
        $deviceId = DB::table('user_devices')->insertGetId([
            'user_id'    => $userId,
            'fcm_token'  => $fcmToken,
            'platform'   => $platform,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 6. Fetch the inserted record
        $device = DB::table('user_devices')
            ->select('id', 'user_id', 'fcm_token', 'platform', 'created_at', 'updated_at')
            ->where('id', $deviceId)
            ->first();

        DB::commit();

        // Format the response data
        $responseData = [
            'id'         => $device->id,
            'user_id'    => $device->user_id,
            'fcm_token'  => $device->fcm_token,
            'platform'   => $device->platform,
            'created_at' => $device->created_at,
            'updated_at' => $device->updated_at,
        ];

        return CommonHelper::apiResponse(
            200,
            true,
            'FCM token saved successfully.',
            $responseData
        );

    } catch (\Exception $e) {
        DB::rollBack();
        
        \Log::error('Save FCM Token Error', [
            'user_id' => $user->id ?? null,
            'token'   => $request->fcm_token ?? null,
            'error'   => $e->getMessage(),
            'trace'   => $e->getTraceAsString()
        ]);
        
        return CommonHelper::apiResponse(
            500,
            false,
            'Failed to save FCM token.',
            ['error' => $e->getMessage()]
        );
    }
}

    
}