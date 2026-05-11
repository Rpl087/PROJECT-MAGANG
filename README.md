# 📂 Sistem Pengarsipan Digital Surat — BPSDMP

Aplikasi web berbasis **Laravel 11** untuk pengarsipan surat masuk dan surat keluar secara digital. Data arsip disimpan langsung ke **Google Spreadsheet** via Google Sheets API, file PDF disimpan di server lokal dan dapat **diakses publik** melalui URL unik tanpa perlu login.

---

## 🚀 Fitur Utama

- 🔐 **Autentikasi** — Login berbasis username & password, hanya 2 akun admin yang berwenang
- 📥 **Arsip Surat Masuk** — Input 17 field data + upload file PDF (maks. 10MB)
- 📤 **Arsip Surat Keluar** — Input 18 field data + upload file PDF (maks. 10MB)
- 📊 **Google Sheets Integration** — Data tersimpan otomatis ke spreadsheet (sheet `dbSM` dan `dbSK`)
- 📎 **PDF Publik** — File PDF surat dapat diakses publik via URL `/pdf/{type}/{filename}` tanpa login
- 📈 **Dashboard Rangkuman** — Statistik bulanan & tahunan surat masuk/keluar + pie chart kode klasifikasi
- 📱 **Responsive** — Hamburger menu untuk tampilan mobile & tablet
- 🌐 **Ngrok Ready** — Trusted proxy dikonfigurasi agar bekerja lewat tunnel HTTPS
- ⚡ **Apache Ready** — Virtual host Laragon dikonfigurasi, tidak perlu `php artisan serve`

---

## 🛠️ Tech Stack

| Layer | Teknologi |
|---|---|
| Framework | Laravel 11 (PHP 8.4) |
| Database | SQLite (local auth & sessions) |
| Storage | Google Sheets API v4 |
| Frontend | Blade + Vanilla CSS (Pure CSS, tanpa framework) |
| Auth | Laravel Session Auth (`Auth::attempt`) |
| File Upload | Laravel Storage (`public` disk) |
| Web Server | Apache (Laragon) — port 80, virtual host `project-magang.test` |
| Tunnel | Ngrok (opsional, untuk akses publik via PC lokal) |

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

## ⚡ Cara Menjalankan Lokal (Laragon)

> Tidak perlu `php artisan serve` — langsung menggunakan Apache Laragon.

### Prasyarat
- Laragon terinstall & Apache **berjalan** (`Start All`)
- PHP 8.4 dengan extension `pdo_sqlite` & `sqlite3` aktif di `php.ini`
- Virtual host `project-magang.test` terkonfigurasi

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

# 5. Migrasi & seed database
php artisan migrate --seed

# 6. Buat symlink storage
php artisan storage:link
```

### Menjalankan Sehari-hari
1. Pastikan **Laragon** aktif (Apache menyala)
2. Akses lokal: `http://project-magang.test`
3. Untuk akses publik via ngrok, **double-click** `start-ngrok.bat`

---

## 🔧 Konfigurasi `.env`

```dotenv
APP_NAME="Arsip Surat BPSDMP"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://project-magang.test

GOOGLE_SHEET_ID=1QdlmA0ug3xNY2-...
GOOGLE_SERVICE_ACCOUNT_JSON=storage/app/google-credentials.json

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
        string B_email_address
        string C_kode_klasifikasi
        string D_kode_surat
        string E_uraian_informasi_berkas
        string F_kurun_waktu
        string G_jumlah_folder
        string H_nomor_item_arsip
        url    I_url_pdf
        string J_uraian_informasi_arsip
        date   K_tanggal_surat
        string L_jumlah_lembar
        string M_tingkat_perkembangan
        string N_lokasi_simpan
        string O_klasifikasi_keamanan
        string P_tingkat_akses
        string Q_retensi_aktif
        string R_retensi_inaktif
        string S_retensi_keterangan
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
    F -- Ya --> H[Masuk Dashboard Utama]
    C -- Ya --> H

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
    W2 --> X[Kirim Data ke Google Sheets\nSheet: dbSK kolom A–S]
    X --> Y[Tampil Notifikasi Berhasil]
    Y --> H

    I -- Dashboard --> DA[Tampil Statistik Bulanan & Tahunan]
    DA --> DB[Pie Chart Kode Klasifikasi]
    DB --> H

    I -- Keluar --> Z[Logout Session]
    Z --> D

    PUB([Pengguna Publik]) --> PURL[Akses URL PDF\n/pdf/type/filename]
    PURL --> PCHECK{File Ada?}
    PCHECK -- Ya --> PPDF[Tampil PDF di Browser]
    PCHECK -- Tidak --> P404[404 Not Found]
```

---

## 👤 Use Case Diagram

