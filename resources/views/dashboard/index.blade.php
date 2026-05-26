@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

<div class="page-header pre-hide">
    <h2 class="page-title">Dashboard</h2>
    <p class="page-description">Rekap surat masuk &amp; keluar — Tahun {{ $currentYear }}</p>
</div>

@if(isset($error))
<div class="alert alert-error" id="alert-error">
    <div class="alert-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
        </svg>
    </div>
    <span>{{ $error }}</span>
</div>
@endif

{{-- ── Kartu Ringkasan ──────────────────────────────────────────── --}}
<div class="db-stats-grid">
    <div class="db-stat-card db-stat-sm pre-hide">
        <div class="db-stat-icon db-stat-icon-sm">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/>
                <path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>
            </svg>
        </div>
        <div class="db-stat-body">
            <span class="db-stat-label">Total Surat Masuk {{ $currentYear }}</span>
            <span class="db-stat-value">{{ number_format($totalSMYear) }}</span>
        </div>
    </div>
    <div class="db-stat-card db-stat-sk pre-hide">
        <div class="db-stat-icon db-stat-icon-sk">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
        </div>
        <div class="db-stat-body">
            <span class="db-stat-label">Total Surat Keluar {{ $currentYear }}</span>
            <span class="db-stat-value">{{ number_format($totalSKYear) }}</span>
        </div>
    </div>
    <div class="db-stat-card db-stat-total pre-hide">
        <div class="db-stat-icon db-stat-icon-total">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/>
            </svg>
        </div>
        <div class="db-stat-body">
            <span class="db-stat-label">Total Semua Surat {{ $currentYear }}</span>
            <span class="db-stat-value">{{ number_format($totalSMYear + $totalSKYear) }}</span>
        </div>
    </div>
</div>

{{-- ── Tabel Rekap Bulanan ──────────────────────────────────────── --}}
<div class="db-card pre-hide" style="margin-bottom:1.5rem;">
    <div class="db-card-header">
        <div class="db-card-title-wrap">
            <div class="db-card-icon db-card-icon-table">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
            </div>
            <h3 class="db-card-title">Rekap Bulanan Tahun {{ $currentYear }}</h3>
        </div>
@php $bulanAktif = collect($monthlyRecap)->filter(fn($r) => $r['total'] > 0)->count(); @endphp
        <span class="db-badge">12 Bulan</span>
    </div>
    <div class="db-table-wrap" style="overflow-x:auto;">
        <table class="db-table" id="tabel-rekap">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Bulan</th>
                    <th>Surat Masuk</th>
                    <th>Surat Keluar</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyRecap as $i => $row)
                <tr class="{{ ($i + 1) == $currentMonth ? 'db-row-active' : '' }}">
                    <td class="db-td-num">{{ $i + 1 }}</td>
                    <td class="db-td-bulan">
                        {{ $row['bulan'] }}
                        @if(($i + 1) == $currentMonth)
                            <span class="db-badge-month">Sekarang</span>
                        @endif
                    </td>
                    <td>
                        <span class="db-count-sm">{{ $row['sm_count'] }}</span>
                        @if($row['sm_count'] > 0)
                        <div class="db-bar-wrap">
                            <div class="db-bar db-bar-sm" style="width:{{ $totalSMYear > 0 ? round($row['sm_count'] / $totalSMYear * 100) : 0 }}%"></div>
                        </div>
                        @endif
                    </td>
                    <td>
                        <span class="db-count-sk">{{ $row['sk_count'] }}</span>
                        @if($row['sk_count'] > 0)
                        <div class="db-bar-wrap">
                            <div class="db-bar db-bar-sk" style="width:{{ $totalSKYear > 0 ? round($row['sk_count'] / $totalSKYear * 100) : 0 }}%"></div>
                        </div>
                        @endif
                    </td>
                    <td><strong>{{ $row['total'] }}</strong></td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="db-tfoot-row">
                    <td colspan="2"><strong>TOTAL TAHUN {{ $currentYear }}</strong></td>
                    <td><strong>{{ $totalSMYear }}</strong></td>
                    <td><strong>{{ $totalSKYear }}</strong></td>
                    <td><strong>{{ $totalSMYear + $totalSKYear }}</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- ── Grafik Pie — Per Bulan ──────────────────────────────────── --}}
