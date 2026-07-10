@extends('layouts.app')

@section('title', 'Report Rekapan Arsip')

@section('content')

<div class="page-header pre-hide" id="report-page-header">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <h2 class="page-title">Report Rekapan Arsip</h2>
            <p class="page-description">Laporan rekapitulasi surat masuk &amp; keluar</p>
        </div>
        <div class="report-actions no-print" style="display:flex;align-items:center;gap:.75rem;">
            <div class="report-clock" id="report-clock" style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;font-weight:600;color:var(--color-text-muted);">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span id="clock-text">--:--:--</span>
                <span id="clock-date" style="font-weight:500;"></span>
            </div>
            {{-- tombol: TIDAK pakai onclick, JS handler yang handle --}}
            <button type="button" class="btn-report-print" id="btn-print">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                    <rect x="6" y="14" width="12" height="8"/>
                </svg>
                Cetak Laporan
            </button>
        </div>
    </div>
</div>

@if(isset($error))
<div class="alert alert-error no-print" style="margin-bottom:1.5rem;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    {{ $error }}
</div>
@endif

{{-- ── Print Header (hanya tampil saat print) ──────────────────────────── --}}
<div class="print-header" id="print-header-wrap">
    <div class="print-header-inner">
        <div class="print-logo-wrap">
            <div class="print-logo-text">BPSDMP</div>
        </div>
        <div class="print-header-text">
            <h1 class="print-title">LAPORAN REKAPITULASI ARSIP SURAT</h1>
            <p class="print-subtitle">Balai Pengembangan Sumber Daya Manusia dan Penelitian (BPSDMP)</p>
            <p class="print-meta" id="print-meta-date">Dicetak pada: {{ now()->format('d F Y, H:i') }} WIB</p>
        </div>
    </div>
    <div class="print-divider"></div>
</div>

{{-- ── TAB NAVIGASI (hanya layar, tidak tercetak) ─────────────────────── --}}
<div class="report-tabs no-print pre-hide">
    <button class="report-tab active" data-tab="bulanan">Rekap Bulanan</button>
    <button class="report-tab" data-tab="tahunan">Rekap Tahunan</button>
    <button class="report-tab" data-tab="klasifikasi">Per Klasifikasi</button>
</div>

{{-- ── REKAP BULANAN PER TAHUN ─────────────────────────────────────────── --}}
<div class="report-section pre-hide" id="tab-bulanan">
    {{-- Filter Tahun + Print Tab --}}
    <div class="report-filter-bar no-print" style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap;">
        <div class="filter-group" style="display:flex;align-items:center;gap:.4rem;">
            <label for="filter-tahun-bulanan" style="font-size:.83rem;font-weight:600;color:var(--color-text-muted);">Filter Tahun:</label>
            <select id="filter-tahun-bulanan" class="filter-select" style="min-width:110px;">
                <option value="">Semua Tahun</option>
                @php
                    $tahun5 = [];
                    $now5 = (int) date('Y');
                    for ($y = $now5; $y >= $now5 - 4; $y--) {
                        if (isset($rekapPerTahun[$y])) $tahun5[] = $y;
                    }
                    foreach(array_keys($rekapPerTahun) as $y) {
                        if (!in_array($y, $tahun5)) $tahun5[] = $y;
                    }
                    rsort($tahun5);
                @endphp
                @foreach($tahun5 as $ty)
                <option value="{{ $ty }}">{{ $ty }}</option>
                @endforeach
            </select>
        </div>
        <button type="button" class="btn-report-tab-print" id="btn-print-bulanan" style="margin-left:auto;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Cetak Tab Ini
        </button>
    </div>
    @if(empty($rekapPerTahun))
        <div class="report-empty">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <span>Belum ada data tersedia</span>
        </div>
    @else
        @foreach($rekapPerTahun as $year => $rekap)
        <div class="report-card" data-year="{{ $year }}" style="margin-bottom:2rem; page-break-inside:avoid;">
            <div class="report-card-header">
                <div class="report-card-title-wrap">
                    <div class="report-card-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <h3 class="report-card-title">Rekap Bulanan Tahun {{ $year }}</h3>
                </div>
                <span class="report-badge">Total: {{ $rekap['total'] }} Surat</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th class="th-no">No</th>
                            <th>Bulan</th>
                            <th class="th-num">Surat Masuk</th>
                            <th class="th-num">Surat Keluar</th>
                            <th class="th-num">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rekap['rows'] as $i => $row)
                        <tr class="{{ ($row['bulan_no'] == $currentMonth && $year == $currentYear) ? 'row-active' : '' }} {{ $row['total'] == 0 ? 'row-empty' : '' }}">
                            <td class="td-no">{{ $i + 1 }}</td>
                            <td class="td-bulan">
                                {{ $row['bulan'] }}
                                @if($row['bulan_no'] == $currentMonth && $year == $currentYear)
                                    <span class="badge-month">Berjalan</span>
                                @endif
                            </td>
                            <td class="td-num">
                                @if($row['sm_count'] > 0)
                                    <span class="count-sm">{{ $row['sm_count'] }}</span>
                                @else
                                    <span class="count-zero">—</span>
                                @endif
                            </td>
                            <td class="td-num">
                                @if($row['sk_count'] > 0)
                                    <span class="count-sk">{{ $row['sk_count'] }}</span>
                                @else
                                    <span class="count-zero">—</span>
                                @endif
                            </td>
                            <td class="td-num">
                                @if($row['total'] > 0)
                                    <strong>{{ $row['total'] }}</strong>
                                @else
                                    <span class="count-zero">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="tfoot-total">
                            <td colspan="2" class="tfoot-label">TOTAL TAHUN {{ $year }}</td>
                            <td class="td-num"><strong>{{ $rekap['total_sm'] }}</strong></td>
                            <td class="td-num"><strong>{{ $rekap['total_sk'] }}</strong></td>
                            <td class="td-num"><strong>{{ $rekap['total'] }}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endforeach
    @endif
