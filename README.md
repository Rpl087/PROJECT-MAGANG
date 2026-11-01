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
- 🗄️ **Database** — Membuat database `arsip_bpsdmp`, menjalankan migrasi, dan seeder user default.
- 🌐 **Virtual Host** — Membuat VirtualHost Apache `kearsipan.local.conf` di Laragon.
- 📌 **Hosts File** — Mendaftarkan domain `127.0.0.1 kearsipan.local` di berkas `hosts` Windows.
- 🛡️ **Firewall** — Membuka Inbound Port 80 pada Windows Firewall.
- 🧹 **Storage & Cache** — Melakukan pembersihan berkas temporary views & cache framework data.

### 🚀 2. Menjalankan Aplikasi Sehari-hari (Daily Runner)
Untuk penggunaan sehari-hari setelah setup awal berhasil, gunakan skrip runner ringan:
```cmd
autostart.bat
```
Atau gunakan skrip server lengkap dengan komponen Queue & Scheduler:
```cmd
"Arsip Surat (server).bat"
```
Skrip ini akan menjalankan:
- ⚡ **Service Check** — Memastikan Laragon, Apache, dan MySQL sudah aktif.
- ⚙️ **Queue Worker** — Menyalakan Laravel Queue Worker di latar belakang.
- 🕔 **Task Scheduler** — Menyalakan `php artisan schedule:work` di latar belakang (otomatis menjalankan pembersihan storage setiap pukul **17:00** dan prune sesi setiap jam).
- 🌐 **Auto-Launch** — Membuka browser langsung ke URL login aplikasi.

### 🌐 3. Akses Aplikasi
- **Akses Komputer Server (Lokal)**: `http://kearsipan.local/`
- **Akses Perangkat Lain (HP/Laptop via LAN/Wi-Fi)**:
  - HTTP: `http://<IP-SERVER>/` (sesuaikan dengan IP server lokal)
  - HTTPS: `https://<IP-SERVER>/`
  *(Catatan: Akses via subfolder seperti `http://<IP-SERVER>/project-magangv1` otomatis di-redirect ke root aplikasi tanpa 404).*
- **Akses WiFi Tamu (Read-Only Tanpa Login)**:
  - `http://kearsipan.local/tamu`
  - `http://<IP-SERVER>/tamu`

---

## 🚀 Fitur Utama

| Fitur | Deskripsi |
|---|---|
| 🔐 **Autentikasi & RBAC** | Login aman dengan multi-role: `admin` & `internship`. Login bisa pakai `name` atau `email`. |
| 📥 **Surat Masuk** | Input data lengkap + upload PDF, tersimpan ke Google Sheets (`dbSM`) dengan 18 kolom |
| 📤 **Surat Keluar** | Input data lengkap + upload PDF, tersimpan ke Google Sheets (`dbSK`) dengan 18 kolom |
| ✏️ **Edit & Rename PDF** | Edit data surat otomatis mengubah nama file PDF di lokal (`/uploads`) & Google Drive |
| 🗑️ **Hapus Synchronous** | Hapus data surat otomatis menghapus file PDF dari lokal (`/uploads`) & Google Drive |
| 🔍 **Filter & Klasifikasi** | Pencarian & filter berdasarkan bulan, tahun, dan kode klasifikasi surat |
| 📊 **Dashboard & Grafik Bar** | Statistik data arsip bulanan, tahunan, & angka total di atas batang grafik (Chart.js + datalabels) |
| 🖨️ **Laporan Rekap Printable** | Tampilan rekap laporan lengkap per tahun, per klasifikasi, detail bulanan SM & SK |
| 🖨️ **Print Dashboard** | Tampilan cetak profesional dengan kisi garis seragam (`1pt solid #333`) |
| 👥 **Manajemen Pengguna** | Admin dapat mereset password internship & menindaklanjuti permintaan reset |
| 🔔 **Notifikasi Realtime (SSE)** | Admin mendapat notifikasi realtime saat internship mengajukan reset password, dengan fallback polling |
| ⚡ **Dual-Tier Cache Sheets** | Cache mikro Admin 30 detik, cache normal non-admin 60 detik. Invalidasi instan saat store/update/destroy |
| ⏱️ **Sesi 4 Jam & Auto-Prune** | Masa aktif sesi 240 menit di database MySQL dengan pembersihan expired per jam |
| 🌐 **Intranet Speed & Smooth Nav** | Loading animation kontekstual & font fallback sistem lokal untuk navigasi instan di Intranet |
| 👀 **Mode Tamu (Read-Only)** | Halaman khusus WiFi tamu tanpa login untuk membaca data arsip surat masuk & keluar |
| 🔒 **Cybersecurity Layer** | Brute Force IP Ban, Formula Injection Guard, CSP, Rate Limiting, Security Headers, PHP2Shell Immunity (PHP 8.4.27+ & upload progress disabled), Production Hardening |
| 🧹 **Task Scheduler** | Pembersihan otomatis sesi expired setiap 1 jam & berkas temporary storage setiap 17:00 |
| 🌐 **Multi-Subnet Support** | Dukungan beberapa subnet sekaligus dipisah koma di `.env` (IPv4 & IPv6 via `ChecksIpSubnet` trait) |
| 🔄 **Kode Klasifikasi Auto-fill** | Kolom retensi & keamanan terisi otomatis berdasarkan `KODE_MAP` sesuai jadwal retensi arsip |
| 🛡️ **Trusted Proxy Support** | Mendukung konfigurasi `TRUSTED_PROXIES` untuk deployment di balik reverse proxy/nginx |

