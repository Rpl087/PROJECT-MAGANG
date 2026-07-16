<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\ArsipController;
use App\Http\Controllers\SuratKeluarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;

// ── Auth Routes ──────────────────────────────────────────────────────────────
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])
    ->middleware('throttle:5,1')   // Maks 5 percobaan login per menit per IP
    ->name('login.post');
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

    $fullPath = null;

    // Cek di folder backup uploads/ di root project terlebih dahulu (untuk file baru)
    $backupPath = base_path('uploads/' . $filename);
    if (file_exists($backupPath)) {
        $fullPath = $backupPath;
    } else {
        // Fallback ke storage/app/public/arsip/ (untuk file lama)
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
// Melayani logo & aset gambar dari storage/image/ tanpa butuh symlink
Route::get('/image/{filename}', function (string $filename) {
    // Keamanan: hanya izinkan ekstensi gambar yang valid & cegah path traversal
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

// ── Protected Routes (wajib login) ───────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Surat Masuk — semua user login (admin & internship) bisa input form
    Route::get('/', [ArsipController::class, 'index'])->name('arsip.index');
    Route::post('/arsip', [ArsipController::class, 'store'])
        ->middleware('internal_only')
        ->name('arsip.store');

    // Surat Keluar — semua user login bisa input form
    Route::get('/surat-keluar', [SuratKeluarController::class, 'index'])->name('surat-keluar.index');
    Route::post('/surat-keluar', [SuratKeluarController::class, 'store'])
        ->middleware('internal_only')
        ->name('surat-keluar.store');
});

// ── Admin-only Routes (wajib login + role admin) ──────────────────────────────
Route::middleware(['auth', \App\Http\Middleware\AdminOnly::class, 'internal_only'])->group(function () {

    // Dashboard (hanya admin)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Report Rekapan (hanya admin)
    Route::get('/dashboard/report', [DashboardController::class, 'report'])->name('dashboard.report');

    // Hapus Surat Masuk — hanya admin (dari sheet "SURAT MASUK")
    Route::delete('/arsip/{rowIndex}', [ArsipController::class, 'destroy'])->name('arsip.destroy');

    // Edit Surat Masuk — ambil data baris sebagai JSON untuk modal
    Route::get('/arsip/{rowIndex}/edit', [ArsipController::class, 'edit'])->name('arsip.edit');

    // Update Surat Masuk — simpan perubahan ke Google Sheets dbSM
    Route::put('/arsip/{rowIndex}', [ArsipController::class, 'update'])->name('arsip.update');

    // Hapus Surat Keluar — hanya admin (dari sheet "SURAT KELUAR")
    Route::delete('/surat-keluar/{rowIndex}', [SuratKeluarController::class, 'destroy'])->name('surat-keluar.destroy');

    // Edit Surat Keluar — ambil data baris sebagai JSON untuk modal
    Route::get('/surat-keluar/{rowIndex}/edit', [SuratKeluarController::class, 'edit'])->name('surat-keluar.edit');

    // Update Surat Keluar — simpan perubahan ke Google Sheets dbSK
    Route::put('/surat-keluar/{rowIndex}', [SuratKeluarController::class, 'update'])->name('surat-keluar.update');
});


// ── Guest Network Routes (WiFi Tamu — read-only, tanpa login) ─────────────────
// Hanya bisa diakses dari subnet WiFi Tamu atau Internal (via middleware GuestNetwork)
Route::middleware('guest_network')->prefix('tamu')->name('tamu.')->group(function () {

    // Halaman utama tamu: daftar surat masuk (read-only)
    Route::get('/', [ArsipController::class, 'indexGuest'])->name('index');

    // Halaman surat keluar tamu (read-only)
    Route::get('/surat-keluar', [SuratKeluarController::class, 'indexGuest'])->name('surat-keluar');
});

