<?php

namespace App\Http\Controllers\LwsisApp\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LwsisApp\UserDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Events\ForceLogoutEvent;
use App\Models\LwsisApp\DeviceUserToken;
class AdminUserDeviceController extends Controller
{

public function resetDevice($userId)
{   
    try {
        DB::transaction(function () use ($userId) {

            //  1. DELETE DEVICE
            UserDevice::where('user_id', $userId)->delete();

            //  2. DELETE AUTH TOKENS
            // DB::table('iam.api_tokens')
            //     ->where('user_id', $userId)
            //     ->delete();

            //  3. DELETE PUSH TOKENS
            DeviceUserToken::where('user_id', $userId)->delete();
        });

        //  ONLY RUN IF TRANSACTION SUCCESS
        event(new ForceLogoutEvent($userId));

        return response()->json([
            'status' => 'success',
            'message' => 'Device reset + user logged out'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Nothing was changed (rollback triggered)',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function getUsers()
{
    try {
        $users = \DB::table('iam.users as u')
    ->leftJoin('mobile.user_devices as d', function ($join) {
        $join->on('u.id', '=', 'd.user_id')
             ->where('d.is_active', true);
    })
    ->select(
        'u.id',
        'u.email',
        'u.name',
        'u.user_type',
        'u.is_active',
        'd.device_id',
        'd.device_name',
        'd.created_at as device_registered_at'
    )
    ->orderBy('u.created_at', 'desc')
    ->get();

        return response()->json([
            'status' => 'success',
            'users' => $users
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to fetch users',
            'error' => $e->getMessage()
        ], 500);
    }
}
}