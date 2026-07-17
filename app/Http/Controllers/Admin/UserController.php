<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Tampilkan daftar semua user (kecuali akun maintenance tidak bisa diedit).
     */
    public function index()
    {
        $users = User::where('role', '!=', 'maintenance')
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        $pendingResets = PasswordResetRequest::pending()->limit(20)->get();

        return view('admin.users', compact('users', 'pendingResets'));
    }

    /**
     * Reset password user tertentu (hanya admin/maintenance).
     * Akun maintenance tidak bisa direset dari sini.
     */
    public function resetPassword(Request $request, User $user)
    {
        // Proteksi: tidak boleh reset akun maintenance
        if ($user->isMaintenance()) {
            abort(403, 'Password akun maintenance tidak dapat diubah melalui panel ini.');
        }

        $request->validate([
            'new_password' => 'required|string|min:8|max:200|confirmed',
        ], [
            'new_password.required'  => 'Password baru wajib diisi.',
            'new_password.min'       => 'Password minimal 8 karakter.',
            'new_password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        Log::info('Password user direset oleh admin.', [
            'target_user'  => $user->name,
            'target_role'  => $user->role,
            'reset_by'     => auth()->user()->name,
            'reset_by_role'=> auth()->user()->role,
        ]);

        // Tandai permintaan reset yang ada sebagai selesai
        PasswordResetRequest::where('username', $user->name)
            ->where('is_resolved', false)
            ->get()
            ->each(fn($req) => $req->resolve());

        return back()->with('success', "Password {$user->name} berhasil direset.");
    }

    /**
     * Tandai permintaan reset password sebagai selesai (tanpa reset password).
     */
    public function resolveResetRequest(PasswordResetRequest $resetRequest)
    {
        $resetRequest->resolve();

        return back()->with('success', 'Permintaan reset ditandai sebagai selesai.');
    }
}
