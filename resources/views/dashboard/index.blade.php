@extends('layouts.app')

@section('title', 'Dashboard')
@section('navbarOnly', true)

@section('content')

<div class="dashboard-viewport">
<div class="page-header pre-hide">
    <div>
        <h2 class="page-title">Dashboard</h2>
        <p class="page-description">Rekap surat masuk &amp; keluar — Tahun {{ $currentYear }}</p>
    </div>
    <button type="button" class="db-fullscreen-btn" id="db-fullscreen-btn" aria-label="Aktifkan layar penuh" title="Tampilkan layar penuh">
        <svg class="db-fullscreen-enter" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="8 3 3 3 3 8"/><line x1="3" y1="3" x2="9" y2="9"/>
            <polyline points="16 3 21 3 21 8"/><line x1="21" y1="3" x2="15" y2="9"/>
            <polyline points="8 21 3 21 3 16"/><line x1="3" y1="21" x2="9" y2="15"/>
            <polyline points="16 21 21 21 21 16"/><line x1="21" y1="21" x2="15" y2="15"/>
        </svg>
        <svg class="db-fullscreen-exit" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="4 14 10 14 10 20"/><line x1="10" y1="14" x2="3" y2="21"/>
            <polyline points="20 10 14 10 14 4"/><line x1="14" y1="10" x2="21" y2="3"/>
        </svg>
        <span id="db-fullscreen-label">Layar Penuh</span>
    </button>
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
<div class="db-main-grid">
<div class="db-card db-table-card pre-hide">
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
        <span class="db-badge">{{ $bulanAktif > 0 ? $bulanAktif . ' Bulan Aktif' : '0 Bulan' }}</span>
    </div>
    <div class="db-table-wrap">
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

{{-- ── Diagram Kolom — Klasifikasi ──────────────────────────────── --}}
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
</div>
</div>

