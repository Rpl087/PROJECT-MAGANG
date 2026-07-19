<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\UserController;
use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PasswordResetController extends Controller
{
    /**
     * Proses permintaan reset password dari user.
     *
     * Karena sistem ini beroperasi di intranet tanpa email server,
     * reset password dilakukan secara manual oleh admin.
     * Request ini dicatat dan admin dapat menindaklanjutinya secara realtime.
     */
    public function requestReset(Request $request)
    {
        $request->validate([
            'reset_username' => 'required|string|max:100',
        ], [
            'reset_username.required' => 'Username wajib diisi.',
        ]);

        $username = trim($request->input('reset_username'));

        // Validasi: pastikan username benar-benar ada di database
        $userExists = User::where('name', $username)->exists();

        if (!$userExists) {
            return back()->with('reset_error', 'Username tidak ditemukan atau tidak dapat mereset password.');
        }

        // Proteksi duplikasi: cek apakah sudah ada request pending dari username ini
        $alreadyPending = PasswordResetRequest::where('username', $username)
            ->where('is_resolved', false)
            ->exists();

        if ($alreadyPending) {
            // Kembalikan pesan sukses palsu agar tidak membocorkan info
            return back()->with('reset_requested', true);
        }

        // Catat permintaan ke database
        PasswordResetRequest::create([
            'username'   => $username,
            'ip_address' => $request->ip(),
        ]);

        // Naikkan versi agar admin SSE mendapat notifikasi realtime
        UserController::bumpResetVersion();

        // Log permintaan untuk audit trail
        Log::info('Permintaan reset password diterima.', [
            'username' => $username,
            'ip'       => $request->ip(),
        ]);

        return back()->with('reset_requested', true);
    }
}