</div>

{{-- ── REKAP TAHUNAN ─────────────────────────────────────────────────── --}}
<div class="report-section pre-hide" id="tab-tahunan" style="display:none;">
    {{-- Filter Tahun + Print Tab --}}
    <div class="report-filter-bar no-print" style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap;">
        <div class="filter-group" style="display:flex;align-items:center;gap:.4rem;">
            <label for="filter-tahun-tahunan" style="font-size:.83rem;font-weight:600;color:var(--color-text-muted);">Filter Tahun:</label>
            <select id="filter-tahun-tahunan" class="filter-select" style="min-width:110px;">
                <option value="">Semua Tahun</option>
                @foreach($tahun5 as $ty)
                <option value="{{ $ty }}">{{ $ty }}</option>
                @endforeach
            </select>
        </div>
        <button type="button" class="btn-report-tab-print" id="btn-print-tahunan" style="margin-left:auto;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Cetak Tab Ini
        </button>
    </div>
    <div class="report-card" style="page-break-inside:avoid;">

        <div class="report-card-header">
            <div class="report-card-title-wrap">
                <div class="report-card-icon">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                </div>
                <h3 class="report-card-title">Rekap Tahunan</h3>
            </div>
            <span class="report-badge">{{ count($rekapTahunan) }} Tahun</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="report-table" id="tabel-tahunan">
                <thead>
                    <tr>
                        <th class="th-no">No</th>
                        <th>Tahun</th>
                        <th class="th-num">Surat Masuk</th>
                        <th class="th-num">Surat Keluar</th>
                        <th class="th-num">Total</th>
                        <th class="th-expand no-print">Detail Kode</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rekapTahunan as $i => $row)
                    {{-- Baris tahun utama --}}
                    <tr class="row-tahun {{ $row['tahun'] == $currentYear ? 'row-active' : '' }}"
                        data-year="{{ $row['tahun'] }}"
                        style="cursor:pointer;"
                        title="Klik untuk lihat detail per kode klasifikasi">
                        <td class="td-no">{{ $i + 1 }}</td>
                        <td class="td-bulan">
                            <span class="tahun-toggle-icon no-print" style="display:inline-block;margin-right:.4rem;transition:transform .2s;">&#9654;</span>
                            <strong>{{ $row['tahun'] }}</strong>
                            @if($row['tahun'] == $currentYear)
                                <span class="badge-month">Berjalan</span>
                            @endif
                        </td>
                        <td class="td-num"><span class="count-sm">{{ $row['sm_count'] }}</span></td>
                        <td class="td-num"><span class="count-sk">{{ $row['sk_count'] }}</span></td>
                        <td class="td-num"><strong>{{ $row['total'] }}</strong></td>
                        <td class="td-center no-print" style="font-size:.75rem;color:var(--color-primary);">
                            {{ count($row['kode_detail']) }} kode
                        </td>
                    </tr>
                    {{-- Baris detail klasifikasi (tersembunyi default) --}}
                    <tr class="row-kode-detail" id="detail-{{ $row['tahun'] }}" style="display:none;">
                        <td colspan="6" style="padding:0;">
                            <div class="kode-detail-wrap">
                                <table class="kode-detail-table">
                                    <thead>
                                        <tr>
                                            <th>Kode Klasifikasi</th>
                                            <th class="th-num">Surat Masuk</th>
                                            <th class="th-num">Surat Keluar</th>
                                            <th class="th-num">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($row['kode_detail'] as $kd)
                                        <tr>
                                            <td><span class="badge-kode-report">{{ $kd['kode'] }}</span></td>
                                            <td class="td-num">
                                                @if($kd['sm_count'] > 0) <span class="count-sm">{{ $kd['sm_count'] }}</span>
                                                @else <span class="count-zero">&mdash;</span> @endif
                                            </td>
                                            <td class="td-num">
                                                @if($kd['sk_count'] > 0) <span class="count-sk">{{ $kd['sk_count'] }}</span>
                                                @else <span class="count-zero">&mdash;</span> @endif
                                            </td>
                                            <td class="td-num"><strong>{{ $kd['total'] }}</strong></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="td-empty-row">Belum ada data</td></tr>
                    @endforelse
                </tbody>
                @if(count($rekapTahunan) > 0)
                <tfoot>
                    <tr class="tfoot-total">
                        <td colspan="2" class="tfoot-label">TOTAL KESELURUHAN</td>
                        <td class="td-num"><strong>{{ collect($rekapTahunan)->sum('sm_count') }}</strong></td>
                        <td class="td-num"><strong>{{ collect($rekapTahunan)->sum('sk_count') }}</strong></td>
                        <td class="td-num"><strong>{{ collect($rekapTahunan)->sum('total') }}</strong></td>
                        <td class="no-print"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

