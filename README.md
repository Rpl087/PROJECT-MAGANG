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
| **Admin** | Login, Logout, Lihat Surat Masuk/Keluar, Input Surat (jaringan internal), Edit Data & Rename PDF, Hapus Data & PDF, Dashboard & Grafik, Rekap Laporan & Print, Kelola Pengguna, Terima Notifikasi SSE Reset Password, Akses PDF |
| **Internship** | Login, Logout, Lihat Surat Masuk/Keluar, Input Surat (jaringan internal only), Ajukan Reset Password, Akses PDF |
| **Tamu WiFi** | Lihat Surat Masuk Read-Only, Lihat Surat Keluar Read-Only (tanpa login, dari subnet tamu/internal) |
| **Publik** | Akses URL PDF langsung (tanpa login) |

> **Catatan Arsitektur:** Input Surat Masuk & Keluar dibatasi `InternalNetworkOnly` middleware â€” hanya bisa dari subnet internal. Admin memiliki akses penuh CRUD; Internship hanya dapat input (tidak bisa edit/hapus). `hasAdminAccess()` saat ini identik dengan `isAdmin()` (tidak ada role tambahan). Tamu WiFi dilayani dari cache SheetsCache tanpa autentikasi.

```mermaid
flowchart LR
    Tamu([Tamu WiFi])
    Publ([Publik])
    Intrn([Internship])
    Adm([Admin])

    subgraph SIS [Sistem Pengarsipan Digital BPSDMP]
        direction TB

        UC_TamuSM(["Lihat Surat Masuk\nRead-Only /tamu"])
        UC_TamuSK(["Lihat Surat Keluar\nRead-Only /tamu/surat-keluar"])
        UC_AksesPDF(["Akses URL PDF\n/pdf/type/filename"])

        UC_Login(["Login\n/login"])
        UC_Logout(["Logout\n/logout"])
        UC_ResetReq(["Ajukan Reset Password\n(Internship only)"])
        UC_LihatSM(["Lihat Surat Masuk\n/arsip"])
        UC_LihatSK(["Lihat Surat Keluar\n/surat-keluar"])
        UC_InputSM(["Input Surat Masuk\nPOST /arsip\n(jaringan internal)"])
        UC_InputSK(["Input Surat Keluar\nPOST /surat-keluar\n(jaringan internal)"])

        UC_Edit(["Edit Data & Rename PDF\n(Admin only)"])
        UC_Hapus(["Hapus Data & PDF\n(Admin only)"])
        UC_Dashboard(["Dashboard & Grafik Bar\n(Chart.js + datalabels)"])
        UC_Rekap(["Rekap Laporan\nper Tahun & Klasifikasi"])
        UC_Print(["Print Laporan\nPrint-Friendly View"])
        UC_KelolaUser(["Kelola Pengguna &\nReset Password Manual"])
        UC_SSE(["Terima Notifikasi\nSSE + Polling Fallback"])

        UC_Upload(["Upload & Backup PDF\nLokal /uploads + Drive"])
        UC_Simpan(["Simpan/Update/Hapus\nGoogle Sheets API"])
        UC_Cache(["Invalidasi SheetsCache\nDual-tier: Admin 30s / Non-admin 60s"])
    end

    GS([Google Sheets API])
    GD([Google Drive API])
    MySQL([MySQL\nAuth + Session + Cache])

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

    Adm --- UC_Login
    Adm --- UC_Logout
    Adm --- UC_LihatSM
    Adm --- UC_LihatSK
    Adm --- UC_InputSM
    Adm --- UC_InputSK
    Adm --- UC_Edit
    Adm --- UC_Hapus
    Adm --- UC_Dashboard
    Adm --- UC_Rekap
    Adm --- UC_Print
    Adm --- UC_KelolaUser
    Adm --- UC_SSE
    Adm --- UC_AksesPDF

    UC_InputSM -. "include" .-> UC_Upload
    UC_InputSK -. "include" .-> UC_Upload
    UC_Edit    -. "include" .-> UC_Upload
    UC_Hapus   -. "include" .-> UC_Upload
    UC_InputSM -. "include" .-> UC_Simpan
    UC_InputSK -. "include" .-> UC_Simpan
    UC_Edit    -. "include" .-> UC_Simpan
    UC_Hapus   -. "include" .-> UC_Simpan
    UC_Simpan  -. "include" .-> UC_Cache

    UC_Simpan     --- GS
    UC_Dashboard  --- GS
    UC_Rekap      --- GS
    UC_TamuSM     --- GS
    UC_TamuSK     --- GS
    UC_LihatSM    --- GS
    UC_LihatSK    --- GS
    UC_Upload     --- GD
    UC_Login      --- MySQL
    UC_SSE        --- MySQL
    UC_KelolaUser --- MySQL
    UC_ResetReq   --- MySQL
```


