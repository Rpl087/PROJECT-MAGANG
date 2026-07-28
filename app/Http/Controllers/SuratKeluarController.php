<?php

namespace App\Http\Controllers;

use App\Services\SheetsCache;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Google\Service\Sheets;

class SuratKeluarController extends Controller
{
    // KODE_KLASIFIKASI_ORDER, KODE_MAP, sortByKlasifikasi(), applyKodeMapFallback()
    // diwarisi dari Controller base class (tidak lagi duplikat di sini).

    // getSheetService(), getSheetId(), escapeFormula(), safeDeleteFile() juga dari base class.

    /**
     * Petakan satu baris raw dbSK (A–R, 18 kolom) ke format tampilan (0–16, 17 kolom).
     *
     * Mapping kolom dbSK → tampilan:
     *   dbSK[0]  (A: Timestamp)        → [0]  Timestamp/NO
     *   dbSK[1]  (B: Kode Klas)  +
     *   dbSK[2]  (C: Kode Surat)       → [1]  Kode Gabungan ("KP 04.01")
     *   dbSK[3]  (D: Uraian Berkas)    → [2]
     *   dbSK[4]  (E: Kurun Waktu)      → [3]
     *   dbSK[5]  (F: Jumlah Folder)    → [4]
     *   dbSK[6]  (G: Nomor Item)       → [5]
     *   dbSK[8]  (I: Uraian Arsip) **  → [6]  (Note: dbSK index 7 = PDF, 8 = Uraian)
     *   dbSK[9]  (J: Tanggal Surat)    → [7]
     *   dbSK[10] (K: Jumlah Lembar)    → [8]
     *   dbSK[11] (L: Tingkat Perkembangan) → [9]
     *   dbSK[12] (M: Lokasi Simpan)    → [10]
     *   dbSK[13] (N: Klas Keamanan)    → [11]
     *   dbSK[14] (O: Tingkat Akses)    → [12]
     *   dbSK[15] (P: Retensi Aktif)    → [13]
     *   dbSK[16] (Q: Retensi Inaktif)  → [14]
     *   dbSK[17] (R: Retensi Ket)      → [15]
     *   dbSK[7]  (H: Link PDF) **      → [16] (PDF pindah ke ujung tampilan)
     */
    private function mapDbskRow(array $row): array
    {
        $kodeKlas  = trim($row[1] ?? '');
        $kodeSurat = trim($row[2] ?? '');

        return [
            $row[0]  ?? '',  // [0]  Timestamp
            ($kodeKlas !== '' && $kodeSurat !== '')
                ? "{$kodeKlas} {$kodeSurat}"
                : ($kodeKlas ?: $kodeSurat),    // [1]  Kode Gabungan
            $row[3]  ?? '',  // [2]  Uraian Berkas
            $row[4]  ?? '',  // [3]  Kurun Waktu
            $row[5]  ?? '',  // [4]  Jumlah Folder
            $row[6]  ?? '',  // [5]  Nomor Item
            $row[8]  ?? '',  // [6]  Uraian Arsip (dbSK: index 8, setelah PDF di index 7)
            $row[9]  ?? '',  // [7]  Tanggal Surat
            $row[10] ?? '',  // [8]  Jumlah Lembar
            $row[11] ?? '',  // [9]  Tingkat Perkembangan
            $row[12] ?? '',  // [10] Lokasi Simpan
            $row[13] ?? '',  // [11] Klas Keamanan
            $row[14] ?? '',  // [12] Tingkat Akses
            $row[15] ?? '',  // [13] Retensi Aktif
            $row[16] ?? '',  // [14] Retensi Inaktif
            $row[17] ?? '',  // [15] Retensi Keterangan
            $row[7]  ?? '',  // [16] Link PDF (dbSK: H/index 7, dipindah ke ujung tampilan)
        ];
    }

    /**
     * Ambil & cache data dbSK dari Google Sheets, dengan mapping kolom ke format tampilan.
     *
     * @return array  Array baris yang sudah di-mapping (index 0 = header row asli)
     */
    private function fetchAndCacheDbsk(): array
    {
        return SheetsCache::remember(
            SheetsCache::KEY_SURAT_KELUAR,
            SheetsCache::TTL_TABLE,
            function () {
                // INFO-02 fix: gunakan getReadonlySheetService() — hanya baca data, tidak perlu scope write
                [$service, $spreadsheetId] = $this->getReadonlySheetService();
                $response = $service->spreadsheets_values->get($spreadsheetId, 'dbSK!A:R');
                $dbRows   = $response->getValues() ?? [];

                if (empty($dbRows)) {
                    return [];
                }

                $mappedRows = [$dbRows[0]]; // Simpan header asli di index 0
                foreach ($dbRows as $i => $row) {
                    if ($i === 0) continue;
                    $mappedRows[] = $this->mapDbskRow($row);
                }
                return $mappedRows;
            }
        );
    }

