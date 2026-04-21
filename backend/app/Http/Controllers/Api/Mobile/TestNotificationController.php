<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Mobile\DeviceToken;
use App\Helpers\NotificationHelper;

class TestNotificationController extends Controller
{
    public function send()
    {
        try {
            // 🟢 Get all Expo device tokens
            $tokens = DeviceToken::pluck('device_token')->toArray();

            if (empty($tokens)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No device tokens found. Please open the mobile app to register token.',
                ], 404);
            }

            // 🟢 Send test push
            NotificationHelper::sendPushAndSave(
                $tokens,
                '🔔 Test Notification',
                'This is a test push sent from the Laravel backend.',
                'test',
                ['test' => true]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Test notification sent!',
                'tokens' => $tokens
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to send test notification',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
