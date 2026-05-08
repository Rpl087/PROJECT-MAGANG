<?php
/**
 * End-to-End Integration Test
 * Jalankan setelah service account ditambahkan ke editor proteksi dbSM:
 *   php _test_e2e.php
 *
 * Jika PASSED, hapus baris test dari spreadsheet lalu hapus file ini.
 */

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Google\Client;
use Google\Service\Sheets;

$client = new Client();
$client->setAuthConfig(base_path(env('GOOGLE_SERVICE_ACCOUNT_JSON')));
$client->addScope(Sheets::SPREADSHEETS);

$service       = new Sheets($client);
$spreadsheetId = env('GOOGLE_SHEET_ID');

echo "=== End-to-End Write Test ===\n\n";

$timestamp = now()->format('Y-m-d H:i:s');

$values = [[
    $timestamp,
    'TEST-KK',
    '00.00',
    '[TEST] Uji integrasi otomatis dari Laravel — hapus baris ini',
    '2025',
    '1',
    '001',
    'Uraian arsip uji coba end-to-end',
    '2025-05-09',
    '1 Lembar',
    'Asli',
    'Rak TEST',
    'Biasa',
    'Terbuka',
    '2 Tahun',
    '5 Tahun',
    'Test keterangan retensi',
    'Ya',
]];

$body   = new Sheets\ValueRange(['values' => $values]);
$params = ['valueInputOption' => 'RAW'];

try {
    $result = $service->spreadsheets_values->append(
        $spreadsheetId, 'dbSM!A:R', $body, $params
    );

    $updated = $result->getUpdates();
    echo "[PASSED] Data berhasil ditulis ke spreadsheet!\n";
    echo "  Range   : " . $result->getTableRange() . "\n";
    echo "  Rows    : " . $updated->getUpdatedRows() . "\n";
    echo "  Columns : " . $updated->getUpdatedColumns() . "\n";
    echo "  Cells   : " . $updated->getUpdatedCells() . "\n";
    echo "\nSilakan periksa sheet 'dbSM' dan hapus baris test tersebut.\n";
    echo "Kemudian hapus file _test_e2e.php ini.\n";

} catch (Exception $e) {
    $msg = $e->getMessage();

    if (str_contains($msg, 'protected')) {
        echo "[BLOCKED] Sheet masih terproteksi.\n";
        echo "Tambahkan email service account ke editor proteksi 'DATA MENTAH':\n";
        echo "  arsip-bpsdmp@enduring-branch-467401-p3.iam.gserviceaccount.com\n";
    } elseif (str_contains($msg, '403') || str_contains($msg, 'permission')) {
        echo "[BLOCKED] Akses ditolak (403). Pastikan spreadsheet di-share ke:\n";
        echo "  arsip-bpsdmp@enduring-branch-467401-p3.iam.gserviceaccount.com\n";
        echo "  dengan role: Editor\n";
    } else {
        echo "[FAILED] Error tidak dikenal:\n";
        echo $msg . "\n";
    }
}
