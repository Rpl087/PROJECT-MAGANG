<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client;
use Google\Service\Sheets;

class SuratKeluarController extends Controller
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
     * Tampilkan daftar surat keluar dari Google Sheets + form input.
     * - Admin   : membaca dari sheet "SURAT KELUAR" dan dapat menghapus baris
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
                $response = $service->spreadsheets_values->get($spreadsheetId, 'SURAT KELUAR!A:S');
                $values   = $response->getValues() ?? [];

                // Skip baris header (index 0), simpan data beserta index aslinya di sheet
                // Hanya tampilkan baris yang punya Tanggal Surat (col K/index 10) terisi
                foreach ($values as $i => $row) {
                    if ($i === 0) continue;
                    $tanggal = trim($row[10] ?? '');
                    // Lewati baris tanpa tanggal — indikator baris kosong/invalid
                    if (empty($tanggal)) continue;
                    $rows[] = ['index' => $i, 'data' => $row];
                }
            } catch (\Exception $e) {
                $fetchError = 'Gagal memuat data dari Google Sheets: ' . $e->getMessage();
            }
        }

        return view('surat-keluar.index', compact('rows', 'fetchError', 'isAdmin'));
    }

    /**
     * Hapus baris surat keluar dari Google Sheets (sheet: SURAT KELUAR) berdasarkan row index (0-based).
     */
    public function destroy(int $rowIndex)
    {
        try {
            [$service, $spreadsheetId] = $this->getSheetService();
            $sheetId = $this->getSheetId($service, $spreadsheetId, 'SURAT KELUAR');

            if ($sheetId === null) {
                return redirect()->route('surat-keluar.index')
                    ->with('error', 'Sheet "SURAT KELUAR" tidak ditemukan di Spreadsheet.');
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

            return redirect()->route('surat-keluar.index')
                ->with('success', 'Data surat keluar berhasil dihapus dari Spreadsheet.');
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
            "email_address"           => "required|email|max:255",
            "kode_klasifikasi"        => "required|string|max:255",
            "kode_surat"              => "required|string|max:255",
            "uraian_informasi_berkas" => "required|string|max:1000",
            "kurun_waktu"             => "required|string|max:255",
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
            "email_address.required"           => "Email Address wajib diisi.",
            "email_address.email"              => "Format Email Address tidak valid.",
            "kode_klasifikasi.required"        => "Kode Klasifikasi wajib diisi.",
            "kode_surat.required"              => "Kode Surat wajib diisi.",
            "uraian_informasi_berkas.required" => "Uraian Informasi Berkas wajib diisi.",
            "kurun_waktu.required"             => "Kurun Waktu wajib diisi.",
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
            // Setup Google Client
            $client = new Client();
            $client->setAuthConfig(
                base_path(env("GOOGLE_SERVICE_ACCOUNT_JSON")),
            );
            $client->addScope(Sheets::SPREADSHEETS);

            $service = new Sheets($client);
            $spreadsheetId = env("GOOGLE_SHEET_ID");
            $range = "dbSK!A:S";

            // Simpan file PDF surat keluar
            $file = $request->file("surat_keluar");
            $filename =
                now()->format("Ymd_His") .
                "_" .
                preg_replace(
                    "/[^a-zA-Z0-9._-]/",
                    "_",
                    $file->getClientOriginalName(),
                );
            $file->storeAs("arsip/surat-keluar", $filename, "public");
            $filePath = route('pdf.view', ['type' => 'surat-keluar', 'filename' => $filename]);

            // Timestamp otomatis
            $timestamp = now()->format("Y-m-d H:i:s");

            // Susun data sesuai urutan kolom spreadsheet dbSK (A–S)
            $values = [
                [
                    $timestamp, // A: Timestamp
                    $validated["email_address"], // B: Email Address
                    $validated["kode_klasifikasi"], // C: Kode Klasifikasi
                    $validated["kode_surat"], // D: Kode Surat
                    $validated["uraian_informasi_berkas"], // E: Uraian Informasi Berkas
                    $validated["kurun_waktu"] ?? "", // F: Kurun Waktu
                    $validated["jumlah_folder"] ?? "", // G: Jumlah Folder
                    $validated["nomor_item_arsip"] ?? "", // H: Nomor: Item Arsip
                    $filePath, // I: Surat Keluar (path file PDF)
                    $validated["uraian_informasi_arsip"] ?? "", // J: Uraian Informasi Arsip
                    $validated["tanggal_surat"], // K: Tanggal Surat
                    $validated["jumlah_lembar"] ?? "", // L: Jumlah Lembar
                    $validated["tingkat_perkembangan"] ?? "", // M: Tingkat Perkembangan
                    $validated["lokasi_simpan"] ?? "", // N: Lokasi Simpan
                    $validated["klasifikasi_keamanan"] ?? "", // O: Klasifikasi Keamanan
                    $validated["tingkat_akses"] ?? "", // P: Tingkat Akses
                    $validated["retensi_aktif"] ?? "", // Q: Retensi "Aktif"
                    $validated["retensi_inaktif"] ?? "", // R: Retensi "Inaktif"
                    $validated["retensi_keterangan"] ?? "", // S: Retensi "Keterangan"
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
