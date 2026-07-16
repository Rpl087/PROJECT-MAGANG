<?php

namespace App\Http\Controllers;

use App\Services\SheetsCache;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Google\Client;
use Google\Service\Sheets;

class SuratKeluarController extends Controller
{
    private const KODE_KLASIFIKASI_ORDER = [
        'PR', 'KP', 'OT', 'KU', 'HK', 'UM', 'PL',
        'UP', 'HM', 'PW', 'LT', 'DL', 'DT', 'KI',
    ];

    /**
     * Urutkan baris berdasarkan prefix kode klasifikasi tanpa mengubah indeks sheet.
     */
    private function sortByKlasifikasi(array &$rows): void
    {
        $rank = array_flip(self::KODE_KLASIFIKASI_ORDER);

        usort($rows, function (array $a, array $b) use ($rank): int {
            $getKode = static function (array $item): string {
                $raw = trim((string) ($item['data'][1] ?? ''));
                return preg_match('/^([A-Za-z]+)/', $raw, $matches)
                    ? strtoupper($matches[1])
                    : '';
            };

            $kodeA = $getKode($a);
            $kodeB = $getKode($b);
            $rankA = $rank[$kodeA] ?? PHP_INT_MAX;
            $rankB = $rank[$kodeB] ?? PHP_INT_MAX;

            return ($rankA <=> $rankB)
                ?: ($kodeA <=> $kodeB)
                ?: (($a['index'] ?? 0) <=> ($b['index'] ?? 0));
        });
    }

    /**
     * Buat instance Google Sheets service.
     */
    private function getSheetService(): array
    {
        $credPath = config('google.service_account_json');
        if (empty($credPath)) {
            throw new \RuntimeException('GOOGLE_SERVICE_ACCOUNT_JSON belum dikonfigurasi di .env');
        }
        $credFullPath = base_path($credPath);
        if (!file_exists($credFullPath) || !is_file($credFullPath)) {
            throw new \RuntimeException("File kredensial Google tidak ditemukan: {$credFullPath}");
        }
        $client = new Client();
        $client->setAuthConfig($credFullPath);
        $client->addScope(Sheets::SPREADSHEETS);
        $service       = new Sheets($client);
        $spreadsheetId = config('google.sheet_id');
        return [$service, $spreadsheetId];
    }

    /**
     * Dapatkan sheet ID numerik berdasarkan nama sheet.
     */
    private function getSheetId(Sheets $service, string $spreadsheetId, string $sheetName): ?int
    {
        $spreadsheet = $service->spreadsheets->get($spreadsheetId);
        foreach ($spreadsheet->getSheets() as $sheet) {
            $title = $sheet->getProperties()->getTitle();
            if (trim($title) === trim($sheetName)) {
                return $sheet->getProperties()->getSheetId();
            }
        }
        return null;
    }

