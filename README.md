# 📂 Sistem Pengarsipan Digital Surat — BPSDMP

Aplikasi web berbasis **Laravel 13** untuk pengarsipan surat masuk dan surat keluar secara digital. Data arsip disimpan secara aman ke **Google Spreadsheet** via Google Sheets API v4, dan file PDF fisik dikelola secara sinkron di server lokal (`/uploads`) serta **Google Drive API**.

---

## ⚡ Quick Start — Setup Awal & Jalankan Aplikasi

Setelah melakukan `git clone` pada PC server khusus, Anda dapat mengelola jalannya aplikasi menggunakan skrip otomatisasi berikut:

### 📥 1. Inisialisasi Pertama Kali (Setup)
Jalankan file berikut **hanya pada saat instalasi pertama kali** di server:
```cmd
setup-and-run.bat
```
Skrip ini memerlukan hak akses **Administrator** untuk melakukan konfigurasi otomatis:
- 🔑 **UAC Elevation** — Meminta akses Administrator Windows otomatis.
- ⚡ **Service Laragon** — Menyalakan dan mengaktifkan Apache & MySQL di Laragon.
- 📝 **Environment** — Membuat berkas `.env` jika belum ada & mengisi parameter default.
- 🗄️ **Database** — Membuat database `arsip_bpsdmp` dan menjalankan migrasi database.
- 🌐 **Virtual Host** — Membuat VirtualHost Apache `kearsipan.local.conf` di Laragon.
- 📌 **Hosts File** — Mendaftarkan domain `127.0.0.1 kearsipan.local` di berkas `hosts` Windows.
- 🛡️ **Firewall** — Membuka Inbound Port 80 pada Windows Firewall.
- 🧹 **Storage & Cache** — Melakukan pembersihan berkas temporary views & cache framework data.

### 🚀 2. Menjalankan Aplikasi Sehari-hari (Daily Runner)
Untuk penggunaan sehari-hari setelah setup awal berhasil, **jangan** menjalankan skrip setup di atas. Gunakan skrip berikut yang jauh lebih ringan, cepat, dan aman:
```cmd
start-all.bat
```
Skrip runner ini **tidak membutuhkan hak Administrator** dan akan melakukan:
- ⚡ **Service Check** — Memastikan Laragon, Apache, dan MySQL sudah aktif.
- ⚙️ **Queue Worker** — Menyalakan Laravel Queue Worker di latar belakang jika belum aktif.
- 🕔 **Task Scheduler** — Menyalakan Laravel Scheduler di latar belakang jika belum aktif (otomatis menjalankan pembersihan storage setiap pukul **17:00**).
- 🌐 **Auto-Launch** — Membuka browser langsung ke URL login aplikasi.

### 🌐 3. Akses Aplikasi & Kredensial Login
- **Akses Komputer Server (Lokal)**: `http://kearsipan.local/`
- **Akses Perangkat Lain (HP/Laptop via LAN/Wi-Fi)**:
  - HTTP: `http://192.168.100.4/` (atau IP server lokal)
  - HTTPS: `https://192.168.100.4/`
  *(Catatan: Akses via subfolder seperti `http://192.168.100.4/project-magangv1` otomatis di-redirect ke root aplikasi tanpa 404).*
- **Akses WiFi Tamu (Read-Only Tanpa Login)**:
  - `http://kearsipan.local/tamu`
  - `http://192.168.100.4/tamu`

| Peran | Username / Email | Password Default |
|---|---|---|
| **Admin** | `Admin` atau `admin@bpsdmp.go.id` | `admin_BPSDMP26` |
| **Internship** | `Internship` atau `internship@bpsdmp.go.id` | `internship_BPSDMP` |

---

## 🚀 Fitur Utama

