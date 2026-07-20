<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes & Scheduled Tasks
|--------------------------------------------------------------------------
|
| Di bawah ini adalah jadwal tugas otomatis (cron) Laravel.
| Menghapus file compiled views dan session temporary setiap jam 12 tengah malam (00:00).
|
*/

Schedule::call(function () {
    // 1. Bersihkan compiled views
    Artisan::call('view:clear');

    // 2. Bersihkan file sessions (kecuali .gitignore / .gitkeep)
    $sessionPath = storage_path('framework/sessions');
    if (File::exists($sessionPath)) {
        foreach (File::files($sessionPath) as $file) {
            if (!in_array($file->getFilename(), ['.gitignore', '.gitkeep'])) {
                File::delete($file->getPathname());
            }
        }
    }
})->dailyAt('00:00')->name('cleanup-views-and-sessions');

