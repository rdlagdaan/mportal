<?php
namespace App\Http\Controllers\Auth\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mobile\DeviceToken;
use App\Models\Mobile\PersonalAccessToken;
use Illuminate\Support\Facades\Log;

class LogoutController extends Controller
{
    public function destroy(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
        ]);

        $userId = $request->user_id;

        // 1️⃣ Delete all device tokens for this user
        $deletedDevices = DeviceToken::where('user_id', $userId)->forceDelete();
        Log::info('Deleted device tokens', ['user_id' => $userId, 'deleted' => $deletedDevices]);

        // 2️⃣ Delete all personal access tokens for this user
        $deletedTokens = PersonalAccessToken::where('tokenable_id', $userId)->forceDelete();
        Log::info('Deleted personal access tokens', ['user_id' => $userId, 'deleted' => $deletedTokens]);

        return response()->json([
            'status'  => 'success',
            'message' => '✅ Logged out successfully, all tokens deleted permanently',
        ]);
    }
}