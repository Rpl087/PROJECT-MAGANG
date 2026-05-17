<?php

namespace App\Http\Controllers;

use Google\Client;
use Google\Service\Sheets;

class DashboardController extends Controller
{
    /**
     * Ambil data dari Google Sheets dan kirim ke view dashboard.
     */
    public function index()
    {
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
            $client->addScope(Sheets::SPREADSHEETS_READONLY);

            $service       = new Sheets($client);
            $spreadsheetId = env('GOOGLE_SHEET_ID');

            // ── Baca sheet dbSM (Surat Masuk) ──────────────────────────────
            // Kolom: A=Timestamp, B=Kode Klasifikasi, I=Tanggal Surat
            $smResponse = $service->spreadsheets_values->get($spreadsheetId, 'dbSM!A:R');
            $smRows     = $smResponse->getValues() ?? [];

            // ── Baca sheet dbSK (Surat Keluar) ─────────────────────────────
            // Kolom: A=Timestamp, C=Kode Klasifikasi, K=Tanggal Surat
            $skResponse = $service->spreadsheets_values->get($spreadsheetId, 'dbSK!A:S');
            $skRows     = $skResponse->getValues() ?? [];

            // Hapus baris header (baris pertama)
            if (!empty($smRows)) array_shift($smRows);
            if (!empty($skRows)) array_shift($skRows);

            $currentYear  = (int) date('Y');
            $currentMonth = (int) date('m');

            // ── Proses Surat Masuk ─────────────────────────────────────────
            // Index kolom: B=1 (Kode Klasifikasi), I=8 (Tanggal Surat)
            $smData = $this->processRows($smRows, dateColIndex: 8, klasifikasiColIndex: 1);

            // ── Proses Surat Keluar ────────────────────────────────────────
            // Index kolom: C=2 (Kode Klasifikasi), K=10 (Tanggal Surat)
            $skData = $this->processRows($skRows, dateColIndex: 10, klasifikasiColIndex: 2);

            // ── Rekap Bulanan (12 bulan dalam tahun berjalan) ──────────────
            $monthlyRecap = [];
            $bulanNames   = [
                1  => 'Januari',  2  => 'Februari', 3  => 'Maret',
                4  => 'April',    5  => 'Mei',       6  => 'Juni',
                7  => 'Juli',     8  => 'Agustus',   9  => 'September',
                10 => 'Oktober',  11 => 'November',  12 => 'Desember',
            ];

            $totalSMYear = 0;
            $totalSKYear = 0;

            for ($m = 1; $m <= 12; $m++) {
                $key      = $currentYear . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
                $smCount  = $smData['monthly'][$key] ?? 0;
                $skCount  = $skData['monthly'][$key] ?? 0;
                $totalSMYear += $smCount;
                $totalSKYear += $skCount;

                $monthlyRecap[] = [
                    'bulan'    => $bulanNames[$m],
                    'sm_count' => $smCount,
                    'sk_count' => $skCount,
                    'total'    => $smCount + $skCount,
                ];
            }

            // ── Pie: Klasifikasi Surat Masuk bulan ini ─────────────────────
            $currentMonthKey = $currentYear . '-' . str_pad($currentMonth, 2, '0', STR_PAD_LEFT);

            $smPieBulan  = $smData['klasifikasiMonthly'][$currentMonthKey] ?? [];
            $skPieBulan  = $skData['klasifikasiMonthly'][$currentMonthKey] ?? [];
            $smPieTahun  = $smData['klasifikasiYearly'][$currentYear]      ?? [];
            $skPieTahun  = $skData['klasifikasiYearly'][$currentYear]      ?? [];

            return view('dashboard.index', compact(
                'monthlyRecap',
                'totalSMYear',
                'totalSKYear',
                'smPieBulan',
                'skPieBulan',
                'smPieTahun',
                'skPieTahun',
                'currentYear',
                'currentMonth',
                'bulanNames',
            ));
        } catch (\Exception $e) {
            return view('dashboard.index', [
                'error'        => 'Gagal memuat data: ' . $e->getMessage(),
                'monthlyRecap' => [],
                'totalSMYear'  => 0,
                'totalSKYear'  => 0,
                'smPieBulan'   => [],
                'skPieBulan'   => [],
                'smPieTahun'   => [],
                'skPieTahun'   => [],
                'currentYear'  => (int) date('Y'),
                'currentMonth' => (int) date('m'),
                'bulanNames'   => [],
            ]);
        }
    }

    /**
     * Memproses baris spreadsheet dan mengelompokkan per bulan & per tahun.
     *
     * @param array $rows              Baris data (sudah tanpa header)
     * @param int   $dateColIndex      Indeks kolom tanggal surat (0-based)
     * @param int   $klasifikasiColIndex Indeks kolom kode klasifikasi (0-based)
     */
    private function processRows(array $rows, int $dateColIndex, int $klasifikasiColIndex): array
    {
        $monthly              = [];   // ['YYYY-MM' => count]
        $klasifikasiMonthly   = [];   // ['YYYY-MM' => ['KODE' => count]]
        $klasifikasiYearly    = [];   // [YYYY => ['KODE' => count]]

        foreach ($rows as $row) {
            // Ambil tanggal surat (bisa kosong)
            $rawDate = $row[$dateColIndex] ?? '';
            if (empty($rawDate)) continue;

            // Parse tanggal (mendukung nama bulan Indonesia & Inggris)
            $ts = $this->parseDate($rawDate);
            if ($ts === false) continue;

            $year  = (int) date('Y', $ts);
            $month = (int) date('m', $ts);
            $key   = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);

            // Hitung per bulan
            $monthly[$key] = ($monthly[$key] ?? 0) + 1;

            // Kode klasifikasi
            $kode = trim($row[$klasifikasiColIndex] ?? 'Tidak Diketahui');
            if (empty($kode)) $kode = 'Tidak Diketahui';

            // Klasifikasi per bulan
            $klasifikasiMonthly[$key][$kode] = ($klasifikasiMonthly[$key][$kode] ?? 0) + 1;

            // Klasifikasi per tahun
            $klasifikasiYearly[$year][$kode] = ($klasifikasiYearly[$year][$kode] ?? 0) + 1;
        }

        return compact('monthly', 'klasifikasiMonthly', 'klasifikasiYearly');
    }

    /**
     * Parse tanggal dari berbagai format, termasuk nama bulan Indonesia.
     *
     * Format yang didukung:
     *   - "11 Mei 2026"      → tanggal bulan(id) tahun
     *   - "11 May 2026"      → tanggal bulan(en) tahun
     *   - "2026-05-11"       → YYYY-MM-DD
     *   - "11/05/2026"       → DD/MM/YYYY
     *   - "11-05-2026"       → DD-MM-YYYY
     *
     * @return int|false  Unix timestamp atau false jika gagal
     */
    private function parseDate(string $raw): int|false
    {
        $raw = trim($raw);
        if (empty($raw)) return false;

        // Tabel konversi nama bulan Indonesia → Inggris
        $idToEn = [
            'januari'   => 'January',
            'februari'  => 'February',
            'maret'     => 'March',
            'april'     => 'April',
            'mei'       => 'May',
            'juni'      => 'June',
            'juli'      => 'July',
            'agustus'   => 'August',
            'september' => 'September',
            'oktober'   => 'October',
            'november'  => 'November',
            'desember'  => 'December',
        ];

        // Ganti nama bulan Indonesia (case-insensitive) dengan bahasa Inggris
        $normalized = preg_replace_callback(
            '/\b(' . implode('|', array_keys($idToEn)) . ')\b/ui',
            fn($m) => $idToEn[strtolower($m[1])],
            $raw
        );

        // Format DD/MM/YYYY atau DD-MM-YYYY → ubah ke YYYY-MM-DD agar strtotime aman
        if (preg_match('/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/', $normalized, $parts)) {
            $normalized = "{$parts[3]}-{$parts[2]}-{$parts[1]}";
        }

        $ts = strtotime($normalized);
        return ($ts !== false && $ts > 0) ? $ts : false;
    }
}
