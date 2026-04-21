<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class UseAssetsSchema
{
    public function handle(Request $request, Closure $next)
    {
        try {
            DB::statement("SET search_path TO assets, public");
        } catch (\Throwable $e) {
            // no-op: fall back to default search_path
        }
        return $next($request);
    }
}
