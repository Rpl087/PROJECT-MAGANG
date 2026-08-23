@echo off
title Arsip BPSDMP - Sistem Runner Ringan
color 0E
setlocal EnableDelayedExpansion

cd /d "%~dp0"

echo ====================================================================
echo   ARSIP SURAT BPSDMP — SISTEM RUNNER HARIAN (RINGAN ^& CEPAT)
echo ====================================================================
echo.

:: ── [1] Deteksi PHP ───────────────────────────────────────────────────
SET "PHP=php"
FOR /D %%V IN ("C:\laragon\bin\php\php-8*") DO (
    IF EXIST "%%V\php.exe" SET "PHP=%%V\php.exe"
)
IF "!PHP!"=="php" (
    FOR /D %%V IN ("C:\laragon\bin\php\php-*") DO (
        IF EXIST "%%V\php.exe" SET "PHP=%%V\php.exe"
    )
)
IF "!PHP!"=="php" (
    where php >nul 2>&1
    if !ERRORLEVEL! NEQ 0 (
        echo [ERROR] PHP tidak ditemukan. Pastikan Laragon terinstall di C:\laragon.
        echo.
        pause
        exit /b 1
    )
)

:: ── [2] Pastikan Laragon & Servis Aktif ───────────────────────────────
echo [1/3] Memeriksa status Laragon ^& Servis (Apache ^& MySQL)...
SET "SVC_OK=1"

tasklist /FI "IMAGENAME eq laragon.exe" 2>NUL | find /I "laragon.exe" >NUL
IF %ERRORLEVEL% NEQ 0 (
    echo [ACTION] Menyalakan Laragon...
    start "" "C:\laragon\laragon.exe"
    echo Menunggu 5 detik untuk inisialisasi Laragon...
    ping 127.0.0.1 -n 6 >nul
)

tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I "httpd.exe" >NUL
IF !ERRORLEVEL! NEQ 0 SET "SVC_OK=0"

tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I "mysqld.exe" >NUL
IF !ERRORLEVEL! NEQ 0 SET "SVC_OK=0"

IF "!SVC_OK!"=="0" (
    echo.
    echo ================================================================
    echo  PERHATIAN: Apache atau MySQL belum aktif!
    echo  1. Buka Laragon
    echo  2. Klik tombol [Start All] di Laragon
    echo  3. Tunggu hingga Apache dan MySQL aktif
    echo  4. Lalu tekan sembarang tombol di sini untuk melanjutkan...
    echo ================================================================
    pause >nul
) else (
    echo [OK] Laragon, Apache, dan MySQL berjalan.
)

:: ── [3] Jalankan Queue Worker jika belum aktif ─────────────────────────
echo.
echo [2/3] Memeriksa Queue Worker...
tasklist /FI "WINDOWTITLE eq Arsip BPSDMP Queue Worker*" 2>NUL | find /I "cmd.exe" >NUL
IF %ERRORLEVEL% NEQ 0 (
    echo [ACTION] Menjalankan Queue Worker di background...
    start "Arsip BPSDMP Queue Worker" /min cmd /k ""!PHP!" artisan queue:work --tries=3 --timeout=90 --max-jobs=500 --max-time=3600"
    echo [OK] Queue Worker diaktifkan di background (auto-restart harian).
) else (
    echo [OK] Queue Worker sudah berjalan di background.
)

:: ── [4] Buka Browser ──────────────────────────────────────────────────
echo.
echo [3/3] Membuka URL Aplikasi...
start http://kearsipan.local/login
echo.
echo ====================================================================
echo  SISTEM BERHASIL DIJALANKAN DAN SIAP DIGUNAKAN!
echo ====================================================================
echo.
pause
endlocal
