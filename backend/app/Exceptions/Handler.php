<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        "current_password",
        "password",
        "password_confirmation",
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        //
    }

    /**
     * For unauthenticated requests under /app/api/*, return 401 JSON
     * instead of redirecting to a non-existent "login" route.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        $path = ltrim($request->path(), "/");
        if ($request->expectsJson() || str_starts_with($path, "app/api")) {
            return response()->json(["message" => "Unauthenticated"], 401);
        }
        // Optional: send browsers to SPA login (you can change this path)
        return redirect()->guest("/app/login");
    }
}
