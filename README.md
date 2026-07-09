# 📂 Sistem Pengarsipan Digital Surat — BPSDMP

Aplikasi web berbasis **Laravel 13.8** untuk pengarsipan surat masuk dan surat keluar secara digital. Data arsip disimpan langsung ke **Google Spreadsheet** via Google Sheets API, file PDF disimpan di server lokal dan dapat **diakses publik** melalui URL unik tanpa perlu login.

---

## 🚀 Fitur Utama

- 🔐 **Autentikasi** — Login berbasis username & password dengan **2 role pengguna** (Admin & Internship)
- 🛡️ **Role-Based Access Control (RBAC)** — Admin punya akses penuh; Internship hanya bisa input form
- 📥 **Arsip Surat Masuk** — Input 17 field data + upload file PDF (maks. 10MB)
- 📤 **Arsip Surat Keluar** — Input 18 field data + upload file PDF (maks. 10MB)
- 🗑️ **Hapus Data** — Hanya Admin yang dapat menghapus baris data dari Google Sheets
- 📊 **Google Sheets Integration** — Data tersimpan ke `dbSM`/`dbSK`; tabel tampilan membaca dari sheet `SURAT MASUK` & `SURAT KELUAR`
- 📎 **PDF Publik** — File PDF surat dapat diakses publik via URL `/pdf/{type}/{filename}` tanpa login
- 📈 **Dashboard Rangkuman** — Statistik bulanan & tahunan + pie chart klasifikasi; header tabel seragam navy (hanya Admin)
- 🖼️ **Logo Komdigi** — Logo resmi Komdigi ditampilkan di header aplikasi (menggantikan ikon generik)
- 📱 **Responsive** — Hamburger/drawer menu untuk tampilan mobile & tablet
- 🌐 **Intranet Ready** — Dapat diakses oleh semua perangkat di jaringan WiFi yang sama tanpa internet
- 🔒 **Cybersecurity** — CSP, HSTS, XSS Protection, Rate Limiting, Session Security, IP-based Access Control

---

## 🛠️ Tech Stack

| Layer | Teknologi |
|---|---|
| Framework | Laravel 13.8 (PHP 8.4) |
| Database | SQLite (local auth & sessions) |
| Storage | Google Sheets API v4 |
| Frontend | Blade + Vanilla CSS (Pure CSS, tanpa framework) |
| Auth | Laravel Session Auth (`Auth::attempt`) |
| RBAC | Custom Middleware `AdminOnly` |
| File Upload | Laravel Storage (`public` disk) |
| Calendar | Flatpickr (CDN jsdelivr.net) |
| Web Server | Apache (Laragon) — port 80, virtual host `project-magang.test` |
| Tunnel | Ngrok (opsional, untuk akses publik via PC lokal) |

---

## 👤 Akun Pengguna

| Username | Role | Akses |
|---|---|---|
| `Admin` | `admin` | Dashboard, lihat tabel, hapus data, input form |
| `internship` | `internship` | Input form surat masuk & keluar saja |

> Password disimpan menggunakan **bcrypt** (`Hash::make()`). Untuk mengubah password, edit `database/seeders/DatabaseSeeder.php` lalu jalankan `php artisan db:seed --force`.

---

## 🔒 Role & Hak Akses

| Fitur / Halaman | Admin | Internship |
|---|:---:|:---:|
| Login / Logout | ✅ | ✅ |
| Form Input Surat Masuk | ✅ | ✅ |
| Form Input Surat Keluar | ✅ | ✅ |
| Lihat Tabel Daftar Surat | ✅ | ❌ |
| Hapus Surat (Masuk/Keluar) | ✅ | ❌ |
| Dashboard Statistik | ✅ | ❌ |
| Akses PDF Publik | ✅ | ✅ (publik) |
| Akses Mode Tamu (WiFi Tamu) | ✅ | ✅ (read-only) |

> Pembatasan akses Admin dilakukan oleh middleware `App\Http\Middleware\AdminOnly`. Pengguna Internship yang mencoba mengakses route admin akan dialihkan ke halaman form.

