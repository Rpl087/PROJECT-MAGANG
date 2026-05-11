@echo off
title Arsip BPSDMP - Ngrok Tunnel
color 0A

echo ============================================
echo   ARSIP SURAT BPSDMP - Ngrok Tunnel
echo ============================================
echo.
echo Menggunakan Apache Laragon (tidak perlu artisan serve)
echo.

:: Hentikan ngrok yang masih berjalan
taskkill /F /IM ngrok.exe >nul 2>&1

echo Menjalankan Ngrok tunnel ke project-magang.test (port 80)...
echo.
C:\laragon\bin\ngrok\ngrok.exe http --host-header=project-magang.test 80

pause