| Fitur | Deskripsi |
|---|---|
| 🔐 **Autentikasi & RBAC** | Login aman dengan multi-role: `admin` & `internship` |
| 📥 **Surat Masuk** | Input data lengkap + upload PDF, tersimpan ke Google Sheets (`dbSM`) |
| 📤 **Surat Keluar** | Input data lengkap + upload PDF, tersimpan ke Google Sheets (`dbSK`) |
| ✏️ **Edit & Rename PDF** | Edit data surat otomatis mengubah nama file PDF mentah di lokal (`/uploads`) & Google Drive |
| 🗑️ **Hapus Synchronous** | Hapus data surat otomatis menghapus file PDF mentah dari lokal (`/uploads`) & Google Drive |
| 🔍 **Filter & Klasifikasi** | Pencarian & filter presisi berdasarkan kode klasifikasi surat |
| 📊 **Dashboard & Grafik Bar** | Statistik data arsip bulanan, tahunan, & angka total tepat di atas batang grafik |
| 🖨️ **Laporan Rekap Printable** | Tampilan cetak profesional dengan kisi garis seragam (`1pt solid #333`) & Uraian Informasi Arsip |
| 👥 **Manajemen Pengguna** | Admin dapat mereset password internship & menindaklanjuti permintaan reset |
| 🔔 **Notifikasi Realtime (SSE)** | Admin mendapat notifikasi realtime saat internship mengajukan reset password |
| ⚡ **Mikro-Cache Sheets (30 Detik)** | Refresh tarikan data terbaru spreadsheet tiap 30 detik untuk Admin; 1 menit untuk Internship/Tamu |
| ⏱️ **Sesi 4 Jam & Auto-Prune** | Masa aktif sesi 240 menit (4 jam) di database MySQL dengan pembersihan expired per jam |
| 🌐 **Intranet Speed & Smooth Nav** | Loading animation kontekstual & font fallback sistem lokal untuk navigasi instan di Intranet WiFi |
| 👀 **Mode Tamu (Read-Only)** | Halaman khusus WiFi tamu tanpa login untuk membaca data arsip |
| 🔒 **Cybersecurity Layer** | Brute Force IP Ban, Proteksi Formula Injection, CSP, Rate Limiting, & Production Hardening |
| 🧹 **Task Scheduler** | Pembersihan otomatis sesi expired setiap 1 jam & berkas temporary storage setiap 17:00 |

---

## 🛠️ Tech Stack

| Lapisan | Teknologi |
|---|---|
| **Framework** | Laravel 13 (PHP 8.3+) |
| **Database** | MySQL / MariaDB (autentikasi & session) |
| **Penyimpanan Data** | Google Sheets API v4 (`dbSM` & `dbSK`) |
| **Penyimpanan File** | Server Lokal (`/uploads`) + Google Drive API |
| **Frontend** | Blade Templates + Vanilla CSS (Dark Mode) + Chart.js |
| **Web Server** | Apache via Laragon — Virtual Host `kearsipan.local` |
| **Real-time** | Server-Sent Events (SSE) untuk notifikasi reset password |

---

## 👥 Peran & Hak Akses

| Kemampuan | Admin | Internship | Tamu (WiFi) | Publik |
|---|:---:|:---:|:---:|:---:|
| Login | ✅ | ✅ | ❌ | ❌ |
| Input Surat Masuk | ✅ | ✅ | ❌ | ❌ |
| Input Surat Keluar | ✅ | ✅ | ❌ | ❌ |
| Edit Data & Rename PDF | ✅ | ❌ | ❌ | ❌ |
| Hapus Data & Hapus PDF | ✅ | ❌ | ❌ | ❌ |
| Dashboard & Rekap | ✅ | ❌ | ❌ | ❌ |
| Kelola Pengguna | ✅ | ❌ | ❌ | ❌ |
| Lihat Arsip (Read-Only) | ✅ | ✅ | ✅ | ❌ |
| Akses PDF Publik | ✅ | ✅ | ✅ | ✅ |

---

## 🗺️ Peta Rute (Routes)

### Publik
| Method | URL | Keterangan |
|---|---|---|
| `GET` | `/login` | Halaman login |
| `POST` | `/login` | Proses login *(Rate Limiting terkelola)* |
| `POST` | `/logout` | Proses logout |
| `POST` | `/password/request-reset` | Ajukan permintaan reset password *(throttle: 3 req/5 menit)* |
| `GET` | `/pdf/{type}/{filename}` | Akses file PDF publik |
| `GET` | `/image/{filename}` | Akses file gambar publik |

### Terautentikasi (Admin & Internship)
| Method | URL | Keterangan |
|---|---|---|
| `GET` | `/` | Daftar Surat Masuk |
| `POST` | `/arsip` | Simpan Surat Masuk baru |
| `GET` | `/surat-keluar` | Daftar Surat Keluar |
| `POST` | `/surat-keluar` | Simpan Surat Keluar baru |

