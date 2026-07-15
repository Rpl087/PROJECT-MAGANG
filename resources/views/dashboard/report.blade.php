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
            <img class="print-logo-image" src="{{ route('image.view', 'komdigi-logo.png') }}" alt="Logo Komdigi">
        </div>
        <div class="print-header-text">
            <h1 class="print-title">LAPORAN REKAPITULASI ARSIP SURAT</h1>
            <p class="print-subtitle">Balai Pengembangan Sumber Daya Manusia dan Penelitian (BPSDMP)</p>
            <p class="print-meta" id="print-meta-date">Dicetak pada: {{ now()->format('d F Y, H:i') }} WITA</p>
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
    {{-- Filter Tahun --}}
    <div class="report-filter-bar no-print">
        <div class="filter-group" style="display:flex;align-items:center;gap:.4rem;">
            <label for="filter-tahun-bulanan" style="font-size:.82rem;font-weight:600;color:var(--color-text-muted);">Filter Tahun:</label>
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
                    <thead><tr><th class="th-no">No</th><th>Bulan</th><th class="th-num">Surat Masuk</th><th class="th-num">Surat Keluar</th><th class="th-num">Total</th><th class="th-expand no-print">Detail Kode</th></tr></thead>
                    <tbody>
                        @foreach($rekap['rows'] as $i => $row)
                        @php
                            $monthKey = $year . '-' . str_pad($row['bulan_no'], 2, '0', STR_PAD_LEFT);
                            $monthDetails = $klasifikasiPerBulan[$monthKey] ?? [];
                        @endphp
                        <tr class="row-bulan {{ ($row['bulan_no'] == $currentMonth && $year == $currentYear) ? 'row-active' : '' }} {{ $row['total'] == 0 ? 'row-empty' : 'row-bulan-expandable' }}" data-month-key="{{ $monthKey }}" @if($row['total'] > 0) title="Klik untuk lihat detail per kode klasifikasi" @endif>
                            <td class="td-no">{{ $i + 1 }}</td>
                            <td class="td-bulan">@if($row['total'] > 0)<span class="bulan-toggle-icon no-print" aria-hidden="true">&#9654;</span>@endif {{ $row['bulan'] }} @if($row['bulan_no'] == $currentMonth && $year == $currentYear)<span class="badge-month">Berjalan</span>@endif</td>
                            <td class="td-num">@if($row['sm_count'] > 0)<span class="count-sm">{{ $row['sm_count'] }}</span>@else<span class="count-zero">&mdash;</span>@endif</td>
                            <td class="td-num">@if($row['sk_count'] > 0)<span class="count-sk">{{ $row['sk_count'] }}</span>@else<span class="count-zero">&mdash;</span>@endif</td>
                            <td class="td-num">@if($row['total'] > 0)<strong>{{ $row['total'] }}</strong>@else<span class="count-zero">&mdash;</span>@endif</td>
                            <td class="td-center no-print detail-code-count">@if($row['total'] > 0){{ count($monthDetails) }} kode @else<span class="count-zero">&mdash;</span>@endif</td>
                        </tr>
                        @if($row['total'] > 0)
                        <tr class="row-kode-detail row-kode-detail-bulan" id="detail-bulan-{{ $monthKey }}" style="display:none;"><td colspan="6" style="padding:0;"><div class="kode-detail-wrap"><table class="kode-detail-table">
                            <thead><tr><th>Kode Klasifikasi</th><th class="th-num">Surat Masuk</th><th class="th-num">Surat Keluar</th><th class="th-num">Total</th></tr></thead>
                            <tbody>@foreach($monthDetails as $kode => $counts)<tr><td><span class="badge-kode-report">{{ $kode }}</span></td><td class="td-num">@if($counts[0] > 0)<span class="count-sm">{{ $counts[0] }}</span>@else<span class="count-zero">&mdash;</span>@endif</td><td class="td-num">@if($counts[1] > 0)<span class="count-sk">{{ $counts[1] }}</span>@else<span class="count-zero">&mdash;</span>@endif</td><td class="td-num"><strong>{{ $counts[2] }}</strong></td></tr>@endforeach</tbody>
                        </table></div></td></tr>
                        @endif
                        @endforeach
                    </tbody>
                    <tfoot><tr class="tfoot-total"><td colspan="2" class="tfoot-label">TOTAL TAHUN {{ $year }}</td><td class="td-num"><strong>{{ $rekap['total_sm'] }}</strong></td><td class="td-num"><strong>{{ $rekap['total_sk'] }}</strong></td><td class="td-num"><strong>{{ $rekap['total'] }}</strong></td><td class="no-print"></td></tr></tfoot>
                </table>
            </div>
        </div>
        @endforeach
    @endif
</div>

