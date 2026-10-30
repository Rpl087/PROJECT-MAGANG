<?php

namespace App\Http\Controllers;

use App\Services\SheetsCache;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Google\Service\Sheets;

class ArsipController extends Controller
{

    private function mapDbsmRow(array $row): array
    {
        $kodeKlas  = trim($row[1] ?? '');
        $kodeSurat = trim($row[2] ?? '');

        return [
            $row[0]  ?? '',
            ($kodeKlas !== '' && $kodeSurat !== '')
                ? "{$kodeKlas} {$kodeSurat}"
                : ($kodeKlas ?: $kodeSurat),
            $row[3]  ?? '',
            $row[4]  ?? '',
            $row[5]  ?? '',
            $row[6]  ?? '',
            $row[7]  ?? '',
            $row[8]  ?? '',
            $row[9]  ?? '',
            $row[10] ?? '',
            $row[11] ?? '',
            $row[12] ?? '',
            $row[13] ?? '',
            $row[14] ?? '',
            $row[15] ?? '',
            $row[16] ?? '',
            $row[17] ?? '',
        ];
    }

    private function fetchAndCacheDbsm(): array
    {
        return SheetsCache::remember(
            SheetsCache::KEY_SURAT_MASUK,
            SheetsCache::TTL_TABLE,
            function () {

                [$service, $spreadsheetId] = $this->getReadonlySheetService();
                $response = $service->spreadsheets_values->get($spreadsheetId, 'dbSM!A:R');
                $dbRows   = $response->getValues() ?? [];

                if (empty($dbRows)) {
                    return [];
                }

                $mappedRows = [$dbRows[0]];
                foreach ($dbRows as $i => $row) {
                    if ($i === 0) continue;
                    $mappedRows[] = $this->mapDbsmRow($row);
                }
                return $mappedRows;
            }
        );
    }

    public function index(Request $request)
    {
        $rows       = [];
        $fetchError = null;
        $isAdmin    = auth()->user()?->hasAdminAccess() ?? false;

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
            SheetsCache::forget(SheetsCache::KEY_SURAT_MASUK);
        }

        $filterBulan = $request->get('bulan', '');
        $filterTahun = $request->get('tahun', '');

