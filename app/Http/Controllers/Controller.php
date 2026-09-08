<?php

namespace App\Http\Controllers;

use Google\Client;
use Google\Service\Sheets;

/**
 * ============================================================
 * BASE CONTROLLER - Controller Induk
 * ============================================================
 *
 * Semua controller utama (ArsipController, SuratKeluarController,
 * DashboardController) mewarisi class ini.
 *
 * BERISI:
 *   1. Konstanta KODE_KLASIFIKASI_ORDER → urutan tampilan kode
 *   2. Konstanta KODE_MAP              → nilai default retensi per kode
 *   3. Helper escapeFormula()          → cegah formula injection di Sheets
 *   4. Helper sortByKlasifikasi()      → sort data berdasar urutan KODE
 *   5. Helper applyKodeMapFallback()   → isi otomatis retensi jika kosong
 *   6. Helper getSheetService()        → koneksi Google Sheets (full access)
 *   7. Helper getReadonlySheetService()→ koneksi Google Sheets (read-only)
 *   8. Helper getSheetId()             → ambil ID numerik sheet berdasar nama
 *   9. Helper safeDeleteFile()         → hapus file lokal dengan aman
 *
 * ARSITEKTUR KONEKSI GOOGLE:
 *   - Menggunakan Service Account (bukan OAuth user).
 *   - File kredensial: path dikonfigurasi di .env → GOOGLE_SERVICE_ACCOUNT_JSON
 *   - ID Spreadsheet: GOOGLE_SHEET_ID di .env
 *   - Jika file kredensial tidak ada, akan throw RuntimeException.
 */
abstract class Controller
{

    /**
     * Urutan tampilan kode klasifikasi di tabel arsip.
     * Data akan diurutkan berdasarkan indeks array ini.
     * Kode yang tidak ada di sini akan ditempatkan di akhir.
     */
    protected const KODE_KLASIFIKASI_ORDER = [
        'PR', 'KP', 'OT', 'KU', 'HK', 'UM', 'PL',
        'UP', 'HM', 'PW', 'LT', 'DL', 'DT', 'KI',
    ];

    /**
     * Nilai default retensi & keamanan untuk setiap kode klasifikasi.
     * Format per kode: [retensi_aktif, retensi_inaktif, retensi_keterangan, klasifikasi_keamanan, tingkat_akses]
     *
     * Digunakan oleh applyKodeMapFallback() saat user tidak mengisi
     * kolom retensi secara manual. Nilai ini sesuai jadwal retensi arsip.
     */
    protected const KODE_MAP = [
        'PR' => ['2 Tahun',  '3 Tahun',  'Musnah',   'Biasa', 'Terbuka' ],
        'KP' => ['5 Tahun',  '10 Tahun', 'Permanen',  'Biasa', 'Terbatas'],
        'OT' => ['2 Tahun',  '3 Tahun',  'Musnah',   'Biasa', 'Terbuka' ],
        'KU' => ['5 Tahun',  '10 Tahun', 'Permanen',  'Biasa', 'Terbatas'],
        'HK' => ['5 Tahun',  '10 Tahun', 'Permanen',  'Biasa', 'Terbatas'],
        'UM' => ['2 Tahun',  '3 Tahun',  'Musnah',   'Biasa', 'Terbuka' ],
        'PL' => ['5 Tahun',  '10 Tahun', 'Musnah',   'Biasa', 'Terbatas'],
        'UP' => ['5 Tahun',  '10 Tahun', 'Permanen',  'Biasa', 'Terbatas'],
        'HM' => ['5 Tahun',  '10 Tahun', 'Permanen',  'Biasa', 'Terbatas'],
        'PW' => ['5 Tahun',  '10 Tahun', 'Musnah',   'Biasa', 'Terbatas'],
        'LT' => ['5 Tahun',  '10 Tahun', 'Musnah',   'Biasa', 'Terbatas'],
        'DL' => ['2 Tahun',  '3 Tahun',  'Musnah',   'Biasa', 'Terbuka' ],
        'DT' => ['5 Tahun',  '10 Tahun', 'Permanen',  'Biasa', 'Terbatas'],
        'KI' => ['5 Tahun',  '10 Tahun', 'Permanen',  'Biasa', 'Terbatas'],
    ];

    /**
     * Mencegah formula injection di Google Sheets.
     * Jika nilai dimulai dengan =, +, -, atau @, tambahkan tanda kutip (') di depan
     * agar Sheets tidak menginterpretasinya sebagai formula berbahaya.
     */
    protected function escapeFormula(mixed $value): mixed
    {
        if (is_string($value) && $value !== '') {
            $firstChar = $value[0];
            if (in_array($firstChar, ['=', '+', '-', '@'])) {
                return "'" . $value;
            }
        }
        return $value;
    }

