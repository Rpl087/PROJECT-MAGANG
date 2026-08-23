<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| Pembersihan storage (views, sessions, cache) dijadwalkan otomatis
| setiap hari pukul 17:00 (5 sore) agar sistem selalu bersih saat
| akhir jam kerja tanpa perlu intervensi manual.
|
| Untuk mengaktifkan scheduler, pastikan Queue Worker aktif dan
| jalankan perintah berikut di background terminal:
|   php artisan schedule:work
|
| Atau daftarkan via Windows Task Scheduler / supervisord untuk produksi.
|
*/

// ── Pembersihan storage otomatis setiap hari pukul 17:00 ──────────────────
Schedule::command('storage:clean')
    ->dailyAt('17:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduler.log'));