---

## 🛠️ Tech Stack

| Lapisan | Teknologi |
|---|---|
| **Framework** | Laravel 13 (PHP 8.4.27+ / PHP 8.3.36+ / PHP 8.5.12+ — Patch Resmi PHP2Shell) |
| **Database** | MySQL / MariaDB (autentikasi, session, password reset requests) |
| **Penyimpanan Data** | Google Sheets API v4 (`dbSM` & `dbSK`) via Service Account |
| **Penyimpanan File** | Server Lokal (`/uploads`) + Google Drive API (dual backup) |
| **Frontend** | Blade Templates + Vanilla CSS (Dark Mode) + Chart.js (+ datalabels plugin) |
| **Web Server** | Apache via Laragon — Virtual Host `kearsipan.local` (port 80 & 443 SSL) |
| **Real-time** | Server-Sent Events (SSE) + polling fallback untuk notifikasi reset password |
| **Cache** | Laravel Cache (file driver, dapat diganti Redis/Memcached via `CACHE_STORE`) |
| **Dependency** | `google/apiclient:^2.0` (Sheets & Drive API services) |
| **Dev Tools** | Laravel Pail, Laravel Pao, Laravel Pint, PHPUnit 12, Faker |

> [!IMPORTANT]
> **Standar Keamanan PHP Runtime (Mitigasi Insiden PHP2Shell — CVE-2026-109618 & CVE-2026-109619)**:
> Server wajib menggunakan runtime PHP versi tertambal (**PHP 8.4.27+**, **PHP 8.3.36+**, atau **PHP 8.5.12+**). Versi PHP unpatched (< 8.4.27) rentan terhadap Remote Code Execution (RCE) pre-auth pada level engine interpretor. Selain pembaruan runtime, konfigurasi `php.ini` diperkuat dengan `session.upload_progress.enabled = Off`.

---

## 👥 Peran & Hak Akses

| Kemampuan | Admin | Internship | Tamu (WiFi) | Publik |
|---|:---:|:---:|:---:|:---:|
| Login | ✅ | ✅ | ❌ | ❌ |
| Input Surat Masuk | ✅ | ✅* | ❌ | ❌ |
| Input Surat Keluar | ✅ | ✅* | ❌ | ❌ |
| Edit Data & Rename PDF | ✅ | ❌ | ❌ | ❌ |
| Hapus Data & Hapus PDF | ✅ | ❌ | ❌ | ❌ |
| Dashboard & Rekap | ✅ | ❌ | ❌ | ❌ |
| Print Laporan | ✅ | ❌ | ❌ | ❌ |
| Kelola Pengguna | ✅ | ❌ | ❌ | ❌ |
| Ajukan Reset Password | ❌ | ✅ | ❌ | ❌ |
| Lihat Arsip (Read-Only) | ✅ | ✅ | ✅ | ❌ |
| Akses PDF Publik | ✅ | ✅ | ✅ | ✅ |

> \* Input hanya bisa dilakukan dari **jaringan internal** (`INTRANET_ENABLED=true`). Jika `INTRANET_ENABLED=false`, semua IP diizinkan (mode development).

---

## 🗺️ Peta Rute (Routes)

### Publik (Tanpa Login)
| Method | URL | Keterangan |
|---|---|---|
| `GET` | `/login` | Halaman login |
| `POST` | `/login` | Proses login *(Rate Limiting: 5 percobaan / 5 menit per username+IP)* |
| `POST` | `/logout` | Proses logout |
| `POST` | `/password/request-reset` | Ajukan permintaan reset password *(throttle: 3 req / 5 menit)* |
| `GET` | `/pdf/{type}/{filename}` | Akses file PDF publik dari `/uploads/` |
| `GET` | `/image/{filename}` | Akses file gambar dari `storage/image/` |