{{-- ── REKAP PER KLASIFIKASI ───────────────────────────────────────────── --}}
<div class="report-section pre-hide" id="tab-klasifikasi" style="display:none;">
    {{-- Filter Tahun + Print Tab --}}
    <div class="report-filter-bar no-print" style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap;">
        <div class="filter-group" style="display:flex;align-items:center;gap:.4rem;">
            <label for="filter-tahun-klasifikasi" style="font-size:.83rem;font-weight:600;color:var(--color-text-muted);">Filter Tahun:</label>
            <select id="filter-tahun-klasifikasi" class="filter-select" style="min-width:110px;">
                <option value="">Semua Tahun</option>
                @foreach($tahun5 as $ty)
                <option value="{{ $ty }}">{{ $ty }}</option>
                @endforeach
            </select>
        </div>
        <button type="button" class="btn-report-tab-print" id="btn-print-klasifikasi" style="margin-left:auto;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Cetak Tab Ini
        </button>
    </div>
    <div class="report-card" style="page-break-inside:avoid;">
        <div class="report-card-header">
            <div class="report-card-title-wrap">
                <div class="report-card-icon">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                </div>
                <h3 class="report-card-title">Rekap Per Kode Klasifikasi (Semua Tahun)</h3>
            </div>
            <span class="report-badge">{{ count($rekapKlasifikasi) }} Kode</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="report-table">
                <thead>
                    <tr>
                        <th class="th-no">No</th>
                        <th>Kode Klasifikasi</th>
                        <th class="th-num">Surat Masuk</th>
                        <th class="th-num">Surat Keluar</th>
                        <th class="th-num">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rekapKlasifikasi as $i => $row)
                    <tr>
                        <td class="td-no">{{ $i + 1 }}</td>
                        <td><span class="badge-kode-report">{{ $row['kode'] }}</span></td>
                        <td class="td-num"><span class="count-sm">{{ $row['sm_count'] }}</span></td>
                        <td class="td-num"><span class="count-sk">{{ $row['sk_count'] }}</span></td>
                        <td class="td-num"><strong>{{ $row['total'] }}</strong></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="td-empty-row">Belum ada data</td></tr>
                    @endforelse
                </tbody>
                @if(count($rekapKlasifikasi) > 0)
                <tfoot>
                    <tr class="tfoot-total">
                        <td colspan="2" class="tfoot-label">TOTAL SEMUA KLASIFIKASI</td>
                        <td class="td-num"><strong>{{ collect($rekapKlasifikasi)->sum('sm_count') }}</strong></td>
                        <td class="td-num"><strong>{{ collect($rekapKlasifikasi)->sum('sk_count') }}</strong></td>
                        <td class="td-num"><strong>{{ collect($rekapKlasifikasi)->sum('total') }}</strong></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

