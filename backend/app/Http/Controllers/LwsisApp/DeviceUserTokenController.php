<?php

namespace App\Http\Controllers\LwsisApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LwsisApp\DeviceUserToken;
use App\Models\LwsisApp\User;
use Illuminate\Validation\ValidationException;

class DeviceUserTokenController extends Controller
{
    /**
     * Store or update a device token (AUTH-BASED)
     */
    public function store(Request $request)
{
    $userId = $request->attributes->get('auth_user_id');

    if (!$userId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized',
        ], 401);
    }

    $validated = $request->validate([
        'device_token' => 'required|string|max:255',
    ]);

    try {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
            ], 404);
        }

        $token = DeviceUserToken::updateOrCreate(
            [
                'user_id' => $userId,
                'device_token' => $validated['device_token'],
            ],
            []
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Device token registered successfully',
            'data' => $token,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to register device token',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    /**
     * Optional: Remove a specific device token (logout use-case)
     */
    public function destroy(Request $request)
    {
        try {
            $userId = $request->attributes->get('auth_user_id');

            if (!$userId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }

            $validated = $request->validate([
                'device_token' => 'required|string'
            ]);

            DeviceUserToken::where('user_id', $userId)
                ->where('device_token', $validated['device_token'])
                ->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Device token removed successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to remove device token',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}