### Terautentikasi (Admin & Internship)
| Method | URL | Keterangan |
|---|---|---|
| `GET` | `/` | Daftar Surat Masuk (`arsip.index`) |
| `POST` | `/arsip` | Simpan Surat Masuk baru *(internal_only)* |
| `GET` | `/surat-keluar` | Daftar Surat Keluar |
| `POST` | `/surat-keluar` | Simpan Surat Keluar baru *(internal_only)* |

### Admin Only *(auth + AdminOnly + internal_only)*
| Method | URL | Keterangan |
|---|---|---|
| `GET` | `/dashboard` | Dashboard statistik & grafik batang |
| `GET` | `/dashboard/report` | Rekap laporan lengkap per tahun & klasifikasi |
| `GET` | `/dashboard/print` | Tampilan cetak print-friendly laporan |
| `GET` | `/arsip/{rowIndex}/edit` | Form edit Surat Masuk (JSON response) |
| `PUT` | `/arsip/{rowIndex}` | Update Surat Masuk + Rename PDF |
| `DELETE` | `/arsip/{rowIndex}` | Hapus Surat Masuk + Hapus PDF |
| `GET` | `/surat-keluar/{rowIndex}/edit` | Form edit Surat Keluar (JSON response) |
| `PUT` | `/surat-keluar/{rowIndex}` | Update Surat Keluar + Rename PDF |
| `DELETE` | `/surat-keluar/{rowIndex}` | Hapus Surat Keluar + Hapus PDF |
| `GET` | `/admin/users` | Kelola pengguna & lihat permintaan reset |
| `POST` | `/admin/users/{user}/reset-password` | Reset password pengguna |
| `POST` | `/admin/password-reset-requests/{id}/resolve` | Tandai permintaan reset selesai |
| `GET` | `/sse/reset-requests` | Stream SSE notifikasi reset password realtime |
| `GET` | `/api/reset-requests-version` | Polling fallback versi permintaan reset |

### Tamu WiFi (Read-Only, Tanpa Login) *(guest_network)*
| Method | URL | Keterangan |
|---|---|---|
| `GET` | `/tamu` | Daftar Surat Masuk (read-only) |
| `GET` | `/tamu/surat-keluar` | Daftar Surat Keluar (read-only) |

---


## ðŸ‘¤ Use Case Diagram

### Ringkasan Aktor & Use Case

| Aktor | Use Case yang Dapat Diakses |
|---|---|
| **Admin** | Login, Logout, Lihat Arsip, Input Surat, Edit & Rename PDF, Hapus & Hapus PDF, Dashboard, Rekap Laporan, Print, Kelola Pengguna, Notifikasi SSE Reset, Akses PDF |
| **Internship** | Login, Logout, Lihat Arsip, Input Surat (jaringan internal), Ajukan Reset Password, Akses PDF |
| **Tamu WiFi** | Lihat Surat Masuk Read-Only, Lihat Surat Keluar Read-Only (tanpa login) |
| **Publik** | Akses URL PDF langsung (tanpa login) |

### Use Case 1 â€” Pengguna Umum (Tamu, Publik, Internship)

```mermaid
flowchart LR
    Tamu([Tamu WiFi])
    Publ([Publik])
    Intrn([Internship])

    subgraph SIS1 [Sistem - Akses Umum]
        direction TB
        UC_TamuSM(["Lihat Surat Masuk\nRead-Only"])
        UC_TamuSK(["Lihat Surat Keluar\nRead-Only"])
        UC_AksesPDF(["Akses URL PDF"])
        UC_Login(["Login"])
        UC_Logout(["Logout"])
        UC_ResetReq(["Ajukan Reset Password"])
        UC_LihatSM(["Lihat Surat Masuk"])
        UC_LihatSK(["Lihat Surat Keluar"])
        UC_InputSM(["Input Surat Masuk\nJaringan Internal"])
        UC_InputSK(["Input Surat Keluar\nJaringan Internal"])
        UC_SimpanArsip(["Simpan PDF & Data\nke Drive + Google Sheets"])
    end

    GS([Google Sheets API])
    GD([Google Drive API])
    MySQL([MySQL])

    Tamu --- UC_TamuSM
    Tamu --- UC_TamuSK
    Tamu --- UC_AksesPDF
    Publ --- UC_AksesPDF

    Intrn --- UC_Login
    Intrn --- UC_Logout
    Intrn --- UC_ResetReq
    Intrn --- UC_LihatSM
    Intrn --- UC_LihatSK
    Intrn --- UC_InputSM
    Intrn --- UC_InputSK
    Intrn --- UC_AksesPDF

    UC_InputSM -. "include" .-> UC_SimpanArsip
    UC_InputSK -. "include" .-> UC_SimpanArsip

    UC_TamuSM      --- GS
    UC_TamuSK      --- GS
    UC_LihatSM     --- GS
    UC_LihatSK     --- GS
    UC_SimpanArsip --- GD
    UC_SimpanArsip --- GS
    UC_Login       --- MySQL
    UC_ResetReq    --- MySQL
```

