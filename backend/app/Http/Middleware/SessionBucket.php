<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SessionBucket
{
    /**
     * Usage: session.bucket:lrwsis (or micro/open)
     * Minimal: itakda lang ang session path sa /app para kumapit ang cookies ng SPA.
     */
    public function handle(Request $request, Closure $next, string $bucket = null)
    {
        if (in_array($bucket, ["lrwsis","micro","open"], true)) {
            config([
                "session.path"   => "/app",
                "session.secure" => true,
            ]);
        }
        return $next($request);
    }
}
