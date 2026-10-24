<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPasswordHasChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && is_null(auth()->user()->password_set_at)) {
            // Allow only the 'change-password' endpoint
            if (! $request->is('api/user/set-initial-password')) {
                return response()->json(['message' => 'PASSWORD_CHANGE_REQUIRED'], 403);
            }
        }

        return $next($request);
    }
}
