<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\ChecksIpSubnet;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InternalNetworkOnly
{
    use ChecksIpSubnet;

    public function handle(Request $request, Closure $next): Response
    {
        if (!config('network.intranet_enabled', false)) {
            return $next($request);
        }

        $clientIp       = $request->ip();
        $internalSubnet = config('network.internal_subnet', '192.168.252.0/22');

        if (in_array($clientIp, ['127.0.0.1', '::1', 'localhost'])) {
            return $next($request);
        }

        if ($this->ipInSubnet($clientIp, $internalSubnet)) {
            return $next($request);
        }

        abort(403, 'Akses hanya tersedia dari jaringan internal kantor.');
    }
}
