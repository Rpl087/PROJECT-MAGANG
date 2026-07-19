<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    /**
     * Batas percobaan login per menit (per IP).
     * Setelah melewati batas ini, user diblokir selama DECAY_MINUTES.
     */
    private const MAX_ATTEMPTS  = 5;
    private const DECAY_MINUTES = 1;

    /**
     * Tampilkan halaman login.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectAfterLogin(Auth::user());
        }
        return view('auth.login');
    }

    /**
     * Proses login: autentikasi menggunakan name (username) + password.
     * Dilindungi rate limiting: maks 5 percobaan per menit per IP.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:100',
            'password' => 'required|string|max:200',
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // ── Cek Rate Limit (Brute Force Protection) ───────────────────────────
        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            Log::warning('Login diblokir — terlalu banyak percobaan.', [
                'ip'       => $request->ip(),
                'username' => $request->input('username'),
                'blocked_seconds_remaining' => $seconds,
            ]);

            return back()
                ->withInput($request->only('username'))
                ->with('login_error',
                    "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik."
                );
        }

        // ── Proses Autentikasi ─────────────────────────────────────────────────
        $credentials = [
            'name'     => $request->username,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // Login berhasil — reset rate limiter dan regenerate session
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            $user = Auth::user();

            Log::info('Login berhasil.', [
                'user_id'  => Auth::id(),
                'username' => $user->name,
                'role'     => $user->role,
                'ip'       => $request->ip(),
            ]);

            return redirect()->intended($this->redirectAfterLogin($user)->getTargetUrl());
        }

        // ── Login Gagal — tambah hit rate limiter & log ───────────────────────
        RateLimiter::hit($throttleKey, self::DECAY_MINUTES * 60);

        Log::warning('Percobaan login gagal.', [
            'ip'       => $request->ip(),
            'username' => $request->input('username'),
            'attempt'  => RateLimiter::attempts($throttleKey),
        ]);

        return back()
            ->withInput($request->only('username'))
            ->with('login_error', 'Username atau password tidak valid. Akses ditolak.');
    }

    /**
     * Logout: hapus sesi dan redirect ke login.
     */
    public function logout(Request $request)
    {
        Log::info('User logout.', [
            'user_id'  => Auth::id(),
            'username' => Auth::user()?->name,
            'ip'       => $request->ip(),
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    /**
     * Tentukan redirect URL berdasarkan role user.
     *
     * - admin      → dashboard
     * - internship → arsip.index
     */
    private function redirectAfterLogin($user)
    {
        if ($user->isAdmin()) {
            return redirect()->route('dashboard.index');
        }

        return redirect()->route('arsip.index');
    }

    /**
     * Buat throttle key unik berdasarkan username + IP address.
     */
    private function throttleKey(Request $request): string
    {
        return Str::lower($request->input('username', '')) . '|' . $request->ip();
    }
}
