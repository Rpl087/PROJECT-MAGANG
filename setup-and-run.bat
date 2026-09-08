@echo off
title Arsip BPSDMP - Master 1-Pintu Auto-Setup & System Runner
color 0A
setlocal EnableDelayedExpansion

net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [INFO] Meminta Hak Administrator untuk Konfigurasi Firewall ^& Hosts...
    powershell -Command "Start-Process -FilePath cmd.exe -ArgumentList '/c \"%~f0\"' -Verb RunAs -WorkingDirectory '%~dp0'"
    exit /b
)

cd /d "%~dp0"

echo ====================================================================
echo   ARSIP SURAT BPSDMP - MASTER 1-PINTU AUTO-SETUP ^& SYSTEM RUNNER
echo ====================================================================
echo.

SET "PHP="
SET "PHP_INI="
echo [PHP] Mendeteksi PHP kompatibel dari Laragon (PHP 8.3+)...
FOR /D %%V IN ("C:\laragon\bin\php\php-8.5*" "C:\laragon\bin\php\php-8.4*" "C:\laragon\bin\php\php-8.3*") DO (
    IF EXIST "%%V\php.exe" (
        SET "PHP=%%V\php.exe"
        SET "PHP_INI=%%V\php.ini"
    )
)
IF NOT DEFINED PHP (
    FOR /D %%V IN ("C:\laragon\bin\php\php-8*") DO (
        IF EXIST "%%V\php.exe" (
            SET "PHP=%%V\php.exe"
            SET "PHP_INI=%%V\php.ini"
        )
    )
)
IF NOT DEFINED PHP (
    where php >nul 2>&1
    if !ERRORLEVEL! EQU 0 (
        SET "PHP=php"
    ) else (
        echo [ERROR] PHP tidak ditemukan. Pastikan Laragon terinstall di C:\laragon
        echo.
        pause
        exit /b 1
    )
)
echo [OK] PHP ditemukan: !PHP!
echo.

SET "COMPOSER_BIN="
IF EXIST "C:\laragon\bin\composer\composer.phar" (
    SET "COMPOSER_BIN="!PHP!" C:\laragon\bin\composer\composer.phar"
) ELSE (
    where composer >nul 2>&1
    IF !ERRORLEVEL! EQU 0 (
        SET "COMPOSER_BIN=composer"
    )
)

IF DEFINED PHP_INI (
    IF EXIST "!PHP_INI!" (
        echo [PRE] Memastikan ekstensi zip, pdo_mysql, curl, fileinfo aktif di php.ini...
        powershell -Command "$f='!PHP_INI!'; (Get-Content $f) -replace '^;extension=zip','extension=zip' -replace '^;extension=pdo_mysql','extension=pdo_mysql' -replace '^;extension=curl','extension=curl' -replace '^;extension=fileinfo','extension=fileinfo' -replace '^;extension=mbstring','extension=mbstring' -replace '^;extension=openssl','extension=openssl' | Set-Content $f"
        echo [OK] Ekstensi php.ini terkonfigurasi.
        echo.
    )
)

echo [PRE] Membersihkan cache sementara dan bootstrap lama...
IF EXIST "bootstrap\cache" (
    del /f /q "bootstrap\cache\*.php" >nul 2>&1
)
IF EXIST "storage\framework\views" (
    del /f /q "storage\framework\views\*.php" >nul 2>&1
    del /f /q "storage\framework\views\*.html" >nul 2>&1
)
IF EXIST "storage\framework\cache\data" (
    rd /s /q "storage\framework\cache\data" >nul 2>&1
    md "storage\framework\cache\data" >nul 2>&1
)
echo [OK] Cache dibersihkan (path lama dinetralisir^).
echo.

echo [1/10] Memeriksa dan mengaktifkan servis Apache ^& MySQL...

tasklist | findstr /i "httpd.exe" >nul
IF !ERRORLEVEL! EQU 0 (
    echo [OK] Apache ^(httpd.exe^) sudah berjalan.
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
    echo [OK] MySQL ^(mysqld.exe^) sudah berjalan.
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
    IF EXIST "C:\laragon\laragon.exe" start "" "C:\laragon\laragon.exe"
)

