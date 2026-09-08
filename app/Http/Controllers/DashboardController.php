<?php

namespace App\Http\Controllers;

use App\Services\SheetsCache;

/**
 * ============================================================
 * DASHBOARD CONTROLLER
 * ============================================================
 *
 * Mengelola tampilan dashboard statistik arsip (hanya untuk admin).
 * Data diambil dari sheet dbSM (Surat Masuk) dan dbSK (Surat Keluar)
 * menggunakan batchGet API untuk efisiensi (1 request, 2 sheet).
 *
 * HALAMAN YANG DIKELOLA:
 *   - /dashboard         → tampilan statistik interaktif (chart, rekap bulanan)
 *   - /dashboard/report  → laporan rekap lengkap per tahun & klasifikasi
 *   - /dashboard/print   → tampilan print-friendly untuk laporan
 *
 * ALUR DATA:
 *   1. Ambil data dbSM dan dbSK dari Google Sheets (via batchGet)
 *   2. Proses baris-baris data menjadi statistik (processRows)
 *   3. Kirim data ke view untuk ditampilkan
 *
 * PARSING TANGGAL:
 *   - Mendukung format tanggal Bahasa Indonesia (Januari, Februari, dst)
 *   - Mendukung format DD/MM/YYYY dan DD-MM-YYYY
 *   - Baris dengan tanggal kosong/tidak valid diabaikan
 *
 * FALLBACK BATCHGET:
 *   Jika batchGet gagal (quota/error), otomatis fallback ke dua request
 *   terpisah untuk dbSM dan dbSK.
 *
 * CATATAN INDEX KOLOM (digunakan di processRows dan report):
 *   - dbSM: dateColIndex=8 (tanggal surat), klasifikasiColIndex=1
 *   - dbSK: dateColIndex=9 (tanggal surat SK berbeda posisi), klasifikasiColIndex=1
 */
