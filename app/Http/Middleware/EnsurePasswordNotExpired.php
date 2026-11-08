<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordNotExpired
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($user->must_change_password || $user->pending_session_choice_id)) {
            if (! $request->routeIs('password.setup*') && ! $request->routeIs('logout')) {
                return redirect()->route('password.setup');
            }
        }

        return $next($request);
    }
}
