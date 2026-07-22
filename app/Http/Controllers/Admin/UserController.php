<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    // Cache key untuk versi pending reset requests (digunakan SSE)
    private const RESET_VERSION_KEY = 'reset_requests_version';

    /**
     * Tampilkan daftar semua user beserta permintaan reset pending.
     */
    public function index()
    {
        $users = User::orderBy('role')
            ->orderBy('name')
            ->get();

        $pendingResets = PasswordResetRequest::pending()->limit(20)->get();

        return view('admin.users', compact('users', 'pendingResets'));
    }

    /**
     * Reset password user tertentu (hanya admin).
     */
    public function resetPassword(Request $request, User $user)
    {
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
            'target_user'   => $user->name,
            'target_role'   => $user->role,
            'reset_by'      => auth()->user()->name,
            'reset_by_role' => auth()->user()->role,
        ]);

        // Tandai permintaan reset yang ada sebagai selesai
        PasswordResetRequest::where('username', $user->name)
            ->where('is_resolved', false)
            ->get()
            ->each(fn($req) => $req->resolve());

        // Naikkan versi agar SSE admin refresh
        static::bumpResetVersion();

        return back()->with('success', "Password {$user->name} berhasil direset.");
    }

    /**
     * Tandai permintaan reset password sebagai selesai (tanpa reset password).
     */
    public function resolveResetRequest(PasswordResetRequest $resetRequest)
    {
        $resetRequest->resolve();

        // Naikkan versi agar SSE admin refresh
        static::bumpResetVersion();

        return back()->with('success', 'Permintaan reset ditandai sebagai selesai.');
    }

    /**
     * SSE Endpoint — kirim notifikasi realtime ke admin saat ada reset request baru.
     *
     * Admin mendengarkan endpoint ini via EventSource. Saat versi berubah
     * (karena internship submit request), event dikirim ke browser admin.
     */
    public function resetRequestsSSE(Request $request)
    {
        // Simpan session sebelum streaming agar tidak terjadi session lock
        $request->session()->save();

        return response()->stream(function () {
            $lastVersion = Cache::get(self::RESET_VERSION_KEY, 0);
            $startTime   = time();
            $maxDuration = 28; // Reconnect setiap 28 detik (sebelum proxy timeout)

            // Kirim state awal beserta jumlah pending saat ini
            $pending = PasswordResetRequest::pending()->count();
            $this->sseEvent('connected', [
                'version' => $lastVersion,
                'pending' => $pending,
            ]);

            while (time() - $startTime < $maxDuration) {
                if (connection_aborted()) break;

                $currentVersion = Cache::get(self::RESET_VERSION_KEY, 0);

                if ($currentVersion !== $lastVersion) {
                    $pending = PasswordResetRequest::pending()->count();
                    $this->sseEvent('reset_updated', [
                        'version' => $currentVersion,
                        'pending' => $pending,
                    ]);
                    $lastVersion = $currentVersion;
                }

                // Heartbeat agar koneksi tidak timeout
                echo ": heartbeat\n\n";
                ob_flush();
                flush();

                sleep(3);
            }

            // Signal reconnect
            $this->sseEvent('reconnect', ['version' => $lastVersion]);
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    /**
     * API endpoint — kembalikan versi dan jumlah pending reset saat ini (polling fallback).
     */
    public function resetRequestsVersion()
    {
        return response()->json([
            'version' => Cache::get(self::RESET_VERSION_KEY, 0),
            'pending' => PasswordResetRequest::pending()->count(),
        ]);
    }

    /**
     * Helper untuk mengirim format event SSE (Server-Sent Events).
     */
    private function sseEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    /**
     * Naikkan versi cache reset requests agar SSE admin mendapat notifikasi.
     */
    public static function bumpResetVersion(): void
    {
        Cache::put(self::RESET_VERSION_KEY, now()->timestamp, 3600);
    }
}