{{-- ── REKAP TAHUNAN ─────────────────────────────────────────────────── --}}
<div class="report-section pre-hide" id="tab-tahunan" style="display:none;">
    {{-- Filter Tahun --}}
    <div class="report-filter-bar no-print">
        <div class="filter-group" style="display:flex;align-items:center;gap:.4rem;">
            <label for="filter-tahun-tahunan" style="font-size:.82rem;font-weight:600;color:var(--color-text-muted);">Filter Tahun:</label>
            <select id="filter-tahun-tahunan" class="filter-select" style="min-width:110px;">
                <option value="">Semua Tahun</option>
                @foreach($tahun5 as $ty)
                <option value="{{ $ty }}">{{ $ty }}</option>
                @endforeach
            </select>
        </div>
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
    {{-- Filter Tahun --}}
    <div class="report-filter-bar no-print">
        <div class="filter-group" style="display:flex;align-items:center;gap:.4rem;">
            <label for="filter-tahun-klasifikasi" style="font-size:.82rem;font-weight:600;color:var(--color-text-muted);">Filter Tahun:</label>
            <select id="filter-tahun-klasifikasi" class="filter-select" style="min-width:110px;">
                <option value="">Semua Tahun</option>
                @foreach($tahun5 as $ty)
                <option value="{{ $ty }}">{{ $ty }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="report-card" style="page-break-inside:avoid;">
        <div class="report-card-header">
            <div class="report-card-title-wrap">
                <div class="report-card-icon">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                </div>
                <h3 class="report-card-title" id="klas-card-title">Rekap Per Kode Klasifikasi (Semua Tahun)</h3>
            </div>
            <span class="report-badge" id="klas-badge">{{ count($rekapKlasifikasi) }} Kode</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="report-table" id="tabel-klasifikasi">
                <thead>
                    <tr>
                        <th class="th-no">No</th>
                        <th>Kode Klasifikasi</th>
                        <th class="th-num">Surat Masuk</th>
                        <th class="th-num">Surat Keluar</th>
                        <th class="th-num">Total</th>
                    </tr>
                </thead>
                <tbody id="tbody-klasifikasi">
                    @forelse($rekapKlasifikasi as $i => $row)
                    <tr data-kode="{{ $row['kode'] }}" data-sm="{{ $row['sm_count'] }}" data-sk="{{ $row['sk_count'] }}" data-total="{{ $row['total'] }}">
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
                    <tr class="tfoot-total" id="klas-tfoot">
                        <td colspan="2" class="tfoot-label" id="klas-tfoot-label">TOTAL SEMUA KLASIFIKASI</td>
                        <td class="td-num"><strong id="klas-total-sm">{{ collect($rekapKlasifikasi)->sum('sm_count') }}</strong></td>
                        <td class="td-num"><strong id="klas-total-sk">{{ collect($rekapKlasifikasi)->sum('sk_count') }}</strong></td>
                        <td class="td-num"><strong id="klas-total-all">{{ collect($rekapKlasifikasi)->sum('total') }}</strong></td>
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

{{-- ── Modal Konfirmasi Cetak ─────────────────────────────────────────────── --}}
<div id="modal-cetak" class="modal-overlay" style="display:none;">
    <div class="modal-box" style="max-width:420px;">
        <div class="modal-icon-print">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"/>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8"/>
            </svg>
        </div>
        <h4 class="modal-title-print">Konfirmasi Cetak</h4>
        <p class="modal-desc-print">
            Anda akan mencetak <strong id="modal-cetak-tab-name">laporan ini</strong>.<br>
            Pastikan printer sudah terhubung dan siap digunakan.
        </p>
        <div class="modal-actions-print">
            <button type="button" id="modal-cetak-cancel">Batal</button>
            <button type="button" id="modal-cetak-confirm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"/>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                    <rect x="6" y="14" width="12" height="8"/>
                </svg>
                Ya, Cetak
            </button>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
/* ═══════════════════════════════════════════════════
   MODAL KONFIRMASI CETAK — Theme-Aware (Light + Dark)
   ═══════════════════════════════════════════════════ */

/* ── Overlay backdrop ──────────────────────────────────── */
/* Light mode: lapisan gelap semi-transparan */
#modal-cetak.modal-overlay {
    background: rgba(15, 23, 42, 0.50);
    backdrop-filter: blur(10px) saturate(130%);
    -webkit-backdrop-filter: blur(10px) saturate(130%);
}
/* Dark mode: lebih pekat */
[data-theme="dark"] #modal-cetak.modal-overlay {
    background: rgba(5, 8, 18, 0.72);
    backdrop-filter: blur(12px) saturate(120%);
    -webkit-backdrop-filter: blur(12px) saturate(120%);
}