### Admin Only
| Method | URL | Keterangan |
|---|---|---|
| `GET` | `/dashboard` | Dashboard & statistik grafik |
| `GET` | `/dashboard/report` | Rekap laporan (printable) |
| `GET` | `/arsip/{rowIndex}/edit` | Form edit Surat Masuk |
| `PUT` | `/arsip/{rowIndex}` | Update Surat Masuk + Rename PDF |
| `DELETE` | `/arsip/{rowIndex}` | Hapus Surat Masuk + Hapus PDF |
| `GET` | `/surat-keluar/{rowIndex}/edit` | Form edit Surat Keluar |
| `PUT` | `/surat-keluar/{rowIndex}` | Update Surat Keluar + Rename PDF |
| `DELETE` | `/surat-keluar/{rowIndex}` | Hapus Surat Keluar + Hapus PDF |
| `GET` | `/admin/users` | Kelola pengguna |
| `POST` | `/admin/users/{user}/reset-password` | Reset password pengguna |
| `POST` | `/admin/password-reset-requests/{id}/resolve` | Tandai permintaan reset selesai |
| `GET` | `/sse/reset-requests` | Stream SSE notifikasi reset password |
| `GET` | `/api/reset-requests-version` | Polling fallback versi permintaan reset |

### Tamu WiFi (Read-Only, Tanpa Login)
| Method | URL | Keterangan |
|---|---|---|
| `GET` | `/tamu` | Daftar Surat Masuk (read-only) |
| `GET` | `/tamu/surat-keluar` | Daftar Surat Keluar (read-only) |

---

## 👤 Use Case Diagram

```mermaid
flowchart LR
    Admin["👨‍💼 Admin"]
    Internship["🧑‍💻 Internship"]
    Public["🌍 Publik"]
    Tamu["👥 Tamu WiFi"]

    subgraph System ["🏢 Sistem Pengarsipan BPSDMP"]
        UC1["🔑 Login Sistem"]
        UC2["📥 Input Surat Masuk"]
        UC3["📤 Input Surat Keluar"]
        UC4["📄 Upload & Sync PDF"]
        UC5["💾 Simpan Google Sheets"]
        UC6["🚪 Logout"]
        UC7["📊 Dashboard & Grafik"]
        UC8["🔍 Akses PDF Publik"]
        UC9["🗑️ Hapus Data & PDF Mentah"]
        UC10["👀 Lihat Read-Only"]
        UC11["✏️ Edit Data & Rename PDF"]
        UC12["🖨️ Rekap Laporan & Print"]
        UC13["👥 Kelola Pengguna"]
        UC14["🔔 Notifikasi Reset SSE"]
    end

    subgraph Cloud ["☁️ Google Cloud API"]
        GS["📊 Google Sheets dbSM / dbSK"]
        GD["📁 Google Drive"]
    end

    Admin --> UC1
    Admin --> UC2
    Admin --> UC3
    Admin --> UC6
    Admin --> UC7
    Admin --> UC9
    Admin --> UC11
    Admin --> UC12
    Admin --> UC13
    Admin --> UC14

    Internship --> UC1
    Internship --> UC2
    Internship --> UC3
    Internship --> UC6

    Public --> UC8
    Tamu --> UC10

    UC2 --> UC4
    UC3 --> UC4
    UC2 --> UC5
    UC3 --> UC5
    UC11 --> UC5
    UC11 --> UC4
    UC9 --> UC4
    UC5 --> GS
    UC4 --> GD
```

---

## 🔄 Flowchart Sistem

