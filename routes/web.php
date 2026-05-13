<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\ArsipController;
use App\Http\Controllers\SuratKeluarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;

// ── Auth Routes ──────────────────────────────────────────────────────────────
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ── Public PDF Access ─────────────────────────────────────────────────────────
// Mengizinkan akses publik ke file PDF arsip surat masuk & surat keluar
Route::get('/pdf/{type}/{filename}', function (string $type, string $filename) {
    // Keamanan: hanya izinkan tipe folder yang valid
    if (!in_array($type, ['surat-masuk', 'surat-keluar'])) {
        abort(404);
    }

    // Keamanan: hanya izinkan file .pdf dan cegah path traversal
    if (!preg_match('/^[\w\-. ]+\.pdf$/i', $filename)) {
        abort(403, 'Akses ditolak.');
    }

    $path = "arsip/{$type}/{$filename}";

    if (!Storage::disk('public')->exists($path)) {
        abort(404, 'File tidak ditemukan.');
    }

    $fullPath = Storage::disk('public')->path($path);

    return response()->file($fullPath, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' . $filename . '"',
        'Cache-Control'       => 'public, max-age=86400',
    ]);
})->name('pdf.view')->where('filename', '.+');

// ── Protected Routes (wajib login) ───────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Surat Masuk — semua user login (admin & internship) bisa input form
    Route::get('/', [ArsipController::class, 'index'])->name('arsip.index');
    Route::post('/arsip', [ArsipController::class, 'store'])->name('arsip.store');

    // Surat Keluar — semua user login bisa input form
    Route::get('/surat-keluar', [SuratKeluarController::class, 'index'])->name('surat-keluar.index');
    Route::post('/surat-keluar', [SuratKeluarController::class, 'store'])->name('surat-keluar.store');
});

// ── Admin-only Routes (wajib login + role admin) ──────────────────────────────
Route::middleware(['auth', \App\Http\Middleware\AdminOnly::class])->group(function () {

    // Dashboard (hanya admin)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Hapus Surat Masuk — hanya admin (dari sheet "SURAT MASUK")
    Route::delete('/arsip/{rowIndex}', [ArsipController::class, 'destroy'])->name('arsip.destroy');

    // Hapus Surat Keluar — hanya admin (dari sheet "SURAT KELUAR")
    Route::delete('/surat-keluar/{rowIndex}', [SuratKeluarController::class, 'destroy'])->name('surat-keluar.destroy');
});
