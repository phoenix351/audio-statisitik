<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            return redirect()->route('login')
                ->with('error', 'Akses ditolak. Anda harus login sebagai admin.');
        }

        return $next($request);
    }
}