---

## ðŸ”„ Flowchart Sistem

### Alur Autentikasi & Navigasi

```mermaid
flowchart TD
    Start(["Buka Aplikasi"]) --> CheckAuth{"Session\nAktif?"}

    CheckAuth -- Tidak --> LoginPage["Halaman Login\n/login"]
    LoginPage --> CheckBan{"IP\nDi-ban?\nBruteForceIpBan"}
    CheckBan -- Ya --> Block429["HTTP 429\nAkses Diblokir 1 Jam"]
    CheckBan -- Tidak --> InputCred["Input Username/Email\n& Password"]
    InputCred --> RateLimit{"Rate Limit\n5x gagal / 5 menit?"}
    RateLimit -- Ya --> ErrThrottle["HTTP 429\nThrottle 5 menit"] --> LoginPage
    RateLimit -- Tidak --> ValidateCred{"Kredensial\nValid?"}
    ValidateCred -- Tidak --> RecordFail["Catat Gagal Login\nBruteForceIpBan::recordFailure()"]
    RecordFail --> CheckBanCount{"â‰¥ 20 gagal\ndalam 10 menit?"}
    CheckBanCount -- Ya --> BanIP["Ban IP 1 Jam\nCache ipban:md5(ip)"]
    BanIP --> Block429
    CheckBanCount -- Tidak --> ErrMsg["Pesan Error\nlogin_error session"] --> LoginPage
    ValidateCred -- Ya --> CreateSession["Buat Session DB MySQL\n240 menit, SameSite=lax"]
    CreateSession --> UpdateLastSeen["Update last_seen_at\nUpdateUserLastSeen middleware"]
    UpdateLastSeen --> CheckRole{"Role User?"}

    CheckAuth -- Ya --> UpdateLastSeen

    CheckRole -- admin --> AdminDash["Dashboard Admin\n/dashboard"]
    CheckRole -- internship --> ArsipRead["Surat Masuk\n/arsip (index)"]

    subgraph TamuFlow ["Alur Tamu WiFi â€” Tanpa Login"]
        TamuReq["/tamu atau /tamu/surat-keluar"] --> CheckSubnet{"Dari Subnet\nTamu / Internal?\nGuestNetwork middleware"}
        CheckSubnet -- Ya --> TamuView["Tampil Read-Only\ndari SheetsCache"]
        CheckSubnet -- Tidak --> Deny403["HTTP 403\nAkses Ditolak"]
    end

    subgraph PublicFlow ["Akses Publik PDF"]
        PDFReq["GET /pdf/type/filename"] --> ValidatePDF{"File .pdf valid\n& ada di /uploads?"}
        ValidatePDF -- Ya --> ServePDF["Serve PDF\nCache-Control 1 hari"]
        ValidatePDF -- Tidak --> PDF404["HTTP 404"]
    end

    subgraph ResetFlow ["Alur Reset Password Internship"]
        BtnForgot["Klik Lupa Password"] --> ResetForm["Isi Username\ndi Modal"]
        ResetForm --> ResetThrottle{"Throttle\n3 req / 5 menit?"}
        ResetThrottle -- Ya --> TooMany["HTTP 429"] --> ResetForm
        ResetThrottle -- Tidak --> SaveRequest["Simpan PasswordResetRequest\nke MySQL (is_resolved=false)"]
        SaveRequest --> SSENotif["SSE event dikirim\nke Admin yang login"]
        SSENotif --> AdminResolve["Admin lihat di\n/admin/users"]
        AdminResolve --> ResetPw["Admin reset password\n(bcrypt hash baru)"]
        ResetPw --> MarkResolved["is_resolved=true\nresolved_at=now()"]
    end
```

