<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: BlockMaintenanceEdit
 *
 * Mencegah siapapun (termasuk admin) mengubah atau menghapus akun maintenance.
 * Akun maintenance adalah akun statis sistem yang bersifat permanen.
 *
 * Cara penggunaan: terapkan pada route edit/update/delete user.
 * Middleware ini mengekstrak user ID dari parameter route dan mengecek rolenya.
 */
class BlockMaintenanceEdit
{
    public function handle(Request $request, Closure $next): Response
    {
        // Cek parameter route 'user' atau 'id'
        $userId = $request->route('user') ?? $request->route('id');

        if ($userId) {
            $targetUser = \App\Models\User::find($userId);

            if ($targetUser && $targetUser->isMaintenance()) {
                abort(403, 'Akun maintenance tidak dapat diubah atau dihapus.');
            }
        }

        // Juga cegah mengubah role menjadi 'maintenance' via request body
        if ($request->input('role') === 'maintenance') {
            abort(403, 'Role maintenance tidak dapat diberikan melalui panel ini.');
        }

        return $next($request);
    }
}