<style>
/* ── Dashboard Styles ─────────────────────────────── */
.dashboard-viewport {
    height: 100%; min-height: 0; padding: .75rem 1rem; overflow: hidden;
    display: grid; grid-template-rows: auto auto minmax(0, 1fr); gap: .65rem;
}
.dashboard-viewport .page-header { margin: 0; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.dashboard-viewport .page-title { font-size: 1.25rem; }
.dashboard-viewport .page-description { font-size: .78rem; }
.db-fullscreen-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
    padding: .42rem .75rem; border: 1px solid var(--color-border); border-radius: 8px;
    background: var(--color-surface); color: var(--color-primary); font: inherit;
    font-size: .75rem; font-weight: 600; cursor: pointer; white-space: nowrap;
    box-shadow: var(--shadow-sm); transition: background .2s ease, border-color .2s ease, color .2s ease;
}
.db-fullscreen-btn:hover { background: var(--color-primary); border-color: var(--color-primary); color: #fff; }
.db-fullscreen-exit { display: none; }
.db-fullscreen-btn.is-fullscreen .db-fullscreen-enter { display: none; }
.db-fullscreen-btn.is-fullscreen .db-fullscreen-exit { display: block; }
.db-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: .65rem;
    margin-bottom: 0;
}
.db-stat-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: .65rem .85rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: var(--shadow-sm);
    transition: box-shadow var(--transition-normal), transform var(--transition-fast);
}
.db-stat-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
.db-stat-icon {
    width: 36px; height: 36px; border-radius: var(--radius-md);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.db-stat-icon-sm   { background: #e0f2fe; color: #0369a1; }
.db-stat-icon-sk   { background: #fce7f3; color: #be185d; }
.db-stat-icon-total{ background: #f3e8ff; color: #7c3aed; }
.db-stat-icon-month{ background: #dcfce7; color: #15803d; }
.db-stat-body { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.db-stat-label { font-size: 0.78rem; font-weight: 500; color: var(--color-text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.db-stat-value { font-size: 1.3rem; font-weight: 700; color: var(--color-text); letter-spacing: -0.03em; line-height: 1.1; }
.db-main-grid {
    display: grid; grid-template-columns: minmax(470px, 1.1fr) minmax(0, 1.4fr);
    gap: .65rem; min-height: 0;
}
.db-table-card { display: flex; flex-direction: column; min-height: 0; }

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
    padding: .5rem .7rem;
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
.db-table-wrap { overflow: auto; min-height: 0; flex: 1; }
.db-table { width: 100%; border-collapse: collapse; font-size: .75rem; }
.db-table th {
    padding: .38rem .55rem; text-align: left; font-size: .66rem; font-weight: 600;
    color: #ffffff; text-transform: uppercase; letter-spacing: .06em;
    background: var(--color-primary); border-bottom: none;
    white-space: nowrap; position: sticky; top: 0; z-index: 2;
}
[data-theme="dark"] .db-table th {
    background: var(--color-primary-hover, #15314f);
    color: #e2e8f0;
}
.db-table td { padding: .34rem .55rem; border-bottom: 1px solid var(--color-border); vertical-align: middle; color: var(--color-text); }
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
    gap: .65rem;
    margin-bottom: 0;
    align-items: stretch;
    min-height: 0;
}
.db-charts-grid .db-card {
    display: flex;
    flex-direction: column;
}
.db-chart-container {
    padding: .4rem;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    flex: 1;
    min-height: 0;
    position: relative;
}
/* Bar chart: canvas melebar penuh, bukan dibatasi seperti pie */
.db-chart-container canvas {
    width: 100% !important;
    max-width: 100% !important;
    height: 100% !important;
    max-height: 100% !important;
}
.db-empty {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    flex: 1; width: 100%;
    padding: 2.5rem 1rem; color: var(--color-text-muted); font-size: .85rem; text-align: center;
}

/* ── Responsive ────────────────── */
@media (min-width: 769px) and (max-width: 1100px) {
    .db-stats-grid { grid-template-columns: repeat(3, 1fr); }
    .dashboard-viewport { padding: .5rem; gap: .45rem; }
    .db-main-grid { grid-template-columns: minmax(390px, 1fr) minmax(0, 1.25fr); gap: .45rem; }
    .db-charts-grid { gap: .45rem; }
    .db-card-title { font-size: .76rem; }
    .db-badge { display: none; }
}
@media (max-width: 768px) {
    html:has(body.navbar-only), body.navbar-only { height: auto; overflow: auto !important; }
    body.navbar-only .layout-body,
    body.navbar-only .main-content,
    body.navbar-only .main-content-wrap { height: auto; min-height: calc(100dvh - var(--header-height, 64px)); overflow: visible; }
    .dashboard-viewport { height: auto; min-height: 100%; overflow: visible; }
    .db-main-grid { grid-template-columns: 1fr; }
    .db-table-wrap { max-height: 440px; }
    .db-stats-grid  { grid-template-columns: 1fr 1fr; }
    .db-charts-grid { grid-template-columns: 1fr; }
    .db-stat-value  { font-size: 1.4rem; }
    .db-chart-container { min-height: 260px; } /* reduce di tablet */
}
@media (max-width: 480px) {
    .db-fullscreen-btn span { display: none; }
    .db-fullscreen-btn { padding: .5rem; }
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
    var fullscreenButton = document.getElementById('db-fullscreen-btn');
    var fullscreenLabel = document.getElementById('db-fullscreen-label');

    function updateFullscreenButton() {
        var active = Boolean(document.fullscreenElement);
        if (!fullscreenButton) return;
        fullscreenButton.classList.toggle('is-fullscreen', active);
        fullscreenButton.setAttribute('aria-label', active ? 'Keluar dari layar penuh' : 'Aktifkan layar penuh');
        fullscreenButton.title = active ? 'Keluar dari layar penuh' : 'Tampilkan layar penuh';
        if (fullscreenLabel) fullscreenLabel.textContent = active ? 'Keluar Fullscreen' : 'Layar Penuh';
        setTimeout(function () { window.dispatchEvent(new Event('resize')); }, 100);
    }

    if (fullscreenButton && document.fullscreenEnabled) {
        fullscreenButton.addEventListener('click', function () {
            if (document.fullscreenElement) {
                document.exitFullscreen().catch(function () {});
            } else {
                document.documentElement.requestFullscreen().catch(function () {});
            }
        });
        document.addEventListener('fullscreenchange', updateFullscreenButton);
    } else if (fullscreenButton) {
        fullscreenButton.hidden = true;
    }

    // ── Deteksi dark mode ──────────────────────────────────────────────
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var gridColor   = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.06)';
    var tickColor   = isDark ? '#8892a4' : '#6b7280';
    var tooltipBg   = isDark ? '#1a2236' : '#ffffff';
    var tooltipText = isDark ? '#e2e8f0' : '#1e293b';

    // ── Palet warna harmonis (satu warna dominan per grafik) ───────────
    // SM = biru; SK = pink/rose
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

    /**
     * Buat diagram kolom (bar chart) vertikal.
     * @param {string}  canvasId  - ID elemen <canvas>
     * @param {object}  dataObj   - { "KodeKlasifikasi": jumlah, ... }
     * @param {string}  accentHex - Warna aksen utama (SM = biru, SK = pink)
     * @param {string[]} palette  - Array palet warna per bar
     */
    function buildBar(canvasId, dataObj, accentHex, palette) {
        var canvas = document.getElementById(canvasId);
        if (!canvas) return;

        var labels = Object.keys(dataObj).map(function(k) {
            // Tampilkan prefix kode saja: "PR.001" → "PR"
            return k.split('.')[0];
        });
        var values = Object.values(dataObj);
        var total  = values.reduce(function(a, b) { return a + b; }, 0);

        // Buat gradient per bar menggunakan canvas context
        var ctx = canvas.getContext('2d');
        var bgColors = palette.slice(0, labels.length).map(function(hex) {
            // Solid dengan opacity 85% untuk kesan modern
            return hex;
        });
        var hoverColors = bgColors.map(function(hex) {
            // Warna lebih terang saat hover: tambahkan channel putih
            return hex;
        });

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Surat',
                    data: values,
                    backgroundColor: bgColors.map(function(hex) {
                        // Konversi hex ke rgba dengan opacity 0.82
                        var r = parseInt(hex.slice(1,3),16);
                        var g = parseInt(hex.slice(3,5),16);
                        var b = parseInt(hex.slice(5,7),16);
                        return 'rgba('+r+','+g+','+b+',0.82)';
                    }),
                    borderColor: bgColors,
                    borderWidth: 1.5,
                    borderRadius: 6,
                    borderSkipped: false,
                    hoverBackgroundColor: bgColors,
                    barPercentage: 0.72,
                    categoryPercentage: 0.78,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 600,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: tooltipBg,
                        borderColor: accentHex + '55',
                        borderWidth: 1,
                        titleColor: tooltipText,
                        bodyColor: tickColor,
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            title: function(items) {
                                // Tampilkan kode lengkap dari label asli
                                var idx = items[0].dataIndex;
                                var fullKey = Object.keys(dataObj)[idx];
                                return fullKey;
                            },
                            label: function(ctx) {
                                var pct = total > 0 ? ((ctx.parsed.y / total) * 100).toFixed(1) : '0.0';
                                return '  Jumlah: ' + ctx.parsed.y + '  (' + pct + '% dari total)';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: {
                            color: tickColor,
                            font: { size: 11, weight: '600' },
                            maxRotation: 0,
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor, lineWidth: 1 },
                        border: { display: false, dash: [4, 4] },
                        ticks: {
                            color: tickColor,
                            font: { size: 10 },
                            precision: 0,
                            maxTicksLimit: 6,
                            callback: function(val) { return val % 1 === 0 ? val : ''; }
                        }
                    }
                }
            }
        });
    }

    // ── Render semua 4 diagram kolom ──────────────────────────────────
    buildBar('chart-sm-bulan', @json($smPieBulan), '#3b82f6', COLORS_SM);
    buildBar('chart-sk-bulan', @json($skPieBulan), '#ec4899', COLORS_SK);
    buildBar('chart-sm-tahun', @json($smPieTahun), '#3b82f6', COLORS_SM);
    buildBar('chart-sk-tahun', @json($skPieTahun), '#ec4899', COLORS_SK);
})();
</script>
@endpush
@endsection