```mermaid
flowchart TD
    Start(["🚀 Mulai"]) --> OpenApp["🌐 Buka Aplikasi"]
    OpenApp --> CheckAuth{"🔒 Sudah Login?"}

    CheckAuth -- Tidak --> LoginPage["🔑 Tampil Halaman Login"]
    LoginPage --> InputCred["📝 Input Username & Password"]
    InputCred --> ValidateCred{"⚡ Kredensial Valid?"}
    ValidateCred -- Tidak --> ErrMsg["⚠️ Pesan Error"] --> LoginPage
    ValidateCred -- Ya --> CheckRole{"👤 Check Role"}

    CheckAuth -- Ya --> CheckRole

    CheckRole -- Admin --> Dashboard["📊 Dashboard Admin"]
    CheckRole -- Internship --> SuratMasukPage["📥 Form Surat Masuk"]

    Dashboard --> MenuOpt{"📌 Pilih Menu"}

    MenuOpt -- Surat Masuk --> SuratMasukPage
    SuratMasukPage --> FormSM["📝 Isi Field Data & Upload PDF"]
    FormSM --> SaveSM["💾 Upload PDF & Simpan ke Sheets dbSM"]
    SaveSM --> SuccessNotif["✅ Notifikasi Berhasil"] --> Dashboard

    MenuOpt -- Surat Keluar --> SuratKeluarPage["📤 Form Surat Keluar"]
    SuratKeluarPage --> FormSK["📝 Isi Field Data & Upload PDF"]
    FormSK --> SaveSK["💾 Upload PDF & Simpan ke Sheets dbSK"]
    SaveSK --> SuccessNotif

    MenuOpt -- Edit Data --> EditData["✏️ Edit Data Surat & Rename PDF"]
    EditData --> UpdateSheets["🔄 Sync Google Sheets & Drive"] --> Dashboard

    MenuOpt -- Hapus Data --> DeleteData["🗑️ Hapus Baris & Delete PDF Mentah"]
    DeleteData --> UpdateSheets

    MenuOpt -- Rekap Laporan --> ReportPage["🖨️ Dashboard Rekap & Print Grid"]
    ReportPage --> Dashboard

    MenuOpt -- Kelola Pengguna --> UserMgmt["👥 Manajemen User & Reset Password"]
    UserMgmt --> Dashboard

    MenuOpt -- Logout --> LogoutSession["🚪 Logout Session"] --> LoginPage

    subgraph GuestFlow ["👥 Akses Tamu & Publik"]
        GuestUser["👥 WiFi Tamu"] --> GuestPage["👀 Akses /tamu Read-Only"]
        PublicUser["🌍 Pengguna Publik"] --> ViewPDF["📄 Akses Link PDF"]
    end
```

---

## 📐 Entity Relationship Diagram (ERD)

```mermaid
erDiagram
    USERS {
        int id PK
        string name
        string email
        string password
        string role
        datetime last_seen_at
        string remember_token
        datetime created_at
        datetime updated_at
    }

    SESSIONS {
        string id PK
        int user_id FK
        string ip_address
        string user_agent
        text payload
        int last_activity
    }

    PASSWORD_RESET_REQUESTS {
        int id PK
        string username
        string ip_address
        boolean is_resolved
        datetime resolved_at
        datetime created_at
        datetime updated_at
    }

    GOOGLE_SHEET_SM {
        string timestamp
        string kode_klasifikasi
        string kode_surat
        string uraian_berkas
        string kurun_waktu
        string jumlah_folder
        string nomor_item
        string uraian_arsip
        string tanggal_surat
        string jumlah_lembar
        string tingkat_perkembangan
        string lokasi_simpan
        string klasifikasi_keamanan
        string tingkat_akses
        string retensi_aktif
        string retensi_inaktif
        string retensi_keterangan
        string url_pdf
    }

    GOOGLE_SHEET_SK {
        string timestamp
        string kode_klasifikasi
        string kode_surat
        string uraian_berkas
        string kurun_waktu
        string jumlah_folder
        string nomor_item
        string url_pdf
        string uraian_arsip
        string tanggal_surat
        string jumlah_lembar
        string tingkat_perkembangan
        string lokasi_simpan
        string klasifikasi_keamanan
        string tingkat_akses
        string retensi_aktif
        string retensi_inaktif
        string retensi_keterangan
    }

    USERS ||--o{ SESSIONS : "memiliki"
    USERS ||--o{ PASSWORD_RESET_REQUESTS : "mengajukan"
    USERS ||--o{ GOOGLE_SHEET_SM : "menginput"
    USERS ||--o{ GOOGLE_SHEET_SK : "menginput"
```

---

## 🔒 Keamanan (Security Layer)

Aplikasi dilengkapi dengan sistem perlindungan komprehensif:

| Komponen | Mekanisme & Fungsi |
|---|---|
| `BruteForceIpBan` | Memantau percobaan login gagal per IP. Jika gagal 20 kali dalam 10 menit, IP diblokir otomatis selama 1 jam (HTTP 429 Too Many Requests) |
| `AdminOnly` | Membatasi akses rute administratif hanya untuk akun role `admin` |
| `InternalNetworkOnly` | Membatasi akses input/edit/hapus hanya dari subnet jaringan internal kantor |
| `GuestNetwork` | Membatasi rute `/tamu` agar hanya dapat diakses dari subnet tamu atau jaringan internal |
| `SecurityHeaders` | Menambahkan HTTP security headers: CSP (dengan dukungan inline aman Chart.js/Blade), `X-Robots-Tag: noindex`, `Cross-Origin-Opener-Policy: same-origin`, `Cross-Origin-Resource-Policy: same-origin`, `X-Permitted-Cross-Domain-Policies: none`, `X-Frame-Options`, dll. |
| `UpdateUserLastSeen` | Mencatat waktu terakhir user aktif (`last_seen_at`) di database |
| **Rate Limiting** | Pembatasan login terkelola per IP & username (lockout 5 menit); Reset Password: maks 3 req / 5 menit |
| **Formula Injection** | Sanitasi karakter prefix `=`, `+`, `-`, `@` pada input teks agar terlindung dari eksekusi formula berbahaya di spreadsheet |
| **Session Protection** | Enkripsi payload sesi, masa aktif 240 menit (4 jam), SameSite cookie, dan penyimpanan persisten di database MySQL |
| **Production Mode** | `APP_DEBUG=false` untuk mencegah kebocoran file path / stack trace dan `LOG_LEVEL=error` untuk menjaga privasi sistem |

---

## ⚡ Strategi Caching Google Sheets (`SheetsCache`)

Untuk mencegah pembatasan kuota Google Sheets API (100 request per 100 detik per user) serta menjaga performa navigasi secepat kilat (0.1 – 0.2 detik per halaman), aplikasi mengimplementasikan strategi *dual-tier caching*:

1. **Mikro-Cache Khusus Admin (30 Detik)**:
   - Nilai konstanta: `SheetsCache::TTL_ADMIN_MICRO = 30`.
   - Admin mendapatkan cache khusus berdurasi 30 detik.
   - Setelah 30 detik berlalu, begitu admin membuka atau me-refresh halaman, sistem akan memicu *API call* ke Google Spreadsheet untuk **menarik data paling mutakhir**.
   - Data segar hasil tarikan admin ini secara otomatis **menulis ulang cache global** (`Cache::put()`), sehingga seluruh staf dan tamu langsung mendapatkan data baru tersebut.
   - Dalam interval 30 detik tersebut, admin bebas berpindah antar-halaman secara instan tanpa terkena jeda pemanggilan Google API.

2. **Cache Normal untuk Non-Admin (60 Detik / 1 Menit)**:
   - Nilai konstanta: `SheetsCache::TTL_TABLE = 60` & `SheetsCache::TTL_DASHBOARD = 60`.
   - Pengguna dengan role **Internship** maupun **Tamu WiFi** menikmati data dari cache berdurasi 1 menit, meminimalkan latensi jaringan.

3. **Invalidasi Cache Seketika (Instant Invalidation)**:
   - Setiap kali terjadi penambahan data baru (`store`), pengeditan (`update`), atau penghapusan data (`destroy`), sistem langsung mengeksekusi `SheetsCache::forgetSuratMasuk()` atau `SheetsCache::forgetSuratKeluar()`.
   - Seluruh cache terkait (tabel, statistik dashboard, dan laporan rekap) langsung terhapus saat itu juga, sehingga perubahan terlihat seketika.

---

## ⚙️ Konfigurasi Environment (`.env`)

Salin `.env.example` ke `.env` dan sesuaikan nilai berikut:

```env
# Aplikasi
APP_NAME="Arsip Surat BPSDMP"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://kearsipan.local
APP_TIMEZONE=Asia/Makassar

# Kontrol Jaringan Intranet
INTRANET_ENABLED=false                     # Ubah ke true untuk mengaktifkan filter subnet
INTERNAL_NETWORK_SUBNET="192.168.100.0/24,192.168.252.0/22"
GUEST_NETWORK_SUBNET="192.168.101.0/24,192.168.248.0/22"

# Sesi & Keamanan
SESSION_DRIVER=database
SESSION_LIFETIME=240
SESSION_ENCRYPT=true
SESSION_EXPIRE_ON_CLOSE=false

# Google API
GOOGLE_SHEET_ID=<id-spreadsheet-anda>
GOOGLE_SERVICE_ACCOUNT_JSON=storage/app/google-credentials.json
GOOGLE_DRIVE_FOLDER_ID=<id-folder-drive-anda>

# Database MySQL
DB_DATABASE=arsip_bpsdmp
DB_USERNAME=root
DB_PASSWORD=
```