---

## 🌐 Akses Intranet (WiFi Lokal)

### ⚠️ PENTING: Apakah Aplikasi Tetap Aktif Jika Laptop/Laragon Dimatikan?

**Jawaban singkat: ❌ TIDAK.** Aplikasi ini berjalan di laptop Anda sendiri sebagai server. Jika laptop atau Laragon dimatikan, **semua pengguna di WiFi lokal akan kehilangan akses.**

**Mengapa?**
- Laragon menjalankan **Apache Web Server** di laptop Anda (port 80/443)
- PHP memproses request di laptop Anda
- SQLite database tersimpan di storage laptop Anda
- Ketika laptop mati → tidak ada yang merespons request → semua koneksi timeout

**Cara agar selalu aktif:**
1. **Laptop harus selalu menyala** dan Laragon harus running (`Start All`)
2. Opsi terbaik: gunakan **PC/server dedicated** yang tidak pernah dimatikan
3. Opsi kedua: aktifkan **Windows Hibernate** agar laptop bisa "sleep" tanpa shutdown total

### Setup Akses Intranet

1. **Double-click** `start-intranet.bat` — script otomatis mendeteksi IP WiFi dan mengupdate `.env`
2. Pilih mode server:
   - **Mode 1 (Laragon/Apache)**: Akses via `http://{IP_LAPTOP}/` — **direkomendasikan**
   - **Mode 2 (Artisan Serve)**: Akses via `http://{IP_LAPTOP}:8000/` — hanya untuk development
3. Semua perangkat di jaringan WiFi yang sama bisa mengakses via IP laptop

### URL Intranet

| Pengguna | URL | Catatan |
|---|---|---|
| Staff/Admin | `http://{IP_LAPTOP}/` | Butuh login |
| Staff Surat Keluar | `http://{IP_LAPTOP}/surat-keluar` | Butuh login |
| Tamu (Read-only) | `http://{IP_LAPTOP}/tamu` | Tanpa login |
| Tamu Surat Keluar | `http://{IP_LAPTOP}/tamu/surat-keluar` | Tanpa login |
| Dashboard Admin | `http://{IP_LAPTOP}/dashboard` | Butuh login admin |

> Ganti `{IP_LAPTOP}` dengan IP aktual laptop server (contoh: `192.168.252.85`)

---

## 🌐 Akses PDF Publik

File PDF yang diupload dapat diakses **tanpa login** melalui URL:

```
https://domain-kamu.com/pdf/surat-masuk/{nama-file.pdf}
https://domain-kamu.com/pdf/surat-keluar/{nama-file.pdf}
```

- URL ini tersimpan otomatis di kolom **R (Surat Masuk)** dan **I (Surat Keluar)** di Google Sheets
- Dapat langsung diklik dari spreadsheet untuk membuka/mengunduh PDF
- Keamanan: hanya ekstensi `.pdf` yang diizinkan, path traversal dicegah

---

## 🔐 Cybersecurity — Lapisan Keamanan

Project ini mengimplementasikan **7 lapisan keamanan**:

### 1. HTTP Security Headers (`SecurityHeaders.php`)
```
Content-Security-Policy   → Batasi asal script/style (whitelist: self, CDN flatpickr, Google Fonts)
X-Frame-Options           → SAMEORIGIN (cegah Clickjacking)
X-Content-Type-Options    → nosniff (cegah MIME sniffing)
X-XSS-Protection          → 1; mode=block (browser lama)
Referrer-Policy           → strict-origin-when-cross-origin
Permissions-Policy        → Matikan camera/mic/geolocation
Strict-Transport-Security → 1 tahun (aktif saat HTTPS)
```

### 2. Rate Limiting — Anti Brute Force
- Login dibatasi **maks 5 percobaan per menit per IP+username**
- Jika melewati batas → blokir 60 detik
- Semua percobaan gagal dicatat di `storage/logs/laravel.log`

### 3. IP-Based Network Access Control
| Middleware | Fungsi |
|---|---|
| `InternalNetworkOnly` | Blokir request write/delete dari luar jaringan internal |
| `GuestNetwork` | Izinkan akses `/tamu` hanya dari WiFi tamu/internal |