echo [WAIT] Menunggu MySQL siap menerima koneksi...
SET "RETRY=0"
:WAIT_MYSQL
"!PHP!" -r "try { new PDO('mysql:host=127.0.0.1;port=3306', 'root', ''); exit(0); } catch (Exception $e) { exit(1); }" >nul 2>&1
IF !ERRORLEVEL! EQU 0 (
    echo [OK] MySQL siap menerima koneksi.
    GOTO :MYSQL_DONE
)
SET /A RETRY+=1
IF !RETRY! GEQ 15 (
    echo [WARNING] MySQL belum merespons setelah 30 detik. Lanjutkan proses...
    GOTO :MYSQL_DONE
)
echo [WAIT] Menghubungi MySQL, percobaan !RETRY!/15 -- tunggu 2 detik...
ping 127.0.0.1 -n 3 >nul
GOTO :WAIT_MYSQL
:MYSQL_DONE
echo.

echo [2/10] Memeriksa berkas konfigurasi .env...
if not exist ".env" (
    echo [ACTION] Membuat berkas .env dari .env.example...
    copy .env.example .env >nul
)
powershell -Command "(Get-Content .env) -replace '^APP_URL=.*','APP_URL=http://kearsipan.local' -replace '^INTRANET_ENABLED=.*','INTRANET_ENABLED=false' -replace '^SESSION_DRIVER=.*','SESSION_DRIVER=database' -replace '^SESSION_EXPIRE_ON_CLOSE=.*','SESSION_EXPIRE_ON_CLOSE=false' -replace '^SESSION_LIFETIME=.*','SESSION_LIFETIME=480' | Set-Content .env"
"!PHP!" artisan key:generate --force >nul 2>&1
echo [OK] Konfigurasi .env ^& APP_KEY siap.
echo.

echo [3/10] Memeriksa berkas Google Credentials JSON...
if exist "storage\app\google-credentials.json" (
    echo [OK] File google-credentials.json ditemukan.
) else (
    echo [WARNING] File storage\app\google-credentials.json TIDAK DITEMUKAN!
    echo [WARNING] Salin file tersebut untuk mengaktifkan sinkronisasi Google Drive ^& Sheets.
)
echo.

echo [4/10] Memeriksa database MySQL arsip_bpsdmp...
"!PHP!" -r "try { $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', ''); $pdo->exec('CREATE DATABASE IF NOT EXISTS `arsip_bpsdmp` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'); echo '[OK] Database arsip_bpsdmp siap.' . PHP_EOL; } catch (Exception $e) { echo '[WARNING] Gagal konek MySQL: ' . $e->getMessage() . PHP_EOL; }"
echo.

echo [5/10] Memeriksa dependensi Composer ^& migrasi database...
if not exist "vendor\autoload.php" (
    echo [ACTION] Menginstall dependensi Composer...
    IF DEFINED COMPOSER_BIN (
        call !COMPOSER_BIN! install --no-dev --optimize-autoloader --no-interaction --prefer-dist
    ) ELSE (
        echo [ERROR] Composer tidak ditemukan untuk instalasi vendor.
    )
)

"!PHP!" artisan optimize:clear >nul 2>&1
"!PHP!" artisan migrate --force
"!PHP!" artisan db:seed --force
"!PHP!" artisan storage:link --force >nul 2>&1
echo.

echo [6/10] Memverifikasi aset frontend...
if exist "public\css\app.css" (
    echo [OK] Berkas CSS utama ditemukan (public\css\app.css^).
    echo [INFO] Sistem menggunakan Vanilla CSS murni ^& CDN (tanpa dependensi Vite/Node^).
) else (
    echo [WARNING] Berkas public\css\app.css tidak ditemukan!
)
echo.

echo [7/10] Mengonfigurasi Virtual Host Apache (kearsipan.local^)...
SET "PROJ_ROOT=%~dp0"
SET "PROJ_ROOT=%PROJ_ROOT:~0,-1%"
SET "PROJ_ROOT_FWD=%PROJ_ROOT:\=/%"
if exist "C:\laragon\etc\apache2\sites-enabled" (
    (
        echo define ROOT "%PROJ_ROOT_FWD%/public"
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
    ) > "C:\laragon\etc\apache2\sites-enabled\kearsipan.local.conf"
    echo [OK] Virtual Host kearsipan.local.conf dibuat.
    echo [OK] DocumentRoot: %PROJ_ROOT_FWD%/public
)
echo.

echo [8/10] Memeriksa entri Windows Hosts...
SET "HOSTS_OK=0"
findstr /C:"kearsipan.local" "C:\Windows\System32\drivers\etc\hosts" >nul 2>&1
if !ERRORLEVEL! EQU 0 (
    echo [OK] Domain kearsipan.local sudah terdaftar di hosts file.
    SET "HOSTS_OK=1"
) ELSE (
    echo 127.0.0.1    kearsipan.local>> "C:\Windows\System32\drivers\etc\hosts"
    if !ERRORLEVEL! EQU 0 (
        echo [OK] Domain kearsipan.local berhasil ditambahkan ke hosts file.
        SET "HOSTS_OK=1"
    ) else (
        echo [WARNING] Gagal memperbarui hosts file. Sistem dapat diakses via http://localhost.
    )
)
echo.

echo [9/10] Memeriksa aturan Windows Firewall Port 80...
powershell -Command "if (!(Get-NetFirewallRule -DisplayName 'Laragon Apache HTTP' -ErrorAction SilentlyContinue)) { New-NetFirewallRule -DisplayName 'Laragon Apache HTTP' -Direction Inbound -Protocol TCP -LocalPort 80 -Action Allow; Write-Host '[OK] Aturan Firewall Port 80 dibuat.' } else { Write-Host '[OK] Aturan Firewall Port 80 sudah ada.' }"
echo.

echo [10/10] Mengoptimalkan cache ^& background workers...
"!PHP!" artisan config:cache
"!PHP!" artisan route:cache
"!PHP!" artisan view:cache

tasklist /v | findstr /i "Arsip BPSDMP Queue Worker" >nul
IF !ERRORLEVEL! NEQ 0 (
    start "Arsip BPSDMP Queue Worker" /min cmd /k ""!PHP!" artisan queue:work --tries=3 --timeout=90 --max-jobs=500 --max-time=3600"
    echo [OK] Queue Worker aktif di background ^(auto-restart setiap 500 jobs/1 jam^).
) else (
    echo [OK] Queue Worker sudah berjalan.
)

tasklist /v | findstr /i "Arsip BPSDMP Scheduler" >nul
IF !ERRORLEVEL! NEQ 0 (
    start "Arsip BPSDMP Scheduler" /min cmd /k ""!PHP!" artisan schedule:work"
    echo [OK] Task Scheduler aktif -- pembersihan storage:clean berjalan otomatis pukul 17:00.
) else (
    echo [OK] Task Scheduler sudah berjalan.
)

tasklist | findstr /i "httpd.exe" >nul
IF !ERRORLEVEL! EQU 0 (
    echo [INFO] Merestart Apache agar Virtual Host aktif...
    taskkill /F /IM httpd.exe >nul 2>&1
    ping 127.0.0.1 -n 3 >nul
)
SET "HTTPD_BIN="
FOR /D %%A IN ("C:\laragon\bin\apache\httpd-*") DO (
    IF EXIST "%%A\bin\httpd.exe" SET "HTTPD_BIN=%%A\bin\httpd.exe"
)
IF DEFINED HTTPD_BIN (
    start "" "!HTTPD_BIN!"
    ping 127.0.0.1 -n 3 >nul
    echo [OK] Apache berhasil direstart.
)

SET "APP_URL=http://localhost"
IF "!HOSTS_OK!"=="1" SET "APP_URL=http://kearsipan.local"

echo.
echo ====================================================================
echo  SISTEM ARSIP BPSDMP BERHASIL DIAKTIFKAN DAN SIAP DIGUNAKAN!
echo ====================================================================
echo  - URL Utama       : !APP_URL!/
echo  - URL Tamu (WiFi^) : !APP_URL!/tamu
echo.
echo  KREDENSIAL LOGIN (Dapat menggunakan Username ATAU Email^):
echo  [1] ADMIN      : Admin (atau admin@bpsdmp.go.id^)        / admin_BPSDMP26
echo  [2] INTERNSHIP : Internship (atau internship@bpsdmp.go.id^) / internship_BPSDMP
echo ====================================================================
echo.

start "" "!APP_URL!/login"
start "" chrome --incognito "!APP_URL!/login" 2>nul || start "" msedge -inprivate "!APP_URL!/login" 2>nul
echo [OK] Selesai. Browser telah dibuka. Silakan login.
echo.
endlocal
pause