### Use Case 2 - Manajemen & Administrasi (Admin)

```mermaid
flowchart LR
    Adm([Admin])

    subgraph SIS2 [Sistem - Manajemen & Administrasi]
        direction TB
        UC_Login2(["Login"])
        UC_Logout2(["Logout"])
        UC_LihatSM2(["Lihat Surat Masuk"])
        UC_LihatSK2(["Lihat Surat Keluar"])
        UC_InputSM2(["Input Surat Masuk\nJaringan Internal"])
        UC_InputSK2(["Input Surat Keluar\nJaringan Internal"])
        UC_Edit(["Edit Data & Rename PDF"])
        UC_Hapus(["Hapus Data & PDF"])
        UC_Dashboard(["Dashboard & Grafik Bar"])
        UC_Rekap(["Rekap Laporan\nper Tahun & Klasifikasi"])
        UC_Print(["Print Laporan"])
        UC_KelolaUser(["Kelola Pengguna\n& Reset Password Manual"])
        UC_SSE(["Terima Notifikasi SSE\n+ Polling Fallback"])
        UC_AksesPDF2(["Akses URL PDF"])
        UC_KelolaPDF(["Kelola File PDF\nUpload / Rename di Drive"])
        UC_Simpan2(["Simpan / Update / Hapus\nGoogle Sheets"])
    end

    GS([Google Sheets API])
    GD([Google Drive API])
    MySQL([MySQL])

    Adm --- UC_Login2
    Adm --- UC_Logout2
    Adm --- UC_LihatSM2
    Adm --- UC_LihatSK2
    Adm --- UC_InputSM2
    Adm --- UC_InputSK2
    Adm --- UC_Edit
    Adm --- UC_Hapus
    Adm --- UC_Dashboard
    Adm --- UC_Rekap
    Adm --- UC_Print
    Adm --- UC_KelolaUser
    Adm --- UC_SSE
    Adm --- UC_AksesPDF2

    UC_InputSM2 -. "include" .-> UC_KelolaPDF
    UC_InputSK2 -. "include" .-> UC_KelolaPDF
    UC_Edit     -. "include" .-> UC_KelolaPDF
    UC_InputSM2 -. "include" .-> UC_Simpan2
    UC_InputSK2 -. "include" .-> UC_Simpan2
    UC_Edit     -. "include" .-> UC_Simpan2
    UC_Hapus    -. "include" .-> UC_Simpan2

    UC_Simpan2    --- GS
    UC_Dashboard  --- GS
    UC_Rekap      --- GS
    UC_LihatSM2   --- GS
    UC_LihatSK2   --- GS
    UC_KelolaPDF  --- GD
    UC_Login2     --- MySQL
    UC_SSE        --- MySQL
    UC_KelolaUser --- MySQL
```

---

## ðŸ”„ Flowchart Sistem

### Flowchart 1 â€” Autentikasi & Reset Password