    /**
     * Tampilkan daftar surat keluar dari Google Sheets + form input.
     * - Admin      : membaca dari sheet "SURAT KELUAR" (sheet tampilan)
     * - Internship : hanya menampilkan form input (rows kosong, tanpa tabel)
     *
     * Struktur SURAT KELUAR (A–Q):
     *   A(0)=NO | B(1)=Kode Klasifikasi | C(2)=Uraian Informasi Berkas | D(3)=Kurun Waktu
     *   E(4)=Jumlah Folder | F(5)=Nomor Item Arsip | G(6)=Uraian Informasi Arsip
     *   H(7)=Tanggal Surat | I(8)=Jumlah Lembar | J(9)=Tingkat Perkembangan
     *   K(10)=Lokasi Simpan | L(11)=Klas Keamanan | M(12)=Tingkat Akses
     *   N(13)=Retensi Aktif | O(14)=Retensi Inaktif | P(15)=Retensi Ket | Q(16)=Link Folder
     */
    public function index(Request $request)
    {
        $rows       = [];
        $fetchError = null;
        $isAdmin    = auth()->user()?->isAdmin() ?? false;

        if ($isAdmin) {
            try {
                $allRows = SheetsCache::remember(
                    SheetsCache::KEY_SURAT_KELUAR,
                    SheetsCache::TTL_TABLE,
                    function () {
                        [$service, $spreadsheetId] = $this->getSheetService();
                        $response = $service->spreadsheets_values->get($spreadsheetId, 'dbSK!A:R');
                        $dbRows = $response->getValues() ?? [];

                        $mappedRows = [];
                        if (!empty($dbRows)) {
                            // Header row
                            $mappedRows[] = $dbRows[0];
                            foreach ($dbRows as $i => $row) {
                                if ($i === 0) continue;
                                $mappedRow = [];
                                $mappedRow[0]  = $row[0]  ?? ''; // A: Timestamp (NO)
                                
                                // Gabungkan Kode Klasifikasi & Kode Surat (misal: "KP" & "04.01" -> "KP 04.01")
                                $kodeKlas = trim($row[1] ?? '');
                                $kodeSurat = trim($row[2] ?? '');
                                $mappedRow[1]  = ($kodeKlas !== '' && $kodeSurat !== '') ? "{$kodeKlas} {$kodeSurat}" : ($kodeKlas ?: $kodeSurat); // B: Kode Klasifikasi
                                
                                $mappedRow[2]  = $row[3]  ?? ''; // C: Uraian Berkas
                                $mappedRow[3]  = $row[4]  ?? ''; // D: Kurun Waktu
                                $mappedRow[4]  = $row[5]  ?? ''; // E: Jumlah Folder
                                $mappedRow[5]  = $row[6]  ?? ''; // F: Nomor Item
                                $mappedRow[6]  = $row[8]  ?? ''; // G: Uraian Arsip (dbSK index 8 adalah Uraian Arsip, index 7 adalah PDF link)
                                $mappedRow[7]  = $row[9]  ?? ''; // H: Tanggal Surat
                                $mappedRow[8]  = $row[10] ?? ''; // I: Jumlah Lembar
                                $mappedRow[9]  = $row[11] ?? ''; // J: Tingkat Perkembangan
                                $mappedRow[10] = $row[12] ?? ''; // K: Lokasi Simpan
                                $mappedRow[11] = $row[13] ?? ''; // L: Klasifikasi Keamanan
                                $mappedRow[12] = $row[14] ?? ''; // M: Tingkat Akses
                                $mappedRow[13] = $row[15] ?? ''; // N: Retensi Aktif
                                $mappedRow[14] = $row[16] ?? ''; // O: Retensi Inaktif
                                $mappedRow[15] = $row[17] ?? ''; // P: Retensi Keterangan
                                $mappedRow[16] = $row[7]  ?? ''; // Q: Link Folder (PDF filename/URL di index 7)
                                $mappedRows[] = $mappedRow;
                            }
                        }
                        return $mappedRows;
                    }
                );

                foreach ($allRows as $i => $row) {
                    if ($i === 0) continue;
                    $tanggal = trim($row[7] ?? '');
                    if (empty($tanggal)) continue;
                    $rows[] = ['index' => $i, 'data' => $row];
                }
                $this->sortByKlasifikasi($rows);
            } catch (\Exception $e) {
                $fetchError = 'Gagal memuat data dari Google Sheets: ' . $e->getMessage();
                SheetsCache::forget(SheetsCache::KEY_SURAT_KELUAR);
            }
        }

        // Filter bulan dari request
        $filterBulan = $request->get('bulan', '');
        $filterTahun = $request->get('tahun', '');

        return view('arsip.surat-keluar', compact('rows', 'fetchError', 'isAdmin', 'filterBulan', 'filterTahun'));
    }

