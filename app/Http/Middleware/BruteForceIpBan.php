<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class BruteForceIpBan
{
    private const MAX_FAILURES   = 20;
    private const WINDOW_SECONDS = 600;
    private const BAN_SECONDS    = 3600;

    public function handle(Request $request, Closure $next): Response
    {
        $ip     = $request->ip();
        $banKey = 'ipban:' . md5($ip);

        if (Cache::has($banKey)) {
            $expiresAt = (int) Cache::get($banKey . ':expires', time());
            $remaining = max(1, $expiresAt - time());
            $minutes   = (int) ceil($remaining / 60);

            Log::warning('Akses ditolak - IP dalam status ban.', [
                'ip'                => $ip,
                'remaining_seconds' => $remaining,
                'path'              => $request->path(),
            ]);

            return response(
                'Akses diblokir sementara. Coba lagi dalam ' . $minutes . ' menit.',
                429,
                ['Content-Type' => 'text/plain; charset=UTF-8']
            );
        }

        return $next($request);
    }

    public static function recordFailure(string $ip): void
    {
        $countKey = 'ipfail:' . md5($ip);
        $banKey   = 'ipban:'  . md5($ip);

        $count = (int) Cache::get($countKey, 0) + 1;
        Cache::put($countKey, $count, self::WINDOW_SECONDS);

        if ($count >= self::MAX_FAILURES) {
            $expiresAt = time() + self::BAN_SECONDS;
            Cache::put($banKey,              true,       self::BAN_SECONDS);
            Cache::put($banKey . ':expires', $expiresAt, self::BAN_SECONDS);
            Cache::forget($countKey);

            Log::warning('IP di-ban otomatis.', [
                'ip'           => $ip,
                'total_gagal'  => $count,
                'ban_menit'    => (int) (self::BAN_SECONDS / 60),
                'ban_berakhir' => date('Y-m-d H:i:s', $expiresAt),
            ]);
        }
    }
}
