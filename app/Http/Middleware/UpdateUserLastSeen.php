<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserLastSeen
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            // Update last_seen_at hanya jika belum pernah diisi atau jeda aktivitas >= 60 detik
            if (!$user->last_seen_at || $user->last_seen_at->diffInSeconds(now()) >= 60) {
                $user->forceFill([
                    'last_seen_at' => now(),
                ])->save();
            }
        }

        return $next($request);
    }
}