> Subnet diatur via `.env`: `INTERNAL_NETWORK_SUBNET` dan `GUEST_NETWORK_SUBNET`

### 4. Session Security
```dotenv
SESSION_ENCRYPT=true       # Enkripsi payload session
SESSION_SAME_SITE=strict   # Cegah CSRF via cross-site request
```
- Session di-regenerate saat login (`session()->regenerate()`)
- Session di-invalidate saat logout (`invalidate()` + `regenerateToken()`)

### 5. CSRF Protection
- Semua form POST/DELETE menggunakan `@csrf` token
- Token divalidasi otomatis oleh Laravel

### 6. Input Validation & Sanitization
- Semua input divalidasi di controller sebelum diproses
- Path traversal dicegah via regex di route PDF/Image
- Kode Klasifikasi: hanya 2 karakter huruf, paksa uppercase (JS)

### 7. Audit Logging
- Login berhasil/gagal: dicatat ke `storage/logs/laravel.log`
- Hapus data: dapat ditambahkan audit log di controller
- Log level: `warning` untuk production (tidak expose debug info)

---

## ⚡ Cara Menjalankan Lokal (Laragon)

> Tidak perlu `php artisan serve` — langsung menggunakan Apache Laragon.

### Prasyarat
- Laragon terinstall & Apache **berjalan** (`Start All`)
- PHP 8.4 dengan extension `pdo_sqlite` & `sqlite3` aktif di `php.ini`
- Virtual host `project-magang.test` terkonfigurasi
- Kredensial Google Service Account tersimpan di `storage/app/google-credentials.json`

### Langkah Setup Awal (sekali saja)
```bash
# 1. Clone project
cd C:\laragon\www
# (letakkan folder project-magang di sini)

# 2. Install dependencies
composer install

# 3. Buat file .env
cp .env.example .env
php artisan key:generate

# 4. Isi konfigurasi .env (lihat bagian Konfigurasi .env)

# 5. Migrasi & seed database (buat tabel + akun admin & internship)
php artisan migrate --seed

# 6. Buat symlink storage
php artisan storage:link
```

### Menjalankan Sehari-hari
1. Pastikan **Laragon** aktif (Apache menyala)
2. Akses lokal: `http://project-magang.test`
3. Untuk akses intranet WiFi, **double-click** `start-intranet.bat`
4. Untuk akses publik via ngrok, **double-click** `start-ngrok.bat`

---

## 🔧 Konfigurasi `.env`

```dotenv
APP_NAME="Arsip Surat BPSDMP"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=http://192.168.252.85  # IP laptop server di jaringan WiFi

# ── Jaringan Intranet ──────────────────────────────────
# Subnet jaringan internal (staff dengan akses penuh)
INTERNAL_NETWORK_SUBNET=192.168.252.0/22
# Subnet WiFi tamu (read-only, tanpa login)
GUEST_NETWORK_SUBNET=10.0.0.0/24

# ── Google Sheets ──────────────────────────────────────
GOOGLE_SHEET_ID=1QdlmA0ug3xNY2-...
GOOGLE_SERVICE_ACCOUNT_JSON=storage/app/google-credentials.json

# ── Session Security ───────────────────────────────────
SESSION_DRIVER=file
SESSION_LIFETIME=60
SESSION_ENCRYPT=true
SESSION_SAME_SITE=strict

DB_CONNECTION=sqlite
```

---

## 📐 Entity Relationship Diagram (ERD)

> Tabel `users` digunakan hanya untuk autentikasi. Data arsip surat disimpan di Google Sheets, bukan di database lokal.

