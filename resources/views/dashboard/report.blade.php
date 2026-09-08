@extends('layouts.app')

@section('title', 'Rekap Laporan Arsip')

@section('content')

<div class="page-header" id="report-page-header">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <h2 class="page-title">Rekap Laporan Arsip</h2>
            <p class="page-description">Rekapitulasi surat masuk &amp; keluar</p>
        </div>
        <div class="report-actions no-print" style="display:flex;align-items:center;gap:.75rem;">

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

<div class="print-header" id="print-header-wrap">

    <div class="print-kop-topbar"></div>

    <div class="print-kop">
        <div class="print-kop-logo">
            <img class="print-logo-image"
                 src="{{ route('image.view', 'komdigi-logo.png') }}"
                 alt="Logo Komdigi"
                 onerror="this.style.display='none'">
        </div>
        <div class="print-kop-separator"></div>
        <div class="print-kop-text">
            <div class="print-kop-ministry">Kementerian Komunikasi dan Digital RI</div>
            <div class="print-kop-unit">
                Badan Pengembangan Sumber Daya Manusia Komunikasi dan Digital<br>
                Balai Pelatihan Sumber Daya Manusia Komunikasi dan Digital Manado
            </div>
            <div class="print-kop-address">Jln. Pomurow No. 76, Manado, No. Tlp/HP 085117816728, tu.bpsdmp_manado@komdigi.go.id</div>
        </div>
    </div>

    <div class="print-kop-divider">
        <div class="print-kop-line-thick"></div>
        <div class="print-kop-line-thin"></div>
    </div>

    <div class="print-doc-title-block">
        <div class="print-title">LAPORAN REKAPITULASI ARSIP SURAT</div>
        <div class="print-doc-jenis" id="print-doc-jenis">Rekap Bulanan</div>
    </div>

    <table class="print-meta-table">
        <tbody>
            <tr>
                <td class="pmt-label">Dicetak Pada</td>
                <td class="pmt-sep">:</td>
                <td class="pmt-val" id="print-meta-date">{{ now()->format('d F Y, H:i') }} WITA</td>
                <td class="pmt-spacer"></td>
                <td class="pmt-label">Jenis Laporan</td>
                <td class="pmt-sep">:</td>
                <td class="pmt-val" id="print-meta-jenis">Rekap Bulanan</td>
            </tr>
            <tr>
                <td class="pmt-label">Sistem</td>
                <td class="pmt-sep">:</td>
                <td class="pmt-val" colspan="5">Sistem Arsip Surat Digital BPSDMP &mdash; <em>Digenerate Otomatis</em></td>
            </tr>
        </tbody>
    </table>

    <div class="print-divider"></div>
</div>

<div class="report-tabs no-print">
    <button class="report-tab active" data-tab="bulanan">Rekap Bulanan</button>
    <button class="report-tab" data-tab="tahunan">Rekap Tahunan</button>
    <button class="report-tab" data-tab="klasifikasi">Klasifikasi</button>
    <button class="report-tab" data-tab="surat-masuk">Surat Masuk</button>
    <button class="report-tab" data-tab="surat-keluar">Surat Keluar</button>
</div>

<div class="report-section" id="tab-bulanan">

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
                    <thead><tr><th class="th-no">No</th><th>Bulan</th><th class="th-num">Surat Masuk</th><th class="th-num">Surat Keluar</th><th class="th-num">Total</th></tr></thead>
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
                        </tr>
                        @if($row['total'] > 0)
                        <tr class="row-kode-detail row-kode-detail-bulan" id="detail-bulan-{{ $monthKey }}"><td colspan="5" style="padding:0;"><div class="kode-detail-wrap"><table class="kode-detail-table">
                            <thead><tr><th>Kode Klasifikasi</th><th class="th-num">Surat Masuk</th><th class="th-num">Surat Keluar</th><th class="th-num">Total</th></tr></thead>
                            <tbody>@foreach($monthDetails as $kode => $counts)<tr><td><span class="badge-kode-report">{{ $kode }}</span></td><td class="td-num">@if($counts[0] > 0)<span class="count-sm">{{ $counts[0] }}</span>@else<span class="count-zero">&mdash;</span>@endif</td><td class="td-num">@if($counts[1] > 0)<span class="count-sk">{{ $counts[1] }}</span>@else<span class="count-zero">&mdash;</span>@endif</td><td class="td-num"><strong>{{ $counts[2] }}</strong></td></tr>@endforeach</tbody>
                        </table></div></td></tr>
                        @endif
                        @endforeach
                    </tbody>
                    <tfoot><tr class="tfoot-total"><td colspan="2" class="tfoot-label">TOTAL TAHUN {{ $year }}</td><td class="td-num"><strong>{{ $rekap['total_sm'] }}</strong></td><td class="td-num"><strong>{{ $rekap['total_sk'] }}</strong></td><td class="td-num"><strong>{{ $rekap['total'] }}</strong></td></tr></tfoot>
                </table>
            </div>
        </div>
        @endforeach
    @endif

    <div class="print-only" id="print-detail-bulanan">
        <div class="print-detail-section-title">Detail Arsip Surat &mdash; Rekap Bulanan</div>
        @php
$bulanNamesPrint = [
                1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
            ];

            $groupedBulanan = [];
            foreach ($printDetailBulanan as $det) {
                $groupKey = $det['tahun'] . '-' . str_pad($det['bulan'], 2, '0', STR_PAD_LEFT);
                $groupedBulanan[$groupKey][] = $det;
            }

            ksort($groupedBulanan);
            $noUrut = 1;
@endphp
        @if(!empty($printDetailBulanan))
        <table class="print-detail-table" id="print-detail-table-bulanan">
            <thead>
                <tr>
                    <th class="pd-no">No</th>
                    <th class="pd-kode">Kode Kelas / Nomor Surat</th>
                    <th class="pd-tgl">Tanggal</th>
                    <th class="pd-uraian">URAIAN INFORMASI ARSIP</th>
                    <th class="pd-jenis">Jenis</th>
                </tr>
            </thead>
            <tbody>
                @foreach($groupedBulanan as $groupKey => $groupRows)
                @php
$firstRow  = $groupRows[0];
                    $tahunGrup = $firstRow['tahun'];
                    $bulanGrup = $firstRow['bulan'];
                    $bulanLabelGrup = strtoupper(($bulanNamesPrint[$bulanGrup] ?? '-') . ' ' . $tahunGrup);
@endphp

                <tr class="pd-month-header pd-row-bulanan" data-print-year="{{ $tahunGrup }}">
                    <td colspan="5" class="pd-month-label">
                        <span class="pd-month-icon">&#9632;</span> {{ $bulanLabelGrup }}
                    </td>
                </tr>

                @foreach($groupRows as $det)
                <tr class="pd-row-bulanan" data-print-year="{{ $det['tahun'] }}">
                    <td class="pd-no">{{ $noUrut++ }}</td>
                    <td class="pd-kode"><span class="pd-kode-badge">{{ $det['kode'] ?: '-' }}</span></td>
                    <td class="pd-tgl">{{ $det['tanggal'] }}</td>
                    <td class="pd-uraian">{{ $det['uraian'] }}</td>
                    <td class="pd-jenis"><span class="pd-jenis-badge pd-jenis-{{ strtolower($det['jenis']) }}">{{ $det['jenis'] }}</span></td>
                </tr>
                @endforeach
                @endforeach
            </tbody>
            <tfoot>
                <tr class="pd-tfoot">
                    <td colspan="5">Total: {{ count($printDetailBulanan) }} arsip surat</td>
                </tr>
            </tfoot>
        </table>
        @else
        <p class="pd-empty">Belum ada data arsip surat.</p>
        @endif
    </div>
