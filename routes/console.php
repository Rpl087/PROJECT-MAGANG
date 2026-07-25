<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| Pembersihan storage (views, sessions, cache) dilakukan secara manual
| setiap kali sistem dijalankan melalui setup-and-run.bat (1-click startup).
| Tidak ada lagi jadwal otomatis tengah malam.
|
*/
