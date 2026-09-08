<?php

/**
 * ============================================================
 * ROUTING UTAMA APLIKASI KEARSIPAN
 * ============================================================
 *
 * Arsitektur akses dibagi menjadi 4 kelompok:
 *
 * 1. PUBLIK (tanpa auth)
 *    - /login, /logout, /password/request-reset
 *    - /pdf/{type}/{filename}  → serve file PDF lokal (uploads/)
 *    - /image/{filename}       → serve gambar dari storage/image/
 *
 * 2. USER TERAUTENTIKASI (middleware: auth)
 *    - GET  /              → ArsipController@index       (Surat Masuk, semua role)
 *    - POST /arsip         → ArsipController@store       (hanya internal_only)
 *    - GET  /surat-keluar  → SuratKeluarController@index (semua role)
 *    - POST /surat-keluar  → SuratKeluarController@store (hanya internal_only)
 *
 * 3. ADMIN SAJA (middleware: auth + AdminOnly + internal_only)
 *    - /dashboard, /dashboard/report, /dashboard/print
 *    - Edit/hapus arsip masuk & keluar
 *    - /admin/users → manajemen pengguna & reset password
 *
 * 4. TAMU/GUEST (middleware: guest_network, tanpa auth)
 *    - /tamu/           → view read-only Surat Masuk
 *    - /tamu/surat-keluar → view read-only Surat Keluar
 *    Akses dibatasi oleh subnet guest (lihat config/network.php)
 *
 * MIDDLEWARE ALIAS (didaftarkan di bootstrap/app.php):
 *    - 'internal_only' → InternalNetworkOnly::class
 *    - 'guest_network' → GuestNetwork::class
 *
 * CATATAN PENTING:
 *    Jika aplikasi berjalan di subfolder (misal: /project-magangv1/public/),
 *    JANGAN gunakan route cache (php artisan route:cache), karena dapat
 *    menyebabkan semua route menjadi 404. Hapus bootstrap/cache/routes-v7.php
 *    secara manual jika cache bermasalah.
 */

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ArsipController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SuratKeluarController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::post('/password/request-reset', [PasswordResetController::class, 'requestReset'])
    ->middleware('throttle:3,5')
    ->name('password.request-reset');

// Route serve PDF: membaca dari folder uploads/ di root project.
// Validasi tipe (surat-masuk/surat-keluar) dan nama file (hanya .pdf, alfanumerik/dash/spasi).
// TIDAK memerlukan login — agar link PDF yang disimpan di Google Sheets tetap bisa dibuka.
// Cache-Control: 1 hari (86400 detik) untuk mengurangi request berulang.
Route::get('/pdf/{type}/{filename}', function (string $type, string $filename) {
    if (!in_array($type, ['surat-masuk', 'surat-keluar'])) {
        abort(404);
    }

    if (!preg_match('/^[\w\-. ]+\.pdf$/i', $filename)) {
        abort(403, 'Akses ditolak.');
    }

    $fullPath = base_path('uploads/' . $filename);

    if (!file_exists($fullPath)) {
        abort(404, 'File tidak ditemukan.');
    }

    return response()->file($fullPath, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' . $filename . '"',
        'Cache-Control'       => 'public, max-age=86400',
    ]);
})->name('pdf.view')->where('filename', '.+');

// Route serve gambar: membaca dari storage/image/ (bukan public/images).
// Digunakan untuk aset gambar seperti logo yang disimpan di storage (bukan public/).
// Cache immutable: 1 tahun — gambar tidak berubah, cocok untuk aset statis.
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

// SSE (Server-Sent Events): digunakan oleh halaman admin untuk notifikasi real-time
// saat ada permintaan reset password baru. Hanya admin yang bisa mengakses.
Route::get('/sse/reset-requests', [UserController::class, 'resetRequestsSSE'])
    ->middleware(['auth', \App\Http\Middleware\AdminOnly::class])
    ->name('sse.reset-requests');

// Endpoint polling versi untuk fallback jika SSE tidak didukung browser.
Route::get('/api/reset-requests-version', [UserController::class, 'resetRequestsVersion'])
    ->middleware(['auth', \App\Http\Middleware\AdminOnly::class])
    ->name('api.reset-requests-version');

// ─── GRUP: USER TERAUTENTIKASI ────────────────────────────────────────────────
// Semua user yang sudah login (admin & internship) bisa membaca data.
// Operasi tulis (POST) dibatasi hanya dari IP jaringan internal (internal_only).
Route::middleware('auth')->group(function () {

    Route::get('/', [ArsipController::class, 'index'])->name('arsip.index');
    Route::post('/arsip', [ArsipController::class, 'store'])
        ->middleware('internal_only') // Hanya bisa tambah data dari jaringan kantor
        ->name('arsip.store');

    Route::get('/surat-keluar', [SuratKeluarController::class, 'index'])->name('surat-keluar.index');
    Route::post('/surat-keluar', [SuratKeluarController::class, 'store'])
        ->middleware('internal_only') // Hanya bisa tambah data dari jaringan kantor
        ->name('surat-keluar.store');
});

// ─── GRUP: ADMIN SAJA ────────────────────────────────────────────────────────
// Hanya user dengan role='admin' yang bisa mengakses.
// Selain itu, WAJIB dari jaringan internal (internal_only).
// Jika INTRANET_ENABLED=false di .env, internal_only tidak aktif (semua IP diizinkan).
Route::middleware(['auth', \App\Http\Middleware\AdminOnly::class, 'internal_only'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    Route::get('/dashboard/report', [DashboardController::class, 'report'])->name('dashboard.report');

    Route::get('/dashboard/print', [DashboardController::class, 'printView'])->name('dashboard.print');

    Route::delete('/arsip/{rowIndex}', [ArsipController::class, 'destroy'])->name('arsip.destroy');

    Route::get('/arsip/{rowIndex}/edit', [ArsipController::class, 'edit'])->name('arsip.edit');

    Route::put('/arsip/{rowIndex}', [ArsipController::class, 'update'])->name('arsip.update');

    Route::delete('/surat-keluar/{rowIndex}', [SuratKeluarController::class, 'destroy'])->name('surat-keluar.destroy');

    Route::get('/surat-keluar/{rowIndex}/edit', [SuratKeluarController::class, 'edit'])->name('surat-keluar.edit');

    Route::put('/surat-keluar/{rowIndex}', [SuratKeluarController::class, 'update'])->name('surat-keluar.update');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])
            ->name('users.reset-password');
        Route::post('/password-reset-requests/{resetRequest}/resolve', [UserController::class, 'resolveResetRequest'])
            ->name('users.resolve-reset');
    });
});

// ─── GRUP: TAMU (GUEST VIEW) ─────────────────────────────────────────────────
// Halaman baca-saja untuk tamu/pegawai tanpa akun.
// Tidak perlu login, tapi IP harus masuk subnet guest atau internal (GuestNetwork middleware).
// Konfigurasi subnet: GUEST_NETWORK_SUBNET di .env → config/network.php
// Berguna untuk pegawai yang ingin cek arsip tanpa harus memiliki akun login.
Route::middleware('guest_network')->prefix('tamu')->name('tamu.')->group(function () {

    Route::get('/', [ArsipController::class, 'indexGuest'])->name('index');

    Route::get('/surat-keluar', [SuratKeluarController::class, 'indexGuest'])->name('surat-keluar');
});
