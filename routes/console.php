<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes & Scheduled Tasks
|--------------------------------------------------------------------------
|
| Di bawah ini adalah jadwal tugas otomatis (cron/scheduler) Laravel.
| Menghapus secara otomatis berkas temporary pada:
|  1. storage/framework/views (Compiled Blade Views)
|  2. storage/framework/sessions (Session files)
|  3. storage/framework/cache/data (Cache data files & subfolders)
|
| Berjalan otomatis setiap hari pukul 00:00 (tengah malam).
|
*/

Schedule::command('storage:clean')
    ->dailyAt('00:00')
    ->name('cleanup-views-sessions-cache');



