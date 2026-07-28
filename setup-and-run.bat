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
echo   ARSIP SURAT BPSDMP — MASTER 1-PINTU AUTO-SETUP ^& SYSTEM RUNNER
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

:: ── [PRE] Pembersihan Storage Saja (Views & Cache - Preservasi Sessions) ──
echo [PRE] Membersihkan cache sementara (views ^& cache data)...
IF EXIST "storage\framework\views" (
    del /f /q "storage\framework\views\*.php" >nul 2>&1
    del /f /q "storage\framework\views\*.html" >nul 2>&1
)
IF EXIST "storage\framework\cache\data" (
    rd /s /q "storage\framework\cache\data" >nul 2>&1
    md "storage\framework\cache\data" >nul 2>&1
)
echo [OK] Cache views ^& framework data berhasil dibersihkan (sesi login pengguna dipertahankan).
echo.

:: ── [1/10] Memeriksa Status Apache & MySQL ────────────────────────────
echo [1/10] Memeriksa status Apache ^& MySQL...
SET "SVC_OK=1"

tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I "httpd.exe" >NUL
IF !ERRORLEVEL! EQU 0 (
    echo [OK] Apache (httpd.exe) berjalan.
) ELSE (
    echo [!] Apache (httpd.exe) belum berjalan.
    SET "SVC_OK=0"
)

tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I "mysqld.exe" >NUL
IF !ERRORLEVEL! EQU 0 (
    echo [OK] MySQL (mysqld.exe) berjalan.
) ELSE (
    echo [!] MySQL (mysqld.exe) belum berjalan.
    SET "SVC_OK=0"
)

IF "!SVC_OK!"=="0" (
    echo.
    echo ================================================================
    echo  PERHATIAN: Laragon belum aktif!
    echo  1. Buka Laragon terlebih dahulu
    echo  2. Klik tombol [Start All] di Laragon
    echo  3. Tunggu Apache dan MySQL aktif
    echo  4. Lalu tekan sembarang tombol di sini untuk melanjutkan...
    echo ================================================================
    pause >nul
    echo.
)

:: ── [WAIT] Tunggu MySQL benar-benar siap menerima koneksi ─────────────
echo [WAIT] Menunggu MySQL siap menerima koneksi...
SET "RETRY=0"
:WAIT_MYSQL
"!PHP!" -r "try { new PDO('mysql:host=127.0.0.1;port=3306', 'root', ''); exit(0); } catch (Exception $e) { exit(1); }" >nul 2>&1
IF !ERRORLEVEL! EQU 0 (
    echo [OK] MySQL siap menerima koneksi.
    GOTO :MYSQL_DONE
)
SET /A RETRY+=1
IF !RETRY! GEQ 20 (
    echo [WARNING] MySQL belum merespons setelah 40 detik. Lanjutkan...
    GOTO :MYSQL_DONE
)
echo [WAIT] MySQL belum siap, percobaan !RETRY!/20 -- tunggu 2 detik...
ping 127.0.0.1 -n 3 >nul
GOTO :WAIT_MYSQL
:MYSQL_DONE
echo.


:: ── [2/10] Inisialisasi Environment File (.env) ──────────────────────
echo [2/10] Memeriksa berkas konfigurasi .env...
if not exist ".env" (
    echo [ACTION] Membuat berkas .env dari .env.example...
    copy .env.example .env >nul
)

:: Pastikan APP_URL=http://kearsipan.local dan INTRANET_ENABLED=true
powershell -Command "(Get-Content .env) -replace '^APP_URL=.*', 'APP_URL=http://kearsipan.local' -replace '^INTRANET_ENABLED=.*', 'INTRANET_ENABLED=true' | Set-Content .env"

"!PHP!" artisan key:generate --force >nul 2>&1

:: ── [3/10] Pengecekan Google Credentials JSON ─────────────────────────
echo.
echo [3/10] Memeriksa berkas Google Credentials JSON...
if exist "storage\app\google-credentials.json" (
    echo [OK] Berkas storage\app\google-credentials.json ditemukan.
) else (
    echo [WARNING] Berkas storage\app\google-credentials.json TIDAK DITEMUKAN!
    echo [WARNING] Integrasi Google Drive ^& Sheets mungkin memerlukan berkas ini.
)

:: ── [4/10] Inisialisasi Database MySQL (arsip_bpsdmp) ──────────────────
echo.
echo [4/10] Memeriksa database MySQL arsip_bpsdmp...
"!PHP!" -r "try { $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', ''); $pdo->exec('CREATE DATABASE IF NOT EXISTS `arsip_bpsdmp` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'); echo '[OK] Database arsip_bpsdmp siap.' . PHP_EOL; } catch (Exception $e) { echo '[WARNING] Gagal konek MySQL: ' . $e->getMessage() . PHP_EOL; }"

:: ── [5/10] Memeriksa Dependensi & Migrasi Database ─────────────────────
echo.
echo [5/10] Memeriksa dependensi Composer ^& menjalankan migrasi database...
if not exist "vendor/autoload.php" (
    echo [ACTION] Menginstall dependensi Composer...
    call composer install --no-dev --optimize-autoloader --no-interaction
)