        return view('arsip.index', compact('rows', 'fetchError', 'isAdmin', 'filterBulan', 'filterTahun'));
    }

    public function indexGuest()
    {
        $rows       = [];
        $fetchError = null;

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
            SheetsCache::forget(SheetsCache::KEY_SURAT_MASUK);
        }

        return view('tamu.index', compact('rows', 'fetchError'));
    }

    public function destroy(int $rowIndex)
    {
        if ($rowIndex < 1) {
            return redirect()->route('arsip.index')
                ->with('error', 'Indeks baris tidak valid.');
        }

        try {
            [$service, $spreadsheetId] = $this->getSheetService();

            $dbsmResponse = $service->spreadsheets_values->get($spreadsheetId, 'dbSM!A:R');
            $dbsmRows     = $dbsmResponse->getValues() ?? [];
            $targetRow    = $dbsmRows[$rowIndex] ?? null;

            if ($targetRow === null) {
                return redirect()->route('arsip.index')
                    ->with('error', 'Data tidak ditemukan di sheet dbSM.');
            }

            $pdfLink = trim($targetRow[17] ?? '');

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
                        'startIndex' => $rowIndex,
                        'endIndex'   => $rowIndex + 1,
                    ],
                ],
            ]];

            $body = new Sheets\BatchUpdateSpreadsheetRequest(['requests' => $requests]);
            $service->spreadsheets->batchUpdate($spreadsheetId, $body);

            if (!empty($pdfLink)) {
                GoogleDriveService::deleteFile($pdfLink);
            }

            SheetsCache::forgetSuratMasuk();

            return redirect()->route('arsip.index')
                ->with('success', 'Data surat masuk berhasil dihapus dari Spreadsheet (dbSM).');
        } catch (\Exception $e) {
            return redirect()->route('arsip.index')
                ->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

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

        $this->applyKodeMapFallback($validated);
        $validated['jumlah_lembar'] = $this->formatJumlahLembar($validated['jumlah_lembar']);

        try {
            [$service, $spreadsheetId] = $this->getSheetService();

            $sheetRange = 'dbSM!A:R';

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

            SheetsCache::forgetSuratMasuk();

            return redirect()->route('arsip.index')
                ->with('success', 'Data surat masuk berhasil diarsipkan ke Spreadsheet!');
        } catch (\Exception $e) {

            if ($storedFilePath !== null) {
                $this->safeDeleteFile(base_path('uploads/' . $storedFilePath));
            }

            if (!empty($linkFolder) && str_starts_with($linkFolder, 'http')) {
                GoogleDriveService::deleteFile($linkFolder);
            }

            return redirect()->route('arsip.index')
                ->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

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
                'kode_klasifikasi'        => $dbsmRow[1]  ?? '',
                'kode_surat'              => $dbsmRow[2]  ?? '',
                'uraian_informasi_berkas' => $dbsmRow[3]  ?? '',
                'kurun_waktu'             => $dbsmRow[4]  ?? '',
                'jumlah_folder'           => $dbsmRow[5]  ?? '',
                'nomor_item_arsip'        => $dbsmRow[6]  ?? '',
                'uraian_informasi_arsip'  => $dbsmRow[7]  ?? '',
                'tanggal_surat'           => $dbsmRow[8]  ?? '',
                'jumlah_lembar'           => $this->formatJumlahLembar($dbsmRow[9] ?? ''),
                'tingkat_perkembangan'    => $dbsmRow[10] ?? '',
                'lokasi_simpan'           => $dbsmRow[11] ?? '',
                'klasifikasi_keamanan'    => $dbsmRow[12] ?? '',
                'tingkat_akses'           => $dbsmRow[13] ?? '',
                'retensi_aktif'           => $dbsmRow[14] ?? '',
                'retensi_inaktif'         => $dbsmRow[15] ?? '',
                'retensi_keterangan'      => $dbsmRow[16] ?? '',
                'pdf_filename'            => $pdfFilename,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal memuat data: ' . $e->getMessage()], 500);
        }
    }

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

        $validated['jumlah_lembar'] = $this->formatJumlahLembar($validated['jumlah_lembar']);

        try {
            [$service, $spreadsheetId] = $this->getSheetService();

            $dbsmIndex      = (int) $validated['dbsm_index'];

            $sheetRowNumber = $dbsmIndex + 1;

            $pdfFilename = trim($validated['pdf_filename'] ?? '');
            if (empty($pdfFilename)) {
                $existingRange    = "dbSM!R{$sheetRowNumber}";
                $existingResponse = $service->spreadsheets_values->get($spreadsheetId, $existingRange);
                $existingValues   = $existingResponse->getValues() ?? [];
                $pdfFilename      = trim($existingValues[0][0] ?? '');
            }

            if (!empty($pdfFilename)) {
                $pdfFilename = GoogleDriveService::renameFile($pdfFilename, $validated['kode_klasifikasi']);
            }

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
                $pdfFilename,
            ]];

            $body   = new Sheets\ValueRange(['values' => $values]);
            $params = ['valueInputOption' => 'RAW'];

            $service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);

            SheetsCache::forgetSuratMasuk();

            return redirect()->route('arsip.index')
                ->with('success', 'Data surat masuk berhasil diperbarui di Spreadsheet (dbSM).');
        } catch (\Exception $e) {
            return redirect()->route('arsip.index')
                ->with('error', 'Gagal memperbarui data: ' . $e->getMessage())
                ->withInput();
        }
    }

    private function formatJumlahLembar(string $value): string
    {
        $val = trim($value);
        if ($val === '') {
            return $val;
        }
        if (ctype_digit($val)) {
            return $val . ' Lembar';
        }
        if (preg_match('/^(\d+)\s*lembar$/i', $val, $matches)) {
            return $matches[1] . ' Lembar';
        }
        return $val;
    }
}
