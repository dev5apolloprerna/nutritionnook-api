<?php

namespace App\Services;

use App\Models\UserDevice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Google\Client as GoogleClient;

class FCMService
{
    public function sendNotificationToUser($userId, $title, $body, $data = [])
    {
        // Get devices with platform
        $devices = UserDevice::where('user_id', $userId)
            ->select('fcm_token','platform')
            ->get();

        if ($devices->isEmpty()) {
            Log::warning("FCM: No tokens found for User ID: {$userId}");
            return ['success' => false, 'message' => 'No tokens found'];
        }

        // Get Access Token once
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'message' => 'Failed to get Access Token'];
        }

        $responses = [];

        foreach ($devices as $device) {
            $responses[] = $this->sendToSingleToken(
                $device->fcm_token,
                $device->platform,
                $accessToken,
                $title,
                $body,
                $data
            );
        }

        return ['success' => true, 'results' => $responses];
    }

    private function sendToSingleToken($token, $platform, $accessToken, $title, $body, $data)
    {
        $projectId = config('services.firebase.project_id');

        if(empty($projectId)) {
            $serviceAccount = json_decode(file_get_contents(storage_path('app/firebase/service-account.json')), true);
            $projectId = $serviceAccount['project_id'];
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        // Convert data to string
        $stringData = array_map(fn($value) => (string)$value, $data);

        $imageUrl = $data['food_image'] ?? $data['logo'] ?? $data['image'] ?? null;

        $message = [
            'token' => $token,
            'data'  => $stringData,
        ];

        /*
        |--------------------------------------------------------------------------
        | Android Notification
        |--------------------------------------------------------------------------
        */
        if ($platform === 'android') {

            // $message['notification'] = [
            //     'title' => $title,
            //     'body'  => $body,
            //     'image' => $imageUrl,
            // ];

            $message['android'] = [
                'priority' => 'high',
                // 'notification' => [
                //     'image' => $imageUrl,
                // ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | iOS Notification
        |--------------------------------------------------------------------------
        */
        if ($platform === 'ios') {

            // $message['notification'] = [
            //     'title' => $title,
            //     'body'  => $body,
            // ];

            $message['apns'] = [
                'headers' => [
                    'apns-priority' => '10',
                    'apns-push-type' => 'alert',
                ],
                'payload' => [
                    'aps' => [
                        'alert' => [
                            'title' => $title,
                            'body'  => $body,
                        ],
                        'sound' => 'default',
                        'mutable-content' => 1,
                    ],
                    'image' => $imageUrl,
                ],
                'fcm_options' => [
                    'image' => $imageUrl,
                ],
            ];
        }

        $payload = ['message' => $message];

        try {
            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->timeout(5)
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info("FCM Sent to {$token}");
                return ['token' => $token, 'status' => 'sent'];
            } else {
                Log::error("FCM Fail: " . $response->body());
                return ['token' => $token, 'status' => 'failed', 'error' => $response->json()];
            }

        } catch (\Exception $e) {
            Log::error("FCM Exception: " . $e->getMessage());
            return ['token' => $token, 'status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function getAccessToken()
    {
        $cached = Cache::get('fcm_access_token');
        if ($cached) {
            return $cached;
        }

        try {
            $client = new GoogleClient();
            $client->setHttpClient(new \GuzzleHttp\Client(['timeout' => 5, 'connect_timeout' => 5]));
            $client->setAuthConfig(storage_path('app/firebase/service-account.json'));
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $client->refreshTokenWithAssertion();
            $token = $client->getAccessToken();

            $accessToken = $token['access_token'] ?? null;

            // Cache it so concurrent/rapid push sends don't each pay for a fresh
            // Google OAuth round-trip. Only cache on success — a failure must
            // never get "stuck" cached and silently block pushes until it expires.
            if ($accessToken) {
                $ttl = max(60, ($token['expires_in'] ?? 3600) - 300);
                Cache::put('fcm_access_token', $accessToken, $ttl);
            }

            return $accessToken;
        } catch (\Exception $e) {
            Log::error("FCM Token Error: " . $e->getMessage());
            return null;
        }
    }
}