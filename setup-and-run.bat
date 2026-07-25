@echo off
title Arsip BPSDMP - Master 1-Pintu Auto-Setup & System Runner
color 0A
setlocal EnableDelayedExpansion

:: ── [0] Self-Elevate Administrator (UAC) ──────────────────────────────
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [INFO] Meminta Hak Access Administrator untuk Konfigurasi Firewall ^& Hosts...
    powershell -Command "Start-Process -FilePath cmd.exe -ArgumentList '/c \"%~f0\"' -Verb RunAs -WorkingDirectory '%~dp0'"
    exit /b
)

cd /d "%~dp0"

echo ====================================================================
echo   ARSIP SURAT BPSDMP ^— MASTER 1-PINTU AUTO-SETUP ^& SYSTEM RUNNER
echo ====================================================================
echo.

:: ── [DETECT PHP] Mencari PHP dari Laragon (tidak butuh PATH) ─────────
SET "PHP=php"
echo [PHP] Mendeteksi PHP dari Laragon...
FOR /D %%V IN ("C:\laragon\bin\php\php-8*") DO (
    IF EXIST "%%V\php.exe" SET "PHP=%%V\php.exe"
)
IF "!PHP!"=="php" (
    FOR /D %%V IN ("C:\laragon\bin\php\php-*") DO (
        IF EXIST "%%V\php.exe" SET "PHP=%%V\php.exe"
    )
)
IF "!PHP!"=="php" (
    echo [WARNING] PHP Laragon tidak ditemukan! Mencoba dari PATH sistem...
    where php >nul 2>&1
    if !ERRORLEVEL! NEQ 0 (
        echo [ERROR] PHP tidak ditemukan sama sekali. Pastikan Laragon terinstall di C:\laragon
        echo.
        pause
        exit /b 1
    )
) ELSE (
    echo [OK] PHP ditemukan: !PHP!
)
echo.

:: ── [PRE] Pembersihan Storage Otomatis Sebelum Startup ───────────────
echo [PRE] Membersihkan storage sementara (views, sessions, cache)...
IF EXIST "storage\framework\views" (
    del /f /q "storage\framework\views\*.php" >nul 2>&1
    del /f /q "storage\framework\views\*.html" >nul 2>&1
)
IF EXIST "storage\framework\sessions" (
    for /f "delims=" %%F in ('dir /b /a-d "storage\framework\sessions" 2^>nul') do (
        del /f /q "storage\framework\sessions\%%F" >nul 2>&1
    )
)
IF EXIST "storage\framework\cache\data" (
    rd /s /q "storage\framework\cache\data" >nul 2>&1
    md "storage\framework\cache\data" >nul 2>&1
)
echo [OK] Storage sementara berhasil dibersihkan.
echo.

:: ── [1] Memeriksa & Menyalakan Service Laragon ────────────────────────
echo [1/8] Memeriksa status Laragon (Apache ^& MySQL)...
tasklist /FI "IMAGENAME eq laragon.exe" 2>NUL | find /I "laragon.exe" >NUL
if %ERRORLEVEL% NEQ 0 (
    echo [ACTION] Menyalakan Laragon...
    if exist "C:\laragon\laragon.exe" (
        start "" "C:\laragon\laragon.exe"
        echo Menunggu service Laragon aktif (10 detik)...
        timeout /t 10 /nobreak >nul
    ) else (
        echo [WARNING] Laragon tidak ditemukan di C:\laragon\laragon.exe! Pastikan Laragon terinstall.
    )
) else (
    echo [OK] Laragon sudah aktif.
)

:: ── [2] Inisialisasi Environment File (.env) ──────────────────────────
echo.
echo [2/8] Memeriksa berkas konfigurasi .env...
if not exist ".env" (
    echo [ACTION] Membuat berkas .env dari .env.example...
    copy .env.example .env >nul
)

:: Pastikan APP_URL=http://kearsipan.local dan INTRANET_ENABLED=true
powershell -Command "(Get-Content .env) -replace '^APP_URL=.*', 'APP_URL=http://kearsipan.local' -replace '^INTRANET_ENABLED=.*', 'INTRANET_ENABLED=true' | Set-Content .env"

"!PHP!" artisan key:generate --force >nul 2>&1

:: ── [3] Inisialisasi Database MySQL (arsip_bpsdmp) ────────────────────
echo.
echo [3/8] Memeriksa database MySQL arsip_bpsdmp...
"!PHP!" -r "try { $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', ''); $pdo->exec('CREATE DATABASE IF NOT EXISTS `arsip_bpsdmp` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'); echo '[OK] Database arsip_bpsdmp siap.' . PHP_EOL; } catch (Exception $e) { echo '[WARNING] Gagal konek MySQL: ' . $e->getMessage() . PHP_EOL; }"

