<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Rekap Dashboard — Tahun {{ $currentYear }}</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 11pt;
            color: #1a1a1a;
            background: #f0f4f8;
        }

        @page {
            size: A4 portrait;
            margin: 15mm 15mm 15mm 20mm;
        }
        @media print {
            html, body {
                background: #fff;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print { display: none !important; }
            .print-page { box-shadow: none !important; margin: 0 !important; padding: 0 !important; }
            .chart-section { break-inside: avoid; page-break-inside: avoid; }
            .page-break-before { page-break-before: always; break-before: page; }
        }

        .print-page {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            padding: 15mm 15mm 15mm 20mm;
            background: #ffffff;
            box-shadow: 0 4px 32px rgba(0,0,0,0.15);
        }

        .action-bar {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            background: #1e3a5f;
            padding: .65rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.25);
        }
        .action-bar-title {
            color: #e2e8f0;
            font-size: .88rem;
            font-weight: 600;
            letter-spacing: .02em;
        }
        .action-bar-btns { display: flex; gap: .65rem; }
        .btn-action {
            display: inline-flex; align-items: center; gap: .4rem;
            padding: .45rem .95rem;
            border-radius: 7px; border: none; cursor: pointer;
            font-size: .82rem; font-weight: 600; font-family: inherit;
            text-decoration: none;
            transition: opacity .15s, transform .1s;
        }
        .btn-action:hover { opacity: .88; transform: translateY(-1px); }
        .btn-print  { background: #10b981; color: #fff; }
        .btn-back   { background: rgba(255,255,255,0.12); color: #e2e8f0; border: 1px solid rgba(255,255,255,0.2); }
        .action-bar-space { height: 48px; }

        .kop-wrap {
            display: flex;
            align-items: center;
            gap: 0;
            border-bottom: 3px solid #1e3a5f;
            padding-bottom: 8px;
            margin-bottom: 6px;
        }
        .kop-logo {
            flex-shrink: 0;
            width: 110px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-right: 14px;
            border-right: 1px solid #c8d0dc;
        }
        .kop-logo img {
            width: 100%;
            height: auto;
            object-fit: contain;
        }
        .kop-text {
            padding-left: 14px;
            flex: 1;
        }
        .kop-kementerian {
            font-size: 12.5pt;
            font-weight: 800;
            color: #1a1a1a;
            letter-spacing: .01em;
            line-height: 1.2;
            text-transform: uppercase;
        }
        .kop-badan {
            font-size: 9pt;
            font-weight: 700;
            color: #1a1a1a;
            line-height: 1.35;
            margin-top: 2px;
            text-transform: uppercase;
        }
        .kop-address {
            font-size: 7.5pt;
            color: #555;
            margin-top: 4px;
            font-style: italic;
        }
        .kop-divider {
            border: none;
            border-top: 1px solid #b0bac7;
            margin: 4px 0 10px 0;
        }

        .report-title-wrap {
            text-align: center;
            margin: 8px 0 14px 0;
        }
        .report-title {
            font-size: 12pt;
            font-weight: 700;
            color: #1e3a5f;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .report-subtitle {
            font-size: 9.5pt;
            color: #4a5568;
            margin-top: 3px;
        }
        .report-date {
            font-size: 8.5pt;
            color: #718096;
            margin-top: 2px;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-bottom: 14px;
        }
        .stat-card {
            border: 1px solid #c8d0dc;
            border-radius: 8px;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .stat-icon {
            width: 36px; height: 36px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .stat-icon-sm    { background: #e0f2fe; color: #0369a1; }
        .stat-icon-sk    { background: #fce7f3; color: #be185d; }
        .stat-icon-total { background: #f3e8ff; color: #7c3aed; }
        .stat-body { display: flex; flex-direction: column; gap: 1px; }
        .stat-label { font-size: 7.5pt; font-weight: 500; color: #6b7280; }
        .stat-value { font-size: 16pt; font-weight: 800; color: #1a1a1a; line-height: 1.1; }
        .stat-sm-val  { color: #0369a1; }
        .stat-sk-val  { color: #be185d; }
        .stat-tot-val { color: #7c3aed; }

        .section-title {
            font-size: 9.5pt;
            font-weight: 700;
            color: #1e3a5f;
            border-left: 3px solid #1e3a5f;
            padding-left: 7px;
            margin-bottom: 7px;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        .rekap-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 16px;
        }
        .rekap-table th {
            background: #1e3a5f;
            color: #ffffff;
            padding: 6px 8px;
            text-align: center;
            font-size: 8pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .rekap-table th:first-child { text-align: center; width: 30px; }
        .rekap-table th:nth-child(2) { text-align: left; }
        .rekap-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #e2e8f0;
            text-align: center;
            vertical-align: middle;
        }
        .rekap-table td:nth-child(2) { text-align: left; font-weight: 500; }
        .rekap-table tbody tr:nth-child(even) { background: #f8fafc; }
        .rekap-table tbody tr.row-active {
            background: #eff6ff;
        }
        .rekap-table tbody tr.row-active td { color: #1e3a5f; font-weight: 600; }
        .rekap-table tfoot td {
            font-weight: 700;
            background: #1e3a5f;
            color: #ffffff;
            padding: 6px 8px;
            text-align: center;
        }
        .rekap-table tfoot td:nth-child(1) { text-align: center; }
        .rekap-table tfoot td:nth-child(2) { text-align: left; }
        .badge-bulan {
            display: inline-block;
            margin-left: 5px;
            font-size: 6.5pt;
            font-weight: 700;
            padding: 1px 5px;
            border-radius: 20px;
            background: #1e3a5f;
            color: #fff;
            vertical-align: middle;
        }
        .count-sm { color: #0369a1; font-weight: 700; }
        .count-sk { color: #be185d; font-weight: 700; }
        .count-tot{ font-weight: 700; color: #1a1a1a; }

        .chart-section { margin-top: 4px; }
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .chart-card {
            border: 1px solid #c8d0dc;
            border-radius: 8px;
            overflow: hidden;
        }
        .chart-card-header {
            background: #1e3a5f;
            color: #fff;
            padding: 6px 10px;
            font-size: 8pt;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .chart-card-badge {
            font-size: 7pt;
            background: rgba(255,255,255,0.18);
            border-radius: 10px;
            padding: 1px 7px;
            font-weight: 600;
        }
        .chart-card-header.sk-header { background: #831843; }
        .chart-body {
            padding: 8px;
            height: 195px;
            position: relative;
        }
        .chart-body canvas {
            width: 100% !important;
            height: 100% !important;
        }
        .chart-empty {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            height: 100%; color: #9ca3af; font-size: 8.5pt; text-align: center; gap: 6px;
        }

        .print-footer {
            margin-top: 16px;
            border-top: 1px solid #c8d0dc;
            padding-top: 8px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 7.5pt;
            color: #6b7280;
        }
        .print-footer-left { line-height: 1.6; }
        .print-footer-right { text-align: right; line-height: 1.6; }

        .error-box {
            background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px;
            padding: 16px; color: #dc2626; font-size: 9.5pt; margin: 20px 0;
        }
    </style>
</head>
<body>

<div class="action-bar no-print">
    <span class="action-bar-title">
        <svg style="display:inline-block;vertical-align:middle;margin-right:6px;" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>
        </svg>
        Pratinjau Cetak — Rekap Dashboard Tahun {{ $currentYear }}
    </span>
    <div class="action-bar-btns">
        <a href="{{ route('dashboard.index') }}" class="btn-action btn-back">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            Kembali
        </a>
        <button type="button" class="btn-action btn-print" onclick="window.print()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>
            </svg>
            Cetak / Simpan PDF
        </button>
    </div>
</div>
<div class="action-bar-space no-print"></div>

<div class="print-page">

    <div class="kop-wrap">
        <div class="kop-logo">
            <img src="{{ route('image.view', 'komdigi-logo.png') }}"
                 alt="Logo Komdigi"
                 onerror="this.style.display='none'">
        </div>
        <div class="kop-text">
            <div class="kop-kementerian">Kementerian Komunikasi dan Digital RI</div>
            <div class="kop-badan">
                Badan Pengembangan Sumber Daya Manusia Komunikasi dan Digital<br>
                Balai Pelatihan Sumber Daya Manusia Komunikasi dan Digital Manado
            </div>
            <div class="kop-address">Jln. Pomurow No. 76, Manado, No. Tlp/HP 085117816728, tu.bpsdmp_manado@komdigi.go.id</div>
        </div>
    </div>
    <hr class="kop-divider">

    <div class="report-title-wrap">
        <div class="report-title">Rekapitulasi Data Arsip Surat</div>
        <div class="report-subtitle">Dashboard Surat Masuk &amp; Surat Keluar — Tahun {{ $currentYear }}</div>
        <div class="report-date">Dicetak pada: {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y, H:i') }} WITA</div>
    </div>

    @if(isset($error))
    <div class="error-box">⚠️ {{ $error }}</div>
    @endif

    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon stat-icon-sm">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/>
                    <path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>
                </svg>
            </div>
            <div class="stat-body">
                <span class="stat-label">Total Surat Masuk {{ $currentYear }}</span>
                <span class="stat-value stat-sm-val">{{ number_format($totalSMYear) }}</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-sk">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
            </div>
            <div class="stat-body">
                <span class="stat-label">Total Surat Keluar {{ $currentYear }}</span>
                <span class="stat-value stat-sk-val">{{ number_format($totalSKYear) }}</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-total">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/>
                </svg>
            </div>
            <div class="stat-body">
                <span class="stat-label">Total Semua Surat {{ $currentYear }}</span>
                <span class="stat-value stat-tot-val">{{ number_format($totalSMYear + $totalSKYear) }}</span>
            </div>
        </div>
    </div>

    <div class="section-title">Rekap Bulanan Tahun {{ $currentYear }}</div>
    <table class="rekap-table">
        <thead>
            <tr>
                <th>No</th>
                <th style="text-align:left;">Bulan</th>
                <th>Surat Masuk</th>
                <th>Surat Keluar</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($monthlyRecap as $i => $row)
            <tr class="{{ ($i + 1) == $currentMonth ? 'row-active' : '' }}">
                <td>{{ $i + 1 }}</td>
                <td>
                    {{ $row['bulan'] }}
                    @if(($i + 1) == $currentMonth)
                        <span class="badge-bulan">Bulan Ini</span>
                    @endif
                </td>
                <td class="count-sm">{{ $row['sm_count'] > 0 ? $row['sm_count'] : '—' }}</td>
                <td class="count-sk">{{ $row['sk_count'] > 0 ? $row['sk_count'] : '—' }}</td>
                <td class="count-tot">{{ $row['total'] > 0 ? $row['total'] : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">TOTAL TAHUN {{ $currentYear }}</td>
                <td>{{ $totalSMYear }}</td>
                <td>{{ $totalSKYear }}</td>
                <td>{{ $totalSMYear + $totalSKYear }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="chart-section page-break-before">

        <div class="kop-wrap" style="margin-bottom:6px;">
            <div class="kop-logo">
                <img src="{{ route('image.view', 'komdigi-logo.png') }}"
                     alt="Logo Komdigi"
                     onerror="this.style.display='none'">
            </div>
            <div class="kop-text">
                <div class="kop-kementerian">Kementerian Komunikasi dan Digital RI</div>
                <div class="kop-badan">
                    Badan Pengembangan Sumber Daya Manusia Komunikasi dan Digital<br>
                    Balai Pelatihan Sumber Daya Manusia Komunikasi dan Digital Manado
                </div>
                <div class="kop-address">Jln. Pomurow No. 76, Manado, No. Tlp/HP 085117816728, tu.bpsdmp_manado@komdigi.go.id</div>
            </div>
        </div>
        <hr class="kop-divider">
        <div class="report-title-wrap" style="margin-bottom:10px;">
            <div class="report-title">Grafik Klasifikasi Surat</div>
            <div class="report-subtitle">Persebaran Surat per Kode Klasifikasi — Tahun {{ $currentYear }}</div>
        </div>

        <div class="section-title">Grafik Surat Masuk &amp; Surat Keluar — Bulan {{ $bulanNames[$currentMonth] ?? '' }} {{ $currentYear }}</div>
        <div class="charts-grid" style="margin-bottom:14px;">

            <div class="chart-card">
                <div class="chart-card-header">
                    <span>📥 Surat Masuk - Klasifikasi Bulan Ini</span>
                    <span class="chart-card-badge">{{ $bulanNames[$currentMonth] ?? '' }} {{ $currentYear }}</span>
                </div>
                <div class="chart-body">
                    @if(count($smPieBulan) > 0)
                        <canvas id="chart-sm-bulan"></canvas>
                    @else
                        <div class="chart-empty">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            Belum ada data surat masuk bulan ini
                        </div>
                    @endif
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-card-header sk-header">
                    <span>📤 Surat Keluar - Klasifikasi Bulan Ini</span>
                    <span class="chart-card-badge">{{ $bulanNames[$currentMonth] ?? '' }} {{ $currentYear }}</span>
                </div>
                <div class="chart-body">
                    @if(count($skPieBulan) > 0)
                        <canvas id="chart-sk-bulan"></canvas>
                    @else
                        <div class="chart-empty">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            Belum ada data surat keluar bulan ini
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="section-title">Grafik Surat Masuk &amp; Surat Keluar — Tahun {{ $currentYear }}</div>
        <div class="charts-grid">

            <div class="chart-card">
                <div class="chart-card-header">
                    <span>📥 Surat Masuk - Klasifikasi Tahun Ini</span>
                    <span class="chart-card-badge">{{ $currentYear }}</span>
                </div>
                <div class="chart-body">
                    @if(count($smPieTahun) > 0)
                        <canvas id="chart-sm-tahun"></canvas>
                    @else
                        <div class="chart-empty">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            Belum ada data surat masuk tahun ini
                        </div>
                    @endif
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-card-header sk-header">
                    <span>📤 Surat Keluar - Klasifikasi Tahun Ini</span>
                    <span class="chart-card-badge">{{ $currentYear }}</span>
                </div>
                <div class="chart-body">
                    @if(count($skPieTahun) > 0)
                        <canvas id="chart-sk-tahun"></canvas>
                    @else
                        <div class="chart-empty">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            Belum ada data surat keluar tahun ini
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    <div class="print-footer">
        <div class="print-footer-left">
            <strong>BPSDMP Komdigi Manado</strong><br>
            Sistem Pengarsipan Digital Surat<br>
            Jln. Pomurow No. 76, Manado
        </div>
        <div class="print-footer-right">
            Dicetak: {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y') }}<br>
            Tahun Rekap: {{ $currentYear }}<br>
            Dokumen ini digenerate otomatis oleh sistem
        </div>
    </div>

</div>

<script>
(function () {
    var COLORS_SM = [
        '#3b82f6','#60a5fa','#06b6d4','#38bdf8','#818cf8',
        '#a78bfa','#34d399','#10b981','#84cc16','#fbbf24',
        '#f59e0b','#f97316','#fb923c','#ef4444','#f43f5e',
        '#ec4899','#e879f9','#c084fc','#4ade80','#2dd4bf'
    ];
    var COLORS_SK = [
        '#f472b6','#ec4899','#fb7185','#f43f5e','#c084fc',
        '#a78bfa','#e879f9','#f97316','#fb923c','#fbbf24',
        '#f59e0b','#84cc16','#34d399','#10b981','#2dd4bf',
        '#38bdf8','#60a5fa','#3b82f6','#818cf8','#4ade80'
    ];

    function buildBar(canvasId, dataObj, accentHex, palette) {
        var canvas = document.getElementById(canvasId);
        if (!canvas || !dataObj || Object.keys(dataObj).length === 0) return;

        var labels = Object.keys(dataObj);
        var values = Object.values(dataObj);
        var total  = values.reduce(function(a,b){ return a+b; }, 0);
        var bgColors = labels.map(function(l, i) {
            var hex = palette[i % palette.length];
            var r = parseInt(hex.slice(1,3),16);
            var g = parseInt(hex.slice(3,5),16);
            var b = parseInt(hex.slice(5,7),16);
            return 'rgba('+r+','+g+','+b+',0.85)';
        });
        var borderColors = labels.map(function(l, i) {
            return palette[i % palette.length];
        });

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Surat',
                    data: values,
                    backgroundColor: bgColors,
                    borderColor: borderColors,
                    borderWidth: 1.5,
                    borderRadius: 5,
                    borderSkipped: false,
                    barPercentage: 0.72,
                    categoryPercentage: 0.78,
                    maxBarThickness: 44,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 0 },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#ffffff',
                        borderColor: accentHex + '55',
                        borderWidth: 1,
                        titleColor: '#1a1a1a',
                        bodyColor: '#4b5563',
                        padding: 8,
                        cornerRadius: 6,
                        callbacks: {
                            title: function(items) {
                                return Object.keys(dataObj)[items[0].dataIndex];
                            },
                            label: function(ctx) {
                                var pct = total > 0 ? ((ctx.parsed.y / total) * 100).toFixed(1) : '0.0';
                                return '  Jumlah: ' + ctx.parsed.y + '  (' + pct + '%)';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: {
                            color: '#374151',
                            font: { size: 10, weight: '600' },
                            maxRotation: 0,
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grace: '18%',
                        suggestedMax: Math.max.apply(null, values) < 3 ? 2 : undefined,
                        grid: { color: 'rgba(0,0,0,0.06)', lineWidth: 1 },
                        border: { display: false },
                        ticks: {
                            color: '#6b7280',
                            font: { size: 9 },
                            precision: 0,
                            stepSize: 1,
                            maxTicksLimit: 5,
                            callback: function(val) { return val % 1 === 0 ? val : ''; }
                        }
                    }
                }
            }
        });
    }

    buildBar('chart-sm-bulan', @json($smPieBulan), '#3b82f6', COLORS_SM);
    buildBar('chart-sk-bulan', @json($skPieBulan), '#ec4899', COLORS_SK);
    buildBar('chart-sm-tahun', @json($smPieTahun), '#3b82f6', COLORS_SM);
    buildBar('chart-sk-tahun', @json($skPieTahun), '#ec4899', COLORS_SK);
})();
</script>
</body>
</html>