```mermaid
flowchart LR
    Start(["Buka Aplikasi"]) --> CheckAuth{"Pengguna Sudah\nLogin?"}

    CheckAuth -- Ya --> UpdateLastSeen["Perbarui Waktu\nAktivitas Terakhir"]
    UpdateLastSeen --> CheckRole{"Peran Akun\n(Role)?"}
    CheckRole -- Admin --> AdminDash["Halaman Utama Admin\n(/dashboard)"]
    CheckRole -- Internship --> ArsipPage["Halaman Arsip Surat\n(/arsip)"]

    CheckAuth -- Tidak --> LoginPage["Halaman Masuk\n(/login)"]
    LoginPage --> CheckBan{"Alamat IP Sedang\nDiblokir Sementara?"}
    CheckBan -- Ya --> Block429["Akses Diblokir 1 Jam\n(Terlalu Sering Gagal)"]
    CheckBan -- Tidak --> InputCred["Masukkan Username\n& Password"]
    InputCred --> RateLimit{"Batas Percobaan\nTerlampaui?\n(5x gagal / 5 menit)"}
    RateLimit -- Ya --> ErrThrottle["Peringatan: Tunggu\nBeberapa Menit"] --> LoginPage
    RateLimit -- Tidak --> ValidateCred{"Username & Password\nSesuai?"}
    ValidateCred -- Tidak --> RecordFail["Catat Percobaan Gagal"]
    RecordFail --> CheckCount{"Mencapai 20x Gagal\ndalam 10 Menit?"}
    CheckCount -- Ya --> BanIP["Blokir Alamat IP\nselama 1 Jam"] --> Block429
    CheckCount -- Tidak --> ErrMsg["Tampilkan Pesan:\nAkun Tidak Ditemukan"] --> LoginPage
    ValidateCred -- Ya --> CreateSession["Buat Sesi Login Baru\n(Aktif 4 Jam)"] --> UpdateLastSeen

    subgraph TamuFlow ["Akses Tamu WiFi (Tanpa Login)"]
        TReq["Akses Halaman Tamu\n(/tamu)"] --> CheckSubnet{"Terhubung ke WiFi Tamu\natau Jaringan Kantor?"}
        CheckSubnet -- Ya --> TamuView["Tampilkan Data Arsip\n(Hanya Baca / Read-Only)"]
        CheckSubnet -- Tidak --> Deny403T["Akses Ditolak\n(403 Forbidden)"]
    end

    subgraph ResetFlow ["Pengajuan Reset Password (Internship)"]
        BtnForgot["Klik 'Lupa Password'"] --> ResetForm["Masukkan Username\npada Formulir"]
        ResetForm --> ResetThrottle{"Batas Pengajuan\nTerlampaui?\n(3x / 5 menit)"}
        ResetThrottle -- Ya --> TooMany["Peringatan: Terlalu\nBanyak Permohonan"] --> ResetForm
        ResetThrottle -- Tidak --> SaveReq["Simpan Permohonan Reset\n(Status: Menunggu Persetujuan)"]
        SaveReq --> SSENotif["Kirim Notifikasi Langsung\nke Layar Admin (Real-Time)"]
        SSENotif --> AdminResolve["Admin Membuka Menu\nKelola Pengguna"]
        AdminResolve --> ResetPw["Admin Buat Password Baru\nuntuk Pengguna"]
        ResetPw --> MarkDone["Permohonan Selesai Diproses"]
    end
```

### Flowchart 2 â€” Input Data & Sinkronisasi Drive/Sheets

```mermaid
flowchart LR
    UserAksi(["Pengguna Aktif\nAdmin / Internship"]) --> CheckNet{"Dari Jaringan\nInternal Kantor?"}
    CheckNet -- Tidak --> Deny403["Akses Ditolak\n(403 Forbidden)"]
    CheckNet -- Ya --> PilihForm{"Pilih Jenis\nSurat"}

    PilihForm -- "Surat Masuk" --> FormSM["Isi Formulir Surat Masuk\n17 field + upload PDF"]
    PilihForm -- "Surat Keluar" --> FormSK["Isi Formulir Surat Keluar\n17 field + upload PDF"]

    FormSM --> ValidSM{"Data Lengkap\n& Valid?"}
    FormSK --> ValidSK{"Data Lengkap\n& Valid?"}
    ValidSM -- Tidak --> ErrSM["Tampilkan Pesan\nKesalahan"] --> FormSM
    ValidSK -- Tidak --> ErrSK["Tampilkan Pesan\nKesalahan"] --> FormSK

    ValidSM -- Ya --> KodeSM["Lengkapi Otomatis\nData Retensi & Keamanan"]
    ValidSK -- Ya --> KodeSK["Lengkapi Otomatis\nData Retensi & Keamanan"]
    KodeSM --> EscSM["Amankan Data dari\nKarakter Berbahaya"]
    KodeSK --> EscSK["Amankan Data dari\nKarakter Berbahaya"]

    EscSM --> UpSM["Simpan PDF ke Server\nlalu Coba Upload ke Drive"]
    EscSK --> UpSK["Simpan PDF ke Server\nlalu Coba Upload ke Drive"]

    UpSM --> DriveOKSM{"Upload Drive\nBerhasil?"}
    UpSK --> DriveOKSK{"Upload Drive\nBerhasil?"}
    DriveOKSM -- Ya --> AppSM["Catat Data ke\nGoogle Sheets\n(Surat Masuk)"]
    DriveOKSK -- Ya --> AppSK["Catat Data ke\nGoogle Sheets\n(Surat Keluar)"]
    DriveOKSM -- Tidak --> LocalSM["Simpan Lokal Saja\n(Drive tidak tersedia)"] --> AppSM
    DriveOKSK -- Tidak --> LocalSK["Simpan Lokal Saja\n(Drive tidak tersedia)"] --> AppSK

    AppSM --> ErrSM2{"Berhasil\nTercatat?"}
    AppSK --> ErrSK2{"Berhasil\nTercatat?"}

    ErrSM2 -- Tidak --> RollSM["Batalkan & Hapus\nFile yang Sudah Tersimpan"] --> Fail["Tampilkan\nPesan Gagal"]
    ErrSK2 -- Tidak --> RollSK["Batalkan & Hapus\nFile yang Sudah Tersimpan"] --> Fail

    ErrSM2 -- Ya --> CacheSM["Perbarui Tampilan\n(hapus data cache lama)"]
    ErrSK2 -- Ya --> CacheSK["Perbarui Tampilan\n(hapus data cache lama)"]
    CacheSM --> OK["Tampilkan Pesan Berhasil\nKembali ke Halaman Arsip"]
    CacheSK --> OK
```

