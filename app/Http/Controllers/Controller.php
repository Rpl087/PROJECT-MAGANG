<?php

namespace App\Http\Controllers;

use Google\Client;
use Google\Service\Sheets;

abstract class Controller
{
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
}
