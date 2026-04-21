<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Mobile\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * Store or update a device token for a user.
     */
    public function store(Request $request)
{
    $validated = $request->validate([
        'user_id'      => 'required|exists:users,id',
        'device_token' => 'required|string|max:255',
    ]);

    try {
        // Optional: remove previous device token(s) for this user
        DeviceToken::where('user_id', $validated['user_id'])->delete();

        // Save the new device token
        $token = DeviceToken::create([
            'user_id'      => $validated['user_id'],
            'device_token' => $validated['device_token'],
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Device token registered successfully',
            'data'    => $token,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Failed to register device token',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

}