```mermaid
erDiagram
    USERS {
        int id PK
        string name
        string email UK
        string password
        string role
        string remember_token
        timestamp email_verified_at
        timestamp created_at
        timestamp updated_at
    }

    SESSIONS {
        string id PK
        int user_id FK
        string ip_address
        text user_agent
        longtext payload
        int last_activity
    }

    GOOGLE_SHEET_dbSM {
        string A_timestamp
        string B_kode_klasifikasi
        string C_kode_surat
        string D_uraian_informasi_berkas
        string E_kurun_waktu
        string F_jumlah_folder
        string G_nomor_item_arsip
        string H_uraian_informasi_arsip
        date   I_tanggal_surat
        string J_jumlah_lembar
        string K_tingkat_perkembangan
        string L_lokasi_simpan
        string M_klasifikasi_keamanan
        string N_tingkat_akses
        string O_retensi_aktif
        string P_retensi_inaktif
        string Q_retensi_keterangan
        url    R_url_pdf
    }

    GOOGLE_SHEET_dbSK {
        string A_timestamp
        string B_kode_klasifikasi
        string C_kode_surat
        string D_uraian_informasi_berkas
        string E_kurun_waktu
        string F_jumlah_folder
        string G_nomor_item_arsip
        url    H_url_pdf
        string I_uraian_informasi_arsip
        date   J_tanggal_surat
        string K_jumlah_lembar
        string L_tingkat_perkembangan
        string M_lokasi_simpan
        string N_klasifikasi_keamanan
        string O_tingkat_akses
        string P_retensi_aktif
        string Q_retensi_inaktif
        string R_retensi_keterangan
    }

    USERS ||--o{ SESSIONS : "memiliki"
    USERS ||--o{ GOOGLE_SHEET_dbSM : "menginput"
    USERS ||--o{ GOOGLE_SHEET_dbSK : "menginput"
```

---

## 🔄 Flowchart Sistem

```mermaid
flowchart TD
    A([Mulai]) --> B[Buka Aplikasi]
    B --> C{Sudah Login?}
    C -- Tidak --> D[Tampil Halaman Login]
    D --> E[Masukkan Username & Password]
    E --> F{Kredensial Valid?}
    F -- Tidak --> G[Tampil Pesan Error\nKembali ke Login]
    G --> D
    F -- Ya --> ROLE{Role Pengguna?}
    ROLE -- Admin --> H[Masuk Dashboard Utama]
    ROLE -- Internship --> J[Buka Form Surat Masuk]
    C -- Ya --> ROLE

    H --> I{Pilih Menu}

    I -- Surat Masuk --> J[Buka Form Surat Masuk]
    J --> K[Isi 17 Field Data]
    K --> L[Upload File PDF]
    L --> M{Validasi Form}
    M -- Gagal --> N[Tampil Pesan Error\nForm tetap terisi]
    N --> K
    M -- Lolos --> O[Simpan PDF ke Server\nstorage/arsip/surat-masuk/]
    O --> O2[Generate URL Publik PDF\n/pdf/surat-masuk/nama-file.pdf]
    O2 --> P[Kirim Data ke Google Sheets\nSheet: dbSM kolom A–R]
    P --> Q[Tampil Notifikasi Berhasil]
    Q --> H

    I -- Surat Keluar --> R[Buka Form Surat Keluar]
    R --> S[Isi 18 Field Data]
    S --> T[Upload File PDF]
    T --> U{Validasi Form}
    U -- Gagal --> V[Tampil Pesan Error\nForm tetap terisi]
    V --> S
    U -- Lolos --> W[Simpan PDF ke Server\nstorage/arsip/surat-keluar/]
    W --> W2[Generate URL Publik PDF\n/pdf/surat-keluar/nama-file.pdf]
    W2 --> X[Kirim Data ke Google Sheets\nSheet: dbSK kolom A–R]
    X --> Y[Tampil Notifikasi Berhasil]
    Y --> H

    I -- Dashboard --> DA[Tampil Statistik Bulanan & Tahunan]
    DA --> DB[Pie Chart Kode Klasifikasi]
    DB --> H

    I -- Hapus Data --> DEL{Konfirmasi Hapus?}
    DEL -- Ya --> DELACT[Hapus baris dari Google Sheets]
    DELACT --> H
    DEL -- Tidak --> H

    I -- Keluar --> Z[Logout Session]
    Z --> D

    PUB([Pengguna Publik]) --> PURL[Akses URL PDF\n/pdf/type/filename]
    PURL --> PCHECK{File Ada?}
    PCHECK -- Ya --> PPDF[Tampil PDF di Browser]
    PCHECK -- Tidak --> P404[404 Not Found]

    TAMU([WiFi Tamu]) --> TAMUURL[Akses /tamu]
    TAMUURL --> TAMUCHECK{IP dari subnet tamu/internal?}
    TAMUCHECK -- Ya --> TAMUVIEW[Tampil Tabel Read-Only]
    TAMUCHECK -- Tidak --> T403[403 Forbidden]
```