{{-- ── TANDA TANGAN (hanya muncul saat print) ──────────────────────────── --}}
<div class="print-signature" id="print-signature-wrap">
    <div class="sig-info">
        <p>Laporan ini digenerate otomatis oleh Sistem Arsip Surat Digital BPSDMP</p>
        <p>Tanggal Cetak: <span id="sig-date">{{ now()->format('d F Y') }}</span></p>
    </div>
    <div class="sig-boxes">
        <div class="sig-box">
            <p>Mengetahui,</p>
            <div class="sig-space"></div>
            <p><strong>Kepala BPSDMP</strong></p>
            <p>____________________</p>
        </div>
        <div class="sig-box">
            <p>Dibuat oleh,</p>
            <div class="sig-space"></div>
            <p><strong>Pengelola Arsip</strong></p>
            <p>____________________</p>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
/* ═══════════════════════════════════════════════════════
   REPORT PAGE STYLES
   ═══════════════════════════════════════════════════════ */

/* ── Tombol Cetak ──────────────────────────────────────── */
.report-actions { display:flex; gap:.75rem; align-items:center; }
.btn-report-print {
    display: inline-flex; align-items: center; gap: .5rem;
    padding: .6rem 1.25rem;
    background: linear-gradient(135deg, var(--color-primary, #1e3a5f), #2563eb);
    color: #fff; border: none; border-radius: .5rem;
    font-size: .875rem; font-weight: 600; font-family: inherit;
    cursor: pointer; transition: all .2s;
    box-shadow: 0 2px 8px rgba(37,99,235,.25);
}
.btn-report-print:hover { opacity:.9; transform:translateY(-1px); box-shadow: 0 4px 16px rgba(37,99,235,.35); }

/* ── Tombol Cetak Per Tab ──────────────────────────────── */
.btn-report-tab-print {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .45rem 1rem;
    background: transparent;
    color: var(--color-primary, #1e3a5f);
    border: 1.5px solid var(--color-primary, #1e3a5f);
    border-radius: .5rem;
    font-size: .8rem; font-weight: 600; font-family: inherit;
    cursor: pointer; transition: all .2s;
}
.btn-report-tab-print:hover {
    background: var(--color-primary, #1e3a5f); color: #fff;
    transform: translateY(-1px); box-shadow: 0 2px 8px rgba(37,99,235,.2);
}


/* ── Tab Navigasi ─────────────────────────────────────── */
.report-tabs {
    display: flex; gap: .375rem; margin-bottom: 1.5rem;
    border-bottom: 2px solid var(--color-border, #2a3347);
    padding-bottom: 0;
}
.report-tab {
    padding: .55rem 1.1rem; border: none; background: transparent;
    color: var(--color-text-muted, #8892a4); font-size: .875rem;
    font-weight: 600; font-family: inherit; cursor: pointer;
    border-bottom: 2px solid transparent; margin-bottom: -2px;
    border-radius: .375rem .375rem 0 0; transition: all .2s;
}
.report-tab:hover { color: var(--color-text, #e2e8f0); background: var(--color-primary-light); }
.report-tab.active {
    color: var(--color-primary, #4f8ef7); border-bottom-color: var(--color-primary, #4f8ef7);
    background: var(--color-primary-light);
}

/* ── Report Card ─────────────────────────────────────── */
.report-card {
    background: var(--color-surface, #1a2236);
    border: 1px solid var(--color-border, #2a3347);
    border-radius: .75rem; overflow: hidden;
}
.report-card-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1rem 1.25rem; border-bottom: 1px solid var(--color-border, #2a3347);
    flex-wrap: wrap; gap: .5rem;
}
.report-card-title-wrap { display: flex; align-items: center; gap: .6rem; }
.report-card-icon {
    width: 30px; height: 30px; border-radius: .5rem;
    background: var(--color-primary-light); color: var(--color-primary);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.report-card-title { font-size: .9375rem; font-weight: 700; color: var(--color-text); margin: 0; }
.report-badge {
    display: inline-flex; align-items: center; padding: .2rem .7rem;
    background: var(--color-primary-light); color: var(--color-primary);
    border: 1px solid var(--color-border); border-radius: 999px;
    font-size: .72rem; font-weight: 600; white-space: nowrap;
}

/* ── Report Table ────────────────────────────────────── */
.report-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
.report-table th {
    background: var(--color-primary, #1e3a5f); color: #fff;
    font-size: .72rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: .06em; padding: .7rem 1rem; text-align: left;
    white-space: nowrap;
}
.report-table td {
    padding: .65rem 1rem; border-bottom: 1px solid var(--color-border, #2a3347);
    color: var(--color-text); vertical-align: middle;
}
.report-table tbody tr:last-child td { border-bottom: none; }
.report-table tbody tr:hover td { background: rgba(99,102,241,.05); }
.report-table tfoot tr td { border-top: 2px solid var(--color-border); border-bottom: none; }
.th-no { width: 48px; }
.th-num { width: 120px; text-align: right !important; }
.td-no { text-align: center; font-weight: 600; color: var(--color-text-muted); width: 48px; }
.td-bulan { font-weight: 500; }
.td-num { text-align: right; }
.td-empty-row { text-align: center; padding: 2rem 1rem !important; color: var(--color-text-muted); }
.tfoot-total { background: var(--color-primary-light); }
.tfoot-label { font-weight: 700; font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; color: var(--color-primary); }

.count-sm {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 28px; padding: .15rem .45rem; border-radius: .3rem;
    background: rgba(99,102,241,.1); color: #818cf8;
    font-weight: 600; font-size: .8rem;
}
.count-sk {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 28px; padding: .15rem .45rem; border-radius: .3rem;
    background: rgba(16,185,129,.1); color: #34d399;
    font-weight: 600; font-size: .8rem;
}
.count-zero { color: var(--color-text-muted); opacity: .4; }
.row-active td { background: rgba(99,102,241,.06) !important; }
.row-empty td { opacity: .45; }
.badge-month {
    display: inline-block; padding: .1rem .45rem; border-radius: .25rem;
    background: rgba(245,158,11,.15); color: #f59e0b;
    font-size: .68rem; font-weight: 700; margin-left: .4rem; vertical-align: middle;
}
.badge-kode-report {
    display: inline-block; padding: .2rem .6rem; border-radius: .375rem;
    background: var(--color-primary-light); color: var(--color-primary);
    font-size: .78rem; font-weight: 700; border: 1px solid var(--color-border);
}

/* ── Empty State ───────────────────────────────────────── */
.report-empty {
    display: flex; flex-direction: column; align-items: center; gap: .75rem;
    padding: 3rem 1rem; color: var(--color-text-muted); text-align: center;
}
.report-empty svg { opacity: .3; }

/* ── Alert ─────────────────────────────────────────────── */
.alert { display:flex; align-items:center; gap:.5rem; padding:.75rem 1rem; border-radius:.5rem; font-size:.875rem; }
.alert-error { background:rgba(239,68,68,.1); color:#f87171; border:1px solid rgba(239,68,68,.2); }

/* ── Accordion Kode Detail ──────────────────────────────── */
.row-tahun:hover td { background: rgba(99,102,241,.08) !important; }
.row-tahun.expanded td { background: rgba(99,102,241,.07) !important; }
.tahun-toggle-icon { font-size: .75rem; color: var(--color-primary); }
.th-expand { width: 90px; text-align: center !important; }
.td-center  { text-align: center; }

.kode-detail-wrap {
    background: var(--color-bg, #0f172a);
    border-top: 1px dashed var(--color-border);
    padding: .75rem 1.5rem 1rem;
}
.kode-detail-table {
    width: 100%; border-collapse: collapse;
    font-size: .82rem;
}
.kode-detail-table th {
    background: rgba(99,102,241,.12);
    color: var(--color-primary);
    font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em;
    padding: .45rem .75rem; text-align: left; white-space: nowrap;
    border-bottom: 1px solid var(--color-border);
}
.kode-detail-table td {
    padding: .45rem .75rem;
    border-bottom: 1px solid rgba(255,255,255,.04);
    color: var(--color-text); vertical-align: middle;
}
.kode-detail-table tbody tr:last-child td { border-bottom: none; }
.kode-detail-table tbody tr:hover td { background: rgba(99,102,241,.05); }


/* ══════════════════════════════════════════════════════════
   PRINT STYLES
   ══════════════════════════════════════════════════════════ */
.print-header   { display: none; }   /* Tersembunyi di layar, tampil di print via JS beforeprint */
.print-signature{ display: none; }   /* Sama */

@page {
    size: A4 portrait;
    margin: 14mm 14mm 14mm 14mm;
}

@media print {
    /* ── Sembunyikan elemen UI ── */
    .no-print,
    .sidebar,
    .header,
    .footer,
    .sidebar-overlay,
    .drawer-footer-fixed,
    #page-loading-bar,
    #toast-container,
    .report-tabs,
    .page-header,
    #report-page-header { display: none !important; }

    /* ── Show print-only elements (toggled via JS beforeprint/afterprint) ── */
    /* Handled by JS; CSS ensures correct print styles */

    /* ── Semua section tampil saat print ── */
    /* JS beforeprint sets display:block on all sections */
    .report-section { margin-bottom: 1.5rem; }

    /* ── Layout print ── */
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    html, body { background: #fff !important; color: #000 !important; font-size: 10pt; }
    .main-content { margin-left: 0 !important; padding: 0 !important; }
    .main-content-wrap { max-width: 100% !important; padding: 0 !important; }
    .layout-body { display: block !important; }
    .main-content-wrap > *:first-child { margin-top: 0 !important; padding-top: 0 !important; }

    /* ── Print header ── */
    .print-header {
        display: block !important;
        margin-bottom: 1rem;
    }
    .print-header-inner {
        display: flex !important; align-items: flex-start; gap: 1rem;
        margin-bottom: .5rem;
    }
    .print-logo-text {
        font-size: 16pt; font-weight: 900; color: #1e3a5f;
        letter-spacing: .05em; border: 3px solid #1e3a5f;
        padding: .25rem .6rem; border-radius: 4px;
        flex-shrink: 0;
    }
    .print-header-text { flex: 1; }
    .print-title { font-size: 12pt; font-weight: 700; color: #1e3a5f; margin: 0 0 .15rem; text-transform: uppercase; letter-spacing: .04em; }
    .print-subtitle { font-size: 9pt; color: #444; margin: 0 0 .1rem; }
    .print-meta { font-size: 8pt; color: #666; margin: 0; }
    .print-divider { border: none; border-top: 2px solid #1e3a5f; margin: .6rem 0 1rem; }

    /* ── Report card ── */
    .report-card {
        border: 1px solid #bbb !important;
        background: #fff !important;
        box-shadow: none !important;
        border-radius: 4px !important;
        margin-bottom: 1rem !important;
        page-break-inside: avoid;
        overflow: visible !important;
    }
    .report-card-header {
        background: #eef2f7 !important;
        border-bottom: 1px solid #bbb !important;
        padding: .5rem .75rem !important;
    }
    .report-card-title-wrap { display: flex !important; align-items: center; gap: .5rem; }
    .report-card-icon { display: none !important; } /* simplify for print */
    .report-card-title { color: #1e3a5f !important; font-size: 10pt !important; font-weight: 700 !important; }
    .report-badge { background: #e0e8f2 !important; color: #1e3a5f !important; border: 1px solid #bbb !important; font-size: 7.5pt !important; }

    /* ── Table ── */
    .report-table { width: 100% !important; border-collapse: collapse !important; font-size: 9pt !important; }
    .report-table th {
        background: #1e3a5f !important; color: #fff !important;
        font-size: 8pt !important; padding: .35rem .6rem !important;
        border: 1px solid #1e3a5f !important;
    }
    .report-table td {
        color: #000 !important;
        border: 1px solid #ccc !important;
        padding: .3rem .6rem !important;
        font-size: 9pt !important;
    }
    .report-table tbody tr:hover td { background: transparent !important; }
    .report-table tfoot tr td { border-top: 2px solid #999 !important; }
    .tfoot-total { background: #eef2f7 !important; }
    .tfoot-label { color: #1e3a5f !important; font-weight: 700 !important; }
    .row-active td { background: #ebf3ff !important; }
    .row-empty td { opacity: .5 !important; }

    .count-sm { background: #dde8ff !important; color: #1e3a5f !important; padding: .1rem .35rem !important; border-radius: 3px !important; font-size: 8pt !important; }
    .count-sk { background: #d1fae5 !important; color: #065f46 !important; padding: .1rem .35rem !important; border-radius: 3px !important; font-size: 8pt !important; }
    .count-zero { color: #aaa !important; opacity: 1 !important; }
    .badge-month { background: #fef3c7 !important; color: #92400e !important; font-size: 7pt !important; }
    .badge-kode-report { background: #e0e8f2 !important; color: #1e3a5f !important; border: 1px solid #bbb !important; }
    .td-num { text-align: right !important; }
    .th-num { text-align: right !important; }

    /* ── Signature ── */
    .print-signature {
        display: block !important;
        margin-top: 2.5rem;
        page-break-inside: avoid;
    }
    .sig-info { font-size: 8pt; color: #555; margin-bottom: 1.5rem; }
    .sig-info p { margin: .1rem 0; }
    .sig-boxes { display: flex !important; justify-content: space-around; }
    .sig-box { text-align: center; font-size: 9pt; }
    .sig-space { height: 50px; }
    .sig-box p { margin: .1rem 0; }

    /* ── Fix animasi pre-hide: paksa opacity penuh saat print ── */
    .pre-hide,
    .pre-hide.is-visible,
    .pre-hide:not(.is-visible) {
        opacity: 1 !important;
        transform: none !important;
        transition: none !important;
        visibility: visible !important;
    }

    /* ── Accordion kode detail: tampil saat print ── */
    .row-kode-detail { display: table-row !important; }
    .kode-detail-wrap { padding: .5rem 1rem !important; }
    .kode-detail-table { font-size: 8.5pt !important; }
    .kode-detail-table th { font-size: 7.5pt !important; padding: .3rem .5rem !important; }
    .kode-detail-table td { padding: .3rem .5rem !important; }
    .no-print { display: none !important; }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var BULAN_ID = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    var HARI_ID  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    function pad(n) { return String(n).padStart(2,'0'); }
    function formatDateID(d) { return HARI_ID[d.getDay()]+', '+d.getDate()+' '+BULAN_ID[d.getMonth()]+' '+d.getFullYear(); }
    function formatDateShortID(d) { return d.getDate()+' '+BULAN_ID[d.getMonth()]+' '+d.getFullYear(); }

    // ── Tab Navigation ────────────────────────────────────────────────
    var tabs     = document.querySelectorAll('.report-tab');
    var sections = document.querySelectorAll('.report-section');

    function showTab(target) {
        // Hanya atur visibility section — active class dikelola di click handler
        sections.forEach(function(sec) {
            sec.style.display = (sec.id === 'tab-' + target) ? '' : 'none';
        });
    }

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            // Hapus active dari semua, lalu tambah ke yang diklik
            tabs.forEach(function(t) { t.classList.remove('active'); });
            this.classList.add('active');
            showTab(this.dataset.tab);
        });
    });

    // Set active class ke tab pertama pada load (sudah ada 'active' di HTML, tapi pastikan)
    var initActiveTab = document.querySelector('.report-tab.active');
    if (!initActiveTab && tabs.length > 0) {
        tabs[0].classList.add('active');
    }

    // ── Print: cetak HANYA tab yang sedang aktif ─────────────────────────
    function preparePrint() {
        // Dapatkan tab aktif
        var activeTab = document.querySelector('.report-tab.active');
        var activeId  = activeTab ? activeTab.dataset.tab : 'bulanan';

        // Tampilkan HANYA section yang aktif
        sections.forEach(function(sec) {
            if (sec.id === 'tab-' + activeId) {
                sec.style.display      = 'block';
                sec.style.marginBottom = '0';
                sec.classList.remove('pre-hide');
                sec.classList.add('is-visible');
            } else {
                sec.style.display = 'none';
            }
        });

        // Jika tab tahunan aktif: buka semua accordion detail kode
        if (activeId === 'tahunan') {
            document.querySelectorAll('.row-kode-detail').forEach(function(r) {
                r.style.display = 'table-row';
            });
            document.querySelectorAll('.tahun-toggle-icon').forEach(function(ic) {
                ic.style.transform = 'rotate(90deg)';
            });
        }

        // Tampilkan print header & signature, sembunyikan page header
        var hdr      = document.getElementById('print-header-wrap');
        var sig      = document.getElementById('print-signature-wrap');
        var pgHeader = document.getElementById('report-page-header');
        if (hdr)      hdr.style.display      = 'block';
        if (sig)      sig.style.display      = 'block';
        if (pgHeader) pgHeader.style.display = 'none';

        // Update teks tanggal cetak
        var metaDate = document.getElementById('print-meta-date');
        if (metaDate) {
            var n = new Date();
            metaDate.textContent = 'Dicetak pada: '+formatDateID(n)+', '+pad(n.getHours())+':'+pad(n.getMinutes())+' WIB';
        }
        var sigDate = document.getElementById('sig-date');
        if (sigDate) sigDate.textContent = formatDateShortID(new Date());
    }

    function restoreAfterPrint() {
        var activeTab = document.querySelector('.report-tab.active');
        var activeId  = activeTab ? activeTab.dataset.tab : 'bulanan';
        sections.forEach(function(sec) {
            sec.style.display      = (sec.id === 'tab-' + activeId) ? '' : 'none';
            sec.style.marginBottom = '';
        });
        var hdr      = document.getElementById('print-header-wrap');
        var sig      = document.getElementById('print-signature-wrap');
        var pgHeader = document.getElementById('report-page-header');
        if (hdr)      hdr.style.display      = 'none';
        if (sig)      sig.style.display      = 'none';
        if (pgHeader) pgHeader.style.display = '';
    }

    // beforeprint/afterprint events
    window.addEventListener('beforeprint', preparePrint);
    window.addEventListener('afterprint',  restoreAfterPrint);

    // Tombol Cetak
    var btnPrint = document.getElementById('btn-print');
    if (btnPrint) {
        btnPrint.addEventListener('click', function() {
            preparePrint();
            window.print();
        });
    }

    // ── Accordion Rekap Tahunan ────────────────────────────────────────

    document.querySelectorAll('.row-tahun').forEach(function(row) {
        row.addEventListener('click', function() {
            var year   = this.getAttribute('data-year');
            var detail = document.getElementById('detail-' + year);
            var icon   = this.querySelector('.tahun-toggle-icon');
            if (!detail) return;

            var isOpen = (detail.style.display !== 'none' && detail.style.display !== '');
            if (isOpen) {
                detail.style.display = 'none';
                this.classList.remove('expanded');
                if (icon) icon.style.transform = '';
            } else {
                detail.style.display = 'table-row';
                this.classList.add('expanded');
                if (icon) icon.style.transform = 'rotate(90deg)';
            }
        });
    });

    // ── Filter Tahun: Tab Bulanan ────────────────────────────────────────
    var selTahunBulanan = document.getElementById('filter-tahun-bulanan');
    if (selTahunBulanan) {
        selTahunBulanan.addEventListener('change', function() {
            var tahun = this.value;
            document.querySelectorAll('#tab-bulanan .report-card[data-year]').forEach(function(card) {
                card.style.display = (!tahun || card.getAttribute('data-year') === tahun) ? '' : 'none';
            });
        });
    }

    // ── Filter Tahun: Tab Tahunan ────────────────────────────────────────
    var selTahunTahunan = document.getElementById('filter-tahun-tahunan');
    if (selTahunTahunan) {
        selTahunTahunan.addEventListener('change', function() {
            var tahun = this.value;
            document.querySelectorAll('#tabel-tahunan tbody .row-tahun').forEach(function(tr) {
                var yr = tr.getAttribute('data-year') || '';
                var show = !tahun || yr === tahun;
                tr.style.display = show ? '' : 'none';
                // Sembunyikan juga baris detail jika tahun-nya tidak ditampilkan
                var detail = document.getElementById('detail-' + yr);
                if (detail && !show) detail.style.display = 'none';
            });
        });
    }

    // ── Filter Tahun: Tab Klasifikasi (jika ada data per tahun) ─────────
    // Data per tahun disediakan inline (tidak perlu filter server-side karena data flat)
    var selTahunKlas = document.getElementById('filter-tahun-klasifikasi');
    if (selTahunKlas) {
        selTahunKlas.addEventListener('change', function() {
            var tahun = this.value;
            // Jika "Semua Tahun", tampilkan semua baris klasifikasi
            // Jika tahun dipilih, filter berdasarkan data-years attribute pada baris
            document.querySelectorAll('#tab-klasifikasi table tbody tr[data-years]').forEach(function(tr) {
                if (!tahun) { tr.style.display = ''; return; }
                var years = (tr.getAttribute('data-years') || '').split(',');
                tr.style.display = years.indexOf(tahun) > -1 ? '' : 'none';
            });
            // Fallback: jika baris tidak punya data-years, tampilkan semua
            document.querySelectorAll('#tab-klasifikasi table tbody tr:not([data-years])').forEach(function(tr) {
                tr.style.display = '';
            });
        });
    }

    // ── Tombol Cetak Per Tab ─────────────────────────────────────────────
    ['bulanan','tahunan','klasifikasi'].forEach(function(tab) {
        var btn = document.getElementById('btn-print-' + tab);
        if (!btn) return;
        btn.addEventListener('click', function() {
            // Aktifkan tab yang sesuai sebelum print
            var tabBtn = document.querySelector('.report-tab[data-tab="' + tab + '"]');
            if (tabBtn && !tabBtn.classList.contains('active')) {
                tabBtn.click();
                setTimeout(function() { preparePrint(); window.print(); }, 50);
            } else {
                preparePrint();
                window.print();
            }
        });
    });

    // ── Live Clock ────────────────────────────────────────────────────
    var clockText = document.getElementById('clock-text');
    var clockDate = document.getElementById('clock-date');

    function tickClock() {
        var now = new Date();
        if (clockText) clockText.textContent = pad(now.getHours())+':'+pad(now.getMinutes())+':'+pad(now.getSeconds());
        if (clockDate) clockDate.textContent = formatDateID(now);
    }
    tickClock();
    setInterval(tickClock, 1000);

    // Init: sembunyikan header/sig di layar
    restoreAfterPrint();
});
</script>
@endpush