:: ── [4] Memeriksa Dependensi & Migrasi Database ───────────────────────
echo.
echo [4/8] Memeriksa dependensi ^& menjalankan migrasi database...
if not exist "vendor/autoload.php" (
    echo [ACTION] Menginstall dependensi Composer...
    call composer install --no-dev --optimize-autoloader --no-interaction
)

"!PHP!" artisan migrate --force
"!PHP!" artisan db:seed --force

:: ── [5] Virtual Host Apache (kearsipan.local.conf) ────────────────────
echo.
echo [5/8] Mengonfigurasi Virtual Host Apache (kearsipan.local)...
if exist "C:\laragon\etc\apache2\sites-enabled" (
    (
        echo define ROOT "C:/laragon/www/project-magang/public"
        echo define SITE "kearsipan.local"
        echo.
        echo ^<VirtualHost *:80^>
        echo     DocumentRoot "${ROOT}"
        echo     ServerName ${SITE}
        echo     ServerAlias *.${SITE}
        echo     ^<Directory "${ROOT}"^>
        echo         AllowOverride All
        echo         Require all granted
        echo     ^</Directory^>
        echo ^</VirtualHost^>
        echo.
        echo ^<VirtualHost *:443^>
        echo     DocumentRoot "${ROOT}"
        echo     ServerName ${SITE}
        echo     ServerAlias *.${SITE}
        echo     ^<Directory "${ROOT}"^>
        echo         AllowOverride All
        echo         Require all granted
        echo     ^</Directory^>
        echo     SSLEngine on
        echo     SSLCertificateFile      C:/laragon/etc/ssl/laragon.crt
        echo     SSLCertificateKeyFile   C:/laragon/etc/ssl/laragon.key
        echo ^</VirtualHost^>
    ) > "C:\laragon\etc\apache2\sites-enabled\kearsipan.local.conf"
    echo [OK] Virtual Host kearsipan.local.conf berhasil dibuat.
)

:: ── [6] Registrasi Windows Hosts File ──────────────────────────────────
echo.
echo [6/8] Memeriksa entri Windows Hosts (127.0.0.1 kearsipan.local)...
findstr /C:"kearsipan.local" "C:\Windows\System32\drivers\etc\hosts" >nul
if %ERRORLEVEL% NEQ 0 (
    echo 127.0.0.1    kearsipan.local>> "C:\Windows\System32\drivers\etc\hosts"
    echo [OK] Domain kearsipan.local berhasil ditambahkan ke hosts file.
) else (
    echo [OK] Domain kearsipan.local sudah terdaftar di hosts file.
)

:: ── [7] Aturan Windows Firewall (Port 80 Inbound) ──────────────────────
echo.
echo [7/8] Memeriksa aturan Windows Firewall Port 80...
powershell -Command "if (!(Get-NetFirewallRule -DisplayName 'Laragon Apache HTTP' -ErrorAction SilentlyContinue)) { New-NetFirewallRule -DisplayName 'Laragon Apache HTTP' -Direction Inbound -Protocol TCP -LocalPort 80 -Action Allow; Write-Host '[OK] Aturan Firewall Port 80 berhasil dibuat.' } else { Write-Host '[OK] Aturan Firewall Port 80 sudah ada.' }"

:: ── [8] Optimization & Launch ──────────────────────────────────────────
echo.
echo [8/8] Mengoptimalkan cache ^& merestart Apache...
"!PHP!" artisan storage:link --force >nul 2>&1
"!PHP!" artisan config:cache
"!PHP!" artisan route:cache
"!PHP!" artisan view:cache

:: Restart httpd jika berjalan
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I "httpd.exe" >NUL
if %ERRORLEVEL% EQ 0 (
    taskkill /F /IM httpd.exe >nul 2>&1
    timeout /t 2 /nobreak >nul
    if exist "C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\bin\httpd.exe" (
        start "" "C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\bin\httpd.exe"
    )
)

echo.
echo ====================================================================
echo  SISTEM SURAT BPSDMP BERHASIL DIAKTIFKAN DAN SIAP DIGUNAKAN!
echo ====================================================================
echo  - Akses Internal (Staff/Admin) : http://kearsipan.local/
echo  - Akses WiFi Tamu (Read-Only)  : http://kearsipan.local/tamu
echo.
echo  Catatan: Storage (views, sessions, cache) dibersihkan otomatis
echo  setiap kali setup-and-run.bat dijalankan (sebelum sistem aktif).
echo ====================================================================
echo.
endlocal
pause