/* ── Modal Box ─────────────────────────────────────────── */
#modal-cetak .modal-box {
    background: var(--color-surface, #ffffff);
    border: 1px solid var(--color-border, #e2e5ea);
    border-radius: 1.125rem;
    padding: 2.25rem 2rem 1.75rem;
    max-width: 420px;
    width: 90%;
    text-align: center;
    box-shadow:
        0 0 0 1px rgba(37,99,235,.07),
        0 24px 64px rgba(0,0,0,.18),
        0 8px 20px rgba(0,0,0,.10);
    animation: modalCetakIn .25s cubic-bezier(.16,1,.3,1) both;
    position: relative;
    overflow: hidden;
}
/* Dark mode: surface lebih gelap */
[data-theme="dark"] #modal-cetak .modal-box {
    background: var(--color-surface, #1a1d27);
    border-color: var(--color-border, #2d3148);
    box-shadow:
        0 0 0 1px rgba(99,102,241,.12),
        0 24px 64px rgba(0,0,0,.55),
        0 8px 24px rgba(0,0,0,.35);
}

/* Garis gradient di atas modal */
#modal-cetak .modal-box::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, #1e3a5f 0%, #2563eb 50%, #6366f1 100%);
    border-radius: 1.125rem 1.125rem 0 0;
}

@keyframes modalCetakIn {
    from { opacity: 0; transform: translateY(18px) scale(.96); }
    to   { opacity: 1; transform: translateY(0)    scale(1);   }
}

/* ── Icon Print ───────────────────────────────────────── */
#modal-cetak .modal-icon-print {
    width: 62px;
    height: 62px;
    border-radius: 50%;
    background: rgba(37, 99, 235, .1);
    border: 1.5px solid rgba(37, 99, 235, .2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.25rem;
    color: #2563eb;
    transition: transform .3s cubic-bezier(.34,1.56,.64,1), background .2s;
}
#modal-cetak .modal-box:hover .modal-icon-print {
    transform: scale(1.1);
    background: rgba(37,99,235,.15);
}
[data-theme="dark"] #modal-cetak .modal-icon-print {
    background: rgba(99, 102, 241, .12);
    border-color: rgba(99, 102, 241, .25);
    color: #818cf8;
}
[data-theme="dark"] #modal-cetak .modal-box:hover .modal-icon-print {
    background: rgba(99,102,241,.18);
}

/* ── Title ──────────────────────────────────────────────── */
#modal-cetak .modal-title-print {
    font-size: 1.0625rem;
    font-weight: 700;
    color: var(--color-text, #1e293b);
    margin: 0 0 .5rem;
    letter-spacing: -.01em;
}

/* ── Description ────────────────────────────────────────── */
#modal-cetak .modal-desc-print {
    font-size: .875rem;
    color: var(--color-text-secondary, #64748b);
    margin: 0 0 1.75rem;
    line-height: 1.65;
}
#modal-cetak .modal-desc-print strong {
    color: var(--color-text, #1e293b);
    font-weight: 600;
}

/* ── Actions Row ────────────────────────────────────────── */
#modal-cetak .modal-actions-print {
    display: flex;
    gap: .75rem;
    justify-content: center;
    flex-wrap: wrap;
}