```mermaid
graph LR
    Admin(["👤 Admin\n(2 akun)"])
    Public(["🌐 Publik\n(tanpa login)"])

    subgraph Sistem ["🖥️ Sistem Pengarsipan BPSDMP"]
        UC1("🔐 Login ke Sistem")
        UC2("📥 Input Surat Masuk")
        UC3("📤 Input Surat Keluar")
        UC4("📎 Upload File PDF")
        UC5("📊 Simpan ke Google Sheets")
        UC6("🚪 Logout")
        UC7("📈 Lihat Dashboard Statistik")
        UC8("🔗 Akses PDF via URL Publik")

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
    Public --> UC8

    UC1 -.->|"«include»"| VALID["Validasi Kredensial\n(username + password)"]
    UC5 -.->|"«include»"| GS
```

---

## 🗂️ Struktur Direktori Penting

```
project-magang/
├── app/
│   └── Http/
│       └── Controllers/
│           ├── ArsipController.php          ← Surat Masuk (form + Google Sheets + URL PDF)
│           ├── SuratKeluarController.php    ← Surat Keluar (form + Google Sheets + URL PDF)
│           ├── DashboardController.php      ← Statistik bulanan/tahunan dari Google Sheets
│           └── Auth/
│               └── LoginController.php      ← Login & Logout
├── bootstrap/
│   └── app.php                              ← TrustProxies untuk Ngrok (at: '*')
├── database/
│   ├── migrations/                          ← Tabel users, sessions, cache
│   ├── seeders/
│   │   └── DatabaseSeeder.php               ← 2 akun admin hardcoded
│   └── database.sqlite                      ← Database lokal (SQLite)
├── public/
│   ├── css/
│   │   └── app.css                          ← Stylesheet utama (Vanilla CSS)
│   └── storage/                             ← Symlink ke storage/app/public/
├── resources/
│   └── views/
│       ├── auth/
│       │   └── login.blade.php              ← Halaman login (dark glassmorphism)
│       ├── arsip/
│       │   └── index.blade.php              ← Form surat masuk
│       ├── surat-keluar/
│       │   └── index.blade.php              ← Form surat keluar
│       ├── dashboard/
│       │   └── index.blade.php              ← Dashboard statistik & chart
│       └── layouts/
│           └── app.blade.php                ← Layout utama (sidebar + header)
├── routes/
│   └── web.php                              ← Semua route (auth middleware + PDF publik)
├── storage/
│   └── app/public/
│       ├── arsip/surat-masuk/               ← PDF surat masuk tersimpan di sini
│       └── arsip/surat-keluar/              ← PDF surat keluar tersimpan di sini
├── start-ngrok.bat                          ← Script 1-klik untuk jalankan ngrok
└── C:\laragon\etc\apache2\sites-enabled\
    └── auto.project-magang.test.conf        ← Virtual host Apache Laragon
```

---

## 🔑 Akun Admin

| Username | Keterangan |
|---|---|
| `admin` | Admin Utama |
| `admin2` | Admin Kedua |

> Password disimpan menggunakan **bcrypt** (Laravel `Hash::make()`). Untuk mengubah password, edit `database/seeders/DatabaseSeeder.php` lalu jalankan `php artisan db:seed --force`.

---

## 📋 Kolom Google Sheets

### Sheet `dbSM` — Surat Masuk (Kolom A–R)

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
| R | **URL Publik File PDF** *(dapat diklik langsung dari spreadsheet)* |

### Sheet `dbSK` — Surat Keluar (Kolom A–S)

| Kolom | Field |
|---|---|
| A | Timestamp |
| B | Email Address |
| C | Kode Klasifikasi |
| D | Kode Surat |
| E | Uraian Informasi Berkas |
| F | Kurun Waktu |
| G | Jumlah Folder |
| H | Nomor Item Arsip |
| I | **URL Publik File PDF** *(dapat diklik langsung dari spreadsheet)* |
| J | Uraian Informasi Arsip |
| K | Tanggal Surat |
| L | Jumlah Lembar |
| M | Tingkat Perkembangan |
| N | Lokasi Simpan |
| O | Klasifikasi Keamanan |
| P | Tingkat Akses |
| Q | Retensi Aktif |
| R | Retensi Inaktif |
| S | Retensi Keterangan |

---

## 🔄 Changelog Signifikan

| Versi | Perubahan |
|---|---|
| v1.0 | Setup awal: form surat masuk + keluar + Google Sheets |
| v1.1 | Sistem autentikasi login/logout (SQLite) |
| v1.2 | Responsive layout: hamburger menu mobile/tablet |
| v1.3 | Dashboard statistik bulanan & tahunan + pie chart |
| v1.4 | Apache Laragon virtual host (`project-magang.test`) — tanpa `artisan serve` |
| v1.4 | Enable PHP extensions `pdo_sqlite` & `sqlite3` untuk Apache |
| v1.5 | **PDF Publik**: Route `/pdf/{type}/{filename}` tanpa autentikasi |
| v1.5 | URL PDF lengkap tersimpan ke Google Sheets (bisa diklik dari spreadsheet) |

---

## 📜 Lisensi

Proyek ini dikembangkan untuk keperluan magang di **BPSDMP** (Balai Pengembangan Sumber Daya Manusia dan Penelitian). Tidak untuk didistribusikan secara publik.
