<?php

namespace App\Http\Controllers;

use Google\Client;
use Google\Service\Sheets;

abstract class Controller
{

    protected const KODE_KLASIFIKASI_ORDER = [
        'PR', 'KP', 'OT', 'KU', 'HK', 'UM', 'PL',
        'UP', 'HM', 'PW', 'LT', 'DL', 'DT', 'KI',
    ];

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
