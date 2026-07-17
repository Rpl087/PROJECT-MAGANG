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

## 👤 Use Case Diagram

```mermaid
graph LR
    Admin(["👤 Admin"])
    Internship(["🎓 Internship"])
    Maintenance(["🛠️ Maintenance"])
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
        UC11("✏️ Edit Data Surat")
        UC12("📋 Rekap Laporan & Filter Tahun")
        UC13("🛠️ Kelola Menu Dinamis & Sistem")

        UC2 --> UC4
        UC3 --> UC4
        UC2 --> UC5
        UC3 --> UC5
        UC11 --> UC5
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
    Admin --> UC11
    Admin --> UC12
    Internship --> UC1
    Internship --> UC2
    Internship --> UC3
    Internship --> UC6
    Maintenance --> UC1
    Maintenance --> UC13
    Public --> UC8
    Tamu --> UC10

    UC1 -.->|"«include»"| VALID["Validasi Kredensial\n(username + password)"]
    UC5 -.->|"«include»"| GS
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
    ROLE -- Maintenance --> MAINT[Buka Panel Maintenance]
    C -- Ya --> ROLE

    H --> I{Pilih Menu}

    I -- Surat Masuk --> J[Buka Form Surat Masuk]
    J --> K[Isi 17 Field Data]
    K --> L[Upload File PDF]
    L --> M{Validasi Form}
    M -- Gagal --> N[Tampil Pesan Error\nForm tetap terisi]
    N --> K
    M -- Lolos --> O[Upload PDF ke Google Drive\nBackup lokal ke uploads/]
    O --> P[Kirim Data ke Google Sheets\nSheet: dbSM kolom A–R]
    P --> Q[Tampil Notifikasi Berhasil]
    Q --> H

    I -- Surat Keluar --> R[Buka Form Surat Keluar]
    R --> S[Isi 18 Field Data]
    S --> T[Upload File PDF]
    T --> U{Validasi Form}
    U -- Gagal --> V[Tampil Pesan Error\nForm tetap terisi]
    V --> S
    U -- Lolos --> W[Upload PDF ke Google Drive\nBackup lokal ke uploads/]
    W --> X[Kirim Data ke Google Sheets\nSheet: dbSK kolom A–R]
    X --> Y[Tampil Notifikasi Berhasil]
    Y --> H

    I -- Dashboard --> DA[Tampil Statistik Bulanan & Tahunan]
    DA --> DB[Pie Chart Kode Klasifikasi]
    DB --> H

    I -- Hapus Data --> DEL{Konfirmasi Hapus?}
    DEL -- Ya --> DELACT[Hapus baris dari Google Sheets dbSM/dbSK]
    DELACT --> H
    DEL -- Tidak --> H

    I -- Edit Data --> EDIT[Klik tombol Edit di tabel]
    EDIT --> EDITFETCH[AJAX fetch data baris\n/arsip/{index}/edit → JSON]
    EDITFETCH --> EDITMODAL[Modal Edit pre-filled\n17 field data surat]
    EDITMODAL --> EDITSUB{Submit form PUT?}
    EDITSUB -- Ya --> EDITUPDATE[Update baris di dbSM/dbSK\nspreadsheets.values.update]
    EDITUPDATE --> H
    EDITSUB -- Tidak/Batal --> H

    I -- Rekap --> RPT[Dashboard Report]
    RPT --> RPT2[Filter Tahun per Tab\nRekap Bulanan/Tahunan/Klasifikasi]
    RPT2 --> RPT3[Cetak Tab / Cetak Semua]
    RPT3 --> H

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

## 📐 Entity Relationship Diagram (ERD)

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
