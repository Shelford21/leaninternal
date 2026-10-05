<?php

namespace App\Http\Middleware;

use App\Models\LoginLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogUserLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Called after the response is prepared.
     */
    public function terminate(Request $request, Response $response): void
    {
        // This middleware is no longer needed — login/logout logging is done
        // directly in AuthenticatedSessionController.
    }
}
