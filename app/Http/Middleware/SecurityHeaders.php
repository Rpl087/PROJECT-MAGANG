<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ============================================================
 * MIDDLEWARE: SecurityHeaders
 * ============================================================
 *
 * Menambahkan HTTP security headers pada setiap respons untuk
 * melindungi aplikasi dari berbagai serangan umum web.
 * Didaftarkan sebagai global middleware (diterapkan ke semua route).
 *
 * HEADER YANG DITAMBAHKAN:
 *   - X-Frame-Options: SAMEORIGIN
 *       Mencegah clickjacking (halaman tidak bisa di-embed di iframe luar)
 *   - X-Content-Type-Options: nosniff
 *       Mencegah browser menebak/sniff MIME type berbeda dari yang dideklarasikan
 *   - X-XSS-Protection: 1; mode=block
 *       Mengaktifkan filter XSS bawaan browser lama
 *   - Referrer-Policy: strict-origin-when-cross-origin
 *       Membatasi informasi Referer yang dikirim ke domain lain
 *   - Permissions-Policy
 *       Menonaktifkan fitur browser berbahaya (kamera, mikrofon, GPS, dsb)
 *   - Content-Security-Policy (CSP)
 *       Membatasi sumber script, style, font, dan gambar yang diizinkan
 *   - Strict-Transport-Security (HSTS)
 *       Hanya aktif pada HTTPS. Memaksa browser selalu pakai HTTPS selama 1 tahun.
 *   - X-Robots-Tag: noindex, nofollow, noarchive
 *       Mencegah search engine (Google, Bing) mengindeks halaman & route aplikasi
 *   - Cross-Origin-Opener-Policy: same-origin
 *       Isolasi tab browser — mencegah tab lain mengakses window objek halaman ini
 *   - Cross-Origin-Resource-Policy: same-origin
 *       Blokir browser lain me-load resource (gambar, API) dari domain berbeda
 *   - X-Permitted-Cross-Domain-Policies: none
 *       Blokir Flash/Acrobat PDF dari domain lain mengakses konten halaman ini
 *
 * HEADER YANG DIHAPUS:
 *   - X-Powered-By  → menyembunyikan bahwa app berjalan di PHP
 *   - Server        → menyembunyikan versi web server (Apache/Nginx)
 *
 * CATATAN PENGEMBANGAN:
 *   - Jika menambahkan CDN atau domain eksternal baru, tambahkan ke CSP
 *     (script-src, style-src, dsb) agar tidak diblokir browser.
 *   - Untuk mengizinkan embed iframe dari domain lain, ubah X-Frame-Options.
 *   - 'unsafe-inline' dipertahankan karena banyak data PHP di-pass ke JS
 *     via Blade template (misalnya @json() untuk Chart.js dan {{ url() }} untuk AJAX).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // ── Perlindungan framing & clickjacking ─────────────────────────────
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // ── Perlindungan MIME sniffing ───────────────────────────────────────
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // ── Filter XSS bawaan browser lama ──────────────────────────────────
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // ── Kontrol Referrer Header ──────────────────────────────────────────
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // ── Nonaktifkan fitur browser yang tidak digunakan ──────────────────
        $response->headers->set('Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()'
        );

        // ── Cegah crawler & search engine mengindeks route aplikasi ─────────
        // noindex    : jangan tampilkan di hasil pencarian
        // nofollow   : jangan ikuti link di halaman ini
        // noarchive  : jangan simpan salinan cache halaman
        // noimageindex: jangan indeks gambar di halaman ini
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive, noimageindex');

        // ── Isolasi cross-origin tab browser ────────────────────────────────
        // Mencegah tab/popup lain mengakses window.opener objek halaman ini
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        // ── Blokir loading resource dari domain berbeda ──────────────────────
        // Browser lain tidak bisa me-load resource halaman ini (gambar, script, dll)
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        // ── Blokir akses Flash/PDF lintas domain ────────────────────────────
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // ── CSP: Hanya izinkan resource dari domain sendiri + Google Fonts + jsDelivr CDN ──
        // 'unsafe-inline' dipertahankan — diperlukan untuk inline script Blade (@json, {{ url() }})
        // dan inline style="" (214 atribut di seluruh template).
        $csp = implode('; ', array_filter([
            "default-src 'self'",

            // Script: izinkan inline (Blade @json/{{ }}) + CDN flatpickr & Chart.js
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",

            // Style: izinkan inline (214 style="" attr) + Google Fonts + CDN
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",

            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data:",
            "connect-src 'self'",

            // Blokir semua frame dari domain lain (dua layer: CSP + X-Frame-Options)
            "frame-ancestors 'self'",

            // Batasi target <base href> hanya ke domain sendiri
            "base-uri 'self'",

            // Batasi target form submit hanya ke domain sendiri — blokir CSRF form injection
            "form-action 'self'",

            $request->isSecure() ? "upgrade-insecure-requests" : null,
        ]));
        $response->headers->set('Content-Security-Policy', $csp);

        // ── HSTS: Hanya aktif jika koneksi sudah HTTPS ──────────────────────
        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // ── Sembunyikan informasi stack teknologi dari header respons ────────
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
