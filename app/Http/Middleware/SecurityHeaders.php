<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: SecurityHeaders
 *
 * Menambahkan HTTP Security Headers ke setiap response untuk melindungi
 * aplikasi dari serangan umum:
 *
 *  - Clickjacking        → X-Frame-Options
 *  - MIME Sniffing       → X-Content-Type-Options
 *  - XSS (browser lama) → X-XSS-Protection
 *  - Data Leakage        → Referrer-Policy
 *  - Resource injection  → Content-Security-Policy
 *  - Feature abuse       → Permissions-Policy
 *  - Downgrade attack    → Strict-Transport-Security (jika HTTPS)
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // ── [1] Cegah Clickjacking ─────────────────────────────────────────────
        // Melarang halaman ini di-embed dalam iframe oleh domain lain
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // ── [2] Cegah MIME Sniffing ────────────────────────────────────────────
        // Browser tidak boleh menebak content-type di luar yang dideklarasikan
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // ── [3] XSS Protection (browser lama) ─────────────────────────────────
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // ── [4] Referrer Policy ────────────────────────────────────────────────
        // Hanya kirim origin saat cross-origin, full URL untuk same-origin
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // ── [5] Permissions Policy ─────────────────────────────────────────────
        // Batasi akses ke fitur browser sensitif yang tidak dibutuhkan aplikasi
        $response->headers->set('Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()'
        );

        // ── [6] Content Security Policy ───────────────────────────────────────
        // Batasi asal resource yang boleh dimuat browser
        // Disesuaikan dengan kebutuhan: Google Fonts, inline style (untuk Laravel)
        $csp = implode('; ', array_filter([
            "default-src 'self'",
            // Izinkan inline script Blade + CDN flatpickr (diperlukan ikon kalender)
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
            // Izinkan inline style Blade + Google Fonts CSS + CDN flatpickr CSS
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data:",
            "connect-src 'self'",
            "frame-ancestors 'self'",                                  // gantikan X-Frame-Options di browser modern
            "base-uri 'self'",
            "form-action 'self'",
            // upgrade-insecure-requests HANYA jika HTTPS — di HTTP/intranet ini menyebabkan resource gagal dimuat
            $request->isSecure() ? "upgrade-insecure-requests" : null,
        ]));
        $response->headers->set('Content-Security-Policy', $csp);

        // ── [7] HSTS — hanya aktif jika request lewat HTTPS ───────────────────
        // Memaksa browser selalu gunakan HTTPS selama 1 tahun
        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // ── [8] Hapus header yang mengungkap teknologi server ─────────────────
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
