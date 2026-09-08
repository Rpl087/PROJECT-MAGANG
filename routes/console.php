<?php

use Illuminate\Support\Facades\Schedule;

// Pembersihan sesi kadaluarsa di database setiap 1 jam
Schedule::command('session:prune-expired')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// Pembersihan berkas temporary storage setiap pukul 17:00
Schedule::command('storage:clean')
    ->dailyAt('17:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduler.log'));
