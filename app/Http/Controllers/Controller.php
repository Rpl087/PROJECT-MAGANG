<?php

namespace App\Http\Controllers;

use Google\Client;
use Google\Service\Sheets;

abstract class Controller
{
    /**
     * Urutan prioritas kode klasifikasi surat (digunakan untuk sorting).
     * Didefinisikan di sini agar tidak duplikat di ArsipController & SuratKeluarController.
     */
    protected const KODE_KLASIFIKASI_ORDER = [
        'PR', 'KP', 'OT', 'KU', 'HK', 'UM', 'PL',
        'UP', 'HM', 'PW', 'LT', 'DL', 'DT', 'KI',
    ];

    /**
     * Mapping kode klasifikasi → [Aktif, Inaktif, Keterangan, Keamanan, Akses].
     * Didefinisikan di base controller agar tidak duplikat di ArsipController & SuratKeluarController.
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
     * Mengamankan data dari Google Sheets Formula Injection (CSV Injection).
     * Jika string diawali dengan karakter formula (=, +, -, @),
     * kita tambahkan kutip satu (') di awal agar Google Sheets menganggapnya sebagai teks biasa.
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
     * Urutkan baris berdasarkan prefix kode klasifikasi tanpa mengubah indeks sheet.
     * Digunakan oleh ArsipController & SuratKeluarController.
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
     * Terapkan fallback kodeMap ke array validated berdasarkan kode klasifikasi.
     * Digunakan oleh store() dan update() di ArsipController & SuratKeluarController.
     *
     * @param array $validated  Array validated request (passed by reference)
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
     * Buat instance Google Sheets service dari kredensial service account.
     * Method ini digunakan oleh ArsipController, SuratKeluarController, dan DashboardController.
     *
     * @throws \RuntimeException jika file kredensial tidak ditemukan atau env tidak dikonfigurasi
     * @return array [$service (Sheets), $spreadsheetId (string)]
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
     * Buat instance Google Sheets service dengan scope READ-ONLY.
     * Digunakan untuk halaman dashboard/report yang hanya membaca data.
     * Lebih aman dari getSheetService() karena tidak bisa menulis.
     *
     * @throws \RuntimeException jika file kredensial tidak ditemukan
     * @return array [$service (Sheets), $spreadsheetId (string)]
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
     * Dapatkan sheet ID numerik berdasarkan nama sheet.
     * Digunakan untuk operasi batchUpdate (hapus baris) yang memerlukan numeric sheet ID.
     *
     * @param  Sheets $service        Instance Google Sheets service
     * @param  string $spreadsheetId  ID spreadsheet
     * @param  string $sheetName      Nama sheet yang dicari
     * @return int|null               Sheet ID numerik, atau null jika tidak ditemukan
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
     * Hapus file lokal secara aman dengan logging jika gagal.
     * Menggantikan penggunaan @unlink() yang menekan error diam-diam.
     *
     * @param  string $filePath  Path absolut file yang akan dihapus
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
