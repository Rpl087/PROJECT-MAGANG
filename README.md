# 📂 Sistem Pengarsipan Digital Surat — BPSDMP

Aplikasi web berbasis **Laravel 13** untuk pengarsipan surat masuk dan surat keluar secara digital. Data arsip disimpan langsung ke **Google Spreadsheet** via Google Sheets API, file PDF disimpan di server lokal dan dapat diakses publik melalui URL unik.

---

## 🚀 Fitur Utama

- 🔐 **Autentikasi & RBAC** — Akses login aman dengan multi-role (Admin, Internship, Maintenance).
- 📥 **Arsip Surat Masuk & Keluar** — Input data lengkap dan upload file PDF.
- ✏️ **Edit & Hapus Data** — Pengelolaan data arsip langsung terintegrasi dengan Google Sheets.
- 🔍 **Filter & Klasifikasi** — Filter pencarian berdasarkan kode klasifikasi surat.
- 📊 **Dashboard & Rekapitulasi** — Statistik data arsip bulanan, tahunan, dan per klasifikasi.
- 🌐 **Intranet Ready** — Dapat diakses di jaringan lokal (LAN/WiFi) menggunakan domain lokal `kearsipan.local`.
- 🔒 **Cybersecurity Layer** — Proteksi terhadap Formula Injection, CSP, Rate Limiting, dan Session Protection.

---

## 🛠️ Tech Stack

- **Framework**: Laravel 13 (PHP 8.3+)
- **Database**: MySQL / MariaDB (untuk autentikasi & session)
- **Integrasi**: Google Sheets API v4 (storage utama data arsip)
- **Frontend**: Blade + Vanilla CSS (tanpa CSS framework external)
- **Web Server**: Apache (Laragon) — Virtual Host `kearsipan.local`

---

## 🌐 Akses Jaringan Lokal (WiFi / Intranet)

Aplikasi ini mendukung akses intranet menggunakan domain lokal **`kearsipan.local`**. 

### 1. Setup Virtual Host (Server)
Untuk mengarahkan domain lokal ke folder project, tambahkan konfigurasi Virtual Host pada server web Apache:
```apache
<VirtualHost *:80> 
    DocumentRoot "C:/laragon/www/project-magang/public"
    ServerName kearsipan.local
    ServerAlias *.kearsipan.local
    <Directory "C:/laragon/www/project-magang/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 2. Resolusi Host Lokal
Tambahkan pemetaan IP ke berkas `hosts` sistem operasi server (misal: `C:\Windows\System32\drivers\etc\hosts` pada Windows):
```hosts
127.0.0.1 kearsipan.local
```

### 3. Akses Perangkat Lain di WiFi
Perangkat lain yang berada di dalam jaringan WiFi/LAN yang sama dapat mengakses aplikasi dengan:
- **DNS Kantor**: Mengarahkan domain `kearsipan.local` ke IP LAN server via DNS server lokal pada router.
- **Akses Langsung**: Mengakses menggunakan IP LAN Server secara langsung (contoh: `http://192.168.252.15/` atau `http://10.0.0.5/`). Laravel akan mendeteksi Host Header secara otomatis dan menyesuaikan seluruh tautan internal halaman.

---

## ⚡ Setup Awal Aplikasi

1. **Clone Project**: Unduh berkas proyek ke direktori server (`C:\laragon\www\project-magang`).
2. **Install Dependensi**:
   ```bash
   composer install
   ```
3. **Konfigurasi Environment**:
   - Salin file `.env.example` menjadi `.env`.
   - Konfigurasikan koneksi database MySQL, ID Google Sheet, dan folder Google Drive.
   - Buat kunci enkripsi aplikasi:
     ```bash
     php artisan key:generate
     ```
4. **Migrasi Database**:
   ```bash
   php artisan migrate --seed
   ```
5. **Storage Link**: Buat symbolic link untuk file uploads:
   ```bash
   php artisan storage:link
   ```
