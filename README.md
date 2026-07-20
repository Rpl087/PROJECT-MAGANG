# 📂 Sistem Pengarsipan Digital Surat — BPSDMP

Aplikasi web berbasis **Laravel 13** untuk pengarsipan surat masuk dan surat keluar secara digital. Data arsip disimpan langsung ke **Google Spreadsheet** via Google Sheets API, file PDF disimpan di server lokal dan Google Drive.

---

## ⚡ Quick Start — Jalankan Pertama Kali (Mode 1-Pintu)

Setelah melakukan `git clone` pada PC server khusus, Anda **cukup menjalankan 1 berkas skrip master** tanpa perlu konfigurasi manual:

1. Buka folder proyek dan klik ganda berkas:
   ```cmd
   setup-and-run.bat
   ```
   *(atau jalankan `start-all.bat`)*

2. Skrip master 1-Pintu akan secara otomatis mengeksekusi alur berikut:
   - 🔑 **UAC Elevation**: Meminta akses Administrator Windows otomatis
   - ⚡ **Service Laragon**: Menyalakan Apache & MySQL
   - 📝 **Environment**: Mengisi berkas `.env` & generate `APP_KEY`
   - 🗄️ **Database**: Membuat database `arsip_bpsdmp`, menjalankan migrasi & seed
   - 🌐 **Virtual Host**: Membuat VirtualHost Apache `kearsipan.local.conf`
   - 📌 **Hosts File**: Mendaftarkan domain `127.0.0.1 kearsipan.local` di `hosts` Windows
   - 🛡️ **Firewall**: Membuka Inbound Port 80 pada Windows Firewall
   - 🧹 **Storage & Cache**: Membersihkan temporary views, sessions, & cache/data (`storage:clean`)

3. Akses aplikasi melalui browser:
   - **Akses Staff / Admin**: `http://kearsipan.local/`
   - **Akses WiFi Tamu (Read-Only)**: `http://kearsipan.local/tamu`

---

## 🚀 Fitur Utama

- 🔐 **Autentikasi & RBAC** — Akses login aman dengan multi-role (Admin & Internship).
- 📥 **Arsip Surat Masuk & Keluar** — Input data lengkap dan upload file PDF.
- ✏️ **Edit & Hapus Data** — Pengelolaan data arsip langsung terintegrasi dengan Google Sheets.
- 🔍 **Filter & Klasifikasi** — Filter pencarian berdasarkan kode klasifikasi surat.
- 📊 **Dashboard & Rekapitulasi** — Statistik data arsip bulanan, tahunan, dan per klasifikasi.
- 🌐 **Intranet Ready** — Dapat diakses di jaringan lokal (LAN/WiFi) menggunakan domain `kearsipan.local`.
- 🔒 **Cybersecurity Layer** — Proteksi terhadap Formula Injection, CSP, Rate Limiting, dan Session Protection.

---

## 🛠️ Tech Stack

- **Framework**: Laravel 13 (PHP 8.3+)
- **Database**: MySQL / MariaDB (untuk autentikasi & session)
- **Integrasi**: Google Sheets API v4 & Google Drive API
- **Frontend**: Blade + Vanilla CSS (Aesthetically Rich & Dark Mode)
- **Web Server**: Apache (Laragon) — Virtual Host `kearsipan.local`

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
        UC4["📄 Upload PDF"]
        UC5["💾 Simpan Google Sheets"]
        UC6["🚪 Logout"]
        UC7["📊 Dashboard & Statistik"]
        UC8["🔍 Akses PDF Publik"]
        UC9["🗑️ Hapus Data Surat"]
        UC10["👀 Lihat Read-Only"]
        UC11["✏️ Edit Data Surat"]
        UC12["🖨️ Rekap Laporan"]
    end

    subgraph Cloud ["☁️ Google Cloud API"]
        GS["📊 Google Sheets dbSM / dbSK"]
    end

    Admin --> UC1
    Admin --> UC2
    Admin --> UC3
    Admin --> UC6
    Admin --> UC7
    Admin --> UC9
    Admin --> UC11
    Admin --> UC12

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
    UC5 --> GS
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

    MenuOpt -- Edit/Hapus --> ManageData["✏️ Edit / Hapus Data Arsip"]
    ManageData --> UpdateSheets["🔄 Update Google Sheets"] --> Dashboard

    MenuOpt -- Rekap Laporan --> ReportPage["🖨️ Dashboard Rekap & Print"]
    ReportPage --> Dashboard

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
    }

    USERS ||--o{ SESSIONS : "memiliki"
    USERS ||--o{ GOOGLE_SHEET_SM : "menginput"
    USERS ||--o{ GOOGLE_SHEET_SK : "menginput"
```

---

## 🔒 Pemeliharaan & Jadwal Otomatis (Scheduler)

Aplikasi telah dilengkapi dengan **Laravel Task Scheduler** yang secara otomatis berjalan setiap pukul **00:00 (Tengah Malam)** untuk membersihkan berkas-berkas temporary pada direktori storage:
* `storage/framework/views` (Compiled Blade Views)
* `storage/framework/sessions` (File Session Usang)
* `storage/framework/cache/data` (Subfolder Cache Data)

Pembersihan manual juga dapat dilakukan kapan saja melalui terminal:
```bash
php artisan storage:clean
```
