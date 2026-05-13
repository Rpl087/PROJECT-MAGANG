@echo off
title Arsip BPSDMP - Ngrok Tunnel
color 0A

echo ============================================
echo   ARSIP SURAT BPSDMP - Ngrok Tunnel
echo ============================================
echo.

:: ---- LANGKAH 1: Cek dan Jalankan Laragon ----
echo [1/3] Memeriksa Laragon...

tasklist /FI "IMAGENAME eq laragon.exe" 2>NUL | find /I "laragon.exe" >NUL
if %ERRORLEVEL% NEQ 0 (
    echo Laragon belum berjalan. Menyalakan Laragon...
    start "" "C:\laragon\laragon.exe"
    echo Menunggu Laragon dan Apache siap ^(15 detik^)...
    timeout /t 15 /nobreak >nul
) else (
    echo Laragon sudah berjalan. Lanjut...
)

:: ---- LANGKAH 2: Cek Apache sudah aktif ----
echo.
echo [2/3] Memeriksa Apache...

tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I "httpd.exe" >NUL
if %ERRORLEVEL% NEQ 0 (
    echo Apache belum aktif, menunggu 10 detik lagi...
    timeout /t 10 /nobreak >nul
    tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I "httpd.exe" >NUL
    if %ERRORLEVEL% NEQ 0 (
        echo.
        echo [PERINGATAN] Apache masih belum aktif!
        echo Silakan klik "Start All" di Laragon secara manual, lalu tekan sembarang tombol...
        pause >nul
    ) else (
        echo Apache sudah aktif!
    )
) else (
    echo Apache sudah aktif!
)

:: ---- LANGKAH 3: Jalankan Ngrok ----
echo.
echo [3/3] Menjalankan Ngrok tunnel ke project-magang.test (port 80)...
echo.

:: Hentikan ngrok yang masih berjalan
taskkill /F /IM ngrok.exe >nul 2>&1

C:\laragon\bin\ngrok\ngrok.exe http --host-header=project-magang.test 80

pause
