@echo off
title Arsip BPSDMP - Sistem Runner Harian
color 0E
setlocal EnableDelayedExpansion

:: ================================================================
:: ARSIP SURAT (server).bat
:: ================================================================
:: Script startup harian untuk menjalankan semua komponen sistem
:: kearsipan di Windows menggunakan Laragon.
::
:: YANG DILAKUKAN SCRIPT INI:
::   1. Mendeteksi lokasi project secara otomatis
::   2. Menghapus route cache (routes-v7.php) yang bisa menyebabkan 404
::   3. Memastikan Apache dan MySQL Laragon berjalan (auto-start jika mati)
::   4. Menjalankan Queue Worker di background (untuk proses async)
::   5. Menjalankan Scheduler (untuk perintah storage:clean jam 17:00)
::   6. Membuka URL aplikasi di browser
::
:: CARA PAKAI:
::   Klik dua kali file ini setiap kali server dinyalakan/restart.
::   Atau tambahkan shortcut ke folder Startup Windows untuk auto-run.
::
:: TROUBLESHOOTING:
::   - Jika error PHP tidak ditemukan: pastikan Laragon terinstall di C:\laragon
::   - Jika halaman 404: script sudah otomatis hapus routes-v7.php
::   - Jika Apache/MySQL gagal start: coba buka Laragon secara manual dulu
:: ================================================================

:: ================================================================
:: KONFIGURASI: Ubah path ini jika folder project dipindahkan
:: ================================================================
SET "PROJECT_DIR=C:\laragon\www\project-magangv1"

:: Deteksi otomatis: jika .bat berada di dalam folder project, gunakan folder itu
IF EXIST "%~dp0artisan" (
    SET "PROJECT_DIR=%~dp0"
    SET "PROJECT_DIR=!PROJECT_DIR:~0,-1!"
)

:: Validasi: pastikan folder project ada dan berisi file artisan
IF NOT EXIST "!PROJECT_DIR!\artisan" (
    echo [ERROR] Folder project tidak ditemukan di: !PROJECT_DIR!
    echo [INFO]  Pastikan path PROJECT_DIR di dalam file .bat ini sudah benar.
    echo [INFO]  Atau letakkan file .bat ini di dalam folder project.
    echo.
    pause
    exit /b 1
)

cd /d "!PROJECT_DIR!"
echo [OK] Folder project: !PROJECT_DIR!
echo.

echo ====================================================================
echo   ARSIP SURAT BPSDMP - SISTEM RUNNER HARIAN (RINGAN ^& CEPAT)
echo ====================================================================
echo.

SET "PHP="
FOR /D %%V IN ("C:\laragon\bin\php\php-8.5*" "C:\laragon\bin\php\php-8.4*" "C:\laragon\bin\php\php-8.3*") DO (
    IF EXIST "%%V\php.exe" SET "PHP=%%V\php.exe"
)
IF NOT DEFINED PHP (
    FOR /D %%V IN ("C:\laragon\bin\php\php-8*") DO (
        IF EXIST "%%V\php.exe" SET "PHP=%%V\php.exe"
    )
)
IF NOT DEFINED PHP (
    where php >nul 2>&1
    if !ERRORLEVEL! EQU 0 (
        SET "PHP=php"
    ) else (
        echo [ERROR] PHP tidak ditemukan. Pastikan Laragon terinstall di C:\laragon.
        echo.
        pause
        exit /b 1
    )
)

IF EXIST "bootstrap\cache\config.php" (
    findstr /C:"%CD%" "bootstrap\cache\config.php" >nul 2>&1
    if !ERRORLEVEL! NEQ 0 (
        echo [INFO] Mendeteksi perpindahan folder, membersihkan cache bootstrap...
        del /f /q "bootstrap\cache\*.php" >nul 2>&1
    )
)
:: Hapus route cache yang bisa menyebabkan semua route menjadi 404.
:: Ini terjadi jika path project berubah atau setelah update Laravel.
:: routes-v7.php adalah file cache route yang di-generate oleh 'php artisan route:cache'.
:: PENTING: Jangan jalankan 'php artisan route:cache' di project ini!
IF EXIST "bootstrap\cache\routes-v7.php" (
    del /f /q "bootstrap\cache\routes-v7.php" >nul 2>&1
)

echo [1/4] Memeriksa status Laragon ^& Servis (Apache ^& MySQL)...