<div class="db-charts-grid">
    <div class="db-card pre-hide">
        <div class="db-card-header">
            <div class="db-card-title-wrap">
                <div class="db-card-icon db-card-icon-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/>
                        <path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>
                    </svg>
                </div>
                <h3 class="db-card-title">SM — Klasifikasi Bulan Ini</h3>
            </div>
            <span class="db-badge">{{ $bulanNames[$currentMonth] ?? '' }} {{ $currentYear }}</span>
        </div>
        <div class="db-chart-container">
            @if(count($smPieBulan) > 0)
                <canvas id="chart-sm-bulan"></canvas>
                <div class="db-legend" id="legend-sm-bulan"></div>
            @else
                <div class="db-empty">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-text-muted);margin-bottom:8px">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <p>Belum ada data surat masuk bulan ini</p>
                </div>
            @endif
        </div>
    </div>

    <div class="db-card pre-hide">
        <div class="db-card-header">
            <div class="db-card-title-wrap">
                <div class="db-card-icon db-card-icon-sk">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                </div>
                <h3 class="db-card-title">SK — Klasifikasi Bulan Ini</h3>
            </div>
            <span class="db-badge">{{ $bulanNames[$currentMonth] ?? '' }} {{ $currentYear }}</span>
        </div>
        <div class="db-chart-container">
            @if(count($skPieBulan) > 0)
                <canvas id="chart-sk-bulan"></canvas>
                <div class="db-legend" id="legend-sk-bulan"></div>
            @else
                <div class="db-empty">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-text-muted);margin-bottom:8px">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <p>Belum ada data surat keluar bulan ini</p>
                </div>
            @endif
        </div>
    </div>

    <div class="db-card pre-hide">
        <div class="db-card-header">
            <div class="db-card-title-wrap">
                <div class="db-card-icon db-card-icon-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/>
                        <path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>
                    </svg>
                </div>
                <h3 class="db-card-title">SM — Klasifikasi Tahun Ini</h3>
            </div>
            <span class="db-badge">{{ $currentYear }}</span>
        </div>
        <div class="db-chart-container">
            @if(count($smPieTahun) > 0)
                <canvas id="chart-sm-tahun"></canvas>
                <div class="db-legend" id="legend-sm-tahun"></div>
            @else
                <div class="db-empty">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-text-muted);margin-bottom:8px">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <p>Belum ada data surat masuk tahun ini</p>
                </div>
            @endif
        </div>
    </div>

    <div class="db-card pre-hide">
        <div class="db-card-header">
            <div class="db-card-title-wrap">
                <div class="db-card-icon db-card-icon-sk">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                </div>
                <h3 class="db-card-title">SK — Klasifikasi Tahun Ini</h3>
            </div>
            <span class="db-badge">{{ $currentYear }}</span>
        </div>
        <div class="db-chart-container">
            @if(count($skPieTahun) > 0)
                <canvas id="chart-sk-tahun"></canvas>
                <div class="db-legend" id="legend-sk-tahun"></div>
            @else
                <div class="db-empty">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-text-muted);margin-bottom:8px">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <p>Belum ada data surat keluar tahun ini</p>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
