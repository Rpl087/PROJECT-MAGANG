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
        $credPath = env('GOOGLE_SERVICE_ACCOUNT_JSON');
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
    public function index()
    {
        $rows       = [];
        $fetchError = null;
        $isAdmin    = auth()->user()?->isAdmin() ?? false;

        if ($isAdmin) {
            try {
                [$service, $spreadsheetId] = $this->getSheetService();
                // Baca dari sheet tampilan "SURAT MASUK" (sudah di-combine 1 baris per entri)
                // HANYA untuk tampilan tabel ini — store() & destroy() tetap menarget dbSM
                $response = $service->spreadsheets_values->get($spreadsheetId, 'SURAT MASUK!A:Q');
                $values   = $response->getValues() ?? [];

                // Skip baris header (index 0), simpan data beserta index aslinya di sheet
                // Filter: hanya tampilkan baris yang punya Tanggal Surat (col H/index 7) terisi
                foreach ($values as $i => $row) {
                    if ($i === 0) continue;
                    $tanggal = trim($row[7] ?? ''); // H: Tanggal Surat (index 7 di SURAT MASUK)
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

            // PDF link ada di kolom R (index 17) di sheet dbSM
            $pdfLink      = trim($targetRow[17] ?? '');
            $dbsmRowIndex = $rowIndex; // default: pakai langsung

            // Konfirmasi dengan lookup berdasarkan PDF link (jika ada)
            if (!empty($pdfLink)) {
                foreach ($dbsmRows as $i => $row) {
                    if ($i === 0) continue; // skip header
                    if (trim($row[17] ?? '') === $pdfLink) {
                        $dbsmRowIndex = $i;
                        break;
                    }
                }
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
            'klasifikasi_keamanan'    => 'required|string|max:255',
            'tingkat_akses'           => 'required|string|max:255',
            'retensi_aktif'           => 'required|string|max:255',
            'retensi_inaktif'         => 'required|string|max:255',
            'retensi_keterangan'      => 'required|string|max:1000',
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
            'klasifikasi_keamanan.required'    => 'Klasifikasi Keamanan wajib dipilih.',
            'tingkat_akses.required'           => 'Tingkat Akses wajib dipilih.',
            'retensi_aktif.required'           => 'Retensi Aktif wajib diisi.',
            'retensi_inaktif.required'         => 'Retensi Inaktif wajib diisi.',
            'retensi_keterangan.required'      => 'Retensi Keterangan wajib diisi.',
            'surat_masuk.required'             => 'File Surat Masuk (PDF) wajib diunggah.',
        ]);

        try {
            $credPath = env('GOOGLE_SERVICE_ACCOUNT_JSON');
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
            $spreadsheetId = env('GOOGLE_SHEET_ID');
            // Target: sheet dbSM (sumber data asli)
            $sheetRange    = 'dbSM!A:R';

            // ── Upload file PDF → simpan URL sebagai Link Folder (kolom R) ────
            $file       = $request->file('surat_masuk');
            $filename   = now()->format('Ymd_His') . '_' .
                          preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $file->storeAs('arsip/surat-masuk', $filename, 'public');
            // Simpan hanya filename — URL di-generate dinamis saat tampil
            $linkFolder = $filename;

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
                $validated['kode_klasifikasi'],            // B: Kode Klasifikasi
                $validated['kode_surat'],                  // C: Kode Surat
                $validated['uraian_informasi_berkas'],     // D: Uraian Informasi Berkas
                $validated['kurun_waktu'],                 // E: Kurun Waktu
                $validated['jumlah_folder'],               // F: Jumlah Folder
                $validated['nomor_item_arsip'],            // G: Nomor: Item Arsip
                $validated['uraian_informasi_arsip'],      // H: Uraian Informasi Arsip
                $validated['tanggal_surat'],               // I: Tanggal Surat
                $validated['jumlah_lembar'],               // J: Jumlah Lembar
                $validated['tingkat_perkembangan'],        // K: Tingkat Perkembangan
                $validated['lokasi_simpan'],               // L: Lokasi Simpan
                $validated['klasifikasi_keamanan'],        // M: Klasifikasi Keamanan
                $validated['tingkat_akses'],               // N: Tingkat Akses
                $validated['retensi_aktif'],               // O: Retensi "Aktif"
                $validated['retensi_inaktif'],             // P: Retensi "Inaktif"
                $validated['retensi_keterangan'],          // Q: Retensi "Keterangan"
                $linkFolder,                               // R: Link Folder
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
