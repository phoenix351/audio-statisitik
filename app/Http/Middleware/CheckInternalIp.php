<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInternalIp
{
    protected array $internalIps = ['10.0.0.0/8', '192.168.0.0/8', '172.16.0.0/12', '127.0.0.1'];
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $clientIp = $request->ip();
        if ($this->isInternalIp($clientIp)) {
            return $next($request);
        }
        return response()->view('errors.403', [], 403);
    }
    private function isInternalIp(string $ip): bool
    {
        foreach ($this->internalIps as $internalIp) {
            if (strpos($internalIp, '/') !== false) {
                // CIDR notation
                if ($this->cidrMatch($ip, $internalIp)) {
                    return true;
                }
            } else {
                // Single IP
                if ($ip === $internalIp) {
                    return true;
                }
            }
        }
        return false;
    }

    private function cidrMatch(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);
        $ipLong     = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong   = -1 << (32 - (int)$mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