### Flowchart 3 â€” Edit & Hapus Arsip (Admin Only)

```mermaid
flowchart LR
    Adm(["Admin"]) --> CheckAdminNet{"Dari Jaringan Internal\n+ Akses Admin?"}
    CheckAdminNet -- Tidak --> Deny403["Akses Ditolak\n(403 Forbidden)"]
    CheckAdminNet -- Ya --> Pilih{"Pilih Aksi"}

    Pilih -- "Edit Data" --> FetchRow["Ambil Data Arsip\ndari Google Sheets"]
    FetchRow --> EditModal["Admin Edit Data\ndi Form Modal"]
    EditModal --> ValidEdit{"Data Lengkap\n& Valid?"}
    ValidEdit -- Tidak --> ErrEdit["Tampilkan Pesan\nKesalahan"] --> EditModal
    ValidEdit -- Ya --> RenamePDF["Ganti Nama File PDF\ndi Server & Google Drive"]
    RenamePDF --> UpdateSheet["Perbarui Data\ndi Google Sheets"]
    UpdateSheet --> ForgetEdit["Perbarui Tampilan\n(hapus cache lama)"]
    ForgetEdit --> OKEdit["Tampilkan Pesan Berhasil\nKembali ke Halaman Arsip"]

    Pilih -- "Hapus Data" --> ConfirmDel{"Konfirmasi\nHapus?"}
    ConfirmDel -- Tidak --> Pilih
    ConfirmDel -- Ya --> DelSheet["Hapus Baris Data\ndari Google Sheets"]
    DelSheet --> DelPDF["Hapus File PDF\ndari Server & Google Drive"]
    DelPDF --> ForgetDel["Perbarui Tampilan\n(hapus cache lama)"]
    ForgetDel --> OKDel["Tampilkan Pesan Berhasil\nKembali ke Halaman Arsip"]
```

---

## ðŸ“ Entity Relationship Diagram (ERD)

### Tabel Ringkas â€” Kolom-Kolom Penting

> Tabel ini memuat kolom-kolom utama yang paling relevan dari setiap entitas untuk keperluan laporan. Rincian lengkap seluruh kolom tersedia pada kamus data teknis.

```mermaid
erDiagram
    USERS {
        bigint id PK
        string name "Username login"
        string email "Alternatif login"
        string password "bcrypt hash"
        string role "admin / internship"
        datetime last_seen_at
    }

    SESSIONS {
        string id PK "Session token"
        bigint user_id FK
        string ip_address
        int last_activity "Unix timestamp"
    }

    PASSWORD_RESET_REQUESTS {
        bigint id PK
        string username "Nama user pengaju"
        boolean is_resolved "Default: false"
        datetime resolved_at
        datetime created_at
    }

    CACHE {
        string key PK "sheets.* / admin.micro.*"
        int expiration "Unix timestamp TTL"
    }

    USERS ||--o{ SESSIONS : "memiliki sesi"
    USERS ||--o{ PASSWORD_RESET_REQUESTS : "mengajukan reset"
```

### Skema Data Google Sheets â€” Kolom Penting

> **Catatan:** Google Sheets bukan database relasional. Kolom di bawah adalah kolom-kolom penting dari tiap sheet. dbSM dan dbSK **berbeda posisi kolom PDF dan tanggal surat** â€” ini adalah perbedaan desain kritis yang harus diperhatikan.