---

## 👤 Use Case Diagram

```mermaid
graph LR
    Admin(["👤 Admin"])
    Internship(["🎓 Internship"])
    Public(["🌐 Publik\n(tanpa login)"])
    Tamu(["👁️ Tamu WiFi\n(read-only)"])

    subgraph Sistem ["🖥️ Sistem Pengarsipan BPSDMP"]
        UC1("🔐 Login ke Sistem")
        UC2("📥 Input Surat Masuk")
        UC3("📤 Input Surat Keluar")
        UC4("📎 Upload File PDF")
        UC5("📊 Simpan ke Google Sheets")
        UC6("🚪 Logout")
        UC7("📈 Lihat Dashboard Statistik")
        UC8("🔗 Akses PDF via URL Publik")
        UC9("🗑️ Hapus Data Surat")
        UC10("👁️ Lihat Arsip Read-Only")

        UC2 --> UC4
        UC3 --> UC4
        UC2 --> UC5
        UC3 --> UC5
    end

    subgraph Google ["☁️ Google Cloud"]
        GS[("📋 Google Spreadsheet\ndbSM / dbSK")]
    end

    Admin --> UC1
    Admin --> UC2
    Admin --> UC3
    Admin --> UC6
    Admin --> UC7
    Admin --> UC9
    Internship --> UC1
    Internship --> UC2
    Internship --> UC3
    Internship --> UC6
    Public --> UC8
    Tamu --> UC10

    UC1 -.->|"«include»"| VALID["Validasi Kredensial\n(username + password)"]
    UC5 -.->|"«include»"| GS
```

---

## 🗂️ Struktur Direktori Penting

```
project-magang/
├── app/
│   └── Http/
│       ├── Controllers/
│       │   ├── ArsipController.php          ← Surat Masuk (form + Google Sheets + URL PDF)
│       │   ├── SuratKeluarController.php    ← Surat Keluar (form + Google Sheets + URL PDF)
│       │   ├── DashboardController.php      ← Statistik bulanan/tahunan dari Google Sheets
│       │   └── Auth/
│       │       └── LoginController.php      ← Login & Logout (session auth + rate limiting)
│       └── Middleware/
│           ├── AdminOnly.php                ← RBAC: blokir akses non-admin ke route admin
│           ├── InternalNetworkOnly.php      ← Blokir write/delete dari WiFi tamu
│           ├── GuestNetwork.php             ← Izinkan /tamu dari WiFi tamu/internal
│           └── SecurityHeaders.php          ← HTTP Security Headers (CSP, HSTS, dll.)
├── bootstrap/
│   └── app.php                              ← TrustProxies + register middleware alias
├── database/
│   ├── migrations/                          ← Tabel users (+ kolom role), sessions, cache
│   ├── seeders/
│   │   └── DatabaseSeeder.php               ← Akun Admin & Internship (bcrypt)
│   └── database.sqlite                      ← Database lokal (SQLite)
├── public/
│   ├── css/
│   │   └── app.css                          ← Stylesheet utama (Vanilla CSS, dark mode)
│   └── storage/                             ← Symlink ke storage/app/public/
├── resources/
│   └── views/
│       ├── auth/
│       │   └── login.blade.php              ← Halaman login (dark glassmorphism)
│       ├── arsip/
│       │   ├── index.blade.php              ← Tabel + form surat masuk (RBAC-aware)
│       │   └── surat-keluar.blade.php       ← Tabel + form surat keluar (RBAC-aware)
│       ├── tamu/
│       │   ├── index.blade.php              ← Surat masuk read-only (WiFi tamu)
│       │   └── surat-keluar.blade.php       ← Surat keluar read-only (WiFi tamu)
│       ├── dashboard/
│       │   └── index.blade.php              ← Dashboard statistik & chart (admin only)
│       └── layouts/
│           └── app.blade.php                ← Layout utama (sidebar drawer + header)
├── routes/
│   └── web.php                              ← Route auth, publik PDF, protected, admin-only, tamu
├── storage/
│   ├── app/public/
│   │   ├── arsip/surat-masuk/               ← PDF surat masuk tersimpan di sini
│   │   └── arsip/surat-keluar/              ← PDF surat keluar tersimpan di sini
│   └── image/
│       └── komdigi-logo.png                 ← Logo header aplikasi
├── start-intranet.bat                       ← Script 1-klik: aktifkan server intranet WiFi
├── start-ngrok.bat                          ← Script 1-klik untuk jalankan ngrok
└── C:\laragon\etc\apache2\sites-enabled\
    └── auto.project-magang.test.conf        ← Virtual host Apache Laragon
```