> **Catatan**: Letakkan file `google-credentials.json` (Service Account key) di `storage/app/`.

---

## 🔒 Pemeliharaan & Jadwal Otomatis (Scheduler)

Aplikasi dilengkapi dengan **Laravel Task Scheduler** yang berjalan di latar belakang:

1. **Pembersihan Sesi Kadaluarsa (Setiap 1 Jam)**:
   - Perintah: `session:prune-expired`
   - Berjalan otomatis setiap jam (`->hourly()`) untuk menghapus record sesi di database MySQL dan file temporary yang tidak aktif lebih dari 240 menit (4 jam).
2. **Pembersihan Temporary Storage Harian (Pukul 17:00)**:
   - Perintah: `storage:clean`
   - Berjalan setiap sore pukul 17:00 untuk membersihkan:
     - `storage/framework/views` — Compiled Blade Views
     - `storage/framework/sessions` — Berkas Sesi Usang
     - `storage/framework/cache/data` — Subfolder Cache Data (termasuk cache Google Sheets)

Scheduler dijalankan secara otomatis oleh [`start-all.bat`](file:///c:/laragon/www/project-magangv1/start-all.bat) menggunakan `php artisan schedule:work` di background. Log eksekusi scheduler tersimpan di `storage/logs/scheduler.log`.

Eksekusi manual dapat dijalankan kapan saja melalui terminal:
```bash
# Membersihkan sesi expired di database
php artisan session:prune-expired

# Membersihkan temporary storage & cache
php artisan storage:clean
```

---

## 📁 Struktur Direktori Penting

```
project-magangv1/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── CleanStorageCommand.php          # Pembersihan temporary storage & cache
│   │       └── PruneExpiredSessionsCommand.php  # Pembersihan sesi database kadaluarsa
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/UserController.php         # Kelola pengguna & reset password + SSE
│   │   │   ├── Auth/LoginController.php          # Login & logout
│   │   │   ├── Auth/PasswordResetController.php  # Permintaan reset password
│   │   │   ├── ArsipController.php               # CRUD Surat Masuk
│   │   │   ├── SuratKeluarController.php         # CRUD Surat Keluar
│   │   │   ├── DashboardController.php           # Dashboard & rekap laporan
│   │   │   └── Controller.php                    # Base controller: KODE_MAP, getSheetService, escapeFormula, dll.
│   │   └── Middleware/
│   │       ├── AdminOnly.php                    # Pembatasan rute khusus admin
│   │       ├── BruteForceIpBan.php              # Auto-ban IP 1 jam setelah 20x gagal login
│   │       ├── InternalNetworkOnly.php          # Pembatasan rute intranet staff
│   │       ├── GuestNetwork.php                 # Pembatasan rute wifi tamu
│   │       ├── SecurityHeaders.php              # CSP, X-Robots-Tag, COOP, CORP, HSTS
│   │       ├── UpdateUserLastSeen.php           # Catat aktivitas terakhir user
│   │       └── Concerns/
│   │           └── ChecksIpSubnet.php            # Trait bersama: validasi IP subnet CIDR (IPv4 & IPv6)
│   ├── Models/
│   │   ├── User.php
│   │   └── PasswordResetRequest.php
│   └── Services/
│       ├── GoogleDriveService.php                # Upload, rename, & delete di Google Drive & /uploads
│       └── SheetsCache.php                       # Dual-tier cache data Google Sheets (Admin 30s, Non-Admin 60s)
├── database/
│   ├── migrations/                               # Skema tabel MySQL (users, sessions, reset requests, dll)
│   └── seeders/                                  # Data awal (user default)
├── routes/
│   ├── web.php                                   # Definisi seluruh rute web
│   └── console.php                               # Definisi Task Scheduler (hourly prune & daily clean)
├── storage/app/
│   └── google-credentials.json                   # (tidak di-commit) Service Account key
├── uploads/                                      # File PDF lokal yang diupload
├── setup-and-run.bat                             # Skrip setup 1-Pintu (Administrator Windows)
└── start-all.bat                                 # Skrip runner harian (Laragon, Worker, Scheduler, Auto-Launch)
```

---

## 🔧 Optimalisasi & Refaktor (Changelog Teknis)

| # | Perubahan | File |
|---|---|---|
| 1 | Konsolidasikan `KODE_KLASIFIKASI_ORDER`, `KODE_MAP`, `sortByKlasifikasi()`, `applyKodeMapFallback()`, `getSheetService()`, `escapeFormula()` ke base `Controller.php` | `Controller.php` |
| 2 | Tambahkan `GoogleDriveService::renameFile()` → ubah nama PDF di `/uploads` & Google Drive saat Edit | `GoogleDriveService.php` |
| 3 | Tambahkan `GoogleDriveService::deleteFile()` → hapus PDF fisik di `/uploads` & Google Drive saat Destroy | `GoogleDriveService.php` |
| 4 | Tambahkan plugin datalabels pada Chart.js → angka total tampil di atas batang grafik | `dashboard/index.blade.php` |
| 5 | Standardisasi loading animation kontekstual & font fallback sistem untuk navigasi Intranet WiFi instan | `layouts/app.blade.php`, `tamu/index.blade.php` |
| 6 | Penyeragaman kisi garis tabel cetak (`1pt solid #333`), header *URAIAN INFORMASI ARSIP*, hapus kolom Tahun & tanda tangan | `dashboard/report.blade.php` |
| 7 | Trait `ChecksIpSubnet` → penanganan subnet IPv4 & IPv6 yang aman tanpa exception | `Concerns/ChecksIpSubnet.php` |
| 8 | Perbaikan keamanan database seeder → menghapus truncate user tabel agar user kustom persisten | `DatabaseSeeder.php` |
| 9 | Pemisahan skrip startup harian `start-all.bat` dari skrip inisialisasi awal `setup-and-run.bat` | `start-all.bat`, `setup-and-run.bat` |
| 10 | Penyesuaian parameter host header Ngrok agar sesuai dengan virtual host `kearsipan.local` | `start-ngrok.bat` |
| 11 | Aktifkan Task Scheduler `storage:clean` otomatis setiap pukul **17:00** dengan logging ke `storage/logs/scheduler.log` | `routes/console.php` |
| 12 | Tambahkan spawn `schedule:work` di background pada runner harian agar scheduler aktif tanpa intervensi manual | `start-all.bat` |
| 13 | Implementasi middleware `BruteForceIpBan` → pantau kegagalan login dan ban IP otomatis 1 jam setelah 20x gagal dalam 10 menit | `BruteForceIpBan.php`, `LoginController.php`, `bootstrap/app.php` |
| 14 | Penguatan Security Headers (`X-Robots-Tag`, `COOP`, `CORP`, `X-Permitted-Cross-Domain-Policies`, CSP aman inline script) & mode produksi (`APP_DEBUG=false`, `LOG_LEVEL=error`) | `SecurityHeaders.php`, `.env` |
| 15 | Penyesuaian masa aktif session menjadi 4 jam (240 menit) dan pembuatan command `session:prune-expired` terjadwal per jam via Scheduler | `config/session.php`, `PruneExpiredSessionsCommand.php`, `routes/console.php` |
| 16 | Optimalisasi Google Sheets Cache: Mikro-cache role Admin 30 detik (`TTL_ADMIN_MICRO = 30`) dan cache normal 1 menit (`TTL_TABLE = 60`, `TTL_DASHBOARD = 60`) | `SheetsCache.php`, `ArsipController.php` |
| 17 | Konfigurasi VirtualHost Apache (`00-default.conf` & `kearsipan.local.conf`) port 80 dan 443 (SSL) agar perangkat lain dapat mengakses langsung via IP server (`192.168.100.4`) | `00-default.conf`, `kearsipan.local.conf` |
| 18 | Penambahan aturan URL Rewrite di `.htaccess` & `public/.htaccess` untuk otomatis me-redirect akses subfolder `/project-magangv1` ke root aplikasi tanpa 404 | `.htaccess`, `public/.htaccess` |

---

## 📄 Lisensi

Proyek ini dikembangkan sebagai bagian dari **program magang** di **BPSDMP** dan bersifat internal.
