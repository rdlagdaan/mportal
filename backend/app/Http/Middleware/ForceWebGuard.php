<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ForceWebGuard
{
    public function handle(Request $request, Closure $next)
    {
        Auth::shouldUse('web'); // evaluate permissions against "web"
        return $next($request);
    }
}
