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
            "kode_klasifikasi"        => "required|string|max:255",
            "kode_surat"              => "required|string|max:255",
            "uraian_informasi_berkas" => "required|string|max:1000",
            "kurun_waktu"             => "required|string|max:255",
            "jumlah_folder"           => "required|string|max:255",
            "nomor_item_arsip"        => "required|string|max:255",
            "uraian_informasi_arsip"  => "required|string|max:1000",
            "tanggal_surat"           => "required|date",
            "jumlah_lembar"           => "required|string|max:255",
            "tingkat_perkembangan"    => "required|string|max:255",
            "lokasi_simpan"           => "required|string|max:255",
            "klasifikasi_keamanan"    => "required|string|max:255",
            "tingkat_akses"           => "required|string|max:255",
            "retensi_aktif"           => "required|string|max:255",
            "retensi_inaktif"         => "required|string|max:255",
            "retensi_keterangan"      => "required|string|max:1000",
            "surat_masuk"             => "required|file|mimes:pdf|max:10240",
        ], [
            "kode_klasifikasi.required"        => "Kode Klasifikasi wajib diisi.",
            "kode_surat.required"              => "Kode Surat wajib diisi.",
            "uraian_informasi_berkas.required" => "Uraian Informasi Berkas wajib diisi.",
            "kurun_waktu.required"             => "Kurun Waktu wajib diisi.",
            "jumlah_folder.required"           => "Jumlah Folder wajib diisi.",
            "nomor_item_arsip.required"        => "Nomor Item Arsip wajib diisi.",
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
            "surat_masuk.required"             => "File Surat Masuk (PDF) wajib diunggah.",
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
