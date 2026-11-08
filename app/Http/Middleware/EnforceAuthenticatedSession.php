<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceAuthenticatedSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $user = $user->fresh();
        $temporaryVersion = $request->session()->get('temporary_credential_version');

        if (! $user || ! $user->is_active
            || $request->session()->get('session_generation') !== $user->session_generation
            || ($temporaryVersion !== null && (! $user->must_change_password || $temporaryVersion !== $user->credential_version))
            || ($user->must_change_password && $user->temporary_password_expires_at?->isPast())) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw new AuthenticationException;
        }

        Auth::guard('web')->setUser($user);

        if (($user->must_change_password || $user->pending_session_choice_id)
            && ! $request->routeIs('password.setup*', 'logout')) {
            if ($request->expectsJson()) {
                abort(403, 'Selesaikan pengaturan keamanan akun terlebih dahulu.');
            }

            return redirect()->route('password.setup');
        }

        return $next($request);
    }
}
