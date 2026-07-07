@echo off
title Arsip BPSDMP - Intranet Mode
color 0B

echo ============================================
echo   ARSIP SURAT BPSDMP - Intranet (LAN)
echo ============================================
echo.

:: ---- Cek IP Lokal (ambil IP WiFi yang aktif, bukan Hotspot/VPN) ----
echo [1/3] Mendeteksi IP Lokal...

:: Gunakan PowerShell untuk mendapatkan IP WiFi yang benar (bukan hotspot 192.168.137.x)
for /f "usebackq delims=" %%a in (`powershell -NoProfile -Command "(Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.IPAddress -notlike '127.*' -and $_.IPAddress -notlike '192.168.137.*' -and $_.PrefixOrigin -eq 'Dhcp' } | Select-Object -First 1).IPAddress"`) do set LOCAL_IP=%%a

:: Fallback jika PowerShell gagal
if "%LOCAL_IP%"=="" (
    for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /i "IPv4"') do (
        set TEMP_IP=%%a
    )
    for /f "tokens=* delims= " %%b in ("%TEMP_IP%") do set LOCAL_IP=%%b
)

if "%LOCAL_IP%"=="" (
    echo [PERINGATAN] Tidak dapat mendeteksi IP. Pastikan WiFi terhubung!
    set LOCAL_IP=127.0.0.1
)

echo IP Lokal terdeteksi: %LOCAL_IP%
echo.

:: ---- Update APP_URL di .env otomatis sesuai IP saat ini ----
echo Mengupdate APP_URL di .env...
powershell -NoProfile -Command "(Get-Content '%~dp0.env') -replace 'APP_URL=.*', 'APP_URL=http://%LOCAL_IP%:8000' | Set-Content '%~dp0.env'"
echo APP_URL berhasil diupdate ke http://%LOCAL_IP%:8000
echo.

:: ---- Update INTERNAL_NETWORK_SUBNET otomatis ----
:: Deteksi subnet /22 dari IP saat ini
for /f "usebackq delims=" %%a in (`powershell -NoProfile -Command "$ip = '%LOCAL_IP%'; $parts = $ip.Split('.'); $parts[2] = [string]([int]$parts[2] - [int]$parts[2] %% 4); $parts[3] = '0'; $parts -join '.' + '/22'"`) do set SUBNET=%%a

echo Mengupdate INTERNAL_NETWORK_SUBNET ke %SUBNET%...
powershell -NoProfile -Command "(Get-Content '%~dp0.env') -replace 'INTERNAL_NETWORK_SUBNET=.*', 'INTERNAL_NETWORK_SUBNET=%SUBNET%' | Set-Content '%~dp0.env'"
echo.

:: ---- Pilih mode server ----
echo Pilih mode server:
echo   [1] Laragon (Apache) - Akses via http://%LOCAL_IP%
echo   [2] PHP Artisan Serve - Akses via http://%LOCAL_IP%:8000
echo.
set /p PILIHAN="Masukkan pilihan (1 atau 2) [default: 2]: "

if "%PILIHAN%"=="1" goto LARAGON_MODE
goto ARTISAN_MODE

:: ========== MODE LARAGON (APACHE) ==========
:LARAGON_MODE
echo.
echo [2/3] Menggunakan Laragon (Apache)...

tasklist /FI "IMAGENAME eq laragon.exe" 2>NUL | find /I "laragon.exe" >NUL
if %ERRORLEVEL% NEQ 0 (
    echo Laragon belum berjalan. Menyalakan Laragon...
    start "" "C:\laragon\laragon.exe"
    echo Menunggu Laragon siap (15 detik)...
    timeout /t 15 /nobreak >nul
) else (
    echo Laragon sudah berjalan.
)

:: Clear Laravel cache
echo Membersihkan cache Laravel...
php artisan config:clear >nul 2>&1
php artisan cache:clear >nul 2>&1
echo.

echo ============================================
echo   AKSES INTRANET SIAP! (Laragon Mode)
echo ============================================
echo.
echo [STAFF / INTERNAL]
echo   http://%LOCAL_IP%/
echo   http://project-magang.test/  (jika hosts sudah diset)
echo.
echo [TAMU - Read Only]
echo   http://%LOCAL_IP%/tamu
echo.
echo ============================================
echo Tekan sembarang tombol untuk keluar...
pause >nul
goto END

:: ========== MODE ARTISAN SERVE ==========
:ARTISAN_MODE
echo.
echo [2/3] Menjalankan PHP Artisan Serve...

:: Hentikan PHP server yang mungkin masih jalan di port 8000
echo Menghentikan server PHP lama (jika ada)...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8000 " ^| findstr "LISTENING"') do (
    taskkill /F /PID %%a >nul 2>&1
)
timeout /t 1 /nobreak >nul

:: Clear Laravel cache sebelum start
echo Membersihkan cache Laravel...
php artisan config:clear >nul 2>&1
php artisan cache:clear >nul 2>&1
php artisan session:flush >nul 2>&1
echo.

echo ============================================
echo   SERVER INTRANET BERJALAN!
echo ============================================
echo.
echo [STAFF / INTERNAL - %LOCAL_IP%]
echo   Surat Masuk  : http://%LOCAL_IP%:8000/
echo   Surat Keluar : http://%LOCAL_IP%:8000/surat-keluar
echo   Dashboard    : http://%LOCAL_IP%:8000/dashboard  (admin)
echo.
echo [TAMU - Read Only]
echo   Surat Masuk  : http://%LOCAL_IP%:8000/tamu
echo   Surat Keluar : http://%LOCAL_IP%:8000/tamu/surat-keluar
echo.
echo Server berjalan di semua interface (0.0.0.0:8000)
echo Tekan CTRL+C untuk menghentikan server
echo ============================================
echo.

php artisan serve --host=0.0.0.0 --port=8000

:END
pause
