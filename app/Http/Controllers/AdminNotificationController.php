<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\UserDevice;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminNotificationController extends Controller
{
    public function create()
    {
        $chefs = DB::table('chefs')
            ->whereNull('deleted_at')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $customers = DB::table('users')
            ->where('user_role', 5)
            ->whereNull('deleted_at')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return view('admin.notifications.create', compact('chefs', 'customers'));
    }

    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'user_type' => 'required|in:chef,customer,both',
            'send_to_all' => 'nullable|boolean',
            'chef_ids' => 'nullable|array',
            'customer_ids' => 'nullable|array',
            'image_url' => 'nullable|url|max:500',
            'click_action' => 'nullable|string|max:255',
            'custom_data' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $userType = $request->input('user_type');
        $sendToAll = $request->boolean('send_to_all');
        $customData = [];

        if ($request->filled('custom_data')) {
            $decoded = json_decode($request->input('custom_data'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return redirect()->back()->with('error', 'Custom Data must be valid JSON.')->withInput();
            }
            $customData = $decoded;
        }

        $userIds = $this->resolveUserIds($userType, $sendToAll, $request);

        if (empty($userIds)) {
            return redirect()->back()->with('error', 'No users found for the selected criteria.')->withInput();
        }

        // Get devices with platform information
        $devices = UserDevice::whereIn('user_id', $userIds)
            ->select('fcm_token', 'platform')
            ->get();

        if ($devices->isEmpty()) {
            return redirect()->back()->with('error', 'No device tokens found for the selected users.')->withInput();
        }

        $data = [
            'type' => 'custom_admin_notification',
            'title' => $request->input('title'),
            'body' => $request->input('body'),
        ];

        if ($request->filled('click_action')) {
            $data['click_action'] = $request->input('click_action');
        }

        if ($request->filled('image_url')) {
            $data['image'] = $request->input('image_url');
        }

        foreach ($customData as $key => $value) {
            $data[$key] = (string) $value;
        }

        $fcmService = new FCMService();
        $accessToken = $this->getAccessTokenFromFCM($fcmService);

        if (!$accessToken) {
            return redirect()->back()->with('error', 'Failed to authenticate with Firebase. Check service account.')->withInput();
        }

        $successCount = 0;
        $failedCount = 0;
        $failedTokens = [];

        foreach ($devices as $device) {
            $result = $this->sendSingleNotification(
                $fcmService, 
                $accessToken, 
                $device->fcm_token,
                $device->platform,
                $request->input('title'), 
                $request->input('body'),
                $data, 
                $request->input('image_url')
            );

            if ($result['status'] === 'sent') {
                $successCount++;
            } else {
                $failedCount++;
                $failedTokens[] = ['token' => substr($device->fcm_token, 0, 20) . '...', 'platform' => $device->platform, 'error' => $result['error'] ?? 'Unknown'];
            }
        }

        $selectedIds = [];
        if (!$sendToAll) {
            if (in_array($userType, ['chef', 'both'])) {
                $selectedIds['chef_ids'] = $request->input('chef_ids', []);
            }
            if (in_array($userType, ['customer', 'both'])) {
                $selectedIds['customer_ids'] = $request->input('customer_ids', []);
            }
        }

        AdminNotification::create([
            'title' => $request->input('title'),
            'body' => $request->input('body'),
            'user_type' => $userType,
            'send_to_all' => $sendToAll,
            'user_ids' => !$sendToAll ? $selectedIds : null,
            'image_url' => $request->input('image_url'),
            'click_action' => $request->input('click_action'),
            'custom_data' => !empty($customData) ? $customData : null,
            'total_tokens' => count($devices),
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'failed_tokens' => !empty($failedTokens) ? $failedTokens : null,
            'sent_by' => Auth::id(),
        ]);

        return redirect()->route('admin.notifications.history')
            ->with('success', "Notification sent! Success: {$successCount}, Failed: {$failedCount}");
    }

    public function history()
    {
        $notifications = AdminNotification::orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.notifications.history', compact('notifications'));
    }

    private function resolveUserIds(string $userType, bool $sendToAll, Request $request): array
    {
        $userIds = [];

        if ($userType === 'chef' || $userType === 'both') {
            if ($sendToAll) {
                $chefIds = DB::table('chefs')->whereNull('deleted_at')->pluck('id')->toArray();
            } else {
                $chefIds = $request->input('chef_ids', []);
            }
            $userIds = array_merge($userIds, $chefIds);
        }

        if ($userType === 'customer' || $userType === 'both') {
            if ($sendToAll) {
                $customerIds = DB::table('users')->where('user_role', 5)->whereNull('deleted_at')->pluck('id')->toArray();
            } else {
                $customerIds = $request->input('customer_ids', []);
            }
            $userIds = array_merge($userIds, $customerIds);
        }

        return array_unique(array_map('intval', $userIds));
    }

    private function getAccessTokenFromFCM(FCMService $fcmService)
    {
        try {
            $reflection = new \ReflectionMethod($fcmService, 'getAccessToken');
            $reflection->setAccessible(true);
            return $reflection->invoke($fcmService);
        } catch (\Throwable $e) {
            Log::error("Admin Notification - FCM auth error: " . $e->getMessage());
            return null;
        }
    }

    private function sendSingleNotification($fcmService, $accessToken, $token, $platform, $title, $body, $data, $imageUrl = null)
    {
        $projectId = config('services.firebase.project_id');
        if (empty($projectId)) {
            $serviceAccount = json_decode(file_get_contents(storage_path('app/firebase/service-account.json')), true);
            $projectId = $serviceAccount['project_id'];
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $stringData = array_map(fn($v) => (string) $v, $data);

        $message = [
            'token' => $token,
            'data'  => $stringData,
        ];

        // Platform-specific notification configuration
        if (strtolower($platform) === 'ios') {
            // iOS configuration (APNs)
            $message['apns'] = [
                'headers' => [
                    'apns-priority' => '5',
                    'apns-push-type' => 'background',
                ],
                'payload' => [
                    'aps' => [
                       'content-available' => 1,
                    ],
                ],
            ];
        } else {
            // Android configuration (and default)
            $message['android'] = [
                'priority' => 'high',

            ];
        }

        $payload = ['message' => $message];

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            if ($response->successful()) {
                return ['status' => 'sent'];
            } else {
                $errorBody = $response->json();
                $errorCode = $errorBody['error']['details'][0]['errorCode'] ?? '';

                if ($errorCode === 'UNREGISTERED') {
                    UserDevice::where('fcm_token', $token)->delete();
                }

                return ['status' => 'failed', 'error' => $errorCode ?: $response->body()];
            }
        } catch (\Exception $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }
    
//     private function sendSingleNotification($fcmService, $accessToken, $token, $platform, $title, $body, $data, $imageUrl = null)
// {
//     $projectId = config('services.firebase.project_id');
//     if (empty($projectId)) {
//         $serviceAccount = json_decode(file_get_contents(storage_path('app/firebase/service-account.json')), true);
//         $projectId = $serviceAccount['project_id'];
//     }

//     $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

//     // Convert all data to strings
//     $stringData = array_map(fn($v) => (string) $v, $data);
    
//     // Add platform info to data so app knows how to handle it
//     $stringData['platform'] = $platform;
//     $stringData['title'] = $title;
//     $stringData['body'] = $body;
    
//     if ($imageUrl) {
//         $stringData['image_url'] = $imageUrl;
//     }

//     // Create pure data message - NO notification block at all
//     $message = [
//         'token' => $token,
//         'data' => $stringData,
//     ];

//     // Add ONLY platform-specific configurations for delivery, NOT for notification display
//     if (strtolower($platform) === 'ios') {
//         // iOS specific delivery settings only - no notification payload
//         $message['apns'] = [
//             'headers' => [
//                 'apns-priority' => '10',
//                 'apns-push-type' => 'background', // Use background for data-only
//             ],
//             'payload' => [
//                 'aps' => [
//                     'content-available' => 1, // Important for background delivery
//                 ],
//             ],
//         ];
        
//         // If you want the notification to be shown, the app must create it from data
//         // Don't include any alert/badge/sound in aps
//     } else {
//         // Android specific delivery settings only - no notification payload
//         $message['android'] = [
//             'priority' => 'high',
//             // Don't include notification object here
//         ];
//     }

//     $payload = ['message' => $message];

//     try {
//         $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
//             ->withHeaders(['Content-Type' => 'application/json'])
//             ->post($url, $payload);

//         if ($response->successful()) {
//             return ['status' => 'sent'];
//         } else {
//             $errorBody = $response->json();
//             $errorCode = $errorBody['error']['details'][0]['errorCode'] ?? '';

//             if ($errorCode === 'UNREGISTERED') {
//                 UserDevice::where('fcm_token', $token)->delete();
//             }

//             return ['status' => 'failed', 'error' => $errorCode ?: $response->body()];
//         }
//     } catch (\Exception $e) {
//         return ['status' => 'error', 'error' => $e->getMessage()];
//     }
// }
}