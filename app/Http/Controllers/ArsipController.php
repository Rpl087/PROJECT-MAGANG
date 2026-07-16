<?php

namespace App\Http\Controllers;

use App\Services\SheetsCache;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Google\Client;
use Google\Service\Sheets;

class ArsipController extends Controller
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
            if ($sheet->getProperties()->getTitle() === $sheetName) {
                return $sheet->getProperties()->getSheetId();
            }
        }
        return null;
    }

    /**
     * Tampilkan daftar surat masuk dari Google Sheets + form input.
     * - Admin      : membaca dari sheet "SURAT MASUK" (sheet tampilan — sudah di-combine 1 baris/entri)
     * - Internship : hanya menampilkan form input (rows kosong, tanpa tabel)
     *
     * Struktur SURAT MASUK (A–Q):
     *   A(0)=NO | B(1)=Kode Klas | C(2)=Uraian Berkas | D(3)=Kurun Waktu
     *   E(4)=Jumlah Folder | F(5)=Nomor Item | G(6)=Uraian Arsip
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
                    SheetsCache::KEY_SURAT_MASUK,
                    SheetsCache::TTL_TABLE,
                    function () {
                        [$service, $spreadsheetId] = $this->getSheetService();
                        $response = $service->spreadsheets_values->get($spreadsheetId, 'dbSM!A:R');
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
                                $mappedRow[6]  = $row[7]  ?? ''; // G: Uraian Arsip
                                $mappedRow[7]  = $row[8]  ?? ''; // H: Tanggal Surat
                                $mappedRow[8]  = $row[9]  ?? ''; // I: Jumlah Lembar
                                $mappedRow[9]  = $row[10] ?? ''; // J: Tingkat Perkembangan
                                $mappedRow[10] = $row[11] ?? ''; // K: Lokasi Simpan
                                $mappedRow[11] = $row[12] ?? ''; // L: Klasifikasi Keamanan
                                $mappedRow[12] = $row[13] ?? ''; // M: Tingkat Akses
                                $mappedRow[13] = $row[14] ?? ''; // N: Retensi Aktif
                                $mappedRow[14] = $row[15] ?? ''; // O: Retensi Inaktif
                                $mappedRow[15] = $row[16] ?? ''; // P: Retensi Keterangan
                                $mappedRow[16] = $row[17] ?? ''; // Q: Link Folder (PDF filename)
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
                // Hapus cache yang mungkin korup agar request berikutnya retry ke API
                SheetsCache::forget(SheetsCache::KEY_SURAT_MASUK);
            }
        }

        // Filter bulan dari request
        $filterBulan = $request->get('bulan', '');
        $filterTahun = $request->get('tahun', '');

        return view('arsip.index', compact('rows', 'fetchError', 'isAdmin', 'filterBulan', 'filterTahun'));
    }

    /**
     * Tampilkan daftar surat masuk dalam mode READ-ONLY untuk tamu (WiFi Tamu).
     * Tidak memerlukan autentikasi. Hanya menampilkan data tanpa form input atau tombol hapus.
     */
    public function indexGuest()
    {
        $rows       = [];
        $fetchError = null;

        try {
            // Gunakan cache yang sama dengan admin untuk efisiensi
            $allRows = SheetsCache::remember(
                SheetsCache::KEY_SURAT_MASUK,
                SheetsCache::TTL_TABLE,
                function () {
                    [$service, $spreadsheetId] = $this->getSheetService();
                    $response = $service->spreadsheets_values->get($spreadsheetId, 'dbSM!A:R');
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
                            $mappedRow[6]  = $row[7]  ?? '';
                            $mappedRow[7]  = $row[8]  ?? '';
                            $mappedRow[8]  = $row[9]  ?? '';
                            $mappedRow[9]  = $row[10] ?? '';
                            $mappedRow[10] = $row[11] ?? '';
                            $mappedRow[11] = $row[12] ?? '';
                            $mappedRow[12] = $row[13] ?? '';
                            $mappedRow[13] = $row[14] ?? '';
                            $mappedRow[14] = $row[15] ?? '';
                            $mappedRow[15] = $row[16] ?? '';
                            $mappedRow[16] = $row[17] ?? '';
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
            SheetsCache::forget(SheetsCache::KEY_SURAT_MASUK);
        }

        return view('tamu.index', compact('rows', 'fetchError'));
    }

    /**
     * Hapus baris surat masuk dari sheet "dbSM".
     * - Tabel membaca dari sheet "dbSM" (sumber data asli), rowIndex adalah index di dbSM
     * - Pencarian baris dilakukan berdasarkan PDF link (kolom R, index 17) sebagai unique identifier
     * - Fallback ke rowIndex langsung jika PDF link tidak ditemukan
     *
     * @param int $rowIndex  Row index (1-based di sheet, 0-based di array) dari dbSM
     */
    public function destroy(int $rowIndex)
    {
        try {
            [$service, $spreadsheetId] = $this->getSheetService();

            // ── Ambil data dari dbSM untuk mendapatkan PDF link sebagai unique identifier ──
            $dbsmResponse = $service->spreadsheets_values->get($spreadsheetId, 'dbSM!A:R');
            $dbsmRows     = $dbsmResponse->getValues() ?? [];
            $targetRow    = $dbsmRows[$rowIndex] ?? null;

            if ($targetRow === null) {
                return redirect()->route('arsip.index')
                    ->with('error', 'Data tidak ditemukan di sheet dbSM.');
            }

            // PDF link ada di kolom R (index 17) di sheet dbSM — digunakan sebagai unique identifier
            $pdfLink      = trim($targetRow[17] ?? '');
            $dbsmRowIndex = null; // harus ditemukan lewat lookup PDF link

            // Cari baris di dbSM yang cocok berdasarkan PDF link
            if (!empty($pdfLink)) {
                foreach ($dbsmRows as $i => $row) {
                    if ($i === 0) continue; // skip header
                    if (trim($row[17] ?? '') === $pdfLink) {
                        $dbsmRowIndex = $i;
                        break;
                    }
                }
            }

            // Batalkan jika baris tidak dapat diidentifikasi — lebih aman daripada hapus baris salah
            if ($dbsmRowIndex === null) {
                return redirect()->route('arsip.index')
                    ->with('error', 'Gagal menghapus: data tidak dapat diidentifikasi secara unik di dbSM (PDF link tidak ditemukan atau kosong).');
            }

            // ── Hapus baris dari dbSM ─────────────────────────────────────────────
            $sheetId = $this->getSheetId($service, $spreadsheetId, 'dbSM');

            if ($sheetId === null) {
                return redirect()->route('arsip.index')
                    ->with('error', 'Sheet "dbSM" tidak ditemukan di Spreadsheet.');
            }

            $requests = [[
                'deleteDimension' => [
                    'range' => [
                        'sheetId'    => $sheetId,
                        'dimension'  => 'ROWS',
                        'startIndex' => $dbsmRowIndex,
                        'endIndex'   => $dbsmRowIndex + 1,
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
            SheetsCache::forgetSuratMasuk();

            return redirect()->route('arsip.index')
                ->with('success', 'Data surat masuk berhasil dihapus dari Spreadsheet (dbSM).');
        } catch (\Exception $e) {
            return redirect()->route('arsip.index')
                ->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    /**
     * Simpan data surat masuk ke Google Spreadsheet (sheet: SURAT MASUK).
     *
     * Struktur kolom SURAT MASUK (A–Q):
     *   A=NO | B=Kode Klasifikasi | C=Uraian Informasi Berkas | D=Kurun Waktu
     *   E=Jumlah Folder | F=Nomor Item Arsip | G=Uraian Informasi Arsip
     *   H=Tanggal Surat | I=Jumlah Lembar | J=Tingkat Perkembangan
     *   K=Lokasi Simpan | L=Klasifikasi Keamanan | M=Tingkat Akses
     *   N=Retensi "Aktif" | O=Retensi "Inaktif" | P=Retensi "Keterangan"
     *   Q=Link Folder
     */
    public function store(Request $request)
    {
        $storedFilePath = null;

        // Validasi input sesuai kolom dbSM
        $validated = $request->validate([
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
            'klasifikasi_keamanan'    => 'nullable|string|max:255',  // diisi otomatis oleh JS/kodeMap
            'tingkat_akses'           => 'nullable|string|max:255',  // diisi otomatis oleh JS/kodeMap
            'retensi_aktif'           => 'nullable|string|max:255',  // diisi otomatis oleh JS/kodeMap
            'retensi_inaktif'         => 'nullable|string|max:255',  // diisi otomatis oleh JS/kodeMap
            'retensi_keterangan'      => 'nullable|string|max:1000', // diisi otomatis oleh JS/kodeMap
            'surat_masuk'             => 'required|file|mimes:pdf|max:10240',
        ], [
            'kode_klasifikasi.required'        => 'Kode Klasifikasi wajib diisi.',
            'kode_surat.required'              => 'Kode Surat wajib diisi.',
            'uraian_informasi_berkas.required' => 'Uraian Informasi Berkas wajib diisi.',
            'kurun_waktu.required'             => 'Kurun Waktu wajib diisi.',
            'kurun_waktu.numeric'              => 'Kurun Waktu harus berupa angka tahun (contoh: 2024).',
            'kurun_waktu.min'                  => 'Kurun Waktu minimal tahun 1900.',
            'kurun_waktu.max'                  => 'Kurun Waktu maksimal tahun 2100.',
            'jumlah_folder.required'           => 'Jumlah Folder wajib diisi.',
            'nomor_item_arsip.required'        => 'Nomor Item Arsip wajib diisi.',
            'nomor_item_arsip.numeric'         => 'Nomor Item Arsip harus berupa angka.',
            'nomor_item_arsip.min'             => 'Nomor Item Arsip minimal 1.',
            'uraian_informasi_arsip.required'  => 'Uraian Informasi Arsip wajib diisi.',
            'tanggal_surat.required'           => 'Tanggal Surat wajib diisi.',
            'jumlah_lembar.required'           => 'Jumlah Lembar wajib diisi.',
            'tingkat_perkembangan.required'    => 'Tingkat Perkembangan wajib diisi.',
            'lokasi_simpan.required'           => 'Lokasi Simpan wajib diisi.',
            // klasifikasi_keamanan, tingkat_akses, retensi_* sekarang nullable (diisi server-side)
            'surat_masuk.required'             => 'File Surat Masuk (PDF) wajib diunggah.',
        ]);

        // ── Server-side kodeMap fallback (konsisten dengan SuratKeluarController) ──────────────
        // Jika field auto-filled kosong (JS gagal atau kode tidak dikenali), isi dari kodeMap.
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

            // Target: sheet dbSM (sumber data asli)
            $sheetRange = 'dbSM!A:R';

            // ── Upload file PDF ke Google Drive & Backup Lokal ke uploads/ ────
            $file = $request->file('surat_masuk');
            $uploadResult = GoogleDriveService::uploadAndBackup($file, $validated['kode_klasifikasi']);
            $linkFolder = $uploadResult['driveLink']; // Gunakan link Google Drive untuk disimpan ke spreadsheet
            $storedFilePath = $uploadResult['filename'];

            // Timestamp otomatis
            $timestamp = now()->format('Y-m-d H:i:s');

            // ── Susun data sesuai kolom dbSM (A–R) ───────────────────────────
            // A=Timestamp | B=Kode Klasifikasi | C=Kode Surat | D=Uraian Berkas
            // E=Kurun Waktu | F=Jumlah Folder | G=Nomor Item | H=Uraian Arsip
            // I=Tanggal Surat | J=Jumlah Lembar | K=Tingkat Perkembangan
            // L=Lokasi Simpan | M=Klas Keamanan | N=Tingkat Akses
            // O=Retensi Aktif | P=Retensi Inaktif | Q=Retensi Ket | R=Link Folder
            $values = [[
                $timestamp,                                // A: Timestamp
                $this->escapeFormula($validated['kode_klasifikasi']),            // B: Kode Klasifikasi
                $this->escapeFormula($validated['kode_surat']),                  // C: Kode Surat
                $this->escapeFormula($validated['uraian_informasi_berkas']),     // D: Uraian Informasi Berkas
                $this->escapeFormula($validated['kurun_waktu']),                 // E: Kurun Waktu
                $this->escapeFormula($validated['jumlah_folder']),               // F: Jumlah Folder
                $this->escapeFormula($validated['nomor_item_arsip']),            // G: Nomor: Item Arsip
                $this->escapeFormula($validated['uraian_informasi_arsip']),      // H: Uraian Informasi Arsip
                $this->escapeFormula($validated['tanggal_surat']),               // I: Tanggal Surat
                $this->escapeFormula($validated['jumlah_lembar']),               // J: Jumlah Lembar
                $this->escapeFormula($validated['tingkat_perkembangan']),        // K: Tingkat Perkembangan
                $this->escapeFormula($validated['lokasi_simpan']),               // L: Lokasi Simpan
                $this->escapeFormula($validated['klasifikasi_keamanan']),        // M: Klasifikasi Keamanan
                $this->escapeFormula($validated['tingkat_akses']),               // N: Tingkat Akses
                $this->escapeFormula($validated['retensi_aktif']),               // O: Retensi "Aktif"
                $this->escapeFormula($validated['retensi_inaktif']),             // P: Retensi "Inaktif"
                $this->escapeFormula($validated['retensi_keterangan']),          // Q: Retensi "Keterangan"
                $linkFolder,                               // R: Link Folder
            ]];

            $body   = new Sheets\ValueRange(['values' => $values]);
            $params = ['valueInputOption' => 'RAW', 'insertDataOption' => 'INSERT_ROWS'];

            $service->spreadsheets_values->append($spreadsheetId, $sheetRange, $body, $params);

            // Invalidasi cache agar data baru langsung terlihat
            SheetsCache::forgetSuratMasuk();

            return redirect()->route('arsip.index')
                ->with('success', 'Data surat masuk berhasil diarsipkan ke Spreadsheet!');
        } catch (\Exception $e) {
            // Hapus file orphan yang sudah ter-upload ke uploads/ agar tidak menumpuk
            if ($storedFilePath !== null) {
                $orphanPath = base_path('uploads/' . $storedFilePath);
                if (file_exists($orphanPath)) {
                    @unlink($orphanPath);
                }
            }

            return redirect()->route('arsip.index')
                ->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    /**
     * Ambil data satu baris Surat Masuk dari sheet SURAT MASUK (by rowIndex)
     * dan kembalikan sebagai JSON untuk pre-fill modal edit.
     *
     * @param int $rowIndex  Row index (1-based di sheet, 0-based di array) dari SURAT MASUK
     */
    public function edit(int $rowIndex)
    {
        try {
            [$service, $spreadsheetId] = $this->getSheetService();

            $dbsmResponse = $service->spreadsheets_values->get($spreadsheetId, 'dbSM!A:R');
            $dbsmValues   = $dbsmResponse->getValues() ?? [];
            $dbsmRow      = $dbsmValues[$rowIndex] ?? null;

            if ($dbsmRow === null) {
                return response()->json(['error' => 'Data tidak ditemukan.'], 404);
            }

            $pdfFilename = trim($dbsmRow[17] ?? '');

            return response()->json([
                'rowIndex'               => $rowIndex,
                'dbsmIndex'              => $rowIndex,
                'kode_klasifikasi'       => $dbsmRow[1]  ?? '',   // B
                'uraian_informasi_berkas'=> $dbsmRow[3]  ?? '',   // D
                'kurun_waktu'            => $dbsmRow[4]  ?? '',   // E
                'jumlah_folder'          => $dbsmRow[5]  ?? '',   // F
                'nomor_item_arsip'       => $dbsmRow[6]  ?? '',   // G
                'uraian_informasi_arsip' => $dbsmRow[7]  ?? '',   // H
                'tanggal_surat'          => $dbsmRow[8]  ?? '',   // I
                'jumlah_lembar'          => $dbsmRow[9]  ?? '',   // J
                'tingkat_perkembangan'   => $dbsmRow[10] ?? '',   // K
                'lokasi_simpan'          => $dbsmRow[11] ?? '',   // L
                'klasifikasi_keamanan'   => $dbsmRow[12] ?? '',   // M
                'tingkat_akses'          => $dbsmRow[13] ?? '',   // N
                'retensi_aktif'          => $dbsmRow[14] ?? '',   // O
                'retensi_inaktif'        => $dbsmRow[15] ?? '',   // P
                'retensi_keterangan'     => $dbsmRow[16] ?? '',   // Q
                'pdf_filename'           => $pdfFilename,         // R
                'kode_surat'             => $dbsmRow[2]  ?? '',   // C
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal memuat data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update data surat masuk di Google Sheets (sheet: dbSM).
     * Mencari baris di dbSM berdasarkan dbsmIndex yang dikirim dari modal edit.
     *
     * @param Request $request
     * @param int     $rowIndex  Row index dari SURAT MASUK (untuk redirect)
     */
    public function update(Request $request, int $rowIndex)
    {
        $validated = $request->validate([
            'dbsm_index'              => 'required|integer|min:1',
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

            $dbsmIndex = (int) $validated['dbsm_index'];

            // Baris di Google Sheets dimulai dari 1 (row 1 = header, row 2 = data ke-1)
            // dbsmIndex adalah 0-based array index, jadi row number = dbsmIndex + 1
            $sheetRowNumber = $dbsmIndex + 1;
            // Mulai dari kolom B agar timestamp di kolom A TIDAK tertimpa
            $range = "dbSM!B{$sheetRowNumber}:R{$sheetRowNumber}";

            $values = [[
                // Kolom A (Timestamp) dilewati — range dimulai dari B
                $this->escapeFormula($validated['kode_klasifikasi']),            // B
                $this->escapeFormula($validated['kode_surat']),                  // C
                $this->escapeFormula($validated['uraian_informasi_berkas']),     // D
                $this->escapeFormula($validated['kurun_waktu']),                 // E
                $this->escapeFormula($validated['jumlah_folder']),               // F
                $this->escapeFormula($validated['nomor_item_arsip']),            // G
                $this->escapeFormula($validated['uraian_informasi_arsip']),      // H
                $this->escapeFormula($validated['tanggal_surat']),               // I
                $this->escapeFormula($validated['jumlah_lembar']),               // J
                $this->escapeFormula($validated['tingkat_perkembangan']),        // K
                $this->escapeFormula($validated['lokasi_simpan']),               // L
                $this->escapeFormula($validated['klasifikasi_keamanan']),        // M
                $this->escapeFormula($validated['tingkat_akses']),               // N
                $this->escapeFormula($validated['retensi_aktif']),               // O
                $this->escapeFormula($validated['retensi_inaktif']),             // P
                $this->escapeFormula($validated['retensi_keterangan']),          // Q
                $validated['pdf_filename'] ?? '',          // R
            ]];

            $body   = new Sheets\ValueRange(['values' => $values]);
            $params = ['valueInputOption' => 'RAW'];

            $service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);

            // Invalidasi cache agar perubahan langsung terlihat
            SheetsCache::forgetSuratMasuk();

            return redirect()->route('arsip.index')
                ->with('success', 'Data surat masuk berhasil diperbarui di Spreadsheet (dbSM).');
        } catch (\Exception $e) {
            return redirect()->route('arsip.index')
                ->with('error', 'Gagal memperbarui data: ' . $e->getMessage())
                ->withInput();
        }
    }
}
