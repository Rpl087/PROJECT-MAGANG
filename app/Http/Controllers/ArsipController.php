<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client;
use Google\Service\Sheets;

class ArsipController extends Controller
{
    /**
     * Buat instance Google Sheets service.
     */
    private function getSheetService(): array
    {
        $client = new Client();
        $client->setAuthConfig(base_path(env('GOOGLE_SERVICE_ACCOUNT_JSON')));
        $client->addScope(Sheets::SPREADSHEETS);
        $service       = new Sheets($client);
        $spreadsheetId = env('GOOGLE_SHEET_ID');
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
     * - Admin   : membaca dari sheet "SURAT MASUK" dan dapat menghapus baris
     * - Internship: hanya menampilkan form input (rows kosong, tanpa tabel)
     */
    public function index()
    {
        $rows       = [];
        $fetchError = null;
        $isAdmin    = auth()->user()?->isAdmin() ?? false;

        if ($isAdmin) {
            try {
                [$service, $spreadsheetId] = $this->getSheetService();
                $response = $service->spreadsheets_values->get($spreadsheetId, 'SURAT MASUK!A:Q');
                $values   = $response->getValues() ?? [];

                // Skip baris header (index 0), simpan data beserta index aslinya di sheet
                // Hanya tampilkan baris yang punya Tanggal Surat (col H/index 7) terisi
                foreach ($values as $i => $row) {
                    if ($i === 0) continue;
                    $tanggal = trim($row[7] ?? '');
                    // Lewati baris tanpa tanggal — indikator baris kosong/invalid
                    if (empty($tanggal)) continue;
                    $rows[] = ['index' => $i, 'data' => $row];
                }
            } catch (\Exception $e) {
                $fetchError = 'Gagal memuat data dari Google Sheets: ' . $e->getMessage();
            }
        }

        return view('arsip.index', compact('rows', 'fetchError', 'isAdmin'));
    }

    /**
     * Hapus baris surat masuk dari Google Sheets (sheet: SURAT MASUK) berdasarkan row index (0-based).
     */
    public function destroy(int $rowIndex)
    {
        try {
            [$service, $spreadsheetId] = $this->getSheetService();
            $sheetId = $this->getSheetId($service, $spreadsheetId, 'SURAT MASUK');

            if ($sheetId === null) {
                return redirect()->route('arsip.index')
                    ->with('error', 'Sheet "SURAT MASUK" tidak ditemukan di Spreadsheet.');
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

            return redirect()->route('arsip.index')
                ->with('success', 'Data surat masuk berhasil dihapus dari Spreadsheet.');
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
        // Validasi input (sesuai kolom SURAT MASUK — tanpa Kode Surat & Timestamp)
        $validated = $request->validate([
            'kode_klasifikasi'        => 'required|string|max:255',
            'uraian_informasi_berkas' => 'required|string|max:1000',
            'kurun_waktu'             => 'required|string|max:255',
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
            'surat_masuk'             => 'required|file|mimes:pdf|max:10240',
        ], [
            'kode_klasifikasi.required'        => 'Kode Klasifikasi wajib diisi.',
            'uraian_informasi_berkas.required' => 'Uraian Informasi Berkas wajib diisi.',
            'kurun_waktu.required'             => 'Kurun Waktu wajib diisi.',
            'jumlah_folder.required'           => 'Jumlah Folder wajib diisi.',
            'nomor_item_arsip.required'        => 'Nomor Item Arsip wajib diisi.',
            'nomor_item_arsip.numeric'         => 'Nomor Item Arsip harus berupa angka.',
            'nomor_item_arsip.min'             => 'Nomor Item Arsip minimal 1.',
            'uraian_informasi_arsip.required'  => 'Uraian Informasi Arsip wajib diisi.',
            'tanggal_surat.required'           => 'Tanggal Surat wajib diisi.',
            'jumlah_lembar.required'           => 'Jumlah Lembar wajib diisi.',
            'tingkat_perkembangan.required'    => 'Tingkat Perkembangan wajib diisi.',
            'lokasi_simpan.required'           => 'Lokasi Simpan wajib diisi.',
            'klasifikasi_keamanan.required'    => 'Klasifikasi Keamanan wajib dipilih.',
            'tingkat_akses.required'           => 'Tingkat Akses wajib dipilih.',
            'retensi_aktif.required'           => 'Retensi Aktif wajib diisi.',
            'retensi_inaktif.required'         => 'Retensi Inaktif wajib diisi.',
            'retensi_keterangan.required'      => 'Retensi Keterangan wajib diisi.',
            'surat_masuk.required'             => 'File Surat Masuk (PDF) wajib diunggah.',
        ]);

        try {
            $client = new Client();
            $client->setAuthConfig(base_path(env('GOOGLE_SERVICE_ACCOUNT_JSON')));
            $client->addScope(Sheets::SPREADSHEETS);

            $service       = new Sheets($client);
            $spreadsheetId = env('GOOGLE_SHEET_ID');
            $sheetRange    = 'SURAT MASUK!A:Q';

            // ── Hitung NO otomatis (jumlah baris yang ada - 1 untuk header + 1 = count baris data) ─
            $existing = $service->spreadsheets_values->get($spreadsheetId, $sheetRange);
            $allRows  = $existing->getValues() ?? [];
            // allRows[0] = baris header; data mulai index 1; next NO = jumlah baris data + 1
            $nextNo   = max(1, count($allRows)); // count sudah termasuk header, jadi next NO = count

            // ── Upload file PDF → simpan URL sebagai Link Folder (kolom Q) ────
            $file       = $request->file('surat_masuk');
            $filename   = now()->format('Ymd_His') . '_' .
                          preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $file->storeAs('arsip/surat-masuk', $filename, 'public');
            $linkFolder = route('pdf.view', ['type' => 'surat-masuk', 'filename' => $filename]);

            // ── Susun data sesuai kolom SURAT MASUK (A–Q) ────────────────────
            $values = [[
                $nextNo,                               // A: NO
                $validated['kode_klasifikasi'],        // B: Kode Klasifikasi
                $validated['uraian_informasi_berkas'], // C: Uraian Informasi Berkas
                $validated['kurun_waktu'],             // D: Kurun Waktu
                $validated['jumlah_folder'],           // E: Jumlah Folder
                $validated['nomor_item_arsip'],        // F: Nomor: Item Arsip
                $validated['uraian_informasi_arsip'],  // G: Uraian Informasi Arsip
                $validated['tanggal_surat'],           // H: Tanggal Surat
                $validated['jumlah_lembar'],           // I: Jumlah Lembar
                $validated['tingkat_perkembangan'],    // J: Tingkat Perkembangan
                $validated['lokasi_simpan'],           // K: Lokasi Simpan
                $validated['klasifikasi_keamanan'],    // L: Klasifikasi Keamanan
                $validated['tingkat_akses'],           // M: Tingkat Akses
                $validated['retensi_aktif'],           // N: Retensi "Aktif"
                $validated['retensi_inaktif'],         // O: Retensi "Inaktif"
                $validated['retensi_keterangan'],      // P: Retensi "Keterangan"
                $linkFolder,                           // Q: Link Folder
            ]];

            $body   = new Sheets\ValueRange(['values' => $values]);
            $params = ['valueInputOption' => 'RAW', 'insertDataOption' => 'INSERT_ROWS'];

            $service->spreadsheets_values->append($spreadsheetId, $sheetRange, $body, $params);

            return redirect()->back()->with('success', 'Data Anda telah terkirim');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menyimpan data: ' . $e->getMessage())
                ->withInput();
        }
    }
}