### Alur Input & CRUD Arsip

```mermaid
flowchart TD
    UserAksi(["User Terautentikasi\nAdmin / Internship"]) --> CheckNet{"Dari Jaringan\nInternal?\nInternalNetworkOnly middleware"}
    CheckNet -- Tidak --> Deny403["HTTP 403 â€” Ditolak"]
    CheckNet -- Ya --> PilihAksi{"Pilih Aksi"}

    PilihAksi -- "Input Surat Masuk" --> FormSM["Isi Form dbSM\n17 field + 1 PDF"]
    PilihAksi -- "Input Surat Keluar" --> FormSK["Isi Form dbSK\n17 field + 1 PDF"]

    FormSM --> ValidasiSM{"Validasi\nInput Laravel"}
    FormSK --> ValidasiSK{"Validasi\nInput Laravel"}
    ValidasiSM -- Gagal --> ErrSM["Error Validasi\nredirect + withInput"] --> FormSM
    ValidasiSK -- Gagal --> ErrSK["Error Validasi\nredirect + withInput"] --> FormSK

    ValidasiSM -- OK --> KodeMapSM["applyKodeMapFallback()\nAuto-fill retensi & keamanan"]
    ValidasiSK -- OK --> KodeMapSK["applyKodeMapFallback()\nAuto-fill retensi & keamanan"]
    KodeMapSM --> EscapeSM["escapeFormula()\nSanitasi = + - @ prefix"]
    KodeMapSK --> EscapeKSK["escapeFormula()\nSanitasi = + - @ prefix"]

    EscapeSM --> UploadDrive1["GoogleDriveService::uploadAndBackup()\nBackup ke /uploads lokal DULU\nlalu upload ke Google Drive"]
    EscapeKSK --> UploadDrive2["GoogleDriveService::uploadAndBackup()\nBackup ke /uploads lokal DULU\nlalu upload ke Google Drive"]

    UploadDrive1 --> AppendSheet1["Sheets API: append baris\ndbSM!A:R â€” 18 kolom"]
    UploadDrive2 --> AppendSheet2["Sheets API: append baris\ndbSK!A:R â€” 18 kolom"]

    AppendSheet1 --> Err1{"Exception\nSheets API?"}
    AppendSheet2 --> Err2{"Exception\nSheets API?"}

    Err1 -- Ya --> Rollback1["Rollback:\nHapus file lokal /uploads\n+ deleteFile() dari Drive"] --> ErrNotif["Flash error\nredirect back"]
    Err2 -- Ya --> Rollback2["Rollback:\nHapus file lokal /uploads\n+ deleteFile() dari Drive"] --> ErrNotif

    Err1 -- Tidak --> Forget1["SheetsCache::forgetSuratMasuk()\nHapus: SM + Dashboard + Report cache"]
    Err2 -- Tidak --> Forget2["SheetsCache::forgetSuratKeluar()\nHapus: SK + Dashboard + Report cache"]
    Forget1 --> OKNotif["Flash success\nredirect index"]
    Forget2 --> OKNotif

    PilihAksi -- "Edit (Admin only)" --> CheckAdmin1{"AdminOnly\nmiddleware"}
    CheckAdmin1 -- Tidak --> Deny403
    CheckAdmin1 -- Ya --> EditFetch["GET /arsip/{rowIndex}/edit\nAmbiil baris dari Sheets\nJSON response"]
    EditFetch --> EditForm["User isi form edit\ndi modal"]
    EditForm --> UpdateSheet["Sheets API: update baris\ndbSM!B{n}:R{n}"]
    UpdateSheet --> RenameFile["GoogleDriveService::renameFile()\nRename PDF lokal + Drive"]
    RenameFile --> Forget1

    PilihAksi -- "Hapus (Admin only)" --> CheckAdmin2{"AdminOnly\nmiddleware"}
    CheckAdmin2 -- Tidak --> Deny403
    CheckAdmin2 -- Ya --> DelRow["Sheets API: delete baris\nvia shiftDimensionRequest"]
    DelRow --> DelFile["GoogleDriveService::deleteFile()\nHapus PDF lokal + Drive"]
    DelFile --> Forget1
```

