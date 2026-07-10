<?php

namespace App\Http\Controllers;

use App\Services\SheetsCache;
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
            $credPath = config('google.service_account_json');
            if (empty($credPath)) {
                throw new \RuntimeException('GOOGLE_SERVICE_ACCOUNT_JSON belum dikonfigurasi di .env');
            }

            $credFullPath = base_path($credPath);
            if (!file_exists($credFullPath) || !is_file($credFullPath)) {
                throw new \RuntimeException("File kredensial Google tidak ditemukan: {$credFullPath}");
            }

            // Ambil raw rows dari cache (atau fetch dari API jika cache miss)
            [$smRows, $skRows] = SheetsCache::remember(
                SheetsCache::KEY_DASHBOARD,
                SheetsCache::TTL_DASHBOARD,
                function () use ($credFullPath) {
                    $client = new Client();
                    $client->setAuthConfig($credFullPath);
                    $client->addScope(Sheets::SPREADSHEETS_READONLY);
                    $service       = new Sheets($client);
                    $spreadsheetId = config('google.sheet_id');

                    // Baca sheet dbSM (Surat Masuk)
                    $smResponse = $service->spreadsheets_values->get($spreadsheetId, 'dbSM!A:R');
                    $smRows     = $smResponse->getValues() ?? [];

                    // Baca sheet SURAT KELUAR
                    $skResponse = $service->spreadsheets_values->get($spreadsheetId, 'SURAT KELUAR!A:Q');
                    $skRows     = $skResponse->getValues() ?? [];

                    return [$smRows, $skRows];
                }
            );

            // Hapus baris header
            if (!empty($smRows)) array_shift($smRows);
            if (!empty($skRows)) array_shift($skRows);

            $currentYear  = (int) date('Y');
            $currentMonth = (int) date('m');

            // ── Proses Surat Masuk ─────────────────────────────────────────
            // B(1)=Kode Klasifikasi, I(8)=Tanggal Surat
            $smData = $this->processRows($smRows, dateColIndex: 8, klasifikasiColIndex: 1);

            // ── Proses Surat Keluar ────────────────────────────────────────
            // B(1)=Kode Klasifikasi, H(7)=Tanggal Surat — filter baris tanpa tanggal
            $skRowsFiltered = array_filter($skRows, fn($r) => !empty(trim($r[7] ?? '')));
            $skData = $this->processRows(array_values($skRowsFiltered), dateColIndex: 7, klasifikasiColIndex: 1);

            // ── Rekap Bulanan (12 bulan dalam tahun berjalan) ──────────────
            $bulanNames = [
                1  => 'Januari',  2  => 'Februari', 3  => 'Maret',
                4  => 'April',    5  => 'Mei',       6  => 'Juni',
                7  => 'Juli',     8  => 'Agustus',   9  => 'September',
                10 => 'Oktober',  11 => 'November',  12 => 'Desember',
            ];

            $monthlyRecap = [];
            $totalSMYear  = 0;
            $totalSKYear  = 0;

            for ($m = 1; $m <= 12; $m++) {
                $key     = $currentYear . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
                $smCount = $smData['monthly'][$key] ?? 0;
                $skCount = $skData['monthly'][$key] ?? 0;
                $totalSMYear += $smCount;
                $totalSKYear += $skCount;

                $monthlyRecap[] = [
                    'bulan'    => $bulanNames[$m],
                    'sm_count' => $smCount,
                    'sk_count' => $skCount,
                    'total'    => $smCount + $skCount,
                ];
            }

            // ── Data Pie Klasifikasi ────────────────────────────────────────
            $currentMonthKey = $currentYear . '-' . str_pad($currentMonth, 2, '0', STR_PAD_LEFT);

            $smPieBulan = $smData['klasifikasiMonthly'][$currentMonthKey] ?? [];
            $skPieBulan = $skData['klasifikasiMonthly'][$currentMonthKey] ?? [];
            $smPieTahun = $smData['klasifikasiYearly'][$currentYear]      ?? [];
            $skPieTahun = $skData['klasifikasiYearly'][$currentYear]      ?? [];

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
            // Hapus cache yang mungkin korup
            SheetsCache::forget(SheetsCache::KEY_DASHBOARD);
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
     * Halaman Report Rekapan Bulanan & Tahunan (printable).
     */
    public function report()
    {
        try {
            $credPath = config('google.service_account_json');
            if (empty($credPath)) {
                throw new \RuntimeException('GOOGLE_SERVICE_ACCOUNT_JSON belum dikonfigurasi di .env');
            }

            $credFullPath = base_path($credPath);
            if (!file_exists($credFullPath) || !is_file($credFullPath)) {
                throw new \RuntimeException("File kredensial Google tidak ditemukan: {$credFullPath}");
            }

            // Ambil raw rows dari cache (atau fetch dari API jika cache miss)
            [$smRows, $skRows] = SheetsCache::remember(
                SheetsCache::KEY_REPORT,
                SheetsCache::TTL_DASHBOARD,
                function () use ($credFullPath) {
                    $client = new Client();
                    $client->setAuthConfig($credFullPath);
                    $client->addScope(Sheets::SPREADSHEETS_READONLY);
                    $service       = new Sheets($client);
                    $spreadsheetId = config('google.sheet_id');

                    // Baca sheet dbSM (Surat Masuk)
                    $smResponse = $service->spreadsheets_values->get($spreadsheetId, 'dbSM!A:R');
                    $smRows     = $smResponse->getValues() ?? [];

                    // Baca sheet SURAT KELUAR
                    $skResponse = $service->spreadsheets_values->get($spreadsheetId, 'SURAT KELUAR!A:Q');
                    $skRows     = $skResponse->getValues() ?? [];

                    return [$smRows, $skRows];
                }
            );

            // Hapus baris header
            if (!empty($smRows)) array_shift($smRows);
            if (!empty($skRows)) array_shift($skRows);

            // ── Proses Surat Masuk — I(8)=Tanggal ──────────────────────────
            $smData = $this->processRows($smRows, dateColIndex: 8, klasifikasiColIndex: 1);

            // ── Proses Surat Keluar — H(7)=Tanggal ────────────────────────
            $skRowsFiltered = array_filter($skRows, fn($r) => !empty(trim($r[7] ?? '')));
            $skData = $this->processRows(array_values($skRowsFiltered), dateColIndex: 7, klasifikasiColIndex: 1);

            $bulanNames = [
                1  => 'Januari',  2  => 'Februari', 3  => 'Maret',
                4  => 'April',    5  => 'Mei',       6  => 'Juni',
                7  => 'Juli',     8  => 'Agustus',   9  => 'September',
                10 => 'Oktober',  11 => 'November',  12 => 'Desember',
            ];

            $currentYear  = (int) date('Y');
            $currentMonth = (int) date('m');

            // ── Kumpulkan semua tahun yang ada datanya ─────────────────────
            $allYears = array_unique(array_merge(
                array_keys($smData['klasifikasiYearly']),
                array_keys($skData['klasifikasiYearly'])
            ));
            sort($allYears);

            // ── Rekap Bulanan per Tahun ─────────────────────────────────────
            $rekapPerTahun = [];
            foreach ($allYears as $year) {
                $rows = [];
                $totalSM = 0;
                $totalSK = 0;
                for ($m = 1; $m <= 12; $m++) {
                    $key     = $year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
                    $smCount = $smData['monthly'][$key] ?? 0;
                    $skCount = $skData['monthly'][$key] ?? 0;
                    $totalSM += $smCount;
                    $totalSK += $skCount;
                    $rows[] = [
                        'bulan'    => $bulanNames[$m],
                        'bulan_no' => $m,
                        'sm_count' => $smCount,
                        'sk_count' => $skCount,
                        'total'    => $smCount + $skCount,
                    ];
                }
                $rekapPerTahun[$year] = [
                    'tahun'    => $year,
                    'rows'     => $rows,
                    'total_sm' => $totalSM,
                    'total_sk' => $totalSK,
                    'total'    => $totalSM + $totalSK,
                ];
            }

            // ── Rekap Tahunan (ringkasan per tahun) ───────────────────────────────────
            $kodePriority = ['PR','KP','OT','KU','HK','UM','PL','UP','HM','PW','LT','DL','DT','KI'];
            $rekapTahunan = [];
            foreach ($allYears as $year) {
                // Kumpulkan semua kode untuk tahun ini
                $smKode  = $smData['klasifikasiYearly'][$year] ?? [];
                $skKode  = $skData['klasifikasiYearly'][$year] ?? [];
                $allK    = array_unique(array_merge(array_keys($smKode), array_keys($skKode)));
                usort($allK, function ($a, $b) use ($kodePriority) {
                    $ia = array_search($a, $kodePriority); $ib = array_search($b, $kodePriority);
                    return ($ia === false ? 999 : $ia) <=> ($ib === false ? 999 : $ib);
                });
                $kodeDetail = [];
                foreach ($allK as $kode) {
                    $sm = $smKode[$kode] ?? 0;
                    $sk = $skKode[$kode] ?? 0;
                    $kodeDetail[] = ['kode' => $kode, 'sm_count' => $sm, 'sk_count' => $sk, 'total' => $sm + $sk];
                }

                $rekapTahunan[] = [
                    'tahun'      => $year,
                    'sm_count'   => $rekapPerTahun[$year]['total_sm'],
                    'sk_count'   => $rekapPerTahun[$year]['total_sk'],
                    'total'      => $rekapPerTahun[$year]['total'],
                    'kode_detail'=> $kodeDetail,
                ];
            }

            // ── Rekap Per Klasifikasi (semua tahun digabung) ───────────────
            $klasifikasiSM = [];
            foreach ($smData['klasifikasiYearly'] as $year => $kodes) {
                foreach ($kodes as $kode => $count) {
                    $klasifikasiSM[$kode] = ($klasifikasiSM[$kode] ?? 0) + $count;
                }
            }
            $klasifikasiSK = [];
            foreach ($skData['klasifikasiYearly'] as $year => $kodes) {
                foreach ($kodes as $kode => $count) {
                    $klasifikasiSK[$kode] = ($klasifikasiSK[$kode] ?? 0) + $count;
                }
            }

            // Sort berdasarkan kodePriority
            $allKodes = array_unique(array_merge(array_keys($klasifikasiSM), array_keys($klasifikasiSK)));
            usort($allKodes, function ($a, $b) use ($kodePriority) {
                $ia = array_search($a, $kodePriority);
                $ib = array_search($b, $kodePriority);
                $ia = $ia === false ? 999 : $ia;
                $ib = $ib === false ? 999 : $ib;
                return $ia <=> $ib;
            });

            $rekapKlasifikasi = [];
            foreach ($allKodes as $kode) {
                $sm = $klasifikasiSM[$kode] ?? 0;
                $sk = $klasifikasiSK[$kode] ?? 0;
                $rekapKlasifikasi[] = [
                    'kode'     => $kode,
                    'sm_count' => $sm,
                    'sk_count' => $sk,
                    'total'    => $sm + $sk,
                ];
            }

            return view('dashboard.report', compact(
                'rekapPerTahun',
                'rekapTahunan',
                'rekapKlasifikasi',
                'allYears',
                'currentYear',
                'currentMonth',
                'bulanNames',
            ));
        } catch (\Exception $e) {
            // Hapus cache yang mungkin korup
            SheetsCache::forget(SheetsCache::KEY_REPORT);
            return view('dashboard.report', [
                'error'            => 'Gagal memuat data: ' . $e->getMessage(),
                'rekapPerTahun'    => [],
                'rekapTahunan'     => [],
                'rekapKlasifikasi' => [],
                'allYears'         => [],
                'currentYear'      => (int) date('Y'),
                'currentMonth'     => (int) date('m'),
                'bulanNames'       => [],
            ]);
        }
    }

    /**
     * Memproses baris spreadsheet dan mengelompokkan per bulan & per tahun.
     *
     * @param array $rows                 Baris data (sudah tanpa header)
     * @param int   $dateColIndex         Indeks kolom tanggal surat (0-based)
     * @param int   $klasifikasiColIndex  Indeks kolom kode klasifikasi (0-based)
     */
    private function processRows(array $rows, int $dateColIndex, int $klasifikasiColIndex): array
    {
        $monthly            = [];  // ['YYYY-MM' => count]
        $klasifikasiMonthly = [];  // ['YYYY-MM' => ['KODE' => count]]
        $klasifikasiYearly  = [];  // [YYYY => ['KODE' => count]]

        foreach ($rows as $row) {
            $rawDate = $row[$dateColIndex] ?? '';
            if (empty($rawDate)) continue;

            $ts = $this->parseDate($rawDate);
            if ($ts === false) continue;

            $year  = (int) date('Y', $ts);
            $month = (int) date('m', $ts);
            $key   = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);

            $monthly[$key] = ($monthly[$key] ?? 0) + 1;

            $kode = trim($row[$klasifikasiColIndex] ?? '');
            // Abaikan formula error Google Sheets (misal #N/A, #REF!) untuk grouping
            if (empty($kode) || str_starts_with($kode, '#')) {
                $kode = 'Tidak Diketahui';
            } else {
                // Normalisasi: ambil hanya prefix huruf di awal kode
                // Contoh: "KP 03" → "KP", "PR.02" → "PR", "KP/01" → "KP"
                if (preg_match('/^([A-Za-z]+)/', $kode, $m)) {
                    $kode = strtoupper($m[1]);
                }
            }

            $klasifikasiMonthly[$key][$kode] = ($klasifikasiMonthly[$key][$kode] ?? 0) + 1;
            $klasifikasiYearly[$year][$kode] = ($klasifikasiYearly[$year][$kode] ?? 0) + 1;

        }

        return compact('monthly', 'klasifikasiMonthly', 'klasifikasiYearly');
    }

    /**
     * Parse tanggal dari berbagai format, termasuk nama bulan Indonesia.
     *
     * Format yang didukung:
     *   - "11 Mei 2026"   → tanggal bulan(id) tahun
     *   - "11 May 2026"   → tanggal bulan(en) tahun
     *   - "2026-05-11"    → YYYY-MM-DD
     *   - "11/05/2026"    → DD/MM/YYYY
     *   - "11-05-2026"    → DD-MM-YYYY
     *
     * @return int|false  Unix timestamp atau false jika gagal
     */
    private function parseDate(string $raw): int|false
    {
        $raw = trim($raw);
        if (empty($raw)) return false;

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

        $normalized = preg_replace_callback(
            '/\b(' . implode('|', array_keys($idToEn)) . ')\b/ui',
            fn($m) => $idToEn[strtolower($m[1])],
            $raw
        );

        // DD/MM/YYYY atau DD-MM-YYYY → YYYY-MM-DD
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $normalized, $parts)) {
            $normalized = "{$parts[3]}-{$parts[2]}-{$parts[1]}";
        }

        $ts = strtotime($normalized);
        return ($ts !== false && $ts > 0) ? $ts : false;
    }
}
