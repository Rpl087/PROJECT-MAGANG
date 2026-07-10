<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Google\Client;
use Google\Service\Sheets;

class SuratKeluarController extends Controller
{
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
                [$service, $spreadsheetId] = $this->getSheetService();
                $response = $service->spreadsheets_values->get($spreadsheetId, 'SURAT KELUAR!A:Q');
                $values   = $response->getValues() ?? [];

                foreach ($values as $i => $row) {
                    if ($i === 0) continue;
                    $tanggal = trim($row[7] ?? '');
                    if (empty($tanggal)) continue;
                    $rows[] = ['index' => $i, 'data' => $row];
                }
            } catch (\Exception $e) {
                $fetchError = 'Gagal memuat data dari Google Sheets: ' . $e->getMessage();
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
            [$service, $spreadsheetId] = $this->getSheetService();
            $response = $service->spreadsheets_values->get($spreadsheetId, 'SURAT KELUAR!A:Q');
            $values   = $response->getValues() ?? [];

            foreach ($values as $i => $row) {
                if ($i === 0) continue;
                $tanggal = trim($row[7] ?? '');
                if (empty($tanggal)) continue;
                $rows[] = ['index' => $i, 'data' => $row];
            }
        } catch (\Exception $e) {
            $fetchError = 'Gagal memuat data dari Google Sheets: ' . $e->getMessage();
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

            // Fallback: jika PDF link kosong atau tidak ditemukan, gunakan rowIndex langsung
            if ($dbskRowIndex === null) {
                Log::warning('[SK destroy] Fallback: PDF link tidak cocok, gunakan rowIndex=' . $rowIndex . ' langsung ke dbSK.');
                $dbskRowIndex = $rowIndex;
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
            "klasifikasi_keamanan"    => "required|string|max:255",
            "tingkat_akses"           => "required|string|max:255",
            "retensi_aktif"           => "required|string|max:255",
            "retensi_inaktif"         => "required|string|max:255",
            "retensi_keterangan"      => "required|string|max:1000",
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
            "klasifikasi_keamanan.required"    => "Klasifikasi Keamanan wajib dipilih.",
            "tingkat_akses.required"           => "Tingkat Akses wajib dipilih.",
            "retensi_aktif.required"           => "Retensi Aktif wajib diisi.",
            "retensi_inaktif.required"         => "Retensi Inaktif wajib diisi.",
            "retensi_keterangan.required"      => "Retensi Keterangan wajib diisi.",
        ]);

        try {
            [$service, $spreadsheetId] = $this->getSheetService();
            $range = "dbSK!A:R";

            // Simpan file PDF surat keluar dengan nama pendek berisi kode klasifikasi
            $file = $request->file("surat_keluar");

            // Format: SK_{KodeKlas}_{KodeSurat}_{Ymd_His}.pdf
            $kodeKlasSlug  = preg_replace('/[^a-zA-Z0-9]/', '', $validated["kode_klasifikasi"]);
            $kodeSuratSlug = preg_replace('/[^a-zA-Z0-9]/', '', $validated["kode_surat"]);
            $filename = 'SK_' . strtoupper($kodeKlasSlug) . '_' . $kodeSuratSlug . '_' . now()->format("Ymd_His") . '.pdf';

            $file->storeAs("arsip/surat-keluar", $filename, "public");
            // Simpan hanya filename — URL di-generate dinamis saat tampil
            $filePath = $filename;

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
                    $validated["kode_klasifikasi"],             // B(1):  Kode Klasifikasi
                    $validated["kode_surat"],                   // C(2):  Kode Surat
                    $validated["uraian_informasi_berkas"],      // D(3):  Uraian Informasi Berkas
                    $validated["kurun_waktu"] ?? "",            // E(4):  Kurun Waktu
                    $validated["jumlah_folder"] ?? "",          // F(5):  Jumlah Folder
                    $validated["nomor_item_arsip"] ?? "",       // G(6):  Nomor: Item Arsip
                    $filePath,                                  // H(7):  Surat Keluar (PDF)
                    $validated["uraian_informasi_arsip"] ?? "", // I(8):  Uraian Informasi Arsip
                    $validated["tanggal_surat"],                // J(9):  Tanggal Surat
                    $validated["jumlah_lembar"] ?? "",          // K(10): Jumlah Lembar
                    $validated["tingkat_perkembangan"] ?? "",   // L(11): Tingkat Perkembangan
                    $validated["lokasi_simpan"] ?? "",          // M(12): Lokasi Simpan
                    $validated["klasifikasi_keamanan"] ?? "",   // N(13): Klasifikasi Keamanan
                    $validated["tingkat_akses"] ?? "",          // O(14): Tingkat Akses
                    $validated["retensi_aktif"] ?? "",          // P(15): Retensi "Aktif"
                    $validated["retensi_inaktif"] ?? "",        // Q(16): Retensi "Inaktif"
                    $validated["retensi_keterangan"],           // R(17): Retensi "Keterangan"
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

            return redirect()
                ->back()
                ->with(
                    "success",
                    "Data surat keluar berhasil diarsipkan ke Spreadsheet!",
                );
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with("error", "Gagal menyimpan data: " . $e->getMessage())
                ->withInput();
        }
    }
}