/* ── Dashboard Styles ─────────────────────────────── */
.db-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.db-stat-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: 1.25rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: var(--shadow-sm);
    transition: box-shadow var(--transition-normal), transform var(--transition-fast);
}
.db-stat-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
.db-stat-icon {
    width: 48px; height: 48px; border-radius: var(--radius-md);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.db-stat-icon-sm   { background: #e0f2fe; color: #0369a1; }
.db-stat-icon-sk   { background: #fce7f3; color: #be185d; }
.db-stat-icon-total{ background: #f3e8ff; color: #7c3aed; }
.db-stat-icon-month{ background: #dcfce7; color: #15803d; }
.db-stat-body { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.db-stat-label { font-size: 0.78rem; font-weight: 500; color: var(--color-text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.db-stat-value { font-size: 1.75rem; font-weight: 700; color: var(--color-text); letter-spacing: -0.03em; line-height: 1.1; }

/* ── Card ──────────────────────────── */
.db-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}
.db-card-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--color-border);
    background: var(--color-bg);
}
.db-card-title-wrap { display: flex; align-items: center; gap: .6rem; }
.db-card-icon {
    width: 28px; height: 28px; border-radius: 6px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.db-card-icon-table { background: var(--color-primary-light); color: var(--color-primary); }
.db-card-icon-sm    { background: var(--color-primary-light); color: var(--color-primary); }
.db-card-icon-sk    { background: var(--color-primary-light); color: var(--color-primary); }
.db-card-title { font-size: .9rem; font-weight: 600; color: var(--color-text); }
.db-badge {
    font-size: .72rem; font-weight: 600; padding: 2px 9px;
    border-radius: 999px; background: var(--color-primary-light); color: var(--color-primary);
}

/* ── Table ─────────────────────────── */
.db-table-wrap { overflow-x: auto; }
.db-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
.db-table th {
    padding: .65rem 1rem; text-align: left; font-size: .75rem; font-weight: 600;
    color: #ffffff; text-transform: uppercase; letter-spacing: .06em;
    background: var(--color-primary); border-bottom: none;
    white-space: nowrap;
}
[data-theme="dark"] .db-table th {
    background: var(--color-primary-hover, #15314f);
    color: #e2e8f0;
}
.db-table td { padding: .65rem 1rem; border-bottom: 1px solid var(--color-border); vertical-align: middle; color: var(--color-text); }
.db-table tbody tr:last-child td { border-bottom: none; }
.db-table tbody tr:hover { background: var(--color-primary-light); }
.db-row-active { background: var(--color-primary-light) !important; }
.db-row-active td { color: var(--color-primary); }
.db-td-num { color: var(--color-text-muted); font-size: .8rem; width: 36px; }
.db-td-bulan { font-weight: 500; white-space: nowrap; color: var(--color-text); }
.db-badge-month {
    display: inline-block; margin-left: 6px; font-size: .65rem; font-weight: 700;
    padding: 1px 7px; border-radius: 999px; background: var(--color-primary-light); color: var(--color-primary);
    vertical-align: middle;
}
.db-count-sm { font-weight: 600; color: #38bdf8; }
.db-count-sk { font-weight: 600; color: #f472b6; }
.db-bar-wrap { margin-top: 4px; height: 4px; background: var(--color-border); border-radius: 2px; overflow: hidden; max-width: 120px; }
.db-bar { height: 100%; border-radius: 2px; min-width: 4px; transition: width .4s ease; }
.db-bar-sm { background: linear-gradient(90deg,#38bdf8,#0369a1); }
.db-bar-sk { background: linear-gradient(90deg,#f472b6,#be185d); }
.db-tfoot-row td { font-weight: 600; background: var(--color-bg); border-top: 2px solid var(--color-border); }

/* ── Charts Grid ─────────────────── */
.db-charts-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.25rem;
    margin-bottom: 2rem;
    align-items: stretch;
}
.db-charts-grid .db-card {
    display: flex;
    flex-direction: column;
}
.db-chart-container {
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    flex: 1;
    min-height: 340px;
}
.db-chart-container canvas { max-width: 220px; max-height: 220px; }
.db-empty {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    flex: 1; width: 100%;
    padding: 2.5rem 1rem; color: var(--color-text-muted); font-size: .85rem; text-align: center;
}
.db-legend { display: flex; flex-direction: column; gap: .4rem; width: 100%; }
.db-legend-item { display: flex; align-items: center; gap: .5rem; font-size: .78rem; }
.db-legend-dot { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }
.db-legend-label { flex: 1; color: var(--color-text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.db-legend-val { font-weight: 600; color: var(--color-text); }
.db-legend-pct { font-size: .72rem; color: var(--color-text-muted); }

/* ── Responsive ────────────────── */
@media (max-width: 1024px) {
    .db-stats-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
    .db-stats-grid  { grid-template-columns: 1fr 1fr; }
    .db-charts-grid { grid-template-columns: 1fr; }
    .db-stat-value  { font-size: 1.4rem; }
    .db-chart-container { min-height: 260px; } /* reduce di tablet */
}
@media (max-width: 480px) {
    .db-stats-grid  { grid-template-columns: 1fr; }
    .db-chart-container { min-height: 220px; padding: .75rem; } /* reduce di HP */
    .db-chart-container canvas { max-width: 180px; max-height: 180px; }
    .db-stat-card { padding: 1rem; gap: .75rem; }
    .db-stat-icon { width: 40px; height: 40px; }
    .db-stat-value { font-size: 1.25rem; }
}

/* ── Dark mode stat icon overrides ──────── */
[data-theme="dark"] .db-stat-icon-sm    { background: rgba(14,165,233,0.15);  color: #38bdf8; }
[data-theme="dark"] .db-stat-icon-sk    { background: rgba(244,114,182,0.15); color: #f472b6; }
[data-theme="dark"] .db-stat-icon-total { background: rgba(167,139,250,0.15); color: #a78bfa; }
[data-theme="dark"] .db-stat-icon-month { background: rgba(52,211,153,0.15);  color: #34d399; }
</style>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    // Palet warna harmonis
    var COLORS = [
        '#3b82f6','#f59e0b','#10b981','#ef4444','#8b5cf6',
        '#06b6d4','#f97316','#84cc16','#ec4899','#14b8a6',
        '#a78bfa','#fb923c','#34d399','#f43f5e','#60a5fa',
        '#fbbf24','#4ade80','#c084fc','#38bdf8','#e879f9'
    ];

    function buildPie(canvasId, legendId, dataObj) {
        var canvas = document.getElementById(canvasId);
        if (!canvas) return;
        var labels = Object.keys(dataObj);
        var values = Object.values(dataObj);
        var total  = values.reduce(function(a,b){ return a+b; }, 0);
        var colors = labels.map(function(_,i){ return COLORS[i % COLORS.length]; });

        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{ data: values, backgroundColor: colors, borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }]
            },
            options: {
                responsive: true,
                cutout: '62%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                var pct = ((ctx.parsed / total) * 100).toFixed(1);
                                // Ambil prefix kode saja (sebelum titik): PR, KP, PL, dll
                                var code = ctx.label.split('.')[0];
                                return ' ' + code + ': ' + ctx.parsed + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });

        // Custom legend
        var legendEl = document.getElementById(legendId);
        if (!legendEl) return;
        labels.forEach(function(label, i) {
            var pct = total > 0 ? ((values[i] / total) * 100).toFixed(1) : '0.0';
            // Ambil prefix kode saja (sebelum titik): PR, KP, PL, HM, UM, dll
            var shortCode = label.split('.')[0];
            var item = document.createElement('div');
            item.className = 'db-legend-item';
            item.innerHTML =
                '<span class="db-legend-dot" style="background:' + colors[i] + '"></span>' +
                '<span class="db-legend-label" title="' + label + '">' + shortCode + '</span>' +
                '<span class="db-legend-val">' + values[i] + '</span>' +
                '<span class="db-legend-pct">' + pct + '%</span>';
            legendEl.appendChild(item);
        });
    }

    buildPie('chart-sm-bulan', 'legend-sm-bulan', @json($smPieBulan));
    buildPie('chart-sk-bulan', 'legend-sk-bulan', @json($skPieBulan));
    buildPie('chart-sm-tahun', 'legend-sm-tahun', @json($smPieTahun));
    buildPie('chart-sk-tahun', 'legend-sk-tahun', @json($skPieTahun));
})();
</script>
@endpush
@endsection
