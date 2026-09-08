<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\BruteForceIpBan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{

    /** Maksimum percobaan login gagal per kombinasi username+IP sebelum di-throttle */
    private const MAX_ATTEMPTS  = 5;

    /** Durasi lockout throttle per username+IP dalam menit (5 menit) */
    private const DECAY_MINUTES = 5;

    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectAfterLogin(Auth::user());
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:100',
            'password' => 'required|string|max:200',
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

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

        $loginField = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';
        $credentials = [
            $loginField => $request->username,
            'password'  => $request->password,
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {

            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            $user = Auth::user();
            $user->forceFill(['last_seen_at' => now()])->save();

            Log::info('Login berhasil.', [
                'user_id'  => Auth::id(),
                'username' => $user->name,
                'role'     => $user->role,
                'ip'       => $request->ip(),
            ]);

            return redirect()->intended($this->redirectAfterLogin($user)->getTargetUrl());
        }

        RateLimiter::hit($throttleKey, self::DECAY_MINUTES * 60);

        // Catat kegagalan per-IP untuk proteksi bruteforce global (BruteForceIpBan).
        // Jika IP ini mencapai 20 kegagalan dalam 10 menit => IP di-ban 1 jam.
        BruteForceIpBan::recordFailure($request->ip());

        Log::warning('Percobaan login gagal.', [
            'ip'       => $request->ip(),
            'username' => $request->input('username'),
            'attempt'  => RateLimiter::attempts($throttleKey),
        ]);

        return back()
            ->withInput($request->only('username'))
            ->with('login_error', 'Username atau password tidak valid. Akses ditolak.');
    }

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

    private function redirectAfterLogin($user)
    {
        if ($user->isAdmin()) {
            return redirect()->route('dashboard.index');
        }

        return redirect()->route('arsip.index');
    }

    private function throttleKey(Request $request): string
    {
        return Str::lower($request->input('username', '')) . '|' . $request->ip();
    }
}
