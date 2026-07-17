<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ArsipController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\SuratKeluarController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// ── Auth Routes ──────────────────────────────────────────────────────────────
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])
    ->middleware('throttle:5,1')   // Maks 5 percobaan login per menit per IP
    ->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ── Password Reset Request (intranet — tanpa email, admin yang menindaklanjuti) ─
Route::post('/password/request-reset', [PasswordResetController::class, 'requestReset'])
    ->middleware('throttle:3,5')   // Maks 3 request reset per 5 menit per IP
    ->name('password.request-reset');

// ── Public PDF Access ─────────────────────────────────────────────────────────
Route::get('/pdf/{type}/{filename}', function (string $type, string $filename) {
    if (!in_array($type, ['surat-masuk', 'surat-keluar'])) {
        abort(404);
    }

    if (!preg_match('/^[\w\-. ]+\.pdf$/i', $filename)) {
        abort(403, 'Akses ditolak.');
    }

    $fullPath = null;

    $backupPath = base_path('uploads/' . $filename);
    if (file_exists($backupPath)) {
        $fullPath = $backupPath;
    } else {
        $path = "arsip/{$type}/{$filename}";
        if (Storage::disk('public')->exists($path)) {
            $fullPath = Storage::disk('public')->path($path);
        }
    }

    if (!$fullPath) {
        abort(404, 'File tidak ditemukan.');
    }

    return response()->file($fullPath, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' . $filename . '"',
        'Cache-Control'       => 'public, max-age=86400',
    ]);
})->name('pdf.view')->where('filename', '.+');

// ── Public Image Access ───────────────────────────────────────────────────────
Route::get('/image/{filename}', function (string $filename) {
    if (!preg_match('/^[\w\-. ]+\.(png|jpg|jpeg|gif|svg|webp|ico)$/i', $filename)) {
        abort(403, 'Akses ditolak.');
    }

    $path = storage_path('image/' . $filename);

    if (!file_exists($path)) {
        abort(404, 'Gambar tidak ditemukan.');
    }

    $ext  = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        default => 'image/png',
    };

    return response()->file($path, [
        'Content-Type'  => $mime,
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->name('image.view')->where('filename', '.+');

// ── SSE Endpoint (Realtime menu updates) ─────────────────────────────────────
// Semua user yang login dapat terhubung ke stream ini untuk menerima notifikasi
// realtime saat akun maintenance mengubah menu/fitur.
Route::get('/sse/menu-updates', [MaintenanceController::class, 'menuUpdates'])
    ->middleware('auth')
    ->name('sse.menu-updates');

// API endpoint untuk polling fallback (jika SSE tidak didukung)
Route::get('/api/menu-version', [MaintenanceController::class, 'menuVersion'])
    ->middleware('auth')
    ->name('api.menu-version');

// ── Protected Routes (wajib login) ───────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Surat Masuk — semua user login (admin, maintenance & internship)
    Route::get('/', [ArsipController::class, 'index'])->name('arsip.index');
    Route::post('/arsip', [ArsipController::class, 'store'])
        ->middleware('internal_only')
        ->name('arsip.store');

    // Surat Keluar — semua user login
    Route::get('/surat-keluar', [SuratKeluarController::class, 'index'])->name('surat-keluar.index');
    Route::post('/surat-keluar', [SuratKeluarController::class, 'store'])
        ->middleware('internal_only')
        ->name('surat-keluar.store');
});

// ── Admin-only Routes (wajib login + role admin atau maintenance) ─────────────
Route::middleware(['auth', \App\Http\Middleware\AdminOnly::class, 'internal_only'])->group(function () {

    // Dashboard (admin & maintenance)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Report Rekapan (admin & maintenance)
    Route::get('/dashboard/report', [DashboardController::class, 'report'])->name('dashboard.report');

    // Hapus Surat Masuk
    Route::delete('/arsip/{rowIndex}', [ArsipController::class, 'destroy'])->name('arsip.destroy');

    // Edit Surat Masuk
    Route::get('/arsip/{rowIndex}/edit', [ArsipController::class, 'edit'])->name('arsip.edit');

    // Update Surat Masuk
    Route::put('/arsip/{rowIndex}', [ArsipController::class, 'update'])->name('arsip.update');

    // Hapus Surat Keluar
    Route::delete('/surat-keluar/{rowIndex}', [SuratKeluarController::class, 'destroy'])->name('surat-keluar.destroy');

    // Edit Surat Keluar
    Route::get('/surat-keluar/{rowIndex}/edit', [SuratKeluarController::class, 'edit'])->name('surat-keluar.edit');

    // Update Surat Keluar
    Route::put('/surat-keluar/{rowIndex}', [SuratKeluarController::class, 'update'])->name('surat-keluar.update');

    // ── Kelola Pengguna (admin & maintenance) ─────────────────────────────────
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])
            ->middleware('block_maint_edit')
            ->name('users.reset-password');
        Route::post('/password-reset-requests/{resetRequest}/resolve', [UserController::class, 'resolveResetRequest'])
            ->name('users.resolve-reset');
    });
});

// ── Maintenance-only Routes ───────────────────────────────────────────────────
// PENTING: Gunakan 'maintenance_only' (bukan AdminOnly) agar admin biasa tidak bisa akses
Route::middleware(['auth', 'maintenance_only', 'internal_only'])
    ->prefix('maintenance')
    ->name('maintenance.')
    ->group(function () {

        // Panel maintenance
        Route::get('/', [MaintenanceController::class, 'index'])->name('index');

        // CRUD Menu Items
        Route::post('/menu', [MaintenanceController::class, 'storeMenu'])->name('menu.store');
        Route::put('/menu/{menuItem}', [MaintenanceController::class, 'updateMenu'])->name('menu.update');
        Route::delete('/menu/{menuItem}', [MaintenanceController::class, 'destroyMenu'])->name('menu.destroy');
        Route::patch('/menu/{menuItem}/toggle', [MaintenanceController::class, 'toggleMenu'])->name('menu.toggle');
    });

// ── Guest Network Routes (WiFi Tamu — read-only, tanpa login) ─────────────────
// Hanya bisa diakses dari subnet WiFi Tamu atau Internal (via middleware GuestNetwork)
Route::middleware('guest_network')->prefix('tamu')->name('tamu.')->group(function () {

    // Halaman utama tamu: daftar surat masuk (read-only)
    Route::get('/', [ArsipController::class, 'indexGuest'])->name('index');

    // Halaman surat keluar tamu (read-only)
    Route::get('/surat-keluar', [SuratKeluarController::class, 'indexGuest'])->name('surat-keluar');
});