tasklist | findstr /i "httpd.exe" >nul
IF !ERRORLEVEL! EQU 0 (
    echo [OK] Apache ^(httpd.exe^) berjalan.
) ELSE (
    echo [ACTION] Menyalakan Apache otomatis...
    SET "HTTPD_BIN="
    FOR /D %%A IN ("C:\laragon\bin\apache\httpd-*") DO (
        IF EXIST "%%A\bin\httpd.exe" SET "HTTPD_BIN=%%A\bin\httpd.exe"
    )
    IF DEFINED HTTPD_BIN (
        start "" "!HTTPD_BIN!"
        ping 127.0.0.1 -n 3 >nul
        echo [OK] Apache berhasil dinyalakan.
    ) ELSE (
        echo [WARNING] Biner httpd.exe tidak ditemukan di C:\laragon\bin\apache
    )
)

tasklist | findstr /i "mysqld.exe" >nul
IF !ERRORLEVEL! EQU 0 (
    echo [OK] MySQL ^(mysqld.exe^) berjalan.
) ELSE (
    echo [ACTION] Menyalakan MySQL otomatis...
    SET "MYSQLD_BIN="
    FOR /D %%M IN ("C:\laragon\bin\mysql\mysql-*") DO (
        IF EXIST "%%M\bin\mysqld.exe" SET "MYSQLD_BIN=%%M\bin\mysqld.exe"
    )
    IF DEFINED MYSQLD_BIN (
        start "" "!MYSQLD_BIN!" --console
        ping 127.0.0.1 -n 4 >nul
        echo [OK] MySQL berhasil dinyalakan.
    ) ELSE (
        echo [WARNING] Biner mysqld.exe tidak ditemukan di C:\laragon\bin\mysql
    )
)

tasklist | findstr /i "laragon.exe" >nul
IF !ERRORLEVEL! NEQ 0 (
    IF EXIST "C:\laragon\laragon.exe" (
        start "" "C:\laragon\laragon.exe"
        ping 127.0.0.1 -n 3 >nul
    )
)

echo.
echo [2/4] Memeriksa Queue Worker...
tasklist /v | findstr /i "Arsip BPSDMP Queue Worker" >nul
IF !ERRORLEVEL! NEQ 0 (
:: [2/4] Queue Worker menangani pekerjaan async (misal: pengiriman email, notifikasi).
:: Berjalan di window CMD terpisah yang diminimize.
:: --tries=3       : coba ulang job yang gagal maksimal 3 kali
:: --timeout=90    : job yang berjalan >90 detik dianggap gagal
:: --max-jobs=500  : restart worker setiap 500 job (mencegah memory leak)
:: --max-time=3600 : restart worker setiap 1 jam
echo [ACTION] Menjalankan Queue Worker di background...
    start "Arsip BPSDMP Queue Worker" /min cmd /k ""!PHP!" artisan queue:work --tries=3 --timeout=90 --max-jobs=500 --max-time=3600"
    echo [OK] Queue Worker diaktifkan ^(auto-restart setiap 500 jobs/1 jam^).
) else (
    echo [OK] Queue Worker sudah berjalan.
)

echo.
echo [3/4] Memeriksa Task Scheduler (storage:clean jam 17:00^)...
tasklist /v | findstr /i "Arsip BPSDMP Scheduler" >nul
IF !ERRORLEVEL! NEQ 0 (
:: [3/4] Laravel Scheduler menjalankan 'storage:clean' setiap pukul 17:00.
:: storage:clean membersihkan: compiled views, session files, dan cache Google Sheets.
:: Berjalan di window CMD terpisah yang diminimize.
echo [ACTION] Menjalankan Task Scheduler di background...
    start "Arsip BPSDMP Scheduler" /min cmd /k ""!PHP!" artisan schedule:work"
    echo [OK] Scheduler aktif -- storage:clean berjalan otomatis pukul 17:00.
) else (
    echo [OK] Task Scheduler sudah berjalan.
)

echo.
echo [4/4] Membuka URL Aplikasi...
SET "APP_URL=http://localhost"
findstr /C:"kearsipan.local" "C:\Windows\System32\drivers\etc\hosts" >nul 2>&1
IF !ERRORLEVEL! EQU 0 SET "APP_URL=http://kearsipan.local"

start "" "!APP_URL!/login"

echo.
echo ====================================================================
echo  SISTEM BERHASIL DIJALANKAN DAN SIAP DIGUNAKAN!
echo  URL Akses : !APP_URL!/login
echo  Jadwal pembersihan storage otomatis: pukul 17:00 setiap hari.
echo ====================================================================
echo.
pause
endlocal