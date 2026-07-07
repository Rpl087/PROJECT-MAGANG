@echo off
title Arsip BPSDMP - Setup HTTPS (mkcert + Laragon)
color 0A

echo ============================================
echo   ARSIP SURAT BPSDMP - HTTPS Setup
echo ============================================
echo.

:: ---- Cek mkcert ----
set MKCERT=C:\laragon\bin\mkcert.exe
if not exist "%MKCERT%" (
    echo [ERROR] mkcert.exe tidak ditemukan di C:\laragon\bin\
    echo Jalankan start-intranet.bat dulu untuk mendownload mkcert.
    pause
    exit /b 1
)

:: ---- Cek IP Lokal ----
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /i "IPv4"') do set LOCAL_IP=%%a
for /f "tokens=* delims= " %%b in ("%LOCAL_IP%") do set LOCAL_IP=%%b
echo IP Lokal: %LOCAL_IP%
echo.

:: ---- Install CA ke Windows Trust Store ----
echo [1/4] Menginstall root CA mkcert ke Windows...
"%MKCERT%" -install
echo.

:: ---- Generate SSL Certificate ----
echo [2/4] Membuat SSL Certificate untuk project-magang.test dan IP lokal...
set CERT_DIR=C:\laragon\etc\ssl
if not exist "%CERT_DIR%" mkdir "%CERT_DIR%"

cd /d "%CERT_DIR%"
"%MKCERT%" -key-file project-magang.key -cert-file project-magang.crt project-magang.test %LOCAL_IP% localhost 127.0.0.1
echo.

:: ---- Update Laragon VHost Config ----
echo [3/4] Membuat konfigurasi HTTPS di Laragon Apache...
set VHOST_DIR=C:\laragon\etc\apache2\sites-enabled
set VHOST_FILE=%VHOST_DIR%\project-magang.test.conf

echo Backup konfigurasi lama...
if exist "%VHOST_FILE%" copy "%VHOST_FILE%" "%VHOST_FILE%.bak" >nul

(
echo ^<VirtualHost *:80^>
echo     DocumentRoot "C:/laragon/www/project-magang/public"
echo     ServerName project-magang.test
echo     ServerAlias %LOCAL_IP%
echo     ^<IfModule mod_rewrite.c^>
echo         RewriteEngine On
echo         RewriteCond %%{HTTPS} off
echo         RewriteRule ^(.*)$ https://%%{HTTP_HOST}%%{REQUEST_URI} [R=301,L]
echo     ^</IfModule^>
echo ^</VirtualHost^>
echo.
echo ^<VirtualHost *:443^>
echo     DocumentRoot "C:/laragon/www/project-magang/public"
echo     ServerName project-magang.test
echo     ServerAlias %LOCAL_IP%
echo.
echo     SSLEngine on
echo     SSLCertificateFile "C:/laragon/etc/ssl/project-magang.crt"
echo     SSLCertificateKeyFile "C:/laragon/etc/ssl/project-magang.key"
echo.
echo     ^<Directory "C:/laragon/www/project-magang/public"^>
echo         AllowOverride All
echo         Require all granted
echo         Options -Indexes
echo     ^</Directory^>
echo.
echo     ErrorLog "C:/laragon/logs/project-magang-error.log"
echo     CustomLog "C:/laragon/logs/project-magang-access.log" combined
echo ^</VirtualHost^>
) > "%VHOST_FILE%"

echo VHost HTTPS berhasil dibuat!
echo.

:: ---- Restart Apache ----
echo [4/4] Merestart Apache di Laragon...
echo Silakan klik "Restart All" di Laragon, atau tekan sembarang tombol setelah restart...
echo.

echo ============================================
echo   HTTPS SETUP SELESAI!
echo ============================================
echo.
echo Akses aplikasi via HTTPS:
echo.
echo   [STAFF / INTERNAL]
echo   https://%LOCAL_IP%/
echo   https://project-magang.test/         (jika hosts file sudah diset)
echo.
echo   [TAMU - Read Only]
echo   https://%LOCAL_IP%/tamu
echo   https://project-magang.test/tamu
echo.
echo CATATAN: Untuk perangkat lain, install certificate CA di:
echo   C:\Users\%USERNAME%\AppData\Local\mkcert\rootCA.pem
echo   Kirimkan file ini ke perangkat lain dan install sebagai Trusted Root CA.
echo.
echo ============================================
pause
