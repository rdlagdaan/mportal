<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function index(Request $req)
    {
        return $req->user()->unreadNotifications()->latest()->limit(30)->get();
    }

    public function markRead(Request $req, $id)
    {
        $n = $req->user()->notifications()->findOrFail($id);
        $n->markAsRead();
        return response()->noContent();
    }

    public function markAllRead(Request $req)
    {
        $req->user()->unreadNotifications->markAsRead();
        return response()->noContent();
    }
}