```mermaid
erDiagram
    dbSM_SuratMasuk {
        string timestamp "col A â€” Waktu input"
        string kode_klasifikasi "col B â€” KP/PR/HK/dll"
        string kode_surat "col C â€” Nomor surat"
        string uraian_berkas "col D â€” Uraian berkas"
        string kurun_waktu "col E â€” Tahun"
        string tanggal_surat "col I â€” dateColIndex 8"
        string jumlah_lembar "col J â€” auto-format"
        string url_pdf "col R â€” Drive link / lokal"
    }

    dbSK_SuratKeluar {
        string timestamp "col A â€” Waktu input"
        string kode_klasifikasi "col B â€” KP/PR/HK/dll"
        string kode_surat "col C â€” Nomor surat"
        string uraian_berkas "col D â€” Uraian berkas"
        string kurun_waktu "col E â€” Tahun"
        string url_pdf "col H â€” Drive link / lokal"
        string tanggal_surat "col J â€” dateColIndex 9"
        string jumlah_lembar "col K â€” auto-format"
    }

    GoogleDrive {
        string file_id PK
        string webViewLink "URL publik PDF"
        string nama_file "KodeKlas_Ymd_His.pdf"
    }

    LocalStorage {
        string filename PK "KodeKlas_Ymd_His.pdf"
        string path "/uploads/filename"
    }

    dbSM_SuratMasuk }o--o| GoogleDrive : "url_pdf = webViewLink"
    dbSM_SuratMasuk }o--o| LocalStorage : "url_pdf = filename (fallback)"
    dbSK_SuratKeluar }o--o| GoogleDrive : "url_pdf = webViewLink"
    dbSK_SuratKeluar }o--o| LocalStorage : "url_pdf = filename (fallback)"
    GoogleDrive ||--|| LocalStorage : "nama file identik, disimpan sinkron"
```

---

## 🔒 Keamanan (Security Layer)

Aplikasi dilengkapi dengan sistem perlindungan komprehensif berlapis:

| Komponen | Mekanisme & Fungsi |
|---|---|
| `BruteForceIpBan` | Memantau percobaan login gagal per IP. Jika gagal **20 kali dalam 10 menit**, IP diblokir otomatis selama **1 jam** (HTTP 429). Tercatat ke log. |
| **Rate Limiter** | Pembatasan login per username+IP: lockout **5 menit** setelah 5 percobaan gagal. Reset password: maks **3 req / 5 menit** (throttle middleware). |
| `AdminOnly` | Membatasi akses rute administratif hanya untuk akun role `admin`. |
| `InternalNetworkOnly` | Membatasi akses input/edit/hapus hanya dari subnet jaringan internal kantor (multi-subnet CIDR). |
| `GuestNetwork` | Membatasi rute `/tamu` agar hanya dapat diakses dari subnet tamu atau jaringan internal. |
| `SecurityHeaders` | Menambahkan HTTP security headers: CSP, `X-Robots-Tag: noindex`, `Cross-Origin-Opener-Policy`, `Cross-Origin-Resource-Policy`, `X-Permitted-Cross-Domain-Policies`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS (HTTPS only). Menyembunyikan `X-Powered-By` & `Server`. |
| `UpdateUserLastSeen` | Mencatat waktu terakhir user aktif (`last_seen_at`) di database, diperbarui setiap 60 detik. |
| **Formula Injection Guard** | Sanitasi karakter prefix `=`, `+`, `-`, `@` pada semua input teks via `escapeFormula()` di base `Controller`. |
| **Session Protection** | Enkripsi payload sesi, masa aktif 240 menit, SameSite `lax` cookie, penyimpanan persisten di database MySQL. |
| **Trusted Proxy** | Konfigurasi `TRUSTED_PROXIES` di `.env` untuk deployment di balik reverse proxy/load balancer. |
| **Production Mode** | `APP_DEBUG=false` mencegah kebocoran stack trace; `LOG_LEVEL=error` menjaga privasi sistem. |
| `ChecksIpSubnet` (Trait) | Validasi IP terhadap CIDR subnet — mendukung IPv4 & IPv6, multi-subnet dipisah koma, tanpa exception saat format tidak valid. |

---

## ⚡ Strategi Caching Google Sheets (`SheetsCache`)

Untuk mencegah pembatasan kuota Google Sheets API serta menjaga performa navigasi cepat, aplikasi mengimplementasikan strategi *dual-tier caching*:

