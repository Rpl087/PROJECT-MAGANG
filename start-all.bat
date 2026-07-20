@echo off
title Arsip BPSDMP - One-Click Full App System Runner
color 0B

echo ========================================================
echo   ARSIP SURAT BPSDMP - Full System Runner (Deploy/Local)
echo ========================================================
echo.

:: 1. Memeriksa Laragon & Apache
echo [1/3] Memeriksa status Laragon...
tasklist /FI "IMAGENAME eq laragon.exe" 2>NUL | find /I "laragon.exe" >NUL
if %ERRORLEVEL% NEQ 0 (
    echo Menyalakan Laragon...
    start "" "C:\laragon\laragon.exe"
    timeout /t 10 /nobreak >nul
) else (
    echo Laragon sudah aktif.
)

:: 2. Pembersihan Awal & Optimasi ringan
echo.
echo [2/3] Mengoptimalkan cache aplikasi...
call php artisan config:clear
call php artisan route:clear
call php artisan view:clear

:: 3. Menjalankan Seluruh Sistem (Server + Queue + Pail Logs + Vite + Schedule Worker)
echo.
echo [3/3] Menjalankan Server, Queue Worker, Log Monitor, Vite, dan Task Scheduler...
echo.
echo Jalur Task Scheduler otomatis aktif untuk pembersihan views & sessions jam 12 malam.
echo Tekan Ctrl+C untuk menghentikan seluruh layanan.
echo.

npm run dev

pause