### Alur Cache Google Sheets (Dual-Tier)

```mermaid
flowchart TD
    Req(["Request data\nSurat Masuk / Keluar / Dashboard"]) --> IsAdmin{"User\nAdmin?"}

    IsAdmin -- Ya --> CheckMicro{"Cache\nadmin.micro.{key}\nTTL 30s ada?"}
    CheckMicro -- Ya --> ReturnMicro["Return data\ndari micro-cache"]
    CheckMicro -- Tidak --> FetchSheets["Fetch dari\nGoogle Sheets API"]
    FetchSheets --> WriteMicro["Tulis admin.micro.{key}\n(TTL 30 detik)"]
    WriteMicro --> WriteGlobal["Tulis {key} global\n(TTL 60 detik)\nuntuk non-admin"]
    WriteGlobal --> ReturnData["Return data"]

    IsAdmin -- Tidak --> CheckGlobal{"Cache {key}\nTTL 60s ada?"}
    CheckGlobal -- Ya --> ReturnGlobal["Return data\ndari cache global"]
    CheckGlobal -- Tidak --> FetchSheets2["Fetch dari\nGoogle Sheets API"]
    FetchSheets2 --> WriteGlobal2["Tulis {key} global\n(TTL 60 detik)"]
    WriteGlobal2 --> ReturnData

    StoreUpdate(["store() / update() / destroy()"]) --> ForgetAll["SheetsCache::forget(key)\nHapus cache global\n+ admin.micro.{key}"]
```

---

## ðŸ“ Entity Relationship Diagram (ERD)

### Tabel MySQL (Autentikasi, Session, Cache, Reset)

> **Catatan Arsitektur:** MySQL **hanya** menyimpan data autentikasi, session, cache Laravel, dan permintaan reset password. Data arsip surat (Surat Masuk & Keluar) disimpan di **Google Spreadsheet** via API â€” bukan di MySQL.

```mermaid
erDiagram
    USERS {
        bigint id PK "AUTO_INCREMENT"
        string name "Username untuk login, unik"
        string email UK "Email unik, alternatif login"
        string password "bcrypt hash (cast: hashed)"
        string role "admin / internship"
        datetime last_seen_at "Update tiap 60 dtk via middleware"
        string remember_token "Laravel remember-me token"
        datetime email_verified_at "Nullable"
        datetime created_at
        datetime updated_at
    }

    SESSIONS {
        string id PK "Session token (UUID)"
        bigint user_id FK "Nullable â€” guest session"
        string ip_address "IPv4 / IPv6"
        text user_agent
        longtext payload "Encrypted session payload"
        int last_activity "Unix timestamp, index ada"
    }

    PASSWORD_RESET_REQUESTS {
        bigint id PK "AUTO_INCREMENT"
        string username "name user yang mengajukan, index"
        string ip_address "IP pengaju, index"
        boolean is_resolved "Default: false, index"
        datetime resolved_at "Nullable â€” diisi saat admin resolve"
        datetime created_at
        datetime updated_at
    }

    CACHE {
        string key PK "sheets.*, admin.micro.sheets.*"
        mediumtext value "Serialized data dari Google Sheets"
        int expiration "Unix timestamp TTL"
    }

    CACHE_LOCKS {
        string key PK
        string owner
        int expiration
    }

    USERS ||--o{ SESSIONS : "memiliki sesi aktif"
    USERS ||--o{ PASSWORD_RESET_REQUESTS : "mengajukan reset via username=name"
```

### Skema Data Google Sheets (Non-Relasional)