    /**
     * Tampilkan daftar surat keluar dalam mode READ-ONLY untuk tamu (WiFi Tamu).
     * Tidak memerlukan autentikasi. Hanya menampilkan data tanpa form input atau tombol hapus.
     */
    public function indexGuest()
    {
        $rows       = [];
        $fetchError = null;

        try {
            $allRows = SheetsCache::remember(
                SheetsCache::KEY_SURAT_KELUAR,
                SheetsCache::TTL_TABLE,
                function () {
                    [$service, $spreadsheetId] = $this->getSheetService();
                    $response = $service->spreadsheets_values->get($spreadsheetId, 'dbSK!A:R');
                    $dbRows = $response->getValues() ?? [];

                    $mappedRows = [];
                    if (!empty($dbRows)) {
                        $mappedRows[] = $dbRows[0];
                        foreach ($dbRows as $i => $row) {
                            if ($i === 0) continue;
                            $mappedRow = [];
                            $mappedRow[0]  = $row[0]  ?? '';
                            $kodeKlas = trim($row[1] ?? '');
                            $kodeSurat = trim($row[2] ?? '');
                            $mappedRow[1]  = ($kodeKlas !== '' && $kodeSurat !== '') ? "{$kodeKlas} {$kodeSurat}" : ($kodeKlas ?: $kodeSurat);
                            $mappedRow[2]  = $row[3]  ?? '';
                            $mappedRow[3]  = $row[4]  ?? '';
                            $mappedRow[4]  = $row[5]  ?? '';
                            $mappedRow[5]  = $row[6]  ?? '';
                            $mappedRow[6]  = $row[8]  ?? '';
                            $mappedRow[7]  = $row[9]  ?? '';
                            $mappedRow[8]  = $row[10] ?? '';
                            $mappedRow[9]  = $row[11] ?? '';
                            $mappedRow[10] = $row[12] ?? '';
                            $mappedRow[11] = $row[13] ?? '';
                            $mappedRow[12] = $row[14] ?? '';
                            $mappedRow[13] = $row[15] ?? '';
                            $mappedRow[14] = $row[16] ?? '';
                            $mappedRow[15] = $row[17] ?? '';
                            $mappedRow[16] = $row[7]  ?? '';
                            $mappedRows[] = $mappedRow;
                        }
                    }
                    return $mappedRows;
                }
            );

            foreach ($allRows as $i => $row) {
                if ($i === 0) continue;
                $tanggal = trim($row[7] ?? '');
                if (empty($tanggal)) continue;
                $rows[] = ['index' => $i, 'data' => $row];
            }
            $this->sortByKlasifikasi($rows);
        } catch (\Exception $e) {
            $fetchError = 'Gagal memuat data dari Google Sheets: ' . $e->getMessage();
            SheetsCache::forget(SheetsCache::KEY_SURAT_KELUAR);
        }

        return view('tamu.surat-keluar', compact('rows', 'fetchError'));
    }

