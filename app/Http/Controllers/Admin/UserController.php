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

    private const RESET_VERSION_KEY = 'reset_requests_version';

    public function index()
    {
        $users = User::orderBy('role')
            ->orderBy('name')
            ->get();

        $pendingResets = PasswordResetRequest::pending()->limit(20)->get();

        return view('admin.users', compact('users', 'pendingResets'));
    }

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

        PasswordResetRequest::where('username', $user->name)
            ->where('is_resolved', false)
            ->get()
            ->each(fn($req) => $req->resolve());

        static::bumpResetVersion();

        return back()->with('success', "Password {$user->name} berhasil direset.");
    }

    public function resolveResetRequest(PasswordResetRequest $resetRequest)
    {
        $resetRequest->resolve();

        static::bumpResetVersion();

        return back()->with('success', 'Permintaan reset ditandai sebagai selesai.');
    }

    public function resetRequestsSSE(Request $request)
    {

        $request->session()->save();

        return response()->stream(function () {
            $lastVersion = Cache::get(self::RESET_VERSION_KEY, 0);
            $startTime   = time();
            $maxDuration = 28;

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

                echo ": heartbeat\n\n";
                ob_flush();
                flush();

                sleep(3);
            }

            $this->sseEvent('reconnect', ['version' => $lastVersion]);
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    public function resetRequestsVersion()
    {
        return response()->json([
            'version' => Cache::get(self::RESET_VERSION_KEY, 0),
            'pending' => PasswordResetRequest::pending()->count(),
        ]);
    }

    private function sseEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    public static function bumpResetVersion(): void
    {
        Cache::put(self::RESET_VERSION_KEY, now()->timestamp, 3600);
    }
}