| Cache Key | TTL Non-Admin | TTL Admin | Digunakan oleh |
|---|---|---|---|
| `sheets.surat-masuk` | 60 detik | 30 detik | `ArsipController::index()` & `indexGuest()` |
| `sheets.surat-keluar` | 60 detik | 30 detik | `SuratKeluarController::index()` & `indexGuest()` |
| `sheets.dashboard` | 60 detik | 30 detik | `DashboardController::index()` & `printView()` |
| `sheets.report` | 60 detik | 30 detik | `DashboardController::report()` |

**Mekanisme:**

1. **Mikro-Cache Admin (30 Detik)** — Admin mendapat cache khusus `admin.micro.*` berdurasi 30 detik. Setiap refresh admin langsung **menulis ulang cache global** sehingga non-admin otomatis mendapat data terbaru.

2. **Cache Normal Non-Admin (60 Detik)** — Role Internship & Tamu WiFi menikmati data cache 1 menit untuk meminimalkan latensi.

3. **Invalidasi Instan** — Setiap `store()`, `update()`, atau `destroy()` langsung memanggil `SheetsCache::forgetSuratMasuk()` atau `forgetSuratKeluar()`, menghapus semua cache terkait (tabel + dashboard + report) seketika.

---

## 🔧 Artisan Commands & Task Scheduler

### Custom Artisan Commands

| Command | Deskripsi |
|---|---|
| `php artisan storage:clean` | Bersihkan compiled views, session files, & cache data (termasuk cache Google Sheets) |
| `php artisan session:prune-expired` | Hapus record session expired dari tabel database MySQL & file storage |
| `php artisan session:prune-expired --minutes=240` | Hapus session tidak aktif lebih dari N menit |

### Jadwal Otomatis (Scheduler)

| Jadwal | Command | Log |
|---|---|---|
| Setiap 1 Jam | `session:prune-expired` | `storage/logs/scheduler.log` |
| Setiap hari 17:00 | `storage:clean` | `storage/logs/scheduler.log` |

Keduanya berjalan dengan `withoutOverlapping()` & `runInBackground()`. Scheduler aktif via `php artisan schedule:work`.

```bash
# Eksekusi manual
php artisan session:prune-expired
php artisan storage:clean
php artisan schedule:run        # Jalankan sekali (Linux cron)
php artisan schedule:work       # Jalankan terus-menerus (Windows)
```

---

## 📁 Struktur Direktori Penting

```
project-magangv1/
├── app/
│   ├── Console/Commands/
│   │   ├── CleanStorageCommand.php          # php artisan storage:clean
│   │   └── PruneExpiredSessionsCommand.php  # php artisan session:prune-expired
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/UserController.php      # Kelola pengguna, reset password, SSE, polling
│   │   │   ├── Auth/LoginController.php      # Login (name/email), logout, rate limiting
│   │   │   ├── Auth/PasswordResetController.php  # Permintaan reset password internship
│   │   │   ├── ArsipController.php           # CRUD Surat Masuk (dbSM) + mode tamu
│   │   │   ├── SuratKeluarController.php     # CRUD Surat Keluar (dbSK) + mode tamu
│   │   │   ├── DashboardController.php       # Dashboard, rekap laporan, print view
│   │   │   └── Controller.php               # Base: KODE_MAP, getSheetService, escapeFormula
│   │   └── Middleware/
│   │       ├── AdminOnly.php                 # Pembatasan rute khusus admin
│   │       ├── BruteForceIpBan.php           # Auto-ban IP 1 jam setelah 20x gagal (10 menit)
│   │       ├── InternalNetworkOnly.php       # Pembatasan rute intranet staff
│   │       ├── GuestNetwork.php              # Pembatasan rute wifi tamu
│   │       ├── SecurityHeaders.php           # CSP, X-Robots-Tag, COOP, CORP, HSTS, dll.
│   │       ├── UpdateUserLastSeen.php        # Catat aktivitas terakhir user (setiap 60 detik)
│   │       └── Concerns/ChecksIpSubnet.php   # Trait: validasi IP subnet CIDR (IPv4 & IPv6)
│   ├── Models/
│   │   ├── User.php                          # isAdmin(), isInternship(), hasAdminAccess()
│   │   └── PasswordResetRequest.php          # scopePending(), resolve(), relasi ke User
│   └── Services/
│       ├── GoogleDriveService.php            # uploadAndBackup(), renameFile(), deleteFile()
│       └── SheetsCache.php                   # Dual-tier cache: Admin 30s, Non-Admin 60s

---

## 📄 Lisensi

Proyek ini dikembangkan sebagai bagian dari **program magang** di **BPSDMP** dan bersifat internal.