    /**
     * Hapus baris surat keluar dari sheet "dbSK".
     * - Tampilan membaca dari sheet "SURAT KELUAR"
     * - Penghapusan dilakukan di sheet "dbSK" (sumber data asli)
     * - Pencarian baris dilakukan berdasarkan PDF link sebagai unique identifier
     *
     * @param int $rowIndex  Row index (0-based) dari sheet SURAT KELUAR
     */
    public function destroy(int $rowIndex)
    {
        try {
            Log::info('[SK destroy] rowIndex diterima dari SURAT KELUAR: ' . $rowIndex);

            [$service, $spreadsheetId] = $this->getSheetService();

            // ── Langkah 1: Ambil data dari SURAT KELUAR untuk mendapatkan PDF link ──
            $skResponse = $service->spreadsheets_values->get($spreadsheetId, 'SURAT KELUAR!A:R');
            $skRows     = $skResponse->getValues() ?? [];
            $targetRow  = $skRows[$rowIndex] ?? null;

            if ($targetRow === null) {
                Log::warning('[SK destroy] Baris ' . $rowIndex . ' tidak ditemukan di SURAT KELUAR.');
                return redirect()->route('surat-keluar.index')
                    ->with('error', 'Data tidak ditemukan di sheet SURAT KELUAR.');
            }

            // PDF link ada di kolom Q (index 16) di sheet SURAT KELUAR
            // Gunakan sebagai unique identifier untuk mencari row di dbSK
            $pdfLink = trim($targetRow[16] ?? '');
            Log::info('[SK destroy] PDF link yang dicari di dbSK: ' . $pdfLink);

            // ── Langkah 2: Cari row yang cocok di dbSK berdasarkan PDF link ────────
            // Struktur dbSK: A=Timestamp | B=Kode Klas | ... | H=Surat Keluar (PDF, index 7)
            $dbskResponse = $service->spreadsheets_values->get($spreadsheetId, 'dbSK!A:R');
            $dbskRows     = $dbskResponse->getValues() ?? [];
            $dbskRowIndex = null;

            if (!empty($pdfLink)) {
                foreach ($dbskRows as $i => $row) {
                    if ($i === 0) continue; // skip header
                    // PDF link di dbSK ada di kolom H (index 7)
                    if (trim($row[7] ?? '') === $pdfLink) {
                        $dbskRowIndex = $i;
                        Log::info('[SK destroy] Ditemukan di dbSK pada baris index: ' . $i);
                        break;
                    }
                }
            }

            // Jika PDF link tidak ditemukan, batalkan — lebih aman dari hapus baris salah
            if ($dbskRowIndex === null) {
                Log::warning('[SK destroy] Gagal: PDF link tidak ditemukan di dbSK, hapus dibatalkan.');
                return redirect()->route('surat-keluar.index')
                    ->with('error', 'Gagal menghapus: data tidak dapat diidentifikasi secara unik di dbSK (PDF link tidak ditemukan atau kosong).');
            }

            // ── Langkah 3: Hapus baris dari dbSK ──────────────────────────────────
            $sheetId = $this->getSheetId($service, $spreadsheetId, 'dbSK');

            if ($sheetId === null) {
                return redirect()->route('surat-keluar.index')
                    ->with('error', 'Sheet "dbSK" tidak ditemukan di Spreadsheet.');
            }

            $requests = [[
                'deleteDimension' => [
                    'range' => [
                        'sheetId'    => $sheetId,
                        'dimension'  => 'ROWS',
                        'startIndex' => $dbskRowIndex,
                        'endIndex'   => $dbskRowIndex + 1,
                    ],
                ],
            ]];

            $body = new Sheets\BatchUpdateSpreadsheetRequest(['requests' => $requests]);
            $service->spreadsheets->batchUpdate($spreadsheetId, $body);

            // Hapus file lokal dari uploads/ jika PDF link adalah nama file (bukan URL Google Drive)
            // Jika berupa URL Drive (https://...), file backup lokal tetap dipertahankan
            if ($pdfLink !== '' && !str_starts_with($pdfLink, 'http')) {
                $localFilePath = base_path('uploads/' . $pdfLink);
                if (file_exists($localFilePath)) {
                    @unlink($localFilePath);
                }
            }

            // Invalidasi cache agar halaman menampilkan data terbaru
            SheetsCache::forgetSuratKeluar();

            return redirect()->route('surat-keluar.index')
                ->with('success', 'Data surat keluar berhasil dihapus dari Spreadsheet (dbSK).');
        } catch (\Exception $e) {
            return redirect()->route('surat-keluar.index')
                ->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    /**
     * Simpan data surat keluar ke Google Spreadsheet (sheet: dbSK).
     */
    public function store(Request $request)
    {
        $storedFilePath = null; // Inisialisasi agar bisa diakses di catch block untuk cleanup
        $validated = $request->validate([
            "kode_klasifikasi"        => "required|string|max:255",
            "kode_surat"              => "required|string|max:255",
            "uraian_informasi_berkas" => "required|string|max:1000",
            "kurun_waktu"             => "required|numeric|min:1900|max:2100",
            "jumlah_folder"           => "required|string|max:255",
            "nomor_item_arsip"        => "required|numeric|min:1",
            "surat_keluar"            => "required|file|mimes:pdf|max:10240",
            "uraian_informasi_arsip"  => "required|string|max:1000",
            "tanggal_surat"           => "required|string|max:50",
            "jumlah_lembar"           => "required|string|max:255",
            "tingkat_perkembangan"    => "required|string|max:255",
            "lokasi_simpan"           => "required|string|max:255",
            "klasifikasi_keamanan"    => "nullable|string|max:255",
            "tingkat_akses"           => "nullable|string|max:255",
            "retensi_aktif"           => "nullable|string|max:255",
            "retensi_inaktif"         => "nullable|string|max:255",
            "retensi_keterangan"      => "nullable|string|max:1000",
        ], [
            "kode_klasifikasi.required"        => "Kode Klasifikasi wajib diisi.",
            "kode_surat.required"              => "Kode Surat wajib diisi.",
            "uraian_informasi_berkas.required" => "Uraian Informasi Berkas wajib diisi.",
            "kurun_waktu.required"             => "Kurun Waktu wajib diisi.",
            "kurun_waktu.numeric"              => "Kurun Waktu harus berupa angka tahun (contoh: 2024).",
            "kurun_waktu.min"                  => "Kurun Waktu minimal tahun 1900.",
            "kurun_waktu.max"                  => "Kurun Waktu maksimal tahun 2100.",
            "jumlah_folder.required"           => "Jumlah Folder wajib diisi.",
            "nomor_item_arsip.required"        => "Nomor Item Arsip wajib diisi.",
            "nomor_item_arsip.numeric"         => "Nomor Item Arsip harus berupa angka.",
            "nomor_item_arsip.min"             => "Nomor Item Arsip minimal 1.",
            "surat_keluar.required"            => "File Surat Keluar (PDF) wajib diunggah.",
            "uraian_informasi_arsip.required"  => "Uraian Informasi Arsip wajib diisi.",
            "tanggal_surat.required"           => "Tanggal Surat wajib diisi.",
            "jumlah_lembar.required"           => "Jumlah Lembar wajib diisi.",
            "tingkat_perkembangan.required"    => "Tingkat Perkembangan wajib diisi.",
            "lokasi_simpan.required"           => "Lokasi Simpan wajib diisi.",
        ]);

        // ── Server-side KODE_MAP fallback ─────────────────────────────────────
        // Jika field hidden kosong (JS gagal atau kode tidak dikenali),
        // isi otomatis dari KODE_MAP PHP (sama persis dengan KODE_MAP_SK di JS).
        $kodeMap = [
            //  Kode    [Aktif,       Inaktif,      Keterangan,  Keamanan,  Akses      ]
            'PR' => ['2 Tahun',  '3 Tahun',  'Musnah',    'Biasa',   'Terbuka' ],
            'KP' => ['5 Tahun',  '10 Tahun', 'Permanen',  'Biasa',   'Terbatas'],
            'OT' => ['2 Tahun',  '3 Tahun',  'Musnah',    'Biasa',   'Terbuka' ],
            'KU' => ['5 Tahun',  '10 Tahun', 'Permanen',  'Biasa',   'Terbatas'],
            'HK' => ['5 Tahun',  '10 Tahun', 'Permanen',  'Biasa',   'Terbatas'],
            'UM' => ['2 Tahun',  '3 Tahun',  'Musnah',    'Biasa',   'Terbuka' ],
            'PL' => ['5 Tahun',  '10 Tahun', 'Musnah',    'Biasa',   'Terbatas'],
            'UP' => ['5 Tahun',  '10 Tahun', 'Permanen',  'Biasa',   'Terbatas'],
            'HM' => ['5 Tahun',  '10 Tahun', 'Permanen',  'Biasa',   'Terbatas'],
            'PW' => ['5 Tahun',  '10 Tahun', 'Musnah',    'Biasa',   'Terbatas'],
            'LT' => ['5 Tahun',  '10 Tahun', 'Musnah',    'Biasa',   'Terbatas'],
            'DL' => ['2 Tahun',  '3 Tahun',  'Musnah',    'Biasa',   'Terbuka' ],
            'DT' => ['5 Tahun',  '10 Tahun', 'Permanen',  'Biasa',   'Terbatas'],
            'KI' => ['5 Tahun',  '10 Tahun', 'Permanen',  'Biasa',   'Terbatas'],
        ];
        $kodePrefix = strtoupper(preg_replace('/[^A-Za-z]/', '', $validated['kode_klasifikasi']));
        $kodePrefix = substr($kodePrefix, 0, 2);
        if (isset($kodeMap[$kodePrefix])) {
            $mapRow = $kodeMap[$kodePrefix];
            if (empty($validated['retensi_aktif']))        $validated['retensi_aktif']        = $mapRow[0];
            if (empty($validated['retensi_inaktif']))      $validated['retensi_inaktif']      = $mapRow[1];
            if (empty($validated['retensi_keterangan']))   $validated['retensi_keterangan']   = $mapRow[2];
            if (empty($validated['klasifikasi_keamanan'])) $validated['klasifikasi_keamanan'] = $mapRow[3];
            if (empty($validated['tingkat_akses']))        $validated['tingkat_akses']        = $mapRow[4];
        }

        try {
            [$service, $spreadsheetId] = $this->getSheetService();
            $range = "dbSK!A:R";

            // ── Upload file PDF ke Google Drive & Backup Lokal ke uploads/ ────
            $file = $request->file("surat_keluar");
            $uploadResult = GoogleDriveService::uploadAndBackup($file, $validated["kode_klasifikasi"]);
            $filePath = $uploadResult["driveLink"]; // Gunakan link Google Drive untuk disimpan ke spreadsheet
            $storedFilePath = $uploadResult["filename"];

            // Timestamp otomatis
            $timestamp = now()->format("Y-m-d H:i:s");

            // Susun data sesuai urutan kolom spreadsheet SURAT KELUAR (A–R)
            // Struktur dbSK: Timestamp|Kode Klas|Kode Surat|Uraian Berkas|Kurun Waktu|
            //                Jumlah Folder|Nomor Item|Surat Keluar(PDF)|Uraian Arsip|
            //                Tanggal Surat|Jumlah Lembar|Tingkat Perkembangan|Lokasi Simpan|
            //                Klas Keamanan|Tingkat Akses|Retensi Aktif|Retensi Inaktif|Retensi Ket
            $values = [
                [
                    $timestamp,                                 // A(0):  Timestamp
                    $this->escapeFormula($validated["kode_klasifikasi"]),             // B(1):  Kode Klasifikasi
                    $this->escapeFormula($validated["kode_surat"]),                   // C(2):  Kode Surat
                    $this->escapeFormula($validated["uraian_informasi_berkas"]),      // D(3):  Uraian Informasi Berkas
                    $this->escapeFormula($validated["kurun_waktu"] ?? ""),            // E(4):  Kurun Waktu
                    $this->escapeFormula($validated["jumlah_folder"] ?? ""),          // F(5):  Jumlah Folder
                    $this->escapeFormula($validated["nomor_item_arsip"] ?? ""),       // G(6):  Nomor: Item Arsip
                    $filePath,                                  // H(7):  Surat Keluar (PDF)
                    $this->escapeFormula($validated["uraian_informasi_arsip"] ?? ""), // I(8):  Uraian Informasi Arsip
                    $this->escapeFormula($validated["tanggal_surat"]),                // J(9):  Tanggal Surat
                    $this->escapeFormula($validated["jumlah_lembar"] ?? ""),          // K(10): Jumlah Lembar
                    $this->escapeFormula($validated["tingkat_perkembangan"] ?? ""),   // L(11): Tingkat Perkembangan
                    $this->escapeFormula($validated["lokasi_simpan"] ?? ""),          // M(12): Lokasi Simpan
                    $this->escapeFormula($validated["klasifikasi_keamanan"] ?? ""),   // N(13): Klasifikasi Keamanan
                    $this->escapeFormula($validated["tingkat_akses"] ?? ""),          // O(14): Tingkat Akses
                    $this->escapeFormula($validated["retensi_aktif"] ?? ""),          // P(15): Retensi "Aktif"
                    $this->escapeFormula($validated["retensi_inaktif"] ?? ""),        // Q(16): Retensi "Inaktif"
                    $this->escapeFormula($validated["retensi_keterangan"]),           // R(17): Retensi "Keterangan"
                ],
            ];

            $body = new Sheets\ValueRange(["values" => $values]);
            $params = [
                "valueInputOption" => "RAW",
                "insertDataOption" => "INSERT_ROWS",
            ];

            $service->spreadsheets_values->append(
                $spreadsheetId,
                $range,
                $body,
                $params,
            );

            // Invalidasi cache agar data baru langsung terlihat
            SheetsCache::forgetSuratKeluar();

            return redirect()->route('surat-keluar.index')
                ->with('success', 'Data surat keluar berhasil diarsipkan ke Spreadsheet!');
        } catch (\Exception $e) {
            // Hapus file orphan yang sudah ter-upload ke uploads/ agar tidak menumpuk
            if ($storedFilePath !== null) {
                $orphanPath = base_path('uploads/' . $storedFilePath);
                if (file_exists($orphanPath)) {
                    @unlink($orphanPath);
                }
            }
            return redirect()->route('surat-keluar.index')
                ->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    /**
     * Ambil data satu baris Surat Keluar dari sheet SURAT KELUAR (by rowIndex)
     * dan kembalikan sebagai JSON untuk pre-fill modal edit.
     *
     * @param int $rowIndex  Row index (0-based di array) dari SURAT KELUAR
     */
    public function edit(int $rowIndex)
    {
        try {
            [$service, $spreadsheetId] = $this->getSheetService();

            $dbskResponse = $service->spreadsheets_values->get($spreadsheetId, 'dbSK!A:R');
            $dbskValues   = $dbskResponse->getValues() ?? [];
            $dbskRow      = $dbskValues[$rowIndex] ?? null;

            if ($dbskRow === null) {
                return response()->json(['error' => 'Data tidak ditemukan.'], 404);
            }

            $pdfFilename = trim($dbskRow[7] ?? '');

            return response()->json([
                'rowIndex'               => $rowIndex,
                'dbskIndex'              => $rowIndex,
                'kode_klasifikasi'       => $dbskRow[1]  ?? '',   // B
                'kode_surat'             => $dbskRow[2]  ?? '',   // C
                'uraian_informasi_berkas'=> $dbskRow[3]  ?? '',   // D
                'kurun_waktu'            => $dbskRow[4]  ?? '',   // E
                'jumlah_folder'          => $dbskRow[5]  ?? '',   // F
                'nomor_item_arsip'       => $dbskRow[6]  ?? '',   // G
                'pdf_filename'           => $pdfFilename,         // H
                'uraian_informasi_arsip' => $dbskRow[8]  ?? '',   // I
                'tanggal_surat'          => $dbskRow[9]  ?? '',   // J
                'jumlah_lembar'          => $dbskRow[10] ?? '',   // K
                'tingkat_perkembangan'   => $dbskRow[11] ?? '',   // L
                'lokasi_simpan'          => $dbskRow[12] ?? '',   // M
                'klasifikasi_keamanan'   => $dbskRow[13] ?? '',   // N
                'tingkat_akses'          => $dbskRow[14] ?? '',   // O
                'retensi_aktif'          => $dbskRow[15] ?? '',   // P
                'retensi_inaktif'        => $dbskRow[16] ?? '',   // Q
                'retensi_keterangan'     => $dbskRow[17] ?? '',   // R
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal memuat data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update data surat keluar di Google Sheets (sheet: dbSK).
     *
     * @param Request $request
     * @param int     $rowIndex  Row index dari SURAT KELUAR (untuk redirect)
     */
    public function update(Request $request, int $rowIndex)
    {
        $validated = $request->validate([
            'dbsk_index'              => 'required|integer|min:1',
            'kode_klasifikasi'        => 'required|string|max:255',
            'kode_surat'              => 'required|string|max:255',
            'uraian_informasi_berkas' => 'required|string|max:1000',
            'kurun_waktu'             => 'required|numeric|min:1900|max:2100',
            'jumlah_folder'           => 'required|string|max:255',
            'nomor_item_arsip'        => 'required|numeric|min:1',
            'uraian_informasi_arsip'  => 'required|string|max:1000',
            'tanggal_surat'           => 'required|string|max:50',
            'jumlah_lembar'           => 'required|string|max:255',
            'tingkat_perkembangan'    => 'required|string|max:255',
            'lokasi_simpan'           => 'required|string|max:255',
            'klasifikasi_keamanan'    => 'required|string|max:255',
            'tingkat_akses'           => 'required|string|max:255',
            'retensi_aktif'           => 'required|string|max:255',
            'retensi_inaktif'         => 'required|string|max:255',
            'retensi_keterangan'      => 'required|string|max:1000',
            'pdf_filename'            => 'nullable|string|max:255',
        ]);

        try {
            [$service, $spreadsheetId] = $this->getSheetService();

            $dbskIndex      = (int) $validated['dbsk_index'];
            $sheetRowNumber = $dbskIndex + 1;
            // Mulai dari kolom B agar timestamp di kolom A TIDAK tertimpa
            $range = "dbSK!B{$sheetRowNumber}:R{$sheetRowNumber}";

            // Struktur dbSK: B=Kode Klas|C=Kode Surat|D=Uraian Berkas|
            //                E=Kurun Waktu|F=Jumlah Folder|G=Nomor Item|H=PDF|
            //                I=Uraian Arsip|J=Tanggal Surat|K=Jumlah Lembar|L=Tingkat Perkembangan|
            //                M=Lokasi Simpan|N=Klas Keamanan|O=Tingkat Akses|
            //                P=Retensi Aktif|Q=Retensi Inaktif|R=Retensi Ket
            $values = [[
                // Kolom A (Timestamp) dilewati — range dimulai dari B
                $this->escapeFormula($validated['kode_klasifikasi']),            // B
                $this->escapeFormula($validated['kode_surat']),                  // C
                $this->escapeFormula($validated['uraian_informasi_berkas']),     // D
                $this->escapeFormula($validated['kurun_waktu']),                 // E
                $this->escapeFormula($validated['jumlah_folder']),               // F
                $this->escapeFormula($validated['nomor_item_arsip']),            // G
                $validated['pdf_filename'] ?? '',          // H: PDF filename (tetap)
                $this->escapeFormula($validated['uraian_informasi_arsip']),      // I
                $this->escapeFormula($validated['tanggal_surat']),               // J
                $this->escapeFormula($validated['jumlah_lembar']),               // K
                $this->escapeFormula($validated['tingkat_perkembangan']),        // L
                $this->escapeFormula($validated['lokasi_simpan']),               // M
                $this->escapeFormula($validated['klasifikasi_keamanan']),        // N
                $this->escapeFormula($validated['tingkat_akses']),               // O
                $this->escapeFormula($validated['retensi_aktif']),               // P
                $this->escapeFormula($validated['retensi_inaktif']),             // Q
                $this->escapeFormula($validated['retensi_keterangan']),          // R
            ]];

            $body   = new Sheets\ValueRange(['values' => $values]);
            $params = ['valueInputOption' => 'RAW'];

            $service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);

            // Invalidasi cache agar perubahan langsung terlihat
            SheetsCache::forgetSuratKeluar();

            return redirect()->route('surat-keluar.index')
                ->with('success', 'Data surat keluar berhasil diperbarui di Spreadsheet (dbSK).');
        } catch (\Exception $e) {
            return redirect()->route('surat-keluar.index')
                ->with('error', 'Gagal memperbarui data: ' . $e->getMessage())
                ->withInput();
        }
    }
}
