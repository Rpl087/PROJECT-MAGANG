<?php

namespace App\Http\Controllers;

use App\Services\SheetsCache;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Google\Service\Sheets;

class ArsipController extends Controller
{
    // KODE_KLASIFIKASI_ORDER, KODE_MAP, sortByKlasifikasi(), applyKodeMapFallback()
    // diwarisi dari Controller base class (tidak lagi duplikat di sini).

    // getSheetService(), getSheetId(), escapeFormula(), safeDeleteFile() juga dari base class.

    /**
     * Petakan satu baris raw dbSM (A–R, 18 kolom) ke format tampilan (0–16, 17 kolom).
     *
     * Mapping kolom dbSM → tampilan:
     *   dbSM[0]  (A: Timestamp)        → [0]  Timestamp/NO
     *   dbSM[1]  (B: Kode Klas)  +
     *   dbSM[2]  (C: Kode Surat)       → [1]  Kode Gabungan ("KP 04.01")
     *   dbSM[3]  (D: Uraian Berkas)    → [2]
     *   dbSM[4]  (E: Kurun Waktu)      → [3]
     *   dbSM[5]  (F: Jumlah Folder)    → [4]
     *   dbSM[6]  (G: Nomor Item)       → [5]
     *   dbSM[7]  (H: Uraian Arsip)     → [6]
     *   dbSM[8]  (I: Tanggal Surat)    → [7]
     *   dbSM[9]  (J: Jumlah Lembar)    → [8]
     *   dbSM[10] (K: Tingkat Perkembangan) → [9]
     *   dbSM[11] (L: Lokasi Simpan)    → [10]
     *   dbSM[12] (M: Klas Keamanan)    → [11]
     *   dbSM[13] (N: Tingkat Akses)    → [12]
     *   dbSM[14] (O: Retensi Aktif)    → [13]
     *   dbSM[15] (P: Retensi Inaktif)  → [14]
     *   dbSM[16] (Q: Retensi Ket)      → [15]
     *   dbSM[17] (R: Link Folder/PDF)  → [16]
     */
    private function mapDbsmRow(array $row): array
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
            $row[7]  ?? '',  // [6]  Uraian Arsip
            $row[8]  ?? '',  // [7]  Tanggal Surat
            $row[9]  ?? '',  // [8]  Jumlah Lembar
            $row[10] ?? '',  // [9]  Tingkat Perkembangan
            $row[11] ?? '',  // [10] Lokasi Simpan
            $row[12] ?? '',  // [11] Klas Keamanan
            $row[13] ?? '',  // [12] Tingkat Akses
            $row[14] ?? '',  // [13] Retensi Aktif
            $row[15] ?? '',  // [14] Retensi Inaktif
            $row[16] ?? '',  // [15] Retensi Keterangan
            $row[17] ?? '',  // [16] Link Folder/PDF
        ];
    }

    /**
     * Ambil & cache data dbSM dari Google Sheets, dengan mapping kolom ke format tampilan.
     *
     * @return array  Array baris yang sudah di-mapping (index 0 = header row asli)
     */
    private function fetchAndCacheDbsm(): array
    {
        return SheetsCache::remember(
            SheetsCache::KEY_SURAT_MASUK,
            SheetsCache::TTL_TABLE,
            function () {
                // INFO-01 fix: gunakan getReadonlySheetService() — hanya baca data, tidak perlu scope write
                [$service, $spreadsheetId] = $this->getReadonlySheetService();
                $response = $service->spreadsheets_values->get($spreadsheetId, 'dbSM!A:R');
                $dbRows   = $response->getValues() ?? [];

                if (empty($dbRows)) {
                    return [];
                }

                $mappedRows = [$dbRows[0]]; // Simpan header asli di index 0
                foreach ($dbRows as $i => $row) {
                    if ($i === 0) continue;
                    $mappedRows[] = $this->mapDbsmRow($row);
                }
                return $mappedRows;
            }
        );
    }

    /**
     * Tampilkan daftar surat masuk dari Google Sheets + form input.
     * - Admin      : membaca dari sheet "dbSM" (sumber data asli)
     * - Internship : hanya menampilkan form input (rows kosong, tanpa tabel)
     *
     * Struktur SURAT MASUK setelah mapping (index 0–16):
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
                $allRows = $this->fetchAndCacheDbsm();

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

        // Filter bulan/tahun dari request
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
            $allRows = $this->fetchAndCacheDbsm();

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
     * - PDF link (kolom R, index 17) digunakan sebagai unique identifier.
     * - Fallback ke rowIndex langsung jika PDF link tidak ditemukan.
     *
     * @param int $rowIndex  Row index (0-based di array) dari dbSM
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
            $dbsmRowIndex = null;

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
            if ($pdfLink !== '' && !str_starts_with($pdfLink, 'http')) {
                $this->safeDeleteFile(base_path('uploads/' . $pdfLink));
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
     * Simpan data surat masuk ke Google Spreadsheet (sheet: dbSM).
     *
     * Struktur kolom dbSM (A–R):
     *   A=Timestamp | B=Kode Klas | C=Kode Surat | D=Uraian Berkas
     *   E=Kurun Waktu | F=Jumlah Folder | G=Nomor Item | H=Uraian Arsip
     *   I=Tanggal Surat | J=Jumlah Lembar | K=Tingkat Perkembangan
     *   L=Lokasi Simpan | M=Klas Keamanan | N=Tingkat Akses
     *   O=Retensi Aktif | P=Retensi Inaktif | Q=Retensi Ket | R=Link Folder
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
            'klasifikasi_keamanan'    => 'nullable|string|max:255',
            'tingkat_akses'           => 'nullable|string|max:255',
            'retensi_aktif'           => 'nullable|string|max:255',
            'retensi_inaktif'         => 'nullable|string|max:255',
            'retensi_keterangan'      => 'nullable|string|max:1000',
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
            'surat_masuk.required'             => 'File Surat Masuk (PDF) wajib diunggah.',
        ]);

        // ── Server-side kodeMap fallback (dari base Controller::applyKodeMapFallback()) ──
        $this->applyKodeMapFallback($validated);

        try {
            [$service, $spreadsheetId] = $this->getSheetService();

            $sheetRange = 'dbSM!A:R';

            // ── Upload file PDF ke Google Drive & Backup Lokal ke uploads/ ────
            $file         = $request->file('surat_masuk');
            $uploadResult = GoogleDriveService::uploadAndBackup($file, $validated['kode_klasifikasi']);
            $linkFolder   = $uploadResult['driveLink'];
            $storedFilePath = $uploadResult['filename'];

            $timestamp = now()->format('Y-m-d H:i:s');

            $values = [[
                $timestamp,
                $this->escapeFormula($validated['kode_klasifikasi']),
                $this->escapeFormula($validated['kode_surat']),
                $this->escapeFormula($validated['uraian_informasi_berkas']),
                $this->escapeFormula($validated['kurun_waktu']),
                $this->escapeFormula($validated['jumlah_folder']),
                $this->escapeFormula($validated['nomor_item_arsip']),
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
                $linkFolder,
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
                $this->safeDeleteFile(base_path('uploads/' . $storedFilePath));
            }

            return redirect()->route('arsip.index')
                ->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    /**
     * Ambil data satu baris Surat Masuk dari sheet dbSM (by rowIndex)
     * dan kembalikan sebagai JSON untuk pre-fill modal edit.
     *
     * @param int $rowIndex  Row index (0-based di array) dari dbSM
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
                'rowIndex'                => $rowIndex,
                'dbsmIndex'               => $rowIndex,
                'kode_klasifikasi'        => $dbsmRow[1]  ?? '',   // B
                'kode_surat'              => $dbsmRow[2]  ?? '',   // C
                'uraian_informasi_berkas' => $dbsmRow[3]  ?? '',   // D
                'kurun_waktu'             => $dbsmRow[4]  ?? '',   // E
                'jumlah_folder'           => $dbsmRow[5]  ?? '',   // F
                'nomor_item_arsip'        => $dbsmRow[6]  ?? '',   // G
                'uraian_informasi_arsip'  => $dbsmRow[7]  ?? '',   // H
                'tanggal_surat'           => $dbsmRow[8]  ?? '',   // I
                'jumlah_lembar'           => $dbsmRow[9]  ?? '',   // J
                'tingkat_perkembangan'    => $dbsmRow[10] ?? '',   // K
                'lokasi_simpan'           => $dbsmRow[11] ?? '',   // L
                'klasifikasi_keamanan'    => $dbsmRow[12] ?? '',   // M
                'tingkat_akses'           => $dbsmRow[13] ?? '',   // N
                'retensi_aktif'           => $dbsmRow[14] ?? '',   // O
                'retensi_inaktif'         => $dbsmRow[15] ?? '',   // P
                'retensi_keterangan'      => $dbsmRow[16] ?? '',   // Q
                'pdf_filename'            => $pdfFilename,         // R
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
     * @param int     $rowIndex  Row index dari dbSM (untuk redirect)
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

            $dbsmIndex      = (int) $validated['dbsm_index'];
            // Baris di Google Sheets dimulai dari 1 (row 1 = header)
            $sheetRowNumber = $dbsmIndex + 1;
            // Mulai dari kolom B agar timestamp di kolom A TIDAK tertimpa
            $range = "dbSM!B{$sheetRowNumber}:R{$sheetRowNumber}";

            $values = [[
                $this->escapeFormula($validated['kode_klasifikasi']),
                $this->escapeFormula($validated['kode_surat']),
                $this->escapeFormula($validated['uraian_informasi_berkas']),
                $this->escapeFormula($validated['kurun_waktu']),
                $this->escapeFormula($validated['jumlah_folder']),
                $this->escapeFormula($validated['nomor_item_arsip']),
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
                $validated['pdf_filename'] ?? '',
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
