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

    public function requestReset(Request $request)
    {
        $request->validate([
            'reset_username' => 'required|string|max:100',
        ], [
            'reset_username.required' => 'Username wajib diisi.',
        ]);

        $username = trim($request->input('reset_username'));

        $userExists = User::where('name', $username)->exists();

        if (!$userExists) {
            return back()->with('reset_error', 'Username tidak ditemukan atau tidak dapat mereset password.');
        }

        $alreadyPending = PasswordResetRequest::where('username', $username)
            ->where('is_resolved', false)
            ->exists();

        if ($alreadyPending) {

            return back()->with('reset_requested', true);
        }

        PasswordResetRequest::create([
            'username'   => $username,
            'ip_address' => $request->ip(),
        ]);

        UserController::bumpResetVersion();

        Log::info('Permintaan reset password diterima.', [
            'username' => $username,
            'ip'       => $request->ip(),
        ]);

        return back()->with('reset_requested', true);
    }
}