</div>

<div class="report-section" id="tab-tahunan" style="display:none;">

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
                    </tr>
                </thead>
                <tbody>
                    @forelse($rekapTahunan as $i => $row)

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
                    </tr>

                    <tr class="row-kode-detail" id="detail-{{ $row['tahun'] }}">
                        <td colspan="5" style="padding:0;">
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
                    <tr><td colspan="5" class="td-empty-row">Belum ada data</td></tr>
                    @endforelse
                </tbody>
                @if(count($rekapTahunan) > 0)
                <tfoot>
                    <tr class="tfoot-total">
                        <td colspan="2" class="tfoot-label">TOTAL KESELURUHAN</td>
                        <td class="td-num"><strong>{{ collect($rekapTahunan)->sum('sm_count') }}</strong></td>
                        <td class="td-num"><strong>{{ collect($rekapTahunan)->sum('sk_count') }}</strong></td>
                        <td class="td-num"><strong>{{ collect($rekapTahunan)->sum('total') }}</strong></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="print-only" id="print-detail-tahunan">
        <div class="print-detail-section-title">Detail Arsip Surat &mdash; Rekap Tahunan</div>
        @php
$groupedTahunan = [];
            foreach ($printDetailBulanan as $det) {
                $groupedTahunan[$det['tahun']][] = $det;
            }

            krsort($groupedTahunan);
            $noUrutThn = 1;
@endphp
        @if(!empty($printDetailBulanan))
        <table class="print-detail-table" id="print-detail-table-tahunan">
            <thead>
                <tr>
                    <th class="pd-no">No</th>
                    <th class="pd-kode">Kode Kelas / Nomor Surat</th>
                    <th class="pd-tgl">Tanggal</th>
                    <th class="pd-uraian">URAIAN INFORMASI ARSIP</th>
                    <th class="pd-jenis">Jenis</th>
                </tr>
            </thead>
            <tbody>
                @foreach($groupedTahunan as $tahunGrp => $tahunRows)

                <tr class="pd-year-header pd-row-tahunan" data-print-year="{{ $tahunGrp }}">
                    <td colspan="5" class="pd-year-label">
                        <span class="pd-year-icon">&#9632;</span> TAHUN {{ $tahunGrp }}
                    </td>
                </tr>

                @foreach($tahunRows as $det)
                <tr class="pd-row-tahunan" data-print-year="{{ $det['tahun'] }}">
                    <td class="pd-no">{{ $noUrutThn++ }}</td>
                    <td class="pd-kode"><span class="pd-kode-badge">{{ $det['kode'] ?: '-' }}</span></td>
                    <td class="pd-tgl">{{ $det['tanggal'] }}</td>
                    <td class="pd-uraian">{{ $det['uraian'] }}</td>
                    <td class="pd-jenis"><span class="pd-jenis-badge pd-jenis-{{ strtolower($det['jenis']) }}">{{ $det['jenis'] }}</span></td>
                </tr>
                @endforeach
                @endforeach
            </tbody>
            <tfoot>
                <tr class="pd-tfoot">
                    <td colspan="5">Total: {{ count($printDetailBulanan) }} arsip surat</td>
                </tr>
            </tfoot>
        </table>
        @else
        <p class="pd-empty">Belum ada data arsip surat.</p>
        @endif
    </div>
</div>

<div class="report-section" id="tab-klasifikasi" style="display:none;">

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

    <div class="print-only" id="print-detail-klasifikasi">
        <div class="print-detail-section-title">Detail Arsip Surat &mdash; Per Kode Klasifikasi</div>
        @php
$groupedKlasifikasi = [];
            foreach ($printDetailKlasifikasi as $det) {
                $prefixKey = $det['kode_prefix'] ?? preg_replace('/[^A-Za-z]/', '', $det['kode'] ?? '');
                $prefixKey = strtoupper(substr($prefixKey, 0, 2)) ?: '-';
                $groupedKlasifikasi[$prefixKey][] = $det;
            }

            $kodeOrderKlas = ['PR','KP','OT','KU','HK','UM','PL','UP','HM','PW','LT','DL','DT','KI'];
            $sortedKlasGrp = [];
            foreach ($kodeOrderKlas as $k) {
                if (isset($groupedKlasifikasi[$k])) $sortedKlasGrp[$k] = $groupedKlasifikasi[$k];
            }
            foreach ($groupedKlasifikasi as $k => $v) {
                if (!isset($sortedKlasGrp[$k])) $sortedKlasGrp[$k] = $v;
            }
            $noUrutKls = 1;
@endphp
        @if(!empty($printDetailKlasifikasi))
        <table class="print-detail-table" id="print-detail-table-klasifikasi">
            <thead>
                <tr>
                    <th class="pd-no">No</th>
                    <th class="pd-kode">Kode Kelas / Nomor Surat</th>
                    <th class="pd-tgl">Tanggal</th>
                    <th class="pd-uraian">URAIAN INFORMASI ARSIP</th>
                    <th class="pd-jenis">Jenis</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sortedKlasGrp as $kodePrefix => $klasRows)
                @php
$kodeCount = count($klasRows);
@endphp

                <tr class="pd-kode-header pd-row-klasifikasi" data-print-year="" data-print-kode="{{ $kodePrefix }}">
                    <td colspan="5" class="pd-kode-header-label">
                        <span class="pd-kode-header-icon">&#9632;</span>
                        <span class="pd-kode-header-badge">{{ $kodePrefix }}</span>
                        <span class="pd-kode-header-count">({{ $kodeCount }} arsip)</span>
                    </td>
                </tr>

                @foreach($klasRows as $det)
                <tr class="pd-row-klasifikasi" data-print-year="{{ $det['tahun'] }}" data-print-kode="{{ $det['kode_prefix'] ?? $kodePrefix }}">
                    <td class="pd-no">{{ $noUrutKls++ }}</td>
                    <td class="pd-kode"><span class="pd-kode-badge">{{ $det['kode'] ?: '-' }}</span></td>
                    <td class="pd-tgl">{{ $det['tanggal'] }}</td>
                    <td class="pd-uraian">{{ $det['uraian'] }}</td>
                    <td class="pd-jenis"><span class="pd-jenis-badge pd-jenis-{{ strtolower($det['jenis']) }}">{{ $det['jenis'] }}</span></td>
                </tr>
                @endforeach
                @endforeach
            </tbody>
            <tfoot>
                <tr class="pd-tfoot">
                    <td colspan="5">Total: {{ count($printDetailKlasifikasi) }} arsip surat</td>
                </tr>
            </tfoot>
        </table>
        @else
        <p class="pd-empty">Belum ada data arsip surat.</p>
        @endif
    </div>
</div>

