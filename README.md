# 📂 Sistem Pengarsipan Digital Surat — BPSDMP

Aplikasi web berbasis **Laravel 11** untuk pengarsipan surat masuk dan surat keluar secara digital. Data arsip disimpan langsung ke **Google Spreadsheet** via Google Sheets API, serta file PDF surat disimpan di server lokal.

---

## 🚀 Fitur Utama

- 🔐 **Autentikasi** — Login berbasis username & password, hanya 2 akun admin yang berwenang
- 📥 **Arsip Surat Masuk** — Input 17 field data + upload file PDF (maks. 10MB)
- 📤 **Arsip Surat Keluar** — Input 18 field data + upload file PDF (maks. 10MB)
- 📊 **Google Sheets Integration** — Data tersimpan otomatis ke spreadsheet (sheet `dbSM` dan `dbSK`)
- 📱 **Responsive** — Hamburger menu untuk tampilan mobile & tablet
- 🌐 **Ngrok Ready** — Trusted proxy dikonfigurasi agar login bekerja lewat tunnel HTTPS

---

## 🛠️ Tech Stack

| Layer | Teknologi |
|---|---|
| Framework | Laravel 11 (PHP 8.4) |
| Database | SQLite (local auth) |
| Storage | Google Sheets API v4 |
| Frontend | Blade + Vanilla CSS (Pure CSS, tanpa framework) |
| Auth | Laravel Session Auth (`Auth::attempt`) |
| File Upload | Laravel Storage (`public` disk) |
| Tunnel | Ngrok (opsional, untuk akses publik) |

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
        string R_path_pdf
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
        string I_path_pdf
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
    O --> P[Kirim Data ke Google Sheets\nSheet: dbSM kolom A–R]
    P --> Q[Tampil Notifikasi Berhasil]
    Q --> H

    I -- Surat Keluar --> R[Buka Form Surat Keluar]
    R --> S[Isi 18 Field Data]
    S --> T[Upload File PDF]
    T --> U{Validasi Form}
    U -- Gagal --> V[Tampil Pesan Error\nForm tetap terisi]
    V --> S
    U -- Lolos --> W[Simpan PDF ke Server\nstorage/arsip/surat-keluar/]
    W --> X[Kirim Data ke Google Sheets\nSheet: dbSK kolom A–S]
    X --> Y[Tampil Notifikasi Berhasil]
    Y --> H

    I -- Keluar --> Z[Logout Session]
    Z --> D
```

---

## 👤 Use Case Diagram

```mermaid
graph LR
    Admin(["👤 Admin\n(2 akun)"])

    subgraph Sistem ["🖥️ Sistem Pengarsipan BPSDMP"]
        UC1("🔐 Login ke Sistem")
        UC2("📥 Input Surat Masuk")
        UC3("📤 Input Surat Keluar")
        UC4("📎 Upload File PDF")
        UC5("📊 Simpan ke Google Sheets")
        UC6("🚪 Logout")

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

    UC1 -.->|"«include»"| VALID["Validasi Kredensial\n(username + password)"]
    UC5 -.->|"«include»"| GS
```

---

## 🗂️ Struktur Direktori Penting

```
project-magang/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── ArsipController.php       ← Surat Masuk (form + Google Sheets)
│   │   │   ├── SuratKeluarController.php ← Surat Keluar (form + Google Sheets)
│   │   │   └── Auth/
│   │   │       └── LoginController.php   ← Login & Logout
│   │   └── Middleware/
│   └── Models/
│       └── User.php
├── bootstrap/
│   └── app.php                           ← TrustProxies untuk Ngrok
├── database/
│   ├── migrations/                       ← Tabel users, sessions, cache
│   ├── seeders/
│   │   └── DatabaseSeeder.php            ← 2 akun admin hardcoded
│   └── database.sqlite                   ← Database lokal
├── public/
│   └── css/
│       └── app.css                       ← Stylesheet utama (Vanilla CSS, 958 baris)
├── resources/
│   └── views/
│       ├── auth/
│       │   └── login.blade.php           ← Halaman login (dark glassmorphism)
│       ├── arsip/
│       │   └── index.blade.php           ← Form surat masuk
│       ├── surat-keluar/
│       │   └── index.blade.php           ← Form surat keluar
│       └── layouts/
│           └── app.blade.php             ← Layout utama (sidebar + header)
├── routes/
│   └── web.php                           ← Semua route (auth middleware)
└── storage/
    └── app/public/
        ├── arsip/surat-masuk/            ← PDF surat masuk tersimpan di sini
        └── arsip/surat-keluar/           ← PDF surat keluar tersimpan di sini
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
| R | Path File PDF |

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
| I | Path File PDF |
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

## 📜 Lisensi

Proyek ini dikembangkan untuk keperluan magang di **BPSDMP** (Balai Pengembangan Sumber Daya Manusia dan Penelitian). Tidak untuk didistribusikan secara publik.
