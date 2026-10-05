<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            abort(401);
        }

        if (!in_array($request->user()->role->role_name, $roles)) {
            abort(403, 'You do not have permission to access this page. Please Change your account role to access this page. aowkwk ngakak');
        }

        return $next($request);
    }
}