class DashboardController extends Controller
{
    /**
     * Translasi nama bulan Bahasa Indonesia ke Bahasa Inggris.
     * Digunakan oleh parseDate() agar strtotime() bisa memahami tanggal berbahasa Indonesia.
     */
    private const ID_TO_EN = [
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

    public function index()
    {
        try {
            $data = $this->buildDashboardStats(SheetsCache::KEY_DASHBOARD);

            return view('dashboard.index', $data);
        } catch (\Exception $e) {
            SheetsCache::forget(SheetsCache::KEY_DASHBOARD);
            return view('dashboard.index', $this->emptyDashboardData($e->getMessage()));
        }
    }

    public function printView()
    {
        try {
            $data = $this->buildDashboardStats(SheetsCache::KEY_DASHBOARD);

            return view('dashboard.print', $data);
        } catch (\Exception $e) {
            SheetsCache::forget(SheetsCache::KEY_DASHBOARD);
            return view('dashboard.print', $this->emptyDashboardData($e->getMessage()));
        }
    }

    /**
     * Membangun data statistik dashboard (digunakan oleh index() dan printView()).
     * Mengambil dua sheet sekaligus via batchGet, lalu memproses rekap bulanan
     * dan klasifikasi per bulan/tahun.
     */
    private function buildDashboardStats(string $cacheKey): array
    {
        [$smRows, $skRows] = SheetsCache::remember(
            $cacheKey,
            SheetsCache::TTL_DASHBOARD,
            function () {
                [$service, $spreadsheetId] = $this->getReadonlySheetService();
                return $this->fetchBothSheets($service, $spreadsheetId);
            }
        );

        if (!empty($smRows)) array_shift($smRows);
        if (!empty($skRows)) array_shift($skRows);

        $currentYear  = (int) date('Y');
        $currentMonth = (int) date('m');

        $smData = $this->processRows($smRows, dateColIndex: 8, klasifikasiColIndex: 1);

        $skRowsFiltered = array_filter($skRows, fn($r) => !empty(trim($r[9] ?? '')));
        $skData = $this->processRows(array_values($skRowsFiltered), dateColIndex: 9, klasifikasiColIndex: 1);

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

        $currentMonthKey = $currentYear . '-' . str_pad($currentMonth, 2, '0', STR_PAD_LEFT);

        return [
            'monthlyRecap' => $monthlyRecap,
            'totalSMYear'  => $totalSMYear,
            'totalSKYear'  => $totalSKYear,
            'smPieBulan'   => $smData['klasifikasiMonthly'][$currentMonthKey] ?? [],
            'skPieBulan'   => $skData['klasifikasiMonthly'][$currentMonthKey] ?? [],
            'smPieTahun'   => $smData['klasifikasiYearly'][$currentYear]      ?? [],
            'skPieTahun'   => $skData['klasifikasiYearly'][$currentYear]      ?? [],
            'currentYear'  => $currentYear,
            'currentMonth' => $currentMonth,
            'bulanNames'   => $bulanNames,
        ];
    }

    /**
     * Mengembalikan struktur data kosong untuk view dashboard saat terjadi error.
     */
    private function emptyDashboardData(string $errorMessage): array
    {
        return [
            'error'        => 'Gagal memuat data: ' . $errorMessage,
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
        ];
    }

    public function report()
    {
        try {

            [$smRows, $skRows] = SheetsCache::remember(
                SheetsCache::KEY_REPORT,
                SheetsCache::TTL_DASHBOARD,
                function () {
                    [$service, $spreadsheetId] = $this->getReadonlySheetService();
                    return $this->fetchBothSheets($service, $spreadsheetId);
                }
            );

            if (!empty($smRows)) array_shift($smRows);
            if (!empty($skRows)) array_shift($skRows);

            $smData = $this->processRows($smRows, dateColIndex: 8, klasifikasiColIndex: 1);

            $skRowsFiltered = array_filter($skRows, fn($r) => !empty(trim($r[9] ?? '')));
            $skData = $this->processRows(array_values($skRowsFiltered), dateColIndex: 9, klasifikasiColIndex: 1);

            $bulanNames = [
                1  => 'Januari',  2  => 'Februari', 3  => 'Maret',
                4  => 'April',    5  => 'Mei',       6  => 'Juni',
                7  => 'Juli',     8  => 'Agustus',   9  => 'September',
                10 => 'Oktober',  11 => 'November',  12 => 'Desember',
            ];

            $currentYear  = (int) date('Y');
            $currentMonth = (int) date('m');

            $allYears = array_unique(array_merge(
                array_keys($smData['klasifikasiYearly']),
                array_keys($skData['klasifikasiYearly'])
            ));
            sort($allYears);

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

            $kodePriority = ['PR','KP','OT','KU','HK','UM','PL','UP','HM','PW','LT','DL','DT','KI'];
            $rekapTahunan = [];
            foreach ($allYears as $year) {

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

            $rekapKlasifikasiPerTahun = [];
            foreach ($allYears as $year) {
                $smKodeY = $smData['klasifikasiYearly'][$year] ?? [];
                $skKodeY = $skData['klasifikasiYearly'][$year] ?? [];
                $allKY   = array_unique(array_merge(array_keys($smKodeY), array_keys($skKodeY)));
                usort($allKY, function ($a, $b) use ($kodePriority) {
                    $ia = array_search($a, $kodePriority);
                    $ib = array_search($b, $kodePriority);
                    return ($ia === false ? 999 : $ia) <=> ($ib === false ? 999 : $ib);
                });
                $rowsY = [];
                foreach ($allKY as $kode) {
                    $sm = $smKodeY[$kode] ?? 0;
                    $sk = $skKodeY[$kode] ?? 0;
                    $rowsY[] = ['kode' => $kode, 'sm_count' => $sm, 'sk_count' => $sk, 'total' => $sm + $sk];
                }
                $rekapKlasifikasiPerTahun[$year] = $rowsY;
            }

            $klasifikasiPerBulan = [];
            foreach ($allYears as $year) {
                for ($m = 1; $m <= 12; $m++) {
                    $key = $year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
                    $smK = $smData['klasifikasiMonthly'][$key] ?? [];
                    $skK = $skData['klasifikasiMonthly'][$key] ?? [];
                    if (empty($smK) && empty($skK)) continue;
                    $allKM = array_unique(array_merge(array_keys($smK), array_keys($skK)));
                    usort($allKM, function ($a, $b) use ($kodePriority) {
                        $ia = array_search($a, $kodePriority);
                        $ib = array_search($b, $kodePriority);
                        return ($ia === false ? 999 : $ia) <=> ($ib === false ? 999 : $ib);
                    });
                    $detail = [];
                    foreach ($allKM as $kode) {
                        $sm = $smK[$kode] ?? 0;
                        $sk = $skK[$kode] ?? 0;
                        $detail[$kode] = [$sm, $sk, $sm + $sk];
                    }
                    $klasifikasiPerBulan[$key] = $detail;
                }
            }

            $printDetailBulanan = [];

            foreach ($smRows as $row) {
                $rawDate = trim($row[8] ?? '');
                if (empty($rawDate)) continue;
                $ts = $this->parseDate($rawDate);
                if ($ts === false) continue;
                $kodeKlas  = trim($row[1] ?? '');
                $kodeSurat = trim($row[2] ?? '');
                if (str_starts_with($kodeKlas, '#')) $kodeKlas = '';
                $kodeGabung = $kodeKlas !== '' && $kodeSurat !== '' ? "{$kodeKlas} {$kodeSurat}" : ($kodeKlas ?: $kodeSurat);
                $kodePrefix = preg_match('/^([A-Za-z]+)/', $kodeKlas, $km) ? strtoupper($km[1]) : 'Lainnya';
                $uraianArsip = trim($row[7] ?? '');
                $uraianVal   = $uraianArsip !== '' ? $uraianArsip : trim($row[3] ?? '-');
                $printDetailBulanan[] = [
                    'ts'          => $ts,
                    'tahun'       => (int) date('Y', $ts),
                    'bulan'       => (int) date('m', $ts),
                    'kode'        => $kodeGabung,
                    'kode_prefix' => $kodePrefix,
                    'tanggal'     => $rawDate,
                    'uraian'      => $uraianVal,
                    'jenis'       => 'SM',
                ];
            }

            foreach ($skRows as $row) {
                $rawDate = trim($row[9] ?? '');
                if (empty($rawDate)) continue;
                $ts = $this->parseDate($rawDate);
                if ($ts === false) continue;
                $kodeKlas  = trim($row[1] ?? '');
                $kodeSurat = trim($row[2] ?? '');
                if (str_starts_with($kodeKlas, '#')) $kodeKlas = '';
                $kodeGabung = $kodeKlas !== '' && $kodeSurat !== '' ? "{$kodeKlas} {$kodeSurat}" : ($kodeKlas ?: $kodeSurat);
                $kodePrefix = preg_match('/^([A-Za-z]+)/', $kodeKlas, $km) ? strtoupper($km[1]) : 'Lainnya';
                $uraianArsip = trim($row[8] ?? '');
                $uraianVal   = $uraianArsip !== '' ? $uraianArsip : trim($row[3] ?? '-');
                $printDetailBulanan[] = [
                    'ts'          => $ts,
                    'tahun'       => (int) date('Y', $ts),
                    'bulan'       => (int) date('m', $ts),
                    'kode'        => $kodeGabung,
                    'kode_prefix' => $kodePrefix,
                    'tanggal'     => $rawDate,
                    'uraian'      => $uraianVal,
                    'jenis'       => 'SK',
                ];
            }

            usort($printDetailBulanan, fn($a, $b) => $a['ts'] <=> $b['ts']);

            $printDetailSM = array_values(array_filter($printDetailBulanan, fn($d) => $d['jenis'] === 'SM'));

            $printDetailSK = array_values(array_filter($printDetailBulanan, fn($d) => $d['jenis'] === 'SK'));

            $printDetailKlasifikasi = $printDetailBulanan;
            usort($printDetailKlasifikasi, function ($a, $b) use ($kodePriority) {
                $ia = array_search($a['kode_prefix'], $kodePriority);
                $ib = array_search($b['kode_prefix'], $kodePriority);
                $ia = $ia === false ? 999 : $ia;
                $ib = $ib === false ? 999 : $ib;
                if ($ia !== $ib) return $ia <=> $ib;

                $kodeCompare = strcmp($a['kode'], $b['kode']);
                if ($kodeCompare !== 0) return $kodeCompare;
                return $a['ts'] <=> $b['ts'];
            });

            return view('dashboard.report', compact(
                'rekapPerTahun',
                'rekapTahunan',
                'rekapKlasifikasi',
                'rekapKlasifikasiPerTahun',
                'klasifikasiPerBulan',
                'allYears',
                'currentYear',
                'currentMonth',
                'bulanNames',
                'printDetailBulanan',
                'printDetailKlasifikasi',
                'printDetailSM',
                'printDetailSK',
            ));
        } catch (\Exception $e) {

            SheetsCache::forget(SheetsCache::KEY_REPORT);
            return view('dashboard.report', [
                'error'                     => 'Gagal memuat data: ' . $e->getMessage(),
                'rekapPerTahun'             => [],
                'rekapTahunan'              => [],
                'rekapKlasifikasi'          => [],
                'rekapKlasifikasiPerTahun'  => [],
                'klasifikasiPerBulan'       => [],
                'allYears'                  => [],
                'currentYear'               => (int) date('Y'),
                'currentMonth'              => (int) date('m'),
                'bulanNames'                => [],
                'printDetailBulanan'        => [],
                'printDetailKlasifikasi'    => [],
                'printDetailSM'             => [],
                'printDetailSK'             => [],
            ]);
        }
    }

    /**
     * Memproses array baris data sheet menjadi statistik:
     *   - monthly: jumlah arsip per bulan (key: 'YYYY-MM')
     *   - klasifikasiMonthly: jumlah per kode per bulan
     *   - klasifikasiYearly: jumlah per kode per tahun
     *
     * Baris dengan tanggal kosong atau tidak valid diabaikan.
     * Kode klasifikasi diambil hanya prefix huruf (misal: 'KP' dari 'KP.001').
     */
    private function processRows(array $rows, int $dateColIndex, int $klasifikasiColIndex): array
    {
        $monthly            = [];
        $klasifikasiMonthly = [];
        $klasifikasiYearly  = [];

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

            if (empty($kode) || str_starts_with($kode, '#')) {
                $kode = 'Tidak Diketahui';
            } else {

                if (preg_match('/^([A-Za-z]+)/', $kode, $km)) {
                    $kode = strtoupper($km[1]);
                }
            }

            $klasifikasiMonthly[$key][$kode] = ($klasifikasiMonthly[$key][$kode] ?? 0) + 1;
            $klasifikasiYearly[$year][$kode] = ($klasifikasiYearly[$year][$kode] ?? 0) + 1;

        }

        return compact('monthly', 'klasifikasiMonthly', 'klasifikasiYearly');
    }

    /**
     * Mengurai string tanggal ke Unix timestamp.
     * Mendukung:
     *   - Nama bulan Bahasa Indonesia (via ID_TO_EN)
     *   - Format DD/MM/YYYY dan DD-MM-YYYY
     *   - Format-format lain yang didukung strtotime()
     *
     * @return int|false Timestamp atau false jika tidak valid
     */
    private function parseDate(string $raw): int|false
    {
        $raw = trim($raw);
        if (empty($raw)) return false;

        $normalized = preg_replace_callback(
            '/\b(' . implode('|', array_keys(self::ID_TO_EN)) . ')\b/ui',
            fn($m) => self::ID_TO_EN[strtolower($m[1])],
            $raw
        );

        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $normalized, $parts)) {
            $normalized = "{$parts[3]}-{$parts[2]}-{$parts[1]}";
        }

