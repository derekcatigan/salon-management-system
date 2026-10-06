<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Redirect unauthorized users to login page
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if (! \in_array($user->role->value, $roles)) {
            abort(401, 'Unauthorized');
        }

        return $next($request);
    }
}