<div class="report-section" id="tab-surat-masuk" style="display:none;">
    <div class="report-filter-bar no-print">
        <div class="filter-group" style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:.4rem;">
                <label for="filter-tahun-sm" style="font-size:.82rem;font-weight:600;color:var(--color-text-muted);">Filter Tahun:</label>
                <select id="filter-tahun-sm" class="filter-select" style="min-width:110px;">
                    <option value="">Semua Tahun</option>
                    @foreach($tahun5 as $ty)
                    <option value="{{ $ty }}">{{ $ty }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;align-items:center;gap:.4rem;">
                <label for="sort-sm" style="font-size:.82rem;font-weight:600;color:var(--color-text-muted);">Urutan:</label>
                <select id="sort-sm" class="filter-select" style="min-width:160px;">
                    <option value="asc">Tanggal Input (Terlama)</option>
                    <option value="desc">Tanggal Input (Terbaru)</option>
                </select>
            </div>
        </div>
    </div>
    <div class="report-card" style="page-break-inside:avoid;">
        <div class="report-card-header">
            <div class="report-card-title-wrap">
                <div class="report-card-icon">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.72 12 19.79 19.79 0 0 1 1.65 3.42 2 2 0 0 1 3.62 1.2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.84a16 16 0 0 0 6 6l.94-.94a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.5 16.06z"/></svg>
                </div>
                <h3 class="report-card-title" id="sm-card-title">Daftar Surat Masuk</h3>
            </div>
            <span class="report-badge" id="sm-badge">{{ count($printDetailSM) }} Surat</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="report-table" id="tabel-surat-masuk">
                <thead>
                    <tr>
                        <th class="th-no">No</th>
                        <th>Kode / Nomor Surat</th>
                        <th class="th-sortable" data-sort-target="sm" data-col="tanggal" style="cursor:pointer;white-space:nowrap;">
                            Tanggal Input
                            <span class="sort-icon" id="sort-icon-sm">&#9650;</span>
                        </th>
                        <th>Uraian Informasi</th>
                    </tr>
                </thead>
                <tbody id="tbody-surat-masuk">
                    @forelse($printDetailSM as $i => $det)
                    <tr data-ts="{{ $det['ts'] }}" data-tahun="{{ $det['tahun'] }}">
                        <td class="td-no">{{ $i + 1 }}</td>
                        <td><span class="badge-kode-report">{{ $det['kode'] ?: '-' }}</span></td>
                        <td style="white-space:nowrap;">{{ $det['tanggal'] }}</td>
                        <td>{{ $det['uraian'] }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="td-empty-row">Belum ada data surat masuk</td></tr>
                    @endforelse
                </tbody>
                @if(count($printDetailSM) > 0)
                <tfoot>
                    <tr class="tfoot-total">
                        <td colspan="3" class="tfoot-label">TOTAL SURAT MASUK</td>
                        <td class="td-num"><strong id="sm-total">{{ count($printDetailSM) }}</strong> surat</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="print-only" id="print-detail-surat-masuk">
        <div class="print-detail-section-title">Daftar Arsip Surat Masuk</div>
        @php
$noSM = 1;
            $bulanNamesSM = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                             7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
            $groupedSM = [];
            foreach($printDetailSM as $det) {
                $gKey = $det['tahun'] . '-' . str_pad($det['bulan'], 2, '0', STR_PAD_LEFT);
                $groupedSM[$gKey][] = $det;
            }
            ksort($groupedSM);
@endphp
        @if(!empty($printDetailSM))
        <table class="print-detail-table" id="print-detail-table-surat-masuk">
            <thead>
                <tr>
                    <th class="pd-no">No</th>
                    <th class="pd-kode">Kode / Nomor Surat</th>
                    <th class="pd-tgl">Tanggal</th>
                    <th class="pd-uraian">URAIAN INFORMASI ARSIP</th>
                </tr>
            </thead>
            <tbody>
                @foreach($groupedSM as $gKey => $gRows)
                @php
$fRow = $gRows[0];
                    $lblSM = strtoupper(($bulanNamesSM[$fRow['bulan']] ?? '-') . ' ' . $fRow['tahun']);
@endphp
                <tr class="pd-month-header pd-row-sm" data-print-year="{{ $fRow['tahun'] }}">
                    <td colspan="4" class="pd-month-label">
                        <span class="pd-month-icon">&#9632;</span> {{ $lblSM }}
                    </td>
                </tr>
                @foreach($gRows as $det)
                <tr class="pd-row-sm" data-print-year="{{ $det['tahun'] }}">
                    <td class="pd-no">{{ $noSM++ }}</td>
                    <td class="pd-kode"><span class="pd-kode-badge">{{ $det['kode'] ?: '-' }}</span></td>
                    <td class="pd-tgl">{{ $det['tanggal'] }}</td>
                    <td class="pd-uraian">{{ $det['uraian'] }}</td>
                </tr>
                @endforeach
                @endforeach
            </tbody>
            <tfoot>
                <tr class="pd-tfoot">
                    <td colspan="4">Total: {{ count($printDetailSM) }} surat masuk</td>
                </tr>
            </tfoot>
        </table>
        @else
        <p class="pd-empty">Belum ada data surat masuk.</p>
        @endif
    </div>
</div>

<div class="report-section" id="tab-surat-keluar" style="display:none;">
    <div class="report-filter-bar no-print">
        <div class="filter-group" style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:.4rem;">
                <label for="filter-tahun-sk" style="font-size:.82rem;font-weight:600;color:var(--color-text-muted);">Filter Tahun:</label>
                <select id="filter-tahun-sk" class="filter-select" style="min-width:110px;">
                    <option value="">Semua Tahun</option>
                    @foreach($tahun5 as $ty)
                    <option value="{{ $ty }}">{{ $ty }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;align-items:center;gap:.4rem;">
                <label for="sort-sk" style="font-size:.82rem;font-weight:600;color:var(--color-text-muted);">Urutan:</label>
                <select id="sort-sk" class="filter-select" style="min-width:160px;">
                    <option value="asc">Tanggal Input (Terlama)</option>
                    <option value="desc">Tanggal Input (Terbaru)</option>
                </select>
            </div>
        </div>
    </div>
    <div class="report-card" style="page-break-inside:avoid;">
        <div class="report-card-header">
            <div class="report-card-title-wrap">
                <div class="report-card-icon" style="background:rgba(16,185,129,.12);border-color:rgba(16,185,129,.2);color:#34d399;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                </div>
                <h3 class="report-card-title" id="sk-card-title">Daftar Surat Keluar</h3>
            </div>
            <span class="report-badge" id="sk-badge" style="background:rgba(16,185,129,.1);color:#34d399;border-color:rgba(16,185,129,.25);">{{ count($printDetailSK) }} Surat</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="report-table" id="tabel-surat-keluar">
                <thead>
                    <tr>
                        <th class="th-no">No</th>
                        <th>Kode / Nomor Surat</th>
                        <th class="th-sortable" data-sort-target="sk" data-col="tanggal" style="cursor:pointer;white-space:nowrap;">
                            Tanggal Input
                            <span class="sort-icon" id="sort-icon-sk">&#9650;</span>
                        </th>
                        <th>Uraian Informasi</th>
                    </tr>
                </thead>
                <tbody id="tbody-surat-keluar">
                    @forelse($printDetailSK as $i => $det)
                    <tr data-ts="{{ $det['ts'] }}" data-tahun="{{ $det['tahun'] }}">
                        <td class="td-no">{{ $i + 1 }}</td>
                        <td><span class="badge-kode-report" style="background:rgba(16,185,129,.1);color:#34d399;border-color:rgba(16,185,129,.2);">{{ $det['kode'] ?: '-' }}</span></td>
                        <td style="white-space:nowrap;">{{ $det['tanggal'] }}</td>
                        <td>{{ $det['uraian'] }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="td-empty-row">Belum ada data surat keluar</td></tr>
                    @endforelse
                </tbody>
                @if(count($printDetailSK) > 0)
                <tfoot>
                    <tr class="tfoot-total">
                        <td colspan="3" class="tfoot-label">TOTAL SURAT KELUAR</td>
                        <td class="td-num"><strong id="sk-total">{{ count($printDetailSK) }}</strong> surat</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="print-only" id="print-detail-surat-keluar">
        <div class="print-detail-section-title">Daftar Arsip Surat Keluar</div>
        @php
$noSK = 1;
            $bulanNamesSK = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                             7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
            $groupedSK = [];
            foreach($printDetailSK as $det) {
                $gKey = $det['tahun'] . '-' . str_pad($det['bulan'], 2, '0', STR_PAD_LEFT);
                $groupedSK[$gKey][] = $det;
            }
            ksort($groupedSK);
@endphp
        @if(!empty($printDetailSK))
        <table class="print-detail-table" id="print-detail-table-surat-keluar">
            <thead>
                <tr>
                    <th class="pd-no">No</th>
                    <th class="pd-kode">Kode / Nomor Surat</th>
                    <th class="pd-tgl">Tanggal</th>
                    <th class="pd-uraian">URAIAN INFORMASI ARSIP</th>
                </tr>
            </thead>
            <tbody>
                @foreach($groupedSK as $gKey => $gRows)
                @php
$fRow = $gRows[0];
                    $lblSK = strtoupper(($bulanNamesSK[$fRow['bulan']] ?? '-') . ' ' . $fRow['tahun']);
@endphp
                <tr class="pd-month-header pd-row-sk" data-print-year="{{ $fRow['tahun'] }}">
                    <td colspan="4" class="pd-month-label">
                        <span class="pd-month-icon">&#9632;</span> {{ $lblSK }}
                    </td>
                </tr>
                @foreach($gRows as $det)
                <tr class="pd-row-sk" data-print-year="{{ $det['tahun'] }}">
                    <td class="pd-no">{{ $noSK++ }}</td>
                    <td class="pd-kode"><span class="pd-kode-badge">{{ $det['kode'] ?: '-' }}</span></td>
                    <td class="pd-tgl">{{ $det['tanggal'] }}</td>
                    <td class="pd-uraian">{{ $det['uraian'] }}</td>
                </tr>
                @endforeach
                @endforeach
            </tbody>
            <tfoot>
                <tr class="pd-tfoot">
                    <td colspan="4">Total: {{ count($printDetailSK) }} surat keluar</td>
                </tr>
            </tfoot>
        </table>
        @else
        <p class="pd-empty">Belum ada data surat keluar.</p>
        @endif
    </div>
</div>

<div id="modal-cetak" class="modal-overlay" role="dialog" aria-modal="true">
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

#modal-cetak.modal-overlay {
    background: rgba(15, 23, 42, 0.50);
    backdrop-filter: blur(10px) saturate(130%);
    -webkit-backdrop-filter: blur(10px) saturate(130%);
}

[data-theme="dark"] #modal-cetak.modal-overlay {
    background: rgba(5, 8, 18, 0.72);
    backdrop-filter: blur(12px) saturate(120%);
    -webkit-backdrop-filter: blur(12px) saturate(120%);
}

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

[data-theme="dark"] #modal-cetak .modal-box {
    background: var(--color-surface, #1a1d27);
    border-color: var(--color-border, #2d3148);
    box-shadow:
        0 0 0 1px rgba(99,102,241,.12),
        0 24px 64px rgba(0,0,0,.55),
        0 8px 24px rgba(0,0,0,.35);
}

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

#modal-cetak .modal-title-print {
    font-size: 1.0625rem;
    font-weight: 700;
    color: var(--color-text, #1e293b);
    margin: 0 0 .5rem;
    letter-spacing: -.01em;
}

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

#modal-cetak .modal-actions-print {
    display: flex;
    gap: .75rem;
    justify-content: center;
    flex-wrap: wrap;
}

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
    transition: color .18s ease, background .18s ease, box-shadow .18s ease;
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

.report-section {
    transition: none;
}

.th-sortable {
    user-select: none;
}
.th-sortable:hover {
    background: rgba(99,102,241,.18) !important;
    color: #a5b4fc !important;
}
.sort-icon {
    display: inline-block;
    font-size: .6rem;
    margin-left: .35rem;
    opacity: .65;
    vertical-align: middle;
    transition: transform .2s ease;
}

@keyframes tabFadeSlideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.tab-entering {
    animation: tabFadeSlideIn .22s cubic-bezier(.22,1,.36,1) both;
}
.tab-leaving {
    opacity: 0;
    transform: translateY(-6px);
    transition: opacity .1s ease, transform .1s ease;
    pointer-events: none;
}

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

.row-active td { background: rgba(99,102,241,.07) !important; }
.row-empty td  { opacity: .4; }

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

.report-empty {
    display: flex; flex-direction: column; align-items: center; gap: .75rem;
    padding: 3.5rem 1rem; color: var(--color-text-muted); text-align: center;
}
.report-empty svg { opacity: .25; }
.report-empty span { font-size: .9rem; font-weight: 500; }

.alert { display:flex; align-items:center; gap:.5rem; padding:.8rem 1rem; border-radius:.5rem; font-size:.84rem; }
.alert-error { background:rgba(239,68,68,.08); color:#f87171; border:1px solid rgba(239,68,68,.2); }

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
    padding: 0 1.75rem;
    overflow: hidden;
    max-height: 0;
    opacity: 0;
    transition:
        max-height .35s cubic-bezier(.4, 0, .2, 1),
        opacity     .28s ease,
        padding     .35s ease;
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

.print-header,
.print-signature,
.print-only { display: none !important; }
.print-logo-image {
    display: block;
    width: auto;
    height: auto;
    max-width: 100%;
    object-fit: contain;
}

@page {
    size: A4 portrait;
    margin: 15mm 18mm 20mm 18mm;

    @bottom-center {
        content: "Halaman " counter(page) " dari " counter(pages);
        font-size: 7.5pt;
        color: #555;
    }
}

@media print {

    ::-webkit-scrollbar,
    ::-webkit-scrollbar-thumb,
    ::-webkit-scrollbar-track,
    ::-webkit-scrollbar-corner {
        display: none !important; width: 0 !important; height: 0 !important;
        background: transparent !important;
    }
    *, *::before, *::after {
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
    }

    .no-print,
    .sidebar, .header, .footer,
    .sidebar-overlay, .drawer-footer-fixed,
    #page-loading-bar, #toast-container,
    .report-tabs, .page-header,
    #report-page-header,
    .report-filter-bar,

    .report-section .report-card,
    .report-section .report-empty {
        display: none !important;
    }

    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    html, body {
        display: block !important;
        background: #fff !important;
        color: #000 !important;
        font-family: 'Times New Roman', Times, serif !important;
        font-size: 10pt;
        overflow: visible !important;
    }
    div, div[style*="overflow"], .kode-detail-wrap, .report-section, .layout-body, .main-content, .main-content-wrap {
        overflow: visible !important; overflow-x: visible !important;
        overflow-y: visible !important; max-height: none !important;
    }
    html, body, .layout-body, .main-content, .main-content-wrap {
        background-image: none !important; box-shadow: none !important;
    }

html.report-printing, body.report-printing { display: block !important; overflow: visible !important; }
html.report-printing::-webkit-scrollbar,
body.report-printing::-webkit-scrollbar,
html.report-printing *::-webkit-scrollbar,
body.report-printing *::-webkit-scrollbar {
    display: none !important; width: 0 !important; height: 0 !important;
}
    .layout-body, .main-content, .main-content-wrap, .report-section { background: #fff !important; }
    .main-content { margin-left: 0 !important; padding: 0 !important; }
    .main-content-wrap { max-width: 100% !important; padding: 0 !important; }
    .layout-body { display: block !important; }
    .report-section { margin-bottom: 0; }

    .print-header {
        display: block !important;
        margin-bottom: .75rem;
    }

    .print-kop-topbar {
        display: block !important;
        height: 5pt;
        background: #1e3a5f !important;
        margin-bottom: .5rem;
        border-radius: 0;
    }

    .print-kop {
        display: flex !important;
        align-items: center;
        gap: 0;
        padding-bottom: 6pt;
        margin-bottom: 4pt;
    }
    .print-kop-logo {
        flex-shrink: 0;
        width: 28mm;
        display: flex !important;
        align-items: center;
        justify-content: center;
        padding-right: 10pt;
        border-right: 1pt solid #c8d0dc;
    }
    .print-logo-image {
        display: block !important;
        width: 100% !important;
        height: auto !important;
        max-height: 22mm !important;
        object-fit: contain !important;
    }
    .print-kop-separator {
        display: none !important;
    }
    .print-kop-text {
        flex: 1;
        padding-left: 10pt;
        text-align: left;
    }
    .print-kop-ministry {
        font-size: 12.5pt !important;
        font-weight: 800 !important;
        color: #1a1a1a !important;
        letter-spacing: .01em;
        line-height: 1.2;
        text-transform: uppercase;
        font-family: 'Arial', 'Helvetica', sans-serif !important;
    }
    .print-kop-unit {
        font-size: 9pt !important;
        color: #1a1a1a !important;
        font-weight: 700 !important;
        line-height: 1.35;
        margin-top: 2pt;
        text-transform: uppercase;
        font-family: 'Arial', 'Helvetica', sans-serif !important;
    }
    .print-kop-address {
        font-size: 7.5pt !important;
        color: #555 !important;
        margin-top: 4pt;
        font-style: italic;
        font-family: 'Arial', 'Helvetica', sans-serif !important;
    }

    .print-kop-divider {
        display: block !important;
        margin: .4rem 0 .5rem;
    }
    .print-kop-line-thick {
        display: block !important;
        height: 3pt;
        background: #1e3a5f !important;
        margin-bottom: 1.5pt;
    }
    .print-kop-line-thin {
        display: block !important;
        height: 1pt;
        background: #1e3a5f !important;
    }

    .print-doc-title-block {
        display: block !important;
        text-align: center;
        margin: .5rem 0 .4rem;
    }
    .print-title {
        display: block !important;
        font-size: 13pt !important;
        font-weight: 700 !important;
        color: #000 !important;
        letter-spacing: .06em;
        text-transform: uppercase;
        text-decoration: underline;
        text-underline-offset: 3pt;
        font-family: 'Times New Roman', Times, serif !important;
    }
    .print-doc-jenis {
        display: block !important;
        font-size: 10pt !important;
        color: #333 !important;
        font-style: italic;
        margin-top: .15rem;
        font-family: 'Times New Roman', Times, serif !important;
    }

    .print-meta-table {
        display: table !important;
        width: 100%;
        border-collapse: collapse;
        font-size: 8.5pt !important;
        margin: .4rem 0 .25rem;
        font-family: 'Times New Roman', Times, serif !important;
        color: #000 !important;
    }
    .pmt-label {
        width: 90px;
        font-weight: 600;
        padding: .06rem 0;
        vertical-align: top;
    }
    .pmt-sep  { width: 10px; text-align: center; padding: .06rem .15rem; vertical-align: top; }
    .pmt-val  { padding: .06rem .25rem; vertical-align: top; }
    .pmt-spacer { width: 20px; }

    .print-divider {
        display: block !important;
        border: none !important;
        border-top: 1.5pt solid #1e3a5f !important;
        margin: .5rem 0 .75rem !important;
    }

    .print-only {
        display: block !important;
        margin-top: 0;
    }
    .print-only.print-hidden { display: none !important; }

    .print-detail-section-title {
        font-size: 10.5pt !important;
        font-weight: 700 !important;
        color: #000 !important;
        text-align: center;
        margin-bottom: .5rem;
        padding: .25rem 0 .25rem;
        border-top: 1pt solid #000 !important;
        border-bottom: 1pt solid #000 !important;
        text-transform: uppercase;
        letter-spacing: .05em;
        font-family: 'Times New Roman', Times, serif !important;
    }

    .print-detail-table {
        width: 100%;
        border-collapse: collapse !important;
        font-size: 8.5pt !important;
        margin-top: .25rem;
        font-family: 'Times New Roman', Times, serif !important;
        border: 1pt solid #333333 !important;
    }
    .print-detail-table th {
        background: #1e3a5f !important;
        color: #fff !important;
        font-size: 8pt !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: .04em;
        padding: .38rem .55rem !important;
        border: 1pt solid #333333 !important;
        text-align: center !important;
        white-space: nowrap;
        vertical-align: middle;
    }
    .print-detail-table td {
        padding: .3rem .55rem !important;
        border: 1pt solid #333333 !important;
        color: #000 !important;
        vertical-align: top;
        font-size: 8.5pt !important;
        line-height: 1.45;
    }

    .print-detail-table tbody tr:nth-child(odd) td {
        background: #fff !important;
    }
    .print-detail-table tbody tr:nth-child(even) td {
        background: #eef2f7 !important;
    }

    .print-detail-table tfoot td {
        background: #d9e2ef !important;
        border: 1pt solid #333333 !important;
        font-weight: 700 !important;
        font-size: 8pt !important;
        color: #1e3a5f !important;
        text-align: right !important;
        font-style: italic;
    }

    .pd-no    { width: 22px !important; text-align: center !important; font-weight: 700; }
    .pd-tahun { width: 38px !important; text-align: center !important; }
    .pd-bulan { width: 64px !important; }
    .pd-kode  { width: 100px !important; }
    .pd-tgl   { width: 72px !important; white-space: nowrap; }
    .pd-uraian { }
    .pd-jenis { width: 32px !important; text-align: center !important; }

    .pd-kode-badge {
        display: inline !important;
        background: none !important;
        border: none !important;
        padding: 0 !important;
        font-weight: 700 !important;
        font-size: inherit !important;
        color: #000 !important;
        letter-spacing: .01em;
    }

    .pd-jenis-badge {
        display: inline-block;
        border-radius: 2px;
        padding: .04rem .25rem;
        font-size: 7.5pt;
        font-weight: 700;
        text-align: center;
    }
    .pd-jenis-sm {
        background: #dbeafe !important;
        color: #1e3a5f !important;
        border: .75pt solid #93c5fd !important;
    }
    .pd-jenis-sk {
        background: #d1fae5 !important;
        color: #065f46 !important;
        border: .75pt solid #6ee7b7 !important;
    }
    .pd-empty { font-size: 9pt; color: #555; font-style: italic; text-align: center; padding: 1rem; }

    .print-detail-table tbody tr.pd-month-header td {
        background: #1e3a5f !important;
        color: #fff !important;
        font-family: 'Times New Roman', Times, serif !important;
        font-size: 9pt !important;
        font-weight: 700 !important;
        padding: .4rem .7rem !important;
        border: 1pt solid #333333 !important;
        letter-spacing: .07em !important;
        text-transform: uppercase !important;
        page-break-after: avoid !important;
        vertical-align: middle !important;
    }
    .print-detail-table tbody tr.pd-month-header:nth-child(odd) td,
    .print-detail-table tbody tr.pd-month-header:nth-child(even) td {
        background: #1e3a5f !important;
        color: #fff !important;
    }
    .pd-month-icon {
        display: inline-block;
        font-size: 7pt;
        margin-right: .4rem;
        vertical-align: middle;
        opacity: .85;
    }

    .print-detail-table tbody tr.pd-year-header td {
        background: #1e3a5f !important;
        color: #fff !important;
        font-family: 'Times New Roman', Times, serif !important;
        font-size: 9pt !important;
        font-weight: 700 !important;
        padding: .4rem .7rem !important;
        border: 1pt solid #333333 !important;
        letter-spacing: .07em !important;
        text-transform: uppercase !important;
        page-break-after: avoid !important;
        vertical-align: middle !important;
    }
    .print-detail-table tbody tr.pd-year-header:nth-child(odd) td,
    .print-detail-table tbody tr.pd-year-header:nth-child(even) td {
        background: #1e3a5f !important;
        color: #fff !important;
    }
    .pd-year-icon {
        display: inline-block;
        font-size: 7pt;
        margin-right: .4rem;
        vertical-align: middle;
        opacity: .85;
    }

    .print-detail-table tbody tr.pd-kode-header td {
        background: #1a5c3a !important;
        color: #fff !important;
        font-family: 'Times New Roman', Times, serif !important;
        font-size: 9pt !important;
        font-weight: 700 !important;
        padding: .38rem .7rem !important;
        border: 1pt solid #333333 !important;
        page-break-after: avoid !important;
        vertical-align: middle !important;
    }
    .print-detail-table tbody tr.pd-kode-header:nth-child(odd) td,
    .print-detail-table tbody tr.pd-kode-header:nth-child(even) td {
        background: #1a5c3a !important;
        color: #fff !important;
    }
    .pd-kode-header-label {
        display: table-cell !important;
        letter-spacing: .04em !important;
    }
    .pd-kode-header-icon {
        display: inline-block;
        font-size: 7pt;
        margin-right: .45rem;
        vertical-align: middle;
        opacity: .85;
    }
    .pd-kode-header-badge {
        display: inline-block;
        background: rgba(255,255,255,.22) !important;
        color: #fff !important;
        border: .75pt solid rgba(255,255,255,.4) !important;
        border-radius: 2px;
        padding: .05rem .45rem !important;
        font-size: 9pt !important;
        font-weight: 800 !important;
        letter-spacing: .08em;
        margin-right: .45rem;
        vertical-align: middle;
    }
    .pd-kode-header-count {
        font-size: 8pt !important;
        font-weight: 400 !important;
        opacity: .8;
        font-style: italic;
        vertical-align: middle;
    }

    .print-signature {
        display: block !important;
        margin-top: 2rem;
        page-break-inside: avoid;
        font-family: 'Times New Roman', Times, serif !important;
    }
    .sig-info {
        font-size: 8pt;
        color: #444;
        margin-bottom: 1.5rem;
        border-top: 1pt solid #ccc;
        padding-top: .4rem;
        font-style: italic;
    }
    .sig-info p { margin: .1rem 0; }
    .sig-boxes { display: flex !important; justify-content: space-between; padding: 0 1rem; }
    .sig-box { text-align: center; font-size: 9.5pt; min-width: 120px; }
    .sig-space { height: 55px; }
    .sig-box p { margin: .1rem 0; }

    .pre-hide, .pre-hide.is-visible, .pre-hide:not(.is-visible) {
        opacity: 1 !important; transform: none !important;
        transition: none !important; visibility: visible !important;
    }

    .row-kode-detail.print-row-hidden { display: none !important; }
    tr.print-row-hidden { display: none !important; }
    tr.print-hidden { display: none !important; }
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

    var tabs     = document.querySelectorAll('.report-tab');
    var sections = document.querySelectorAll('.report-section');

    function showTab(target) {
        var currentSection = null;
        sections.forEach(function(sec) {
            if (sec.style.display !== 'none' && sec.id !== 'tab-' + target) {
                currentSection = sec;
            }
        });

        function revealTarget() {
            sections.forEach(function(sec) {
                if (sec.id === 'tab-' + target) {
                    sec.style.display = '';
                    sec.classList.remove('tab-leaving');

                    sec.offsetHeight;
                    sec.classList.add('tab-entering');

                    setTimeout(function() { sec.classList.remove('tab-entering'); }, 280);
                } else {
                    sec.style.display = 'none';
                    sec.classList.remove('tab-leaving', 'tab-entering');
                }
            });
        }

        if (currentSection) {
            currentSection.classList.add('tab-leaving');
            setTimeout(revealTarget, 120);
        } else {
            revealTarget();
        }
    }

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {

            tabs.forEach(function(t) { t.classList.remove('active'); });
            this.classList.add('active');
            showTab(this.dataset.tab);
        });
    });

    var initActiveTab = document.querySelector('.report-tab.active');
    if (!initActiveTab && tabs.length > 0) {
        tabs[0].classList.add('active');
    }

    function preparePrint() {
        document.documentElement.classList.add('report-printing');
        document.body.classList.add('report-printing');

        var activeTab = document.querySelector('.report-tab.active');
        var activeId  = activeTab ? activeTab.dataset.tab : 'bulanan';

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

        var pdBulanan       = document.getElementById('print-detail-bulanan');
        var pdTahunan       = document.getElementById('print-detail-tahunan');
        var pdKlasifikasi   = document.getElementById('print-detail-klasifikasi');
        var pdSuratMasuk    = document.getElementById('print-detail-surat-masuk');
        var pdSuratKeluar   = document.getElementById('print-detail-surat-keluar');

        [pdBulanan, pdTahunan, pdKlasifikasi, pdSuratMasuk, pdSuratKeluar].forEach(function(el) {
            if (el) el.classList.add('print-hidden');
        });

        if (activeId === 'bulanan') {
            if (pdBulanan) pdBulanan.classList.remove('print-hidden');

            var selBln = document.getElementById('filter-tahun-bulanan');
            var filterBln = selBln ? selBln.value : '';
            if (pdBulanan && filterBln) {
                pdBulanan.querySelectorAll('tr.pd-row-bulanan').forEach(function(tr) {
                    if (tr.getAttribute('data-print-year') !== filterBln) {
                        tr.classList.add('print-hidden');
                    } else {
                        tr.classList.remove('print-hidden');
                    }
                });
            } else if (pdBulanan) {
                pdBulanan.querySelectorAll('tr.pd-row-bulanan').forEach(function(tr) {
                    tr.classList.remove('print-hidden');
                });
            }

        } else if (activeId === 'tahunan') {
            if (pdTahunan) pdTahunan.classList.remove('print-hidden');

            var selThn = document.getElementById('filter-tahun-tahunan');
            var filterThn = selThn ? selThn.value : '';
            if (pdTahunan && filterThn) {
                pdTahunan.querySelectorAll('tr.pd-row-tahunan').forEach(function(tr) {
                    if (tr.getAttribute('data-print-year') !== filterThn) {
                        tr.classList.add('print-hidden');
                    } else {
                        tr.classList.remove('print-hidden');
                    }
                });
            } else if (pdTahunan) {
                pdTahunan.querySelectorAll('tr.pd-row-tahunan').forEach(function(tr) {
                    tr.classList.remove('print-hidden');
                });
            }

        } else if (activeId === 'klasifikasi') {
            if (pdKlasifikasi) pdKlasifikasi.classList.remove('print-hidden');
            var selKls    = document.getElementById('filter-tahun-klasifikasi');
            var filterKls = selKls ? selKls.value : '';
            if (pdKlasifikasi && filterKls) {

                pdKlasifikasi.querySelectorAll('tr.pd-row-klasifikasi:not(.pd-kode-header)').forEach(function(tr) {
                    var rowYear = tr.getAttribute('data-print-year') || '';
                    if (rowYear && rowYear !== filterKls) {
                        tr.classList.add('print-hidden');
                    } else {
                        tr.classList.remove('print-hidden');
                    }
                });

                var kodeHeaders = pdKlasifikasi.querySelectorAll('tr.pd-kode-header');
                kodeHeaders.forEach(function(headerTr) {
                    var sibling = headerTr.nextElementSibling;
                    var hasVisible = false;
                    while (sibling && !sibling.classList.contains('pd-kode-header')) {
                        if (!sibling.classList.contains('print-hidden')) {
                            hasVisible = true;
                            break;
                        }
                        sibling = sibling.nextElementSibling;
                    }
                    if (hasVisible) {
                        headerTr.classList.remove('print-hidden');
                    } else {
                        headerTr.classList.add('print-hidden');
                    }
                });
            } else if (pdKlasifikasi) {

                pdKlasifikasi.querySelectorAll('tr.pd-row-klasifikasi').forEach(function(tr) {
                    tr.classList.remove('print-hidden');
                });
            }

        } else if (activeId === 'surat-masuk') {
            if (pdSuratMasuk) pdSuratMasuk.classList.remove('print-hidden');
            var selSmPrint = document.getElementById('filter-tahun-sm');
            var filterSm   = selSmPrint ? selSmPrint.value : '';
            if (pdSuratMasuk && filterSm) {
                pdSuratMasuk.querySelectorAll('tr.pd-row-sm').forEach(function(tr) {
                    if (tr.getAttribute('data-print-year') !== filterSm) {
                        tr.classList.add('print-hidden');
                    } else {
                        tr.classList.remove('print-hidden');
                    }
                });
            } else if (pdSuratMasuk) {
                pdSuratMasuk.querySelectorAll('tr.pd-row-sm').forEach(function(tr) {
                    tr.classList.remove('print-hidden');
                });
            }

        } else if (activeId === 'surat-keluar') {
            if (pdSuratKeluar) pdSuratKeluar.classList.remove('print-hidden');
            var selSkPrint = document.getElementById('filter-tahun-sk');
            var filterSk   = selSkPrint ? selSkPrint.value : '';
            if (pdSuratKeluar && filterSk) {
                pdSuratKeluar.querySelectorAll('tr.pd-row-sk').forEach(function(tr) {
                    if (tr.getAttribute('data-print-year') !== filterSk) {
                        tr.classList.add('print-hidden');
                    } else {
                        tr.classList.remove('print-hidden');
                    }
                });
            } else if (pdSuratKeluar) {
                pdSuratKeluar.querySelectorAll('tr.pd-row-sk').forEach(function(tr) {
                    tr.classList.remove('print-hidden');
                });
            }
        }

        if (pdBulanan && !pdBulanan.classList.contains('print-hidden')) {
            var visibleBln = pdBulanan.querySelectorAll(
                'tr.pd-row-bulanan:not(.pd-month-header):not(.print-hidden)'
            ).length;
            var tfootBln = pdBulanan.querySelector('tr.pd-tfoot td');
            if (tfootBln) tfootBln.textContent = 'Total: ' + visibleBln + ' arsip surat';
        }

        if (pdTahunan && !pdTahunan.classList.contains('print-hidden')) {
            var visibleThn = pdTahunan.querySelectorAll(
                'tr.pd-row-tahunan:not(.pd-year-header):not(.print-hidden)'
            ).length;
            var tfootThn = pdTahunan.querySelector('tr.pd-tfoot td');
            if (tfootThn) tfootThn.textContent = 'Total: ' + visibleThn + ' arsip surat';
        }

        if (pdKlasifikasi && !pdKlasifikasi.classList.contains('print-hidden')) {
            var visibleKls = pdKlasifikasi.querySelectorAll(
                'tr.pd-row-klasifikasi:not(.pd-kode-header):not(.print-hidden)'
            ).length;
            var tfootKls = pdKlasifikasi.querySelector('tr.pd-tfoot td');
            if (tfootKls) tfootKls.textContent = 'Total: ' + visibleKls + ' arsip surat';
        }

        if (pdSuratMasuk && !pdSuratMasuk.classList.contains('print-hidden')) {
            var visibleSM = pdSuratMasuk.querySelectorAll(
                'tr.pd-row-sm:not(.pd-month-header):not(.print-hidden)'
            ).length;
            var tfootSM = pdSuratMasuk.querySelector('tr.pd-tfoot td');
            if (tfootSM) tfootSM.textContent = 'Total: ' + visibleSM + ' surat masuk';
        }

        if (pdSuratKeluar && !pdSuratKeluar.classList.contains('print-hidden')) {
            var visibleSK = pdSuratKeluar.querySelectorAll(
                'tr.pd-row-sk:not(.pd-month-header):not(.print-hidden)'
            ).length;
            var tfootSK = pdSuratKeluar.querySelector('tr.pd-tfoot td');
            if (tfootSK) tfootSK.textContent = 'Total: ' + visibleSK + ' surat keluar';
        }

        document.querySelectorAll('#tabel-tahunan tbody tr').forEach(function(tr) {
            if (tr.style.display === 'none') {
                tr.classList.add('print-row-hidden');
            } else {
                tr.classList.remove('print-row-hidden');

                if (tr.classList.contains('row-kode-detail')) {
                    tr.classList.remove('print-row-hidden');
                }
            }
        });

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

        var n = new Date();
        var dateStr = formatDateID(n) + ', ' + pad(n.getHours()) + ':' + pad(n.getMinutes()) + ' WITA';

        var metaDate = document.getElementById('print-meta-date');
        if (metaDate) metaDate.textContent = dateStr;

        var jenisMap = {
            'bulanan'       : 'Rekap Bulanan Per Tahun',
            'tahunan'       : 'Rekap Tahunan (Semua Tahun)',
            'klasifikasi'   : 'Rekap Kode Klasifikasi',
            'surat-masuk'   : 'Daftar Surat Masuk',
            'surat-keluar'  : 'Daftar Surat Keluar'
        };
        var jenisLabel = jenisMap[activeId] || 'Laporan Rekapitulasi';

        var docJenis = document.getElementById('print-doc-jenis');
        if (docJenis) docJenis.textContent = jenisLabel;

        var metaJenis = document.getElementById('print-meta-jenis');
        if (metaJenis) metaJenis.textContent = jenisLabel;

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

        document.querySelectorAll('.print-row-hidden').forEach(function(el) {
            el.classList.remove('print-row-hidden');
        });
        document.querySelectorAll('.print-hidden').forEach(function(el) {
            el.classList.remove('print-hidden');
        });
    }

    window.addEventListener('beforeprint', preparePrint);
    window.addEventListener('afterprint',  restoreAfterPrint);

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

    var TAB_LABELS = {
        bulanan:       'Rekap Bulanan',
        tahunan:       'Rekap Tahunan',
        klasifikasi:   'Klasifikasi',
        'surat-masuk': 'Surat Masuk',
        'surat-keluar':'Surat Keluar'
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
            modalCetak.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModalCetak() {
        if (modalCetak) modalCetak.classList.remove('is-open');
        document.body.style.overflow = '';
    }

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
        if (e.key === 'Escape' && modalCetak && modalCetak.classList.contains('is-open')) closeModalCetak();
    });

    if (modalCetakOk) {
        modalCetakOk.addEventListener('click', function() {
            closeModalCetak();

            setTimeout(function() {
                window.print();
            }, 80);
        });
    }

    document.querySelectorAll('.row-kode-detail').forEach(function(tr) {
        tr.style.display = 'none';
    });

    function openDetailRow(detailTr, icon) {
        var wrap = detailTr.querySelector('.kode-detail-wrap');
        detailTr.style.display = 'table-row';

        detailTr.offsetHeight;
        if (wrap) {
            wrap.style.maxHeight = wrap.scrollHeight + 200 + 'px';
            wrap.style.opacity   = '1';
            wrap.style.padding   = '.85rem 1.75rem 1.1rem';
        }
        if (icon) icon.style.transform = 'rotate(90deg)';
    }

    function closeDetailRow(detailTr, icon) {
        var wrap = detailTr.querySelector('.kode-detail-wrap');
        if (wrap) {
            wrap.style.maxHeight = '0';
            wrap.style.opacity   = '0';
            wrap.style.padding   = '0 1.75rem';
        }
        if (icon) icon.style.transform = '';

        setTimeout(function() {
            if (wrap && wrap.style.maxHeight === '0px') {
                detailTr.style.display = 'none';
            }
        }, 360);
    }

    document.querySelectorAll('.row-tahun').forEach(function(row) {
        row.addEventListener('click', function() {
            var year   = this.getAttribute('data-year');
            var detail = document.getElementById('detail-' + year);
            var icon   = this.querySelector('.tahun-toggle-icon');
            if (!detail) return;
            var isOpen = detail.style.display === 'table-row' &&
                         detail.querySelector('.kode-detail-wrap') &&
                         parseFloat(detail.querySelector('.kode-detail-wrap').style.maxHeight) > 0;
            if (isOpen) {
                closeDetailRow(detail, icon);
                this.classList.remove('expanded');
            } else {
                openDetailRow(detail, icon);
                this.classList.add('expanded');
            }
        });
    });

    document.querySelectorAll('.row-bulan-expandable').forEach(function(row) {
        row.addEventListener('click', function() {
            var key    = this.getAttribute('data-month-key');
            var detail = document.getElementById('detail-bulan-' + key);
            var icon   = this.querySelector('.bulan-toggle-icon');
            if (!detail) return;
            var isOpen = detail.style.display === 'table-row' &&
                         detail.querySelector('.kode-detail-wrap') &&
                         parseFloat(detail.querySelector('.kode-detail-wrap').style.maxHeight) > 0;
            if (isOpen) {
                closeDetailRow(detail, icon);
                this.classList.remove('expanded');
            } else {
                openDetailRow(detail, icon);
                this.classList.add('expanded');
            }
        });
    });

    var selTahunBulanan = document.getElementById('filter-tahun-bulanan');
    if (selTahunBulanan) {
        selTahunBulanan.addEventListener('change', function() {
            var tahun = this.value;
            document.querySelectorAll('#tab-bulanan .report-card[data-year]').forEach(function(card) {
                card.style.display = (!tahun || card.getAttribute('data-year') === tahun) ? '' : 'none';
            });
        });
    }

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

            var tfootLabel = document.querySelector('#tabel-tahunan tfoot .tfoot-label');
            if (tfootLabel) {
                tfootLabel.textContent = tahun ? ('TOTAL TAHUN ' + tahun) : 'TOTAL KESELURUHAN';
            }
        });
    }

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

    function applySuratFilter(tbodyId, filterTahun, sortDir, totalElId, badgeId) {
        var tbody = document.getElementById(tbodyId);
        if (!tbody) return;
        var rows = Array.from(tbody.querySelectorAll('tr[data-ts]'));

        rows.forEach(function(tr) {
            var rowTahun = tr.getAttribute('data-tahun') || '';
            tr.style.display = (!filterTahun || rowTahun === filterTahun) ? '' : 'none';
        });

        var visibleRows = rows.filter(function(tr) { return tr.style.display !== 'none'; });
        visibleRows.sort(function(a, b) {
            var tsA = parseInt(a.getAttribute('data-ts')) || 0;
            var tsB = parseInt(b.getAttribute('data-ts')) || 0;
            return sortDir === 'asc' ? tsA - tsB : tsB - tsA;
        });

        visibleRows.forEach(function(tr, i) {
            tbody.appendChild(tr);
            var tdNo = tr.querySelector('.td-no');
            if (tdNo) tdNo.textContent = i + 1;
        });

        var count = visibleRows.length;
        var totalEl = document.getElementById(totalElId);
        if (totalEl) totalEl.textContent = count;
        var badge = document.getElementById(badgeId);
        if (badge) badge.textContent = count + ' Surat';
    }

    var selTahunSM = document.getElementById('filter-tahun-sm');
    var selSortSM  = document.getElementById('sort-sm');
    var sortIconSM = document.getElementById('sort-icon-sm');

    function updateSM() {
        var tahun   = selTahunSM ? selTahunSM.value : '';
        var sortDir = selSortSM  ? selSortSM.value  : 'asc';

        if (sortIconSM) sortIconSM.innerHTML = sortDir === 'asc' ? '&#9650;' : '&#9660;';
        applySuratFilter('tbody-surat-masuk', tahun, sortDir, 'sm-total', 'sm-badge');
    }

    if (selTahunSM) selTahunSM.addEventListener('change', updateSM);
    if (selSortSM)  selSortSM.addEventListener('change',  updateSM);

    var thSortSM = document.querySelector('#tabel-surat-masuk .th-sortable');
    if (thSortSM) {
        thSortSM.addEventListener('click', function() {
            if (!selSortSM) return;
            selSortSM.value = selSortSM.value === 'asc' ? 'desc' : 'asc';
            updateSM();
        });
    }

    var selTahunSK = document.getElementById('filter-tahun-sk');
    var selSortSK  = document.getElementById('sort-sk');
    var sortIconSK = document.getElementById('sort-icon-sk');

    function updateSK() {
        var tahun   = selTahunSK ? selTahunSK.value : '';
        var sortDir = selSortSK  ? selSortSK.value  : 'asc';
        if (sortIconSK) sortIconSK.innerHTML = sortDir === 'asc' ? '&#9650;' : '&#9660;';
        applySuratFilter('tbody-surat-keluar', tahun, sortDir, 'sk-total', 'sk-badge');
    }

    if (selTahunSK) selTahunSK.addEventListener('change', updateSK);
    if (selSortSK)  selSortSK.addEventListener('change',  updateSK);

    var thSortSK = document.querySelector('#tabel-surat-keluar .th-sortable');
    if (thSortSK) {
        thSortSK.addEventListener('click', function() {
            if (!selSortSK) return;
            selSortSK.value = selSortSK.value === 'asc' ? 'desc' : 'asc';
            updateSK();
        });
    }

    restoreAfterPrint();
});
</script>
@endpush