    /**
     * Mengurutkan array $rows berdasarkan kode klasifikasi sesuai KODE_KLASIFIKASI_ORDER.
     * Jika kode sama, diurutkan berdasarkan nama kode (alfabet),
     * lalu berdasarkan nomor index baris di Spreadsheet.
     *
     * @param array $rows Array berisi elemen ['index' => int, 'data' => array]
     */
    protected function sortByKlasifikasi(array &$rows): void
    {
        $rank = array_flip(static::KODE_KLASIFIKASI_ORDER);

        usort($rows, function (array $a, array $b) use ($rank): int {
            $getKode = static function (array $item): string {
                $raw = trim((string) ($item['data'][1] ?? ''));
                return preg_match('/^([A-Za-z]+)/', $raw, $matches)
                    ? strtoupper($matches[1])
                    : '';
            };

            $kodeA = $getKode($a);
            $kodeB = $getKode($b);
            $rankA = $rank[$kodeA] ?? PHP_INT_MAX;
            $rankB = $rank[$kodeB] ?? PHP_INT_MAX;

            return ($rankA <=> $rankB)
                ?: ($kodeA <=> $kodeB)
                ?: (($a['index'] ?? 0) <=> ($b['index'] ?? 0));
        });
    }

    /**
     * Mengisi kolom retensi & keamanan secara otomatis berdasarkan KODE_MAP
     * jika user tidak mengisi field tersebut saat tambah/edit arsip.
     *
     * Field yang diisi otomatis:
     *   - retensi_aktif, retensi_inaktif, retensi_keterangan
     *   - klasifikasi_keamanan, tingkat_akses
     *
     * Kode diambil dari 2 huruf pertama kode_klasifikasi (prefix).
     * Contoh: 'KP.001' → prefix 'KP' → nilai dari KODE_MAP['KP']
     */
    protected function applyKodeMapFallback(array &$validated): void
    {
        $kodePrefix = strtoupper(preg_replace('/[^A-Za-z]/', '', $validated['kode_klasifikasi'] ?? ''));
        $kodePrefix = substr($kodePrefix, 0, 2);

        if (isset(static::KODE_MAP[$kodePrefix])) {
            $mapRow = static::KODE_MAP[$kodePrefix];
            if (empty($validated['retensi_aktif']))         $validated['retensi_aktif']        = $mapRow[0];
            if (empty($validated['retensi_inaktif']))       $validated['retensi_inaktif']      = $mapRow[1];
            if (empty($validated['retensi_keterangan']))    $validated['retensi_keterangan']   = $mapRow[2];
            if (empty($validated['klasifikasi_keamanan']))  $validated['klasifikasi_keamanan'] = $mapRow[3];
            if (empty($validated['tingkat_akses']))         $validated['tingkat_akses']        = $mapRow[4];
        }
    }

    /**
     * Membuat instance Google Sheets API dengan scope FULL (baca + tulis).
     * Digunakan untuk operasi store, update, delete.
     *
     * Membutuhkan:
     *   - GOOGLE_SERVICE_ACCOUNT_JSON di .env (path relatif ke root project)
     *   - GOOGLE_SHEET_ID di .env (ID spreadsheet target)
     *
     * @return array [Sheets $service, string $spreadsheetId]
     * @throws \RuntimeException jika kredensial tidak dikonfigurasi/tidak ada
     */
    protected function getSheetService(): array
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
     * Membuat instance Google Sheets API dengan scope READ-ONLY.
     * Digunakan untuk operasi index() dan fetchAndCache - lebih aman dan
     * tidak memerlukan izin write pada service account.
     *
     * @return array [Sheets $service, string $spreadsheetId]
     */
    protected function getReadonlySheetService(): array
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
        $client->addScope(Sheets::SPREADSHEETS_READONLY);
        $service       = new Sheets($client);
        $spreadsheetId = config('google.sheet_id');
        return [$service, $spreadsheetId];
    }

    /**
     * Mengambil ID numerik sheet (bukan nama string) dari spreadsheet.
     * Diperlukan untuk operasi deleteDimension (hapus baris) via batchUpdate.
     *
     * @param Sheets  $service       Instance Google Sheets
     * @param string  $spreadsheetId ID spreadsheet
     * @param string  $sheetName     Nama sheet (misal: 'dbSM', 'dbSK')
     * @return int|null ID numerik sheet, atau null jika tidak ditemukan
     */
    protected function getSheetId(Sheets $service, string $spreadsheetId, string $sheetName): ?int
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
     * Menghapus file dari storage lokal secara aman.
     * Jika file tidak ada, tidak melakukan apa-apa.
     * Jika gagal hapus (misal: permission denied), catat ke log warning.
     * Digunakan oleh rollback saat store() gagal.
     */
    protected function safeDeleteFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        if (!@unlink($filePath)) {
            \Illuminate\Support\Facades\Log::warning('Gagal menghapus file lokal.', [
                'path' => $filePath,
            ]);
        }
    }
}