    /**
     * Tampilkan daftar surat keluar dari Google Sheets + form input.
     * - Admin      : membaca dari sheet "dbSK"
     * - Internship : hanya menampilkan form input (rows kosong, tanpa tabel)
     *
     * Struktur SURAT KELUAR setelah mapping (index 0–16):
     *   [0]=Timestamp | [1]=Kode Klas+Surat | [2]=Uraian Berkas | [3]=Kurun Waktu
     *   [4]=Jumlah Folder | [5]=Nomor Item | [6]=Uraian Arsip
     *   [7]=Tanggal Surat | [8]=Jumlah Lembar | [9]=Tingkat Perkembangan
     *   [10]=Lokasi Simpan | [11]=Klas Keamanan | [12]=Tingkat Akses
     *   [13]=Retensi Aktif | [14]=Retensi Inaktif | [15]=Retensi Ket | [16]=Link PDF
     */
    public function index(Request $request)
    {
        $rows       = [];
        $fetchError = null;
        $isAdmin    = auth()->user()?->hasAdminAccess() ?? false;

        if ($isAdmin) {
            try {
                $allRows = $this->fetchAndCacheDbsk();

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

        // Filter bulan/tahun dari request
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
            $allRows = $this->fetchAndCacheDbsk();

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
     * - PDF link ada di kolom H (index 7) di dbSK — digunakan sebagai unique identifier.
     *
     * @param int $rowIndex  Row index (0-based array) dari dbSK
     */
    public function destroy(int $rowIndex)
    {
        try {
            [$service, $spreadsheetId] = $this->getSheetService();

            // ── Ambil data langsung dari dbSK menggunakan rowIndex ─────────────
            $dbskResponse = $service->spreadsheets_values->get($spreadsheetId, 'dbSK!A:R');
            $dbskRows     = $dbskResponse->getValues() ?? [];
            $targetRow    = $dbskRows[$rowIndex] ?? null;

            if ($targetRow === null) {
                return redirect()->route('surat-keluar.index')
                    ->with('error', 'Data tidak ditemukan di sheet dbSK.');
            }

            // PDF link ada di kolom H (index 7) di dbSK — unique identifier jika ada
            $pdfLink      = trim($targetRow[7] ?? '');
            $dbskRowIndex = null;

            // Priority 1: Verifikasi cari baris di dbSK yang cocok berdasarkan PDF link jika tidak kosong
            if (!empty($pdfLink)) {
                foreach ($dbskRows as $i => $row) {
                    if ($i === 0) continue; // skip header
                    if (trim($row[7] ?? '') === $pdfLink) {
                        $dbskRowIndex = $i;
                        break;
                    }
                }
            }

            // Priority 2 (Fallback): Gunakan $rowIndex jika valid di dbSK (misal data tanpa PDF link)
            if ($dbskRowIndex === null && isset($dbskRows[$rowIndex]) && $rowIndex > 0) {
                $dbskRowIndex = $rowIndex;
            }

            if ($dbskRowIndex === null) {
                return redirect()->route('surat-keluar.index')
                    ->with('error', 'Gagal menghapus: data tidak ditemukan di sheet dbSK.');
            }

            // ── Hapus baris dari dbSK ──────────────────────────────────────────
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

            // Hapus file lokal jika PDF link adalah nama file (bukan URL Google Drive)
            if ($pdfLink !== '' && !str_starts_with($pdfLink, 'http')) {
                $this->safeDeleteFile(base_path('uploads/' . $pdfLink));
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
     *
     * Struktur dbSK (A–R):
     *   A=Timestamp | B=Kode Klas | C=Kode Surat | D=Uraian Berkas
     *   E=Kurun Waktu | F=Jumlah Folder | G=Nomor Item | H=Surat Keluar (PDF)
     *   I=Uraian Arsip | J=Tanggal Surat | K=Jumlah Lembar | L=Tingkat Perkembangan
     *   M=Lokasi Simpan | N=Klas Keamanan | O=Tingkat Akses
     *   P=Retensi Aktif | Q=Retensi Inaktif | R=Retensi Ket
     */
    public function store(Request $request)
    {
        $storedFilePath = null;

        $validated = $request->validate([
            'kode_klasifikasi'        => 'required|string|max:255',
            'kode_surat'              => 'required|string|max:255',
            'uraian_informasi_berkas' => 'required|string|max:1000',
            'kurun_waktu'             => 'required|numeric|min:1900|max:2100',
            'jumlah_folder'           => 'required|string|max:255',
            'nomor_item_arsip'        => 'required|numeric|min:1',
            'surat_keluar'            => 'required|file|mimes:pdf|max:10240',
            'uraian_informasi_arsip'  => 'required|string|max:1000',
            'tanggal_surat'           => 'required|string|max:50',
            'jumlah_lembar'           => 'required|string|max:255',
            'tingkat_perkembangan'    => 'required|string|max:255',
            'lokasi_simpan'           => 'required|string|max:255',
            'klasifikasi_keamanan'    => 'nullable|string|max:255',
            'tingkat_akses'           => 'nullable|string|max:255',
            'retensi_aktif'           => 'nullable|string|max:255',
            'retensi_inaktif'         => 'nullable|string|max:255',
            'retensi_keterangan'      => 'nullable|string|max:1000',
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
            'surat_keluar.required'            => 'File Surat Keluar (PDF) wajib diunggah.',
            'uraian_informasi_arsip.required'  => 'Uraian Informasi Arsip wajib diisi.',
            'tanggal_surat.required'           => 'Tanggal Surat wajib diisi.',
            'jumlah_lembar.required'           => 'Jumlah Lembar wajib diisi.',
            'tingkat_perkembangan.required'    => 'Tingkat Perkembangan wajib diisi.',
            'lokasi_simpan.required'           => 'Lokasi Simpan wajib diisi.',
        ]);

        // ── Server-side kodeMap fallback (dari base Controller::applyKodeMapFallback()) ──
        $this->applyKodeMapFallback($validated);

        try {
            [$service, $spreadsheetId] = $this->getSheetService();
            $range = 'dbSK!A:R';

            // ── Upload file PDF ke Google Drive & Backup Lokal ke uploads/ ────
            $file         = $request->file('surat_keluar');
            $uploadResult = GoogleDriveService::uploadAndBackup($file, $validated['kode_klasifikasi']);
            $filePath     = $uploadResult['driveLink'];
            $storedFilePath = $uploadResult['filename'];

            $timestamp = now()->format('Y-m-d H:i:s');

            $values = [[
                $timestamp,                                                      // A: Timestamp
                $this->escapeFormula($validated['kode_klasifikasi']),            // B: Kode Klasifikasi
                $this->escapeFormula($validated['kode_surat']),                  // C: Kode Surat
                $this->escapeFormula($validated['uraian_informasi_berkas']),     // D: Uraian Berkas
                $this->escapeFormula($validated['kurun_waktu']),                 // E: Kurun Waktu
                $this->escapeFormula($validated['jumlah_folder']),               // F: Jumlah Folder
                $this->escapeFormula($validated['nomor_item_arsip']),            // G: Nomor Item
                $filePath,                                                        // H: PDF Link
                $this->escapeFormula($validated['uraian_informasi_arsip']),      // I: Uraian Arsip
                $this->escapeFormula($validated['tanggal_surat']),               // J: Tanggal Surat
                $this->escapeFormula($validated['jumlah_lembar']),               // K: Jumlah Lembar
                $this->escapeFormula($validated['tingkat_perkembangan']),        // L: Tingkat Perkembangan
                $this->escapeFormula($validated['lokasi_simpan']),               // M: Lokasi Simpan
                $this->escapeFormula($validated['klasifikasi_keamanan']),        // N: Klas Keamanan
                $this->escapeFormula($validated['tingkat_akses']),               // O: Tingkat Akses
                $this->escapeFormula($validated['retensi_aktif']),               // P: Retensi Aktif
                $this->escapeFormula($validated['retensi_inaktif']),             // Q: Retensi Inaktif
                $this->escapeFormula($validated['retensi_keterangan']),          // R: Retensi Ket
            ]];

            $body   = new Sheets\ValueRange(['values' => $values]);
            $params = ['valueInputOption' => 'RAW', 'insertDataOption' => 'INSERT_ROWS'];

            $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);

            // Invalidasi cache agar data baru langsung terlihat
            SheetsCache::forgetSuratKeluar();

            return redirect()->route('surat-keluar.index')
                ->with('success', 'Data surat keluar berhasil diarsipkan ke Spreadsheet!');
        } catch (\Exception $e) {
            // Hapus file orphan yang sudah ter-upload ke uploads/
            if ($storedFilePath !== null) {
                $this->safeDeleteFile(base_path('uploads/' . $storedFilePath));
            }
            return redirect()->route('surat-keluar.index')
                ->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    /**
     * Ambil data satu baris Surat Keluar dari sheet dbSK (by rowIndex)
     * dan kembalikan sebagai JSON untuk pre-fill modal edit.
     *
     * @param int $rowIndex  Row index (0-based di array) dari dbSK
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
                'rowIndex'                => $rowIndex,
                'dbskIndex'               => $rowIndex,
                'kode_klasifikasi'        => $dbskRow[1]  ?? '',   // B
                'kode_surat'              => $dbskRow[2]  ?? '',   // C
                'uraian_informasi_berkas' => $dbskRow[3]  ?? '',   // D
                'kurun_waktu'             => $dbskRow[4]  ?? '',   // E
                'jumlah_folder'           => $dbskRow[5]  ?? '',   // F
                'nomor_item_arsip'        => $dbskRow[6]  ?? '',   // G
                'pdf_filename'            => $pdfFilename,         // H
                'uraian_informasi_arsip'  => $dbskRow[8]  ?? '',   // I
                'tanggal_surat'           => $dbskRow[9]  ?? '',   // J
                'jumlah_lembar'           => $dbskRow[10] ?? '',   // K
                'tingkat_perkembangan'    => $dbskRow[11] ?? '',   // L
                'lokasi_simpan'           => $dbskRow[12] ?? '',   // M
                'klasifikasi_keamanan'    => $dbskRow[13] ?? '',   // N
                'tingkat_akses'           => $dbskRow[14] ?? '',   // O
                'retensi_aktif'           => $dbskRow[15] ?? '',   // P
                'retensi_inaktif'         => $dbskRow[16] ?? '',   // Q
                'retensi_keterangan'      => $dbskRow[17] ?? '',   // R
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal memuat data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update data surat keluar di Google Sheets (sheet: dbSK).
     *
     * @param Request $request
     * @param int     $rowIndex  Row index dari dbSK (untuk redirect)
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

            // Pertahankan link PDF lama jika input pdf_filename dari form bernilai kosong
            $pdfFilename = trim($validated['pdf_filename'] ?? '');
            if (empty($pdfFilename)) {
                $existingRange    = "dbSK!H{$sheetRowNumber}";
                $existingResponse = $service->spreadsheets_values->get($spreadsheetId, $existingRange);
                $existingValues   = $existingResponse->getValues() ?? [];
                $pdfFilename      = trim($existingValues[0][0] ?? '');
            }

            // Bila kode_klasifikasi di-edit, ubah nama file PDF fisik di /uploads & Google Drive
            if (!empty($pdfFilename)) {
                $pdfFilename = GoogleDriveService::renameFile($pdfFilename, $validated['kode_klasifikasi']);
            }

            // Mulai dari kolom B agar timestamp di kolom A TIDAK tertimpa
            $range = "dbSK!B{$sheetRowNumber}:R{$sheetRowNumber}";

            $values = [[
                $this->escapeFormula($validated['kode_klasifikasi']),
                $this->escapeFormula($validated['kode_surat']),
                $this->escapeFormula($validated['uraian_informasi_berkas']),
                $this->escapeFormula($validated['kurun_waktu']),
                $this->escapeFormula($validated['jumlah_folder']),
                $this->escapeFormula($validated['nomor_item_arsip']),
                $pdfFilename,                               // H: PDF filename (tetap)
                $this->escapeFormula($validated['uraian_informasi_arsip']),
                $this->escapeFormula($validated['tanggal_surat']),
                $this->escapeFormula($validated['jumlah_lembar']),
                $this->escapeFormula($validated['tingkat_perkembangan']),
                $this->escapeFormula($validated['lokasi_simpan']),
                $this->escapeFormula($validated['klasifikasi_keamanan']),
                $this->escapeFormula($validated['tingkat_akses']),
                $this->escapeFormula($validated['retensi_aktif']),
                $this->escapeFormula($validated['retensi_inaktif']),
                $this->escapeFormula($validated['retensi_keterangan']),
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
