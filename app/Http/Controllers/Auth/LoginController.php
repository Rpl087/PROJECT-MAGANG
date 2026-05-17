<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Tampilkan halaman login.
     */
    public function showLoginForm()
    {
        // Jika sudah login, langsung ke halaman utama
        if (Auth::check()) {
            return redirect()->route('arsip.index');
        }
        return view('auth.login');
    }

    /**
     * Proses login: autentikasi menggunakan name (username) + password.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // Auth::attempt menggunakan kolom 'name' sebagai username
        $credentials = [
            'name'     => $request->username,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Jika "Ingat saya" dicentang, batasi cookie hanya 1 hari (bukan default 5 tahun)
            if ($request->boolean('remember')) {
                $rememberTokenName = Auth::getRecallerName(); // "remember_web_..."
                cookie()->queue(
                    cookie($rememberTokenName,
                        cookie()->get($rememberTokenName) ?? '',
                        60 * 24,          // 1 hari = 1440 menit
                        config('session.path'),
                        config('session.domain'),
                        config('session.secure'),
                        config('session.http_only'),
                        false,
                        config('session.same_site')
                    )
                );
            }

            // Internship hanya bisa akses form input, langsung ke arsip.index
            // Admin diarahkan ke route yang dimaksud (atau arsip.index sebagai default)
            $intendedDefault = route('arsip.index');
            return redirect()->intended($intendedDefault);
        }

        return back()
            ->withInput($request->only('username'))
            ->with('login_error', 'Username atau password salah. Akses ditolak.');
    }

    /**
     * Logout: hapus sesi dan redirect ke login.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