"!PHP!" artisan migrate --force
"!PHP!" artisan db:seed --force

:: ── [6/10] Build Aset Frontend (Vite) ──────────────────────────────────
echo.
echo [6/10] Memeriksa kompilasi aset frontend (Vite)...
if not exist "public/build" (
    echo [ACTION] Membangun aset frontend produksi (npm run build)...
    where npm >nul 2>&1
    if !ERRORLEVEL! EQU 0 (
        if not exist "node_modules" (
            echo [ACTION] Menginstall modul Node.js...
            call npm install --no-audit --no-fund
        )
        call npm run build
        echo [OK] Aset frontend berhasil dikompilasi.
    ) else (
        echo [WARNING] Node.js/NPM tidak ditemukan. Pastikan public/build sudah ada.
    )
) else (
    echo [OK] Aset frontend sudah dikompilasi (public/build ada).
)

:: ── [7/10] Virtual Host Apache (kearsipan.local.conf) ──────────────────
echo.
echo [7/10] Mengonfigurasi Virtual Host Apache (kearsipan.local)...
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

:: ── [8/10] Registrasi Windows Hosts File ────────────────────────────────
echo.
echo [8/10] Memeriksa entri Windows Hosts (127.0.0.1 kearsipan.local)...
findstr /C:"kearsipan.local" "C:\Windows\System32\drivers\etc\hosts" >nul
if %ERRORLEVEL% NEQ 0 (
    echo 127.0.0.1    kearsipan.local>> "C:\Windows\System32\drivers\etc\hosts"
    echo [OK] Domain kearsipan.local berhasil ditambahkan ke hosts file.
) else (
    echo [OK] Domain kearsipan.local sudah terdaftar di hosts file.
)

:: ── [9/10] Aturan Windows Firewall (Port 80 Inbound) ────────────────────
echo.
echo [9/10] Memeriksa aturan Windows Firewall Port 80...
powershell -Command "if (!(Get-NetFirewallRule -DisplayName 'Laragon Apache HTTP' -ErrorAction SilentlyContinue)) { New-NetFirewallRule -DisplayName 'Laragon Apache HTTP' -Direction Inbound -Protocol TCP -LocalPort 80 -Action Allow; Write-Host '[OK] Aturan Firewall Port 80 berhasil dibuat.' } else { Write-Host '[OK] Aturan Firewall Port 80 sudah ada.' }"

:: ── [10/10] Optimization, Queue Worker, & Launch ────────────────────────
echo.
echo [10/10] Mengoptimalkan cache ^& merestart Apache...
"!PHP!" artisan storage:link --force >nul 2>&1
"!PHP!" artisan config:cache
"!PHP!" artisan route:cache
"!PHP!" artisan view:cache

:: Jalankan Queue Worker di background window jika belum aktif
tasklist /FI "WINDOWTITLE eq Arsip BPSDMP Queue Worker*" 2>NUL | find /I "cmd.exe" >NUL
IF !ERRORLEVEL! NEQ 0 (
    echo [INFO] Menjalankan Queue Worker di background...
    start "Arsip BPSDMP Queue Worker" /min cmd /c ""!PHP!" artisan queue:work --tries=3 --timeout=90"
    echo [OK] Queue Worker berhasil aktif di background.
) else (
    echo [OK] Queue Worker sudah berjalan di background.
)

:: Restart Apache agar Virtual Host kearsipan.local aktif
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I "httpd.exe" >NUL
IF !ERRORLEVEL! EQ 0 (
    echo [INFO] Merestart Apache untuk memuat Virtual Host baru...
    taskkill /F /IM httpd.exe >nul 2>&1
    ping 127.0.0.1 -n 3 >nul
    SET "HTTPD_BIN="
    FOR /D %%A IN ("C:\laragon\bin\apache\httpd-*") DO (
        IF EXIST "%%A\bin\httpd.exe" SET "HTTPD_BIN=%%A\bin\httpd.exe"
    )
    IF DEFINED HTTPD_BIN (
        start "" "!HTTPD_BIN!"
        echo [OK] Apache berhasil direstart.
    ) ELSE (
        echo [WARNING] httpd.exe tidak ditemukan. Restart Apache secara manual dari Laragon.
    )
) ELSE (
    echo [WARNING] Apache tidak berjalan. Aktifkan Laragon untuk memuat Virtual Host.
)

echo.
echo ====================================================================
echo  SISTEM SURAT BPSDMP BERHASIL DIAKTIFKAN DAN SIAP DIGUNAKAN!
echo ====================================================================
echo  - Akses Internal (Staff/Admin) : http://kearsipan.local/
echo  - Akses WiFi Tamu (Read-Only)  : http://kearsipan.local/tamu
echo.
echo  Catatan: Storage views ^& cache data dibersihkan otomatis.
echo  Sesi login aktif tetap dipertahankan.
echo ====================================================================
echo.
endlocal
pause
