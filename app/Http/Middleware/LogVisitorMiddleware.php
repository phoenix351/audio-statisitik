<?php

namespace App\Http\Middleware;

use App\Models\VisitorLog;
use Closure;
use Illuminate\Http\Request;

class LogVisitorMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Log visitor for non-admin routes
        if (!$request->is('admin/*') && !$request->is('api/*')) {
            VisitorLog::logVisit($request->path());
        }

        return $next($request);
    }
}