> **Perbedaan Kritis dbSM vs dbSK:** Posisi kolom PDF berbeda â€” dbSM menyimpan PDF di kolom **R (index 17)**, sedangkan dbSK menyimpan di kolom **H (index 7)**. `tanggal_surat` juga berbeda index: dbSM di col **I (index 8)**, dbSK di col **J (index 9)**. Ini digunakan langsung di `DashboardController::buildDashboardStats()` dengan `dateColIndex: 8` untuk SM dan `dateColIndex: 9` untuk SK.

```mermaid
erDiagram
    dbSM_SuratMasuk {
        string A_timestamp "col 0 â€” Waktu input (Y-m-d H:i:s)"
        string B_kode_klasifikasi "col 1 â€” KP/PR/HK/UM/dll"
        string C_kode_surat "col 2 â€” Nomor surat"
        string D_uraian_berkas "col 3 â€” Uraian isi berkas"
        string E_kurun_waktu "col 4 â€” Tahun dokumen (numeric)"
        string F_jumlah_folder "col 5 â€” Jumlah folder fisik"
        string G_nomor_item_arsip "col 6 â€” Nomor urut item (numeric)"
        string H_uraian_informasi_arsip "col 7 â€” Uraian informasi arsip"
        string I_tanggal_surat "col 8 â€” Tanggal surat (dateColIndex=8)"
        string J_jumlah_lembar "col 9 â€” Auto-format N Lembar"
        string K_tingkat_perkembangan "col 10 â€” Asli/Copy/dll"
        string L_lokasi_simpan "col 11 â€” Lokasi penyimpanan fisik"
        string M_klasifikasi_keamanan "col 12 â€” Biasa/Terbatas/Rahasia"
        string N_tingkat_akses "col 13 â€” Publik/Internal/dll"
        string O_retensi_aktif "col 14 â€” auto-fill via KODE_MAP"
        string P_retensi_inaktif "col 15 â€” auto-fill via KODE_MAP"
        string Q_retensi_keterangan "col 16 â€” auto-fill via KODE_MAP"
        string R_url_pdf "col 17 â€” Google Drive webViewLink / filename lokal"
    }

    dbSK_SuratKeluar {
        string A_timestamp "col 0 â€” Waktu input (Y-m-d H:i:s)"
        string B_kode_klasifikasi "col 1 â€” KP/PR/HK/UM/dll"
        string C_kode_surat "col 2 â€” Nomor surat"
        string D_uraian_berkas "col 3 â€” Uraian isi berkas"
        string E_kurun_waktu "col 4 â€” Tahun dokumen"
        string F_jumlah_folder "col 5 â€” Jumlah folder fisik"
        string G_nomor_item_arsip "col 6 â€” Nomor urut item"
        string H_url_pdf "col 7 â€” PDF di H bukan R! Drive link / filename"
        string I_uraian_informasi_arsip "col 8 â€” Uraian informasi arsip"
        string J_tanggal_surat "col 9 â€” Tanggal surat (dateColIndex=9)"
        string K_jumlah_lembar "col 10 â€” Auto-format N Lembar"
        string L_tingkat_perkembangan "col 11 â€” Asli/Copy/dll"
        string M_lokasi_simpan "col 12 â€” Lokasi penyimpanan fisik"
        string N_klasifikasi_keamanan "col 13 â€” Biasa/Terbatas/Rahasia"
        string O_tingkat_akses "col 14 â€” Publik/Internal/dll"
        string P_retensi_aktif "col 15 â€” auto-fill via KODE_MAP"
        string Q_retensi_inaktif "col 16 â€” auto-fill via KODE_MAP"
        string R_retensi_keterangan "col 17 â€” auto-fill via KODE_MAP"
    }

    LocalStorage {
        string filename PK "KodeKlas_Ymd_His_original.pdf"
        string path "/uploads/filename"
    }

    GoogleDrive {
        string file_id PK "Google Drive File ID"
        string webViewLink "https://drive.google.com/file/d/..."
        string name "filename yang sama dengan LocalStorage"
        string parent_folder_id "GOOGLE_DRIVE_FOLDER_ID dari .env"
    }

    dbSM_SuratMasuk }o--o| LocalStorage : "R_url_pdf = filename lokal (fallback)"
    dbSM_SuratMasuk }o--o| GoogleDrive : "R_url_pdf = webViewLink (utama)"
    dbSK_SuratKeluar }o--o| LocalStorage : "H_url_pdf = filename lokal (fallback)"
    dbSK_SuratKeluar }o--o| GoogleDrive : "H_url_pdf = webViewLink (utama)"
    GoogleDrive ||--|| LocalStorage : "Nama file identik, disimpan sinkron"
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
├── config/
│   ├── google.php                            # GOOGLE_SHEET_ID, GOOGLE_SERVICE_ACCOUNT_JSON
│   └── network.php                           # intranet_enabled, internal_subnet, guest_subnet
├── database/
│   ├── migrations/                           # 5 file migrasi (users, role, reset requests, dll)
│   └── seeders/DatabaseSeeder.php            # User default admin & internship (firstOrCreate)
├── routes/
│   ├── web.php                               # Definisi seluruh rute web (4 kelompok akses)
│   └── console.php                           # Task Scheduler: hourly prune & daily 17:00 clean
├── resources/views/
│   ├── admin/users.blade.php                 # Manajemen pengguna & permintaan reset
│   ├── arsip/index.blade.php                 # Tabel surat masuk (auth)
│   ├── arsip/surat-keluar.blade.php          # Tabel surat keluar (auth)
│   ├── auth/login.blade.php                  # Halaman login
│   ├── dashboard/index.blade.php             # Dashboard grafik batang Chart.js
│   ├── dashboard/report.blade.php            # Rekap laporan per tahun & klasifikasi
│   ├── dashboard/print.blade.php             # Tampilan cetak print-friendly
│   ├── layouts/app.blade.php                 # Layout utama + navigasi
│   ├── layouts/_icon.blade.php               # SVG icon komponen
│   ├── tamu/index.blade.php                  # Surat masuk read-only (tamu WiFi)
│   └── tamu/surat-keluar.blade.php           # Surat keluar read-only (tamu WiFi)
├── bootstrap/app.php                         # Registrasi middleware global & alias
├── storage/app/google-credentials.json       # ⚠️ TIDAK di-commit. Service Account key.
├── uploads/                                  # File PDF lokal yang diupload (backup Drive)
├── .htaccess                                 # Redirect subfolder ke root aplikasi
├── autostart.bat                             # Runner harian ringan (tanpa Administrator)
├── setup-and-run.bat                         # Skrip setup 1-Pintu (butuh Administrator)
├── "Arsip Surat (server).bat"                # Runner server lengkap (Queue + Scheduler)

└── supervisord.conf.example                  # Contoh konfigurasi Supervisord (Linux server)
```