/* Batal button */
#modal-cetak-cancel {
    padding: .58rem 1.4rem;
    border-radius: .5rem;
    font-size: .875rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: all .18s ease;
    background: transparent;
    color: var(--color-text-muted, #94a3b8);
    border: 1.5px solid var(--color-border, #e2e5ea);
}
#modal-cetak-cancel:hover {
    background: var(--color-primary-light, #e8edf4);
    color: var(--color-text, #1e293b);
    border-color: var(--color-border-focus, #94a3b8);
}
[data-theme="dark"] #modal-cetak-cancel:hover {
    color: var(--color-text, #e2e8f0);
}

/* Ya, Cetak button */
#modal-cetak-confirm {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .58rem 1.4rem;
    border-radius: .5rem;
    font-size: .875rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: all .18s cubic-bezier(.4,0,.2,1);
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
    color: #fff;
    border: none;
    box-shadow: 0 3px 12px rgba(37,99,235,.3);
}
#modal-cetak-confirm:hover {
    opacity: .92;
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(37,99,235,.45);
}
#modal-cetak-confirm:active { transform: translateY(0); }

/* ═══════════════════════════════════════════════════
   REPORT PAGE STYLES — Full Overhaul
   ═══════════════════════════════════════════════════ */

/* ── Tombol Cetak (Header) ────────────────────────── */
.report-actions { display:flex; gap:.75rem; align-items:center; }
.btn-report-print {
    display: inline-flex; align-items: center; gap: .5rem;
    padding: .55rem 1.2rem;
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
    color: #fff; border: none; border-radius: .5rem;
    font-size: .84rem; font-weight: 600; font-family: inherit;
    cursor: pointer; transition: all .22s cubic-bezier(.4,0,.2,1);
    box-shadow: 0 2px 10px rgba(37,99,235,.3);
    letter-spacing: .01em;
}
.btn-report-print:hover {
    opacity: .92;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(37,99,235,.4);
}
.btn-report-print:active { transform: translateY(0); }

/* ── Tab Navigasi ───────────────────────────────── */
.report-tabs {
    display: flex;
    gap: .25rem;
    margin-bottom: 1.75rem;
    background: var(--color-surface, #1a2236);
    border: 1px solid var(--color-border, #2a3347);
    border-radius: .625rem;
    padding: .3rem;
    width: fit-content;
}
.report-tab {
    padding: .5rem 1.25rem;
    border: none;
    background: transparent;
    color: var(--color-text-muted, #8892a4);
    font-size: .84rem;
    font-weight: 500;
    font-family: inherit;
    cursor: pointer;
    border-radius: .4rem;
    transition: all .2s cubic-bezier(.4,0,.2,1);
    white-space: nowrap;
    position: relative;
}
.report-tab:hover {
    color: var(--color-text, #e2e8f0);
    background: rgba(99,102,241,.1);
}
.report-tab.active {
    color: #fff;
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    font-weight: 600;
    box-shadow: 0 2px 10px rgba(99,102,241,.35);
}

/* ── Report Card ─────────────────────────────── */
.report-card {
    background: var(--color-surface, #1a2236);
    border: 1px solid var(--color-border, #2a3347);
    border-radius: .875rem;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(0,0,0,.25);
    transition: box-shadow .2s;
}
.report-card:hover { box-shadow: 0 6px 32px rgba(0,0,0,.35); }
.report-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.375rem;
    background: linear-gradient(90deg,
        rgba(99,102,241,.08) 0%,
        rgba(99,102,241,.03) 100%);
    border-bottom: 1px solid var(--color-border, #2a3347);
    flex-wrap: wrap;
    gap: .5rem;
}
.report-card-title-wrap {
    display: flex;
    align-items: center;
    gap: .65rem;
}
.report-card-icon {
    width: 32px; height: 32px;
    border-radius: .5rem;
    background: rgba(99,102,241,.15);
    color: #818cf8;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    border: 1px solid rgba(99,102,241,.2);
}
.report-card-title {
    font-size: .9375rem;
    font-weight: 700;
    color: var(--color-text, #e2e8f0);
    margin: 0;
    letter-spacing: -.01em;
}
.report-badge {
    display: inline-flex;
    align-items: center;
    padding: .22rem .75rem;
    background: rgba(99,102,241,.12);
    color: #818cf8;
    border: 1px solid rgba(99,102,241,.25);
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 700;
    white-space: nowrap;
    letter-spacing: .02em;
}

/* ── Filter Bar ────────────────────────────────── */
.report-filter-bar {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
    padding: .75rem 1rem;
    background: var(--color-surface, #1a2236);
    border: 1px solid var(--color-border, #2a3347);
    border-radius: .625rem;
}
.filter-select {
    background: var(--color-bg, #0f172a);
    border: 1px solid var(--color-border, #2a3347);
    border-radius: .4rem;
    color: var(--color-text, #e2e8f0);
    font-family: inherit;
    font-size: .82rem;
    font-weight: 500;
    padding: .38rem .65rem;
    cursor: pointer;
    transition: border-color .2s;
    outline: none;
}
.filter-select:focus { border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,.2); }
.filter-select option { background: var(--color-surface, #1a2236); }

/* ── Report Table ────────────────────────────── */
.report-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .875rem;
}
.report-table th {
    background: rgba(99,102,241,.12);
    color: #818cf8;
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    padding: .75rem 1rem;
    text-align: left;
    white-space: nowrap;
    border-bottom: 1px solid rgba(99,102,241,.2);
}
.report-table td {
    padding: .7rem 1rem;
    border-bottom: 1px solid var(--color-border, #2a3347);
    color: var(--color-text, #e2e8f0);
    vertical-align: middle;
    transition: background .15s;
}
.report-table tbody tr:last-child td { border-bottom: none; }
.report-table tbody tr:hover td { background: rgba(99,102,241,.05); }
.report-table tfoot tr td {
    border-top: 2px solid rgba(99,102,241,.3);
    border-bottom: none;
    background: rgba(99,102,241,.06);
}
.th-no  { width: 48px; }
.th-num { width: 120px; text-align: right !important; }
.th-expand { width: 100px; text-align: center !important; }
.td-no {
    text-align: center;
    font-weight: 700;
    color: #818cf8;
    width: 48px;
    font-size: .78rem;
}
.td-bulan { font-weight: 500; }
.td-num   { text-align: right; }
.td-center { text-align: center; }
.td-empty-row {
    text-align: center;
    padding: 2.5rem 1rem !important;
    color: var(--color-text-muted);
    font-size: .85rem;
}
.tfoot-total { }
.tfoot-label {
    font-weight: 700;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #818cf8;
}

/* ── Count Badges ────────────────────────────── */
.count-sm {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 30px; padding: .18rem .5rem; border-radius: .35rem;
    background: rgba(99,102,241,.12);
    color: #818cf8;
    font-weight: 700; font-size: .8rem;
    border: 1px solid rgba(99,102,241,.2);
}
.count-sk {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 30px; padding: .18rem .5rem; border-radius: .35rem;
    background: rgba(16,185,129,.1);
    color: #34d399;
    font-weight: 700; font-size: .8rem;
    border: 1px solid rgba(16,185,129,.2);
}
.count-zero { color: var(--color-text-muted); opacity: .35; font-size: .85rem; }

/* ── Row States ───────────────────────────────── */
.row-active td { background: rgba(99,102,241,.07) !important; }
.row-empty td  { opacity: .4; }

/* ── Badges ─────────────────────────────────────── */
.badge-month {
    display: inline-block;
    padding: .1rem .45rem;
    border-radius: .3rem;
    background: rgba(245,158,11,.15);
    color: #fbbf24;
    font-size: .67rem;
    font-weight: 700;
    margin-left: .4rem;
    vertical-align: middle;
    border: 1px solid rgba(245,158,11,.25);
}
.badge-kode-report {
    display: inline-block;
    padding: .2rem .65rem;
    border-radius: .375rem;
    background: rgba(99,102,241,.1);
    color: #818cf8;
    font-size: .78rem;
    font-weight: 700;
    border: 1px solid rgba(99,102,241,.2);
    letter-spacing: .02em;
}

/* ── Empty State ──────────────────────────────── */
.report-empty {
    display: flex; flex-direction: column; align-items: center; gap: .75rem;
    padding: 3.5rem 1rem; color: var(--color-text-muted); text-align: center;
}
.report-empty svg { opacity: .25; }
.report-empty span { font-size: .9rem; font-weight: 500; }

/* ── Alert ───────────────────────────────────────── */
.alert { display:flex; align-items:center; gap:.5rem; padding:.8rem 1rem; border-radius:.5rem; font-size:.84rem; }
.alert-error { background:rgba(239,68,68,.08); color:#f87171; border:1px solid rgba(239,68,68,.2); }

/* ── Accordion Rekap Tahunan ─────────────────────── */
.row-tahun { cursor: pointer; transition: background .15s; }
.row-tahun:hover td { background: rgba(99,102,241,.08) !important; }
.row-tahun.expanded td { background: rgba(99,102,241,.07) !important; }
.tahun-toggle-icon {
    display: inline-block;
    font-size: .7rem;
    color: #818cf8;
    margin-right: .4rem;
    transition: transform .25s cubic-bezier(.4,0,.2,1);
}

.kode-detail-wrap {
    background: var(--color-surface);
    background: color-mix(in srgb, var(--color-surface) 94%, #6366f1 6%);
    border-top: 1px dashed var(--color-border);
    padding: .85rem 1.75rem 1.1rem;
}
.kode-detail-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .82rem;
}
.kode-detail-table th {
    background: rgba(99,102,241,.1);
    color: #818cf8;
    font-size: .69rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
    padding: .45rem .85rem; text-align: left; white-space: nowrap;
    border-bottom: 1px solid rgba(99,102,241,.15);
    border-radius: 0;
}
.kode-detail-table td {
    padding: .48rem .85rem;
    border-bottom: 1px solid var(--color-border);
    color: var(--color-text); vertical-align: middle;
}
.kode-detail-table tbody tr:last-child td { border-bottom: none; }
.kode-detail-table tbody tr:hover td { background: rgba(99,102,241,.04); }

.row-bulan-expandable { cursor: pointer; }
.row-bulan-expandable:hover td { background: rgba(99,102,241,.08) !important; }
.row-bulan-expandable.expanded td { background: rgba(99,102,241,.07) !important; }
.bulan-toggle-icon { display:inline-block; width:.8rem; color:#6366f1; font-size:.65rem; margin-right:.35rem; transition:transform .2s ease; }
.detail-code-count { font-size:.75rem; color:var(--color-primary); }

/* ── Print Header ─────────────────────────────── */
.print-header,
.print-signature { display: none !important; }
.print-logo-image {
    display: block;
    width: auto;
    height: auto;
    max-width: 100%;
    object-fit: contain;
}

@page {
    size: A4 portrait;
    margin: 14mm 14mm 14mm 14mm;
}

@media print {
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

    .report-section { margin-bottom: 1.5rem; }

    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    html, body { background: #fff !important; color: #111827 !important; font-size: 10pt; }
    html, body, .layout-body, .main-content, .main-content-wrap {
        background-image: none !important;
        box-shadow: none !important;
    }
    .layout-body, .main-content, .main-content-wrap, .report-section, .report-card,
    .report-table, .report-table tbody, .report-table tr, .kode-detail-table,
    .kode-detail-table tbody, .kode-detail-table tr { background: #fff !important; }
    .main-content { margin-left: 0 !important; padding: 0 !important; }
    .main-content-wrap { max-width: 100% !important; padding: 0 !important; }
    .layout-body { display: block !important; }

    .print-header {
        display: block !important;
        margin-bottom: 1rem;
    }
    .print-header-inner {
        display: flex !important; align-items: flex-start; gap: 1rem;
        margin-bottom: .5rem;
    }
    .print-logo-wrap {
        width: 18mm;
        flex: 0 0 18mm;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .print-logo-image {
        display: block !important;
        width: auto !important;
        height: 14mm !important;
        max-width: 18mm !important;
        max-height: 14mm !important;
        object-fit: contain !important;
        object-position: center;
    }
    .print-header-text { flex: 1; }
    .print-title { font-size: 12pt; font-weight: 700; color: #1e3a5f; margin: 0 0 .15rem; text-transform: uppercase; letter-spacing: .04em; }
    .print-subtitle { font-size: 9pt; color: #444; margin: 0 0 .1rem; }
    .print-meta { font-size: 8pt; color: #666; margin: 0; }
    .print-divider { border: none; border-top: 2px solid #1e3a5f; margin: .6rem 0 1rem; }

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
    .report-card-icon { display: none !important; }
    .report-card-title { color: #1e3a5f !important; font-size: 10pt !important; font-weight: 700 !important; }
    .report-badge { background: #e0e8f2 !important; color: #1e3a5f !important; border: 1px solid #bbb !important; font-size: 7.5pt !important; }

    .report-filter-bar { display: none !important; }

    .report-table { width: 100% !important; border-collapse: collapse !important; font-size: 9pt !important; }
    .report-table th {
        background: #eef2ff !important; color: #1e3a5f !important;
        font-size: 8pt !important; padding: .35rem .6rem !important;
        border: 1px solid #cbd5e1 !important;
    }
    .report-table td {
        color: #000 !important;
        border: 1px solid #ccc !important;
        padding: .3rem .6rem !important;
        font-size: 9pt !important;
    }
    .report-table tbody tr:hover td { background: transparent !important; }
    .report-table tfoot tr td { border-top: 2px solid #999 !important; background: #eef2f7 !important; }
    .tfoot-label { color: #1e3a5f !important; font-weight: 700 !important; }
    .row-active td { background: #ebf3ff !important; }
    .row-empty td  { opacity: .5 !important; }

    .count-sm { background: #dde8ff !important; color: #1e3a5f !important; padding: .1rem .35rem !important; border-radius: 3px !important; font-size: 8pt !important; border: none !important; }
    .count-sk { background: #d1fae5 !important; color: #065f46 !important; padding: .1rem .35rem !important; border-radius: 3px !important; font-size: 8pt !important; border: none !important; }
    .count-zero { color: #aaa !important; opacity: 1 !important; }
    .badge-month { background: #fef3c7 !important; color: #92400e !important; font-size: 7pt !important; border: none !important; }
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
    /* Hanya tampil accordion yang TIDAK ditandai filter-hidden */
    .row-kode-detail { display: table-row !important; }
    .row-kode-detail.print-row-hidden { display: none !important; }
    /* Baris tabel yang disembunyikan oleh filter juga tetap tersembunyi saat print */
    tr.print-row-hidden { display: none !important; }
    .kode-detail-wrap {
        padding: .5rem 1rem !important;
        background: #f8fafc !important;
        border-top: 1px solid #cbd5e1 !important;
    }
    .kode-detail-table { font-size: 8.5pt !important; }
    .kode-detail-table th {
        background: #eef2ff !important;
        color: #1e3a5f !important;
        border: 1px solid #cbd5e1 !important;
        font-size: 7.5pt !important;
        padding: .3rem .5rem !important;
    }
    .kode-detail-table td {
        background: #fff !important;
        color: #111827 !important;
        border: 1px solid #d1d5db !important;
        padding: .3rem .5rem !important;
    }
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
        document.documentElement.classList.add('report-printing');
        document.body.classList.add('report-printing');

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

        // ── Tandai baris yang disembunyikan oleh filter agar tetap hidden saat print ──
        // Rekap Tahunan: mark hidden rows & their detail
        document.querySelectorAll('#tabel-tahunan tbody tr').forEach(function(tr) {
            if (tr.style.display === 'none') {
                tr.classList.add('print-row-hidden');
            } else {
                tr.classList.remove('print-row-hidden');
                // Jika detail row dari baris visible, buka saat print
                if (tr.classList.contains('row-kode-detail')) {
                    tr.classList.remove('print-row-hidden');
                }
            }
        });
        // Detail row accordion ikut hidden jika parent tahun hidden
        document.querySelectorAll('#tabel-tahunan tbody .row-tahun').forEach(function(tr) {
            var yr = tr.getAttribute('data-year');
            var detail = document.getElementById('detail-' + yr);
            if (detail) {
                if (tr.style.display === 'none') {
                    detail.classList.add('print-row-hidden');
                } else {
                    detail.classList.remove('print-row-hidden');
                }
            }
        });

        // Update teks tanggal cetak
        var metaDate = document.getElementById('print-meta-date');
        if (metaDate) {
            var n = new Date();
            metaDate.textContent = 'Dicetak pada: '+formatDateID(n)+', '+pad(n.getHours())+':'+pad(n.getMinutes())+' WITA';
        }
        var sigDate = document.getElementById('sig-date');
        if (sigDate) sigDate.textContent = formatDateShortID(new Date());
    }

    function restoreAfterPrint() {
        document.documentElement.classList.remove('report-printing');
        document.body.classList.remove('report-printing');

        var activeTab = document.querySelector('.report-tab.active');
        var activeId  = activeTab ? activeTab.dataset.tab : 'bulanan';
        sections.forEach(function(sec) {
            sec.style.display      = (sec.id === 'tab-' + activeId) ? '' : 'none';
            sec.style.marginBottom = '';
        });
        // Hapus semua class print-row-hidden setelah print selesai
        document.querySelectorAll('.print-row-hidden').forEach(function(el) {
            el.classList.remove('print-row-hidden');
        });
    }

    // beforeprint/afterprint events
    window.addEventListener('beforeprint', preparePrint);
    window.addEventListener('afterprint',  restoreAfterPrint);

    // Chromium pada beberapa perangkat tidak selalu memicu afterprint.
    // Pulihkan tampilan ketika media print berakhir atau tab kembali fokus.
    if (window.matchMedia) {
        var printMedia = window.matchMedia('print');
        var handlePrintMedia = function(e) {
            if (!e.matches) restoreAfterPrint();
        };
        if (printMedia.addEventListener) printMedia.addEventListener('change', handlePrintMedia);
        else if (printMedia.addListener) printMedia.addListener(handlePrintMedia);
    }
    window.addEventListener('focus', function() {
        if (document.documentElement.classList.contains('report-printing')) {
            setTimeout(restoreAfterPrint, 150);
        }
    });

    // ── Modal Konfirmasi Cetak ───────────────────────────────────────────
    var TAB_LABELS = {
        bulanan:      'Rekap Bulanan',
        tahunan:      'Rekap Tahunan',
        klasifikasi:  'Rekap Per Klasifikasi'
    };

    var modalCetak       = document.getElementById('modal-cetak');
    var modalCetakName   = document.getElementById('modal-cetak-tab-name');
    var modalCetakCancel = document.getElementById('modal-cetak-cancel');
    var modalCetakOk     = document.getElementById('modal-cetak-confirm');

    if (modalCetak) document.body.appendChild(modalCetak);

    function openModalCetak() {
        var activeTab = document.querySelector('.report-tab.active');
        var activeId  = activeTab ? activeTab.dataset.tab : 'bulanan';
        var label     = TAB_LABELS[activeId] || 'laporan ini';
        if (modalCetakName) modalCetakName.textContent = '"' + label + '"';
        if (modalCetak) {
            modalCetak.style.display     = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModalCetak() {
        if (modalCetak) modalCetak.style.display = 'none';
        document.body.style.overflow = '';
    }

    // Tombol Cetak Laporan (satu-satunya tombol cetak)
    var btnPrint = document.getElementById('btn-print');
    if (btnPrint) {
        btnPrint.addEventListener('click', function() {
            openModalCetak();
        });
    }

    if (modalCetakCancel) modalCetakCancel.addEventListener('click', closeModalCetak);
    if (modalCetak) modalCetak.addEventListener('click', function(e) {
        if (e.target === modalCetak) closeModalCetak();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modalCetak && modalCetak.style.display === 'flex') closeModalCetak();
    });

    if (modalCetakOk) {
        modalCetakOk.addEventListener('click', function() {
            closeModalCetak();
            // Sedikit delay agar modal benar-benar hilang dulu sebelum print dialog muncul
            setTimeout(function() {
                window.print();
            }, 80);
        });
    }

    // ── Accordion Rekap Tahunan ──────────────────────────────────────────
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

    // Accordion rincian kode klasifikasi per bulan
    document.querySelectorAll('.row-bulan-expandable').forEach(function(row) {
        row.addEventListener('click', function() {
            var key = this.getAttribute('data-month-key');
            var detail = document.getElementById('detail-bulan-' + key);
            var icon = this.querySelector('.bulan-toggle-icon');
            if (!detail) return;
            var isOpen = detail.style.display === 'table-row';
            detail.style.display = isOpen ? 'none' : 'table-row';
            this.classList.toggle('expanded', !isOpen);
            if (icon) icon.style.transform = isOpen ? '' : 'rotate(90deg)';
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
                var yr   = tr.getAttribute('data-year') || '';
                var show = !tahun || yr === tahun;
                tr.style.display = show ? '' : 'none';
                var detail = document.getElementById('detail-' + yr);
                if (detail && !show) detail.style.display = 'none';
            });
            // Update tfoot label jika ada filter
            var tfootLabel = document.querySelector('#tabel-tahunan tfoot .tfoot-label');
            if (tfootLabel) {
                tfootLabel.textContent = tahun ? ('TOTAL TAHUN ' + tahun) : 'TOTAL KESELURUHAN';
            }
        });
    }

    // ── Filter Tahun: Tab Klasifikasi ────────────────────────────────────
    // Data per tahun di-embed sebagai JSON dari PHP untuk rendering dinamis
    var REKAP_KLAS_PER_TAHUN = @json($rekapKlasifikasiPerTahun ?? []);
    var REKAP_KLAS_ALL       = @json($rekapKlasifikasi ?? []);
    var KODE_ORDER = ['PR','KP','OT','KU','HK','UM','PL','UP','HM','PW','LT','DL','DT','KI'];

    var selTahunKlas = document.getElementById('filter-tahun-klasifikasi');
    var tbodyKlas    = document.getElementById('tbody-klasifikasi');
    var klasTitle    = document.getElementById('klas-card-title');
    var klasBadge    = document.getElementById('klas-badge');
    var klasTfootLbl = document.getElementById('klas-tfoot-label');
    var klasTotalSM  = document.getElementById('klas-total-sm');
    var klasTotalSK  = document.getElementById('klas-total-sk');
    var klasTotalAll = document.getElementById('klas-total-all');

    function renderKlasifikasiTable(tahun) {
        if (!tbodyKlas) return;
        var rows = tahun ? (REKAP_KLAS_PER_TAHUN[tahun] || []) : REKAP_KLAS_ALL;

        // Sort berdasarkan KODE_ORDER
        rows = rows.slice().sort(function(a, b) {
            var ia = KODE_ORDER.indexOf(a.kode);
            var ib = KODE_ORDER.indexOf(b.kode);
            return (ia === -1 ? 999 : ia) - (ib === -1 ? 999 : ib);
        });

        var totalSM = 0, totalSK = 0, totalAll = 0;
        var html = '';
        if (rows.length === 0) {
            html = '<tr><td colspan="5" class="td-empty-row">Belum ada data untuk tahun ' + (tahun || 'ini') + '</td></tr>';
        } else {
            rows.forEach(function(row, i) {
                totalSM  += parseInt(row.sm_count) || 0;
                totalSK  += parseInt(row.sk_count) || 0;
                totalAll += parseInt(row.total)    || 0;
                html += '<tr>';
                html += '<td class="td-no">' + (i + 1) + '</td>';
                html += '<td><span class="badge-kode-report">' + row.kode + '</span></td>';
                html += '<td class="td-num"><span class="count-sm">' + row.sm_count + '</span></td>';
                html += '<td class="td-num"><span class="count-sk">' + row.sk_count + '</span></td>';
                html += '<td class="td-num"><strong>' + row.total + '</strong></td>';
                html += '</tr>';
            });
        }
        tbodyKlas.innerHTML = html;

        // Update judul & badge
        if (klasTitle) klasTitle.textContent = 'Rekap Per Kode Klasifikasi' + (tahun ? ' Tahun ' + tahun : ' (Semua Tahun)');
        if (klasBadge) klasBadge.textContent = rows.length + ' Kode';
        if (klasTfootLbl) klasTfootLbl.textContent = tahun ? 'TOTAL TAHUN ' + tahun : 'TOTAL SEMUA KLASIFIKASI';
        if (klasTotalSM)  klasTotalSM.textContent  = totalSM;
        if (klasTotalSK)  klasTotalSK.textContent  = totalSK;
        if (klasTotalAll) klasTotalAll.textContent = totalAll;
    }

    if (selTahunKlas) {
        selTahunKlas.addEventListener('change', function() {
            renderKlasifikasiTable(this.value);
        });
    }

    // ── Live Clock ────────────────────────────────────────────────────


    // Init: sembunyikan header/sig di layar
    restoreAfterPrint();
});
</script>
@endpush
