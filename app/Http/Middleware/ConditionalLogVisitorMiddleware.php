<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\VisitorLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConditionalLogVisitorMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Only log visitors for non-admin and non-auth routes
        if (!$request->is('admin/*') && !$request->is('api/*') && !$request->is('login') && !$request->is('register')) {
            VisitorLog::logVisit($request->path());
        }

        return $next($request);
    }
}