---

## 🐛 Bug Fix Log

| # | File | Bug | Perbaikan |
|---|---|---|---|
| 1 | `Auth/PasswordResetController.php` | Variabel `$username` tidak terdefinisi pada `Log::info()` di method `requestReset()` — menyebabkan `ErrorException` saat log dicatat | Diganti menjadi `$user->name` yang sudah tersedia dari query `User::where()` sebelumnya |

---

## 🔧 Optimalisasi & Refaktor (Changelog Teknis)

| # | Perubahan | File |
|---|---|---|
| 1 | Konsolidasikan `KODE_KLASIFIKASI_ORDER`, `KODE_MAP`, `sortByKlasifikasi()`, `applyKodeMapFallback()`, `getSheetService()`, `getReadonlySheetService()`, `escapeFormula()`, `safeDeleteFile()`, `getSheetId()` ke base `Controller.php` | `Controller.php` |
| 2 | Tambahkan `GoogleDriveService::renameFile()` → ubah nama PDF di `/uploads` & Google Drive saat Edit | `GoogleDriveService.php` |
| 3 | Tambahkan `GoogleDriveService::deleteFile()` → hapus PDF fisik di `/uploads` & Google Drive saat Destroy | `GoogleDriveService.php` |
| 4 | Tambahkan plugin datalabels pada Chart.js → angka total tampil di atas batang grafik | `dashboard/index.blade.php` |
| 5 | Standardisasi loading animation kontekstual & font fallback sistem untuk navigasi Intranet WiFi instan | `layouts/app.blade.php` |
| 6 | Penyeragaman kisi garis tabel cetak (`1pt solid #333`), header *URAIAN INFORMASI ARSIP*, hapus kolom Tahun & tanda tangan | `dashboard/report.blade.php` |
| 7 | Trait `ChecksIpSubnet` → penanganan subnet IPv4 & IPv6 multi-CIDR yang aman tanpa exception | `Concerns/ChecksIpSubnet.php` |
| 8 | Perbaikan keamanan database seeder → mengganti `truncate()` dengan `firstOrCreate()` agar user kustom persisten | `DatabaseSeeder.php` |
| 9 | Pemisahan skrip startup harian `autostart.bat` dari skrip inisialisasi awal `setup-and-run.bat` | `autostart.bat`, `setup-and-run.bat` |
| 10 | Aktifkan Task Scheduler `storage:clean` otomatis setiap pukul **17:00** dengan logging ke `storage/logs/scheduler.log` | `routes/console.php` |
| 11 | Tambahkan `schedule:work` di background pada runner agar scheduler aktif tanpa intervensi manual | `Arsip Surat (server).bat` |
| 12 | Implementasi middleware `BruteForceIpBan` → pantau kegagalan login & ban IP otomatis 1 jam setelah 20x gagal dalam 10 menit | `BruteForceIpBan.php`, `LoginController.php`, `bootstrap/app.php` |
| 13 | Penguatan Security Headers (CSP aman inline, `X-Robots-Tag`, COOP, CORP, `X-Permitted-Cross-Domain-Policies`) & production mode | `SecurityHeaders.php` |
| 14 | Penyesuaian masa aktif session menjadi 4 jam (240 menit) & command `session:prune-expired` terjadwal per jam | `config/session.php`, `PruneExpiredSessionsCommand.php`, `routes/console.php` |
| 15 | Optimalisasi Google Sheets Cache: mikro-cache Admin 30s (`TTL_ADMIN_MICRO`), cache normal 60s (`TTL_TABLE`, `TTL_DASHBOARD`) | `SheetsCache.php` |
| 16 | Konfigurasi VirtualHost Apache port 80 & 443 (SSL) agar perangkat lain dapat mengakses via IP server | `kearsipan.local.conf`, `00-default.conf` |
| 17 | Penambahan aturan URL Rewrite di `.htaccess` untuk otomatis me-redirect akses subfolder ke root aplikasi | `.htaccess`, `public/.htaccess` |
| 18 | Implementasi `getReadonlySheetService()` — koneksi Google Sheets read-only (scope `SPREADSHEETS_READONLY`) untuk operasi index & fetch | `Controller.php` |
| 19 | **Bug fix**: variabel `$username` tidak terdefinisi di `PasswordResetController::requestReset()` → diganti `$user->name` | `Auth/PasswordResetController.php` |
| 20 | Dukungan multi-subnet CIDR di `InternalNetworkOnly` & `GuestNetwork` via `ChecksIpSubnet::ipInSubnet()` | `ChecksIpSubnet.php` |
| 21 | Tambahkan `Trusted Proxy` support di `bootstrap/app.php` via env `TRUSTED_PROXIES` | `bootstrap/app.php` |
| 22 | **Pembaruan Keamanan PHP Runtime & Mitigasi PHP2Shell**: Upgrade runtime PHP Laragon ke **PHP 8.4.27 (x64 Thread-Safe)** (menambal CVE-2026-109618 & CVE-2026-109619), penguatan `php.ini` (`session.upload_progress.enabled = Off`), sinkronisasi Apache `mod_php.conf`, pembaruan batasan versi `composer.json` (`^8.3.36 || ^8.4.27 || ^8.5.12`), dan perbaikan urutan deteksi ascending pada `setup-and-run.bat` | `composer.json`, `setup-and-run.bat`, `php.ini`, `mod_php.conf`, `laragon.ini` |

---

## 📄 Lisensi

Proyek ini dikembangkan sebagai bagian dari **program magang** di **BPSDMP** dan bersifat internal.

