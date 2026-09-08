@echo off
:: Wrapper untuk menjalankan "Arsip Surat (server).bat" via Windows Task Scheduler
:: Dibuat otomatis - jangan dihapus jika ingin auto-start jam 07:00
cd /d "%~dp0"
call "Arsip Surat (server).bat"