---

## 📋 Kolom Google Sheets

> Sheet `dbSM` dan `dbSK` adalah **sumber data tulis** (tempat form menyimpan data). Sheet `SURAT MASUK` dan `SURAT KELUAR` adalah **sheet tampilan** yang sudah di-combine per baris dan dibaca oleh tabel di aplikasi.

### Sheet `dbSM` — Sumber Data Surat Masuk (Kolom A–R)

| Kolom | Field |
|---|---|
| A | Timestamp |
| B | Kode Klasifikasi |
| C | Kode Surat |
| D | Uraian Informasi Berkas |
| E | Kurun Waktu |
| F | Jumlah Folder |
| G | Nomor Item Arsip |
| H | Uraian Informasi Arsip |
| I | Tanggal Surat |
| J | Jumlah Lembar |
| K | Tingkat Perkembangan |
| L | Lokasi Simpan |
| M | Klasifikasi Keamanan |
| N | Tingkat Akses |
| O | Retensi Aktif |
| P | Retensi Inaktif |
| Q | Retensi Keterangan |
| R | **Filename PDF** *(digunakan untuk generate URL publik secara dinamis)* |

### Sheet `SURAT MASUK` — Tampilan Tabel Aplikasi (Kolom A–Q)

> Sheet ini dibaca oleh `ArsipController::index()` untuk ditampilkan di tabel admin. Data sudah di-combine menjadi 1 baris per entri.

| Kolom | Field |
|---|---|
| A | No |
| B | Kode Klasifikasi |
| C | Uraian Berkas |
| D | Kurun Waktu |
| E | Jumlah Folder |
| F | Nomor Item |
| G | Uraian Arsip |
| H | Tanggal Surat |
| I | Jumlah Lembar |
| J | Tingkat Perkembangan |
| K | Lokasi Simpan |
| L | Klasifikasi Keamanan |
| M | Tingkat Akses |
| N | Retensi Aktif |
| O | Retensi Inaktif |
| P | Retensi Keterangan |
| Q | **Filename PDF** |

### Sheet `dbSK` — Sumber Data Surat Keluar (Kolom A–R)

| Kolom | Field |
|---|---|
| A | Timestamp |
| B | Kode Klasifikasi |
| C | Kode Surat |
| D | Uraian Informasi Berkas |
| E | Kurun Waktu |
| F | Jumlah Folder |
| G | Nomor Item Arsip |
| H | **Filename PDF** |
| I | Uraian Informasi Arsip |
| J | Tanggal Surat |
| K | Jumlah Lembar |
| L | Tingkat Perkembangan |
| M | Lokasi Simpan |
| N | Klasifikasi Keamanan |
| O | Tingkat Akses |
| P | Retensi Aktif |
| Q | Retensi Inaktif |
| R | Retensi Keterangan |

