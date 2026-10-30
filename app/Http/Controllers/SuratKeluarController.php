<?php

namespace App\Http\Controllers;

use App\Services\SheetsCache;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Google\Service\Sheets;

class SuratKeluarController extends Controller
{

    private function mapDbskRow(array $row): array
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
            $row[7]  ?? '',
        ];
    }

    private function fetchAndCacheDbsk(): array
    {
        return SheetsCache::remember(
            SheetsCache::KEY_SURAT_KELUAR,
            SheetsCache::TTL_TABLE,
            function () {

                [$service, $spreadsheetId] = $this->getReadonlySheetService();
                $response = $service->spreadsheets_values->get($spreadsheetId, 'dbSK!A:R');
                $dbRows   = $response->getValues() ?? [];

                if (empty($dbRows)) {
                    return [];
                }

                $mappedRows = [$dbRows[0]];
                foreach ($dbRows as $i => $row) {
                    if ($i === 0) continue;
                    $mappedRows[] = $this->mapDbskRow($row);
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

        $filterBulan = $request->get('bulan', '');
        $filterTahun = $request->get('tahun', '');

        return view('arsip.surat-keluar', compact('rows', 'fetchError', 'isAdmin', 'filterBulan', 'filterTahun'));
    }

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

    public function destroy(int $rowIndex)
    {
        if ($rowIndex < 1) {
            return redirect()->route('surat-keluar.index')
                ->with('error', 'Indeks baris tidak valid.');
        }

        try {
            [$service, $spreadsheetId] = $this->getSheetService();

            $dbskResponse = $service->spreadsheets_values->get($spreadsheetId, 'dbSK!A:R');
            $dbskRows     = $dbskResponse->getValues() ?? [];
            $targetRow    = $dbskRows[$rowIndex] ?? null;

            if ($targetRow === null) {
                return redirect()->route('surat-keluar.index')
                    ->with('error', 'Data tidak ditemukan di sheet dbSK.');
            }

            $pdfLink = trim($targetRow[7] ?? '');

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

            SheetsCache::forgetSuratKeluar();

            return redirect()->route('surat-keluar.index')
                ->with('success', 'Data surat keluar berhasil dihapus dari Spreadsheet (dbSK).');
        } catch (\Exception $e) {
            return redirect()->route('surat-keluar.index')
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

        $this->applyKodeMapFallback($validated);
        $validated['jumlah_lembar'] = $this->formatJumlahLembar($validated['jumlah_lembar']);

        try {
            [$service, $spreadsheetId] = $this->getSheetService();
            $range = 'dbSK!A:R';

            $file         = $request->file('surat_keluar');
            $uploadResult = GoogleDriveService::uploadAndBackup($file, $validated['kode_klasifikasi']);
            $filePath     = $uploadResult['driveLink'];
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
                $filePath,
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
            $params = ['valueInputOption' => 'RAW', 'insertDataOption' => 'INSERT_ROWS'];

            $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);

            SheetsCache::forgetSuratKeluar();

            return redirect()->route('surat-keluar.index')
                ->with('success', 'Data surat keluar berhasil diarsipkan ke Spreadsheet!');
        } catch (\Exception $e) {

            if ($storedFilePath !== null) {
                $this->safeDeleteFile(base_path('uploads/' . $storedFilePath));
            }

            if (!empty($filePath) && str_starts_with($filePath, 'http')) {
                GoogleDriveService::deleteFile($filePath);
            }

            return redirect()->route('surat-keluar.index')
                ->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

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
                'kode_klasifikasi'        => $dbskRow[1]  ?? '',
                'kode_surat'              => $dbskRow[2]  ?? '',
                'uraian_informasi_berkas' => $dbskRow[3]  ?? '',
                'kurun_waktu'             => $dbskRow[4]  ?? '',
                'jumlah_folder'           => $dbskRow[5]  ?? '',
                'nomor_item_arsip'        => $dbskRow[6]  ?? '',
                'pdf_filename'            => $pdfFilename,
                'uraian_informasi_arsip'  => $dbskRow[8]  ?? '',
                'tanggal_surat'           => $dbskRow[9]  ?? '',
                'jumlah_lembar'           => $this->formatJumlahLembar($dbskRow[10] ?? ''),
                'tingkat_perkembangan'    => $dbskRow[11] ?? '',
                'lokasi_simpan'           => $dbskRow[12] ?? '',
                'klasifikasi_keamanan'    => $dbskRow[13] ?? '',
                'tingkat_akses'           => $dbskRow[14] ?? '',
                'retensi_aktif'           => $dbskRow[15] ?? '',
                'retensi_inaktif'         => $dbskRow[16] ?? '',
                'retensi_keterangan'      => $dbskRow[17] ?? '',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal memuat data: ' . $e->getMessage()], 500);
        }
    }

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

        $validated['jumlah_lembar'] = $this->formatJumlahLembar($validated['jumlah_lembar']);

        try {
            [$service, $spreadsheetId] = $this->getSheetService();

            $dbskIndex      = (int) $validated['dbsk_index'];
            $sheetRowNumber = $dbskIndex + 1;

            $pdfFilename = trim($validated['pdf_filename'] ?? '');
            if (empty($pdfFilename)) {
                $existingRange    = "dbSK!H{$sheetRowNumber}";
                $existingResponse = $service->spreadsheets_values->get($spreadsheetId, $existingRange);
                $existingValues   = $existingResponse->getValues() ?? [];
                $pdfFilename      = trim($existingValues[0][0] ?? '');
            }

            if (!empty($pdfFilename)) {
                $pdfFilename = GoogleDriveService::renameFile($pdfFilename, $validated['kode_klasifikasi']);
            }

            $range = "dbSK!B{$sheetRowNumber}:R{$sheetRowNumber}";

            $values = [[
                $this->escapeFormula($validated['kode_klasifikasi']),
                $this->escapeFormula($validated['kode_surat']),
                $this->escapeFormula($validated['uraian_informasi_berkas']),
                $this->escapeFormula($validated['kurun_waktu']),
                $this->escapeFormula($validated['jumlah_folder']),
                $this->escapeFormula($validated['nomor_item_arsip']),
                $pdfFilename,
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

            SheetsCache::forgetSuratKeluar();

            return redirect()->route('surat-keluar.index')
                ->with('success', 'Data surat keluar berhasil diperbarui di Spreadsheet (dbSK).');
        } catch (\Exception $e) {
            return redirect()->route('surat-keluar.index')
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
