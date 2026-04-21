<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceBucketCookie
{
    /**
     * Minimal: siguraduhing /app ang session cookie path kahit anong route order.
     */
    public function handle(Request $request, Closure $next)
    {
        config([
            "session.path"   => "/app",
            "session.secure" => true,
        ]);
        return $next($request);
    }
}
