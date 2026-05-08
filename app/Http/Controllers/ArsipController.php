<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client;
use Google\Service\Sheets;

class ArsipController extends Controller
{
    /**
     * Tampilkan halaman form arsip surat.
     */
    public function index()
    {
        return view("arsip.index");
    }

    /**
     * Simpan data arsip ke Google Spreadsheet.
     */
    public function store(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            "kode_klasifikasi" => "required|string|max:255",
            "kode_surat" => "required|string|max:255",
            "uraian_informasi_berkas" => "required|string|max:1000",
            "kurun_waktu" => "nullable|string|max:255",
            "jumlah_folder" => "nullable|string|max:255",
            "nomor_item_arsip" => "nullable|string|max:255",
            "uraian_informasi_arsip" => "nullable|string|max:1000",
            "tanggal_surat" => "required|date",
            "jumlah_lembar" => "nullable|string|max:255",
            "tingkat_perkembangan" => "nullable|string|max:255",
            "lokasi_simpan" => "nullable|string|max:255",
            "klasifikasi_keamanan" => "nullable|string|max:255",
            "tingkat_akses" => "nullable|string|max:255",
            "retensi_aktif" => "nullable|string|max:255",
            "retensi_inaktif" => "nullable|string|max:255",
            "retensi_keterangan" => "nullable|string|max:1000",
            "surat_masuk" => "required|file|mimes:pdf|max:10240",
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
            $range = "dbSM!A:R";

            // Simpan file PDF surat masuk
            $file = $request->file("surat_masuk");
            $filename =
                now()->format("Ymd_His") .
                "_" .
                preg_replace(
                    "/[^a-zA-Z0-9._-]/",
                    "_",
                    $file->getClientOriginalName(),
                );
            $file->storeAs("arsip/surat-masuk", $filename, "public");
            $filePath = "storage/arsip/surat-masuk/" . $filename;

            // Timestamp otomatis
            $timestamp = now()->format("Y-m-d H:i:s");

            // Susun data sesuai urutan kolom spreadsheet (A-R)
            $values = [
                [
                    $timestamp, // A: Timestamp
                    $validated["kode_klasifikasi"], // B: Kode Klasifikasi
                    $validated["kode_surat"], // C: Kode Surat
                    $validated["uraian_informasi_berkas"], // D: Uraian Informasi Berkas
                    $validated["kurun_waktu"] ?? "", // E: Kurun Waktu
                    $validated["jumlah_folder"] ?? "", // F: Jumlah Folder
                    $validated["nomor_item_arsip"] ?? "", // G: Nomor: Item Arsip
                    $validated["uraian_informasi_arsip"] ?? "", // H: Uraian Informasi Arsip
                    $validated["tanggal_surat"], // I: Tanggal Surat
                    $validated["jumlah_lembar"] ?? "", // J: Jumlah Lembar
                    $validated["tingkat_perkembangan"] ?? "", // K: Tingkat Perkembangan
                    $validated["lokasi_simpan"] ?? "", // L: Lokasi Simpan
                    $validated["klasifikasi_keamanan"] ?? "", // M: Klasifikasi Keamanan
                    $validated["tingkat_akses"] ?? "", // N: Tingkat Akses
                    $validated["retensi_aktif"] ?? "", // O: Retensi "Aktif"
                    $validated["retensi_inaktif"] ?? "", // P: Retensi "Inaktif"
                    $validated["retensi_keterangan"] ?? "", // Q: Retensi "Keterangan"
                    $filePath, // R: Surat Masuk (path file PDF)
                ],
            ];

            $body = new Sheets\ValueRange([
                "values" => $values,
            ]);

            $params = [
                "valueInputOption" => "RAW",
            ];

            $service->spreadsheets_values->append(
                $spreadsheetId,
                $range,
                $body,
                $params,
            );

            return redirect()
                ->back()
                ->with("success", "Data berhasil diarsipkan ke Spreadsheet!");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with("error", "Gagal menyimpan data: " . $e->getMessage())
                ->withInput();
        }
    }
}