        $ts = strtotime($normalized);
        return ($ts !== false && $ts > 0) ? $ts : false;
    }

    /**
     * Mengambil data dari dua sheet (dbSM dan dbSK) dalam satu API call (batchGet).
     * Lebih efisien dari dua request terpisah.
     *
     * Jika batchGet gagal (misal: error jaringan/quota), fallback ke
     * dua request terpisah untuk menjaga keandalan.
     *
     * @return array [$smRows, $skRows]
     */
    private function fetchBothSheets($service, string $spreadsheetId): array
    {
        try {
            $batchResponse = $service->spreadsheets_values->batchGet($spreadsheetId, [
                'ranges' => ['dbSM!A:R', 'dbSK!A:R'],
            ]);
            $valueRanges = $batchResponse->getValueRanges() ?? [];

            $smRows = isset($valueRanges[0]) ? ($valueRanges[0]->getValues() ?? []) : [];
            $skRows = isset($valueRanges[1]) ? ($valueRanges[1]->getValues() ?? []) : [];

            return [$smRows, $skRows];
        } catch (\Exception $e) {
            $smResponse = $service->spreadsheets_values->get($spreadsheetId, 'dbSM!A:R');
            $smRows     = $smResponse->getValues() ?? [];
            $skResponse = $service->spreadsheets_values->get($spreadsheetId, 'dbSK!A:R');
            $skRows     = $skResponse->getValues() ?? [];

            return [$smRows, $skRows];
        }
    }
}
