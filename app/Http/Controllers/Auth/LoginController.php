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
            return redirect()->intended(route('arsip.index'));
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