### Sheet `SURAT KELUAR` — Tampilan Tabel Aplikasi

> Sheet ini dibaca oleh `SuratKeluarController::index()` untuk ditampilkan di tabel admin.

---

## 🔄 Changelog Signifikan

| Versi | Perubahan |
|---|---|
| v1.0 | Setup awal: form surat masuk + keluar + Google Sheets |
| v1.1 | Sistem autentikasi login/logout (SQLite) |
| v1.2 | Responsive layout: hamburger/drawer menu mobile & tablet |
| v1.3 | Dashboard statistik bulanan & tahunan + pie chart |
| v1.4 | Apache Laragon virtual host (`project-magang.test`) — tanpa `artisan serve` |
| v1.4 | Enable PHP extensions `pdo_sqlite` & `sqlite3` untuk Apache |
| v1.5 | **PDF Publik**: Route `/pdf/{type}/{filename}` tanpa autentikasi |
| v1.5 | URL PDF (filename saja) tersimpan ke Google Sheets (bisa diklik dari spreadsheet) |
| v1.6 | **RBAC**: Role `admin` & `internship`, middleware `AdminOnly` |
| v1.6 | Internship hanya bisa input form; Admin punya akses penuh termasuk hapus data |
| v1.6 | Tampilan tabel & dashboard disembunyikan dari user Internship |
| v1.7 | Fix `BadMethodCallException`: hapus blok cookie manual yang memanggil `CookieJar::get()` yang tidak ada |
| v1.7 | Upgrade framework ke **Laravel 13.8** / PHP 8.4.20 |
| v1.7 | Navigasi drawer: icon burger konsisten (☰ saat tutup, ← saat buka) di semua ukuran layar |
| v1.8 | **Logo Komdigi**: header aplikasi menggunakan `komdigi-logo.png` menggantikan SVG generik |
| v1.8 | **Tabel Surat Masuk**: baca dari sheet `SURAT MASUK` (sudah di-combine per baris) bukan `dbSM` |
| v1.8 | **Kolom Kode Surat** dihapus dari tampilan tabel surat masuk (tidak tersedia di sheet display) |
| v1.8 | **Warna header tabel** Dashboard diseragamkan dengan tabel arsip (navy primary + teks putih) |
| v1.9 | **View Surat Keluar** dipindah ke `resources/views/arsip/surat-keluar.blade.php` (konsisten) |
| v1.9 | **Anti-FOUC**: CSS tabel dipindah ke `@push('styles')` → dirender di `<head>`, bukan bottom of body |
| v1.9 | **Skeleton Loading**: shimmer placeholder tabel sebelum data ditampilkan |
| v1.9 | **Page Loading Bar**: indikator progress tipis di atas halaman saat navigasi |
| v1.9 | **Google Fonts non-blocking**: preload + onload trick, tidak lagi render-blocking |
| v1.9 | **Security**: `.gitignore` diperkuat — SQLite, credentials, ngrok, key files terlindungi |
| v2.0 | **Intranet Mode**: `start-intranet.bat` — deteksi IP otomatis, update `.env`, 2 mode server |
| v2.0 | **Cybersecurity Layer**: `SecurityHeaders`, `InternalNetworkOnly`, `GuestNetwork` middleware |
| v2.0 | **Mode Tamu WiFi**: route `/tamu` & `/tamu/surat-keluar` — read-only tanpa login |
| v2.0 | **Rate Limiting** login: 5 percobaan/menit per IP+username (brute force protection) |
| v2.0 | **Session Security**: enkripsi session + SameSite=Strict + regenerate on login |
| v2.0 | **🐛 FIX KRITIS**: CSP kini mengizinkan `cdn.jsdelivr.net` → ikon kalender Flatpickr berfungsi |
| v2.0 | **🐛 FIX**: Typo atribut `style` (kutip buka hilang) di input kode_klasifikasi |

---

## 📜 Lisensi

Proyek ini dikembangkan untuk keperluan magang di **BPSDMP** (Balai Pengembangan Sumber Daya Manusia dan Penelitian). Tidak untuk didistribusikan secara publik.
