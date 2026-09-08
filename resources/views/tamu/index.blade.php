<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Arsip Surat Masuk BPSDMP — Mode Baca Saja untuk Tamu">
    <title>Surat Masuk — Arsip BPSDMP (Mode Tamu)</title>
    <link rel="icon" type="image/png" href="{{ route('image.view', 'komdigi-logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style"
          href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
          onload="this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    </noscript>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-page:       #0f1117;
            --bg-card:       #1a1d27;
            --bg-header:     #13161f;
            --border:        rgba(255,255,255,0.08);
            --text-primary:  #e8eaf0;
            --text-muted:    #8b92a8;
            --text-dim:      #555c72;
            --accent:        #f59e0b;
            --accent-dim:    rgba(245,158,11,0.12);
            --guest-badge:   #f59e0b;
            --tab-active:    #6366f1;
            --tab-active-dim:rgba(99,102,241,0.15);
            --row-hover:     rgba(255,255,255,0.04);
            --shadow-card:   0 8px 32px rgba(0,0,0,0.4);
            --radius:        14px;
            --radius-sm:     8px;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg-page);
            color: var(--text-primary);
            min-height: 100vh;
            font-size: 14px;
            line-height: 1.5;
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: var(--bg-header);
            border-bottom: 1px solid var(--border);
            backdrop-filter: blur(12px);
            padding: 0 24px;
            height: 60px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .topbar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .topbar-logo img {
            height: 30px;
            width: auto;
            object-fit: contain;
        }
        .topbar-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-primary);
            letter-spacing: -0.01em;
        }
        .topbar-divider {
            width: 1px;
            height: 22px;
            background: var(--border);
            margin: 0 4px;
        }

        .badge-guest {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            background: var(--accent-dim);
            border: 1px solid rgba(245,158,11,0.3);
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--accent);
            letter-spacing: 0.05em;
            text-transform: uppercase;
            animation: pulse-badge 3s ease-in-out infinite;
        }
        .badge-guest svg { flex-shrink: 0; }
        @keyframes pulse-badge {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.7; }
        }

        .topbar-spacer { flex: 1; }

        .topbar-clock {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 0.75rem; font-weight: 600;
            color: var(--text-muted);
            white-space: nowrap;
        }
        .topbar-clock svg { color: var(--accent); flex-shrink: 0; }
        @media (max-width: 640px) { .topbar-clock { display: none; } }

        .tamu-date-badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 5px 12px;
            background: rgba(245,158,11,0.08);
            border: 1px solid rgba(245,158,11,0.2);
            border-radius: 999px;
            font-size: 0.75rem; font-weight: 600;
            color: var(--accent);
            white-space: nowrap;
        }
        .tamu-date-badge svg { color: var(--accent); flex-shrink: 0; }

        .main-wrapper {
            max-width: 1300px;
            margin: 0 auto;
            padding: 28px 24px 48px;
        }

        .page-header {
            margin-bottom: 28px;
        }
        .page-header-inner {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .page-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }
        .page-subtitle {
            margin-top: 4px;
            font-size: 0.82rem;
            color: var(--text-muted);
        }

        .readonly-notice {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: var(--accent-dim);
            border: 1px solid rgba(245,158,11,0.25);
            border-radius: var(--radius-sm);
            margin-bottom: 24px;
            font-size: 0.82rem;
            color: #d97706;
        }
        .readonly-notice svg { flex-shrink: 0; color: var(--accent); }

        .tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 24px;
            background: rgba(255,255,255,0.04);
            border-radius: var(--radius-sm);
            padding: 4px;
            width: fit-content;
        }
        .tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 18px;
            border-radius: 6px;
            border: none;
            font-family: inherit;
            font-size: 0.82rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            color: var(--text-muted);
            background: transparent;
        }
        .tab-btn.active {
            background: var(--tab-active-dim);
            color: #818cf8;
            font-weight: 600;
        }
        .tab-btn:not(.active):hover {
            background: rgba(255,255,255,0.06);
            color: var(--text-primary);
        }
        .tab-btn svg { flex-shrink: 0; }

        .stats-row {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 160px;
        }
        .stat-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: var(--tab-active-dim);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #818cf8;
        }
        .stat-label {
            font-size: 0.72rem;
            color: var(--text-muted);
            margin-bottom: 2px;
        }
        .stat-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .search-bar {
            margin-bottom: 16px;
        }
        .search-input-wrap {
            position: relative;
            max-width: 380px;
        }
        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-dim);
            pointer-events: none;
        }
        .search-input {
            width: 100%;
            padding: 9px 14px 9px 38px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 0.84rem;
            color: var(--text-primary);
            outline: none;
            transition: border-color 0.2s;
        }
        .search-input::placeholder { color: var(--text-dim); }
        .search-input:focus { border-color: rgba(99,102,241,0.5); }

        .table-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-card);
        }
        .table-wrapper {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead th {
            background: rgba(99,102,241,0.08);
            border-bottom: 1px solid var(--border);
            padding: 11px 14px;
            text-align: left;
            font-size: 0.7rem;
            font-weight: 600;
            color: #818cf8;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            white-space: nowrap;
        }
        tbody td {
            padding: 11px 14px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            font-size: 0.82rem;
            color: var(--text-primary);
            vertical-align: middle;
        }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr {
            transition: background 0.15s;
        }
        tbody tr:hover { background: var(--row-hover); }
        td.muted { color: var(--text-muted); font-size: 0.78rem; }
        td.no-col {
            font-weight: 600;
            color: #818cf8;
            text-align: center;
            min-width: 44px;
        }

        .badge {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 999px;
            font-size: 0.68rem;
            font-weight: 600;
        }
        .badge-blue   { background: rgba(99,102,241,0.18); color: #a5b4fc; border: 1px solid rgba(99,102,241,0.3); }
        .badge-green  { background: rgba(34,197,94,0.15);  color: #86efac;  border: 1px solid rgba(34,197,94,0.25); }
        .badge-orange { background: rgba(245,158,11,0.15); color: #fcd34d;  border: 1px solid rgba(245,158,11,0.25); }
        .badge-red    { background: rgba(239,68,68,0.15);  color: #fca5a5;  border: 1px solid rgba(239,68,68,0.25); }

        .empty-state {
            text-align: center;
            padding: 60px 24px;
        }
        .empty-state svg { color: var(--text-dim); margin-bottom: 12px; }
        .empty-state h3 { font-size: 1rem; color: var(--text-muted); font-weight: 500; }
        .empty-state p  { font-size: 0.82rem; color: var(--text-dim); margin-top: 4px; }

        .error-box {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            border-radius: var(--radius-sm);
            padding: 16px 20px;
            color: #fca5a5;
            font-size: 0.84rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .guest-footer {
            margin-top: 40px;
            text-align: center;
            font-size: 0.75rem;
            color: var(--text-dim);
        }

        @media (max-width: 640px) {
            .main-wrapper { padding: 16px 12px 40px; }
            .topbar { padding: 0 14px; }
            .topbar-title { display: none; }
            .page-title { font-size: 1.1rem; }
        }

        body.page-leaving {
            opacity: 0;
            transition: opacity 0.15s ease;
            pointer-events: none;
        }

        body {
            animation: pageFadeIn 0.2s ease both;
        }
        @keyframes pageFadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        #nav-loading-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 99998;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 1rem;
            opacity: 0;
            transition: opacity 0.15s ease;
        }
        #nav-loading-overlay.is-visible {
            opacity: 1;
        }
        .nav-loading-spinner {
            width: 44px;
            height: 44px;
            border: 3px solid transparent;
            border-top-color: #38bdf8;
            border-right-color: #38bdf8;
            border-radius: 50%;
            animation: navSpinRotate 0.7s linear infinite;
            position: relative;
        }
        .nav-loading-spinner::after {
            content: '';
            position: absolute;
            inset: 4px;
            border: 2px solid transparent;
            border-bottom-color: #4f8ef7;
            border-left-color: #4f8ef7;
            border-radius: 50%;
            animation: navSpinRotate 0.5s linear infinite reverse;
        }
        @keyframes navSpinRotate {
            to { transform: rotate(360deg); }
        }
        .nav-loading-text {
            font-size: 0.88rem;
            font-weight: 500;
            color: #94a3b8;
            letter-spacing: 0.01em;
            animation: navLoadingPulse 1.4s ease-in-out infinite;
        }
        @keyframes navLoadingPulse {
            0%, 100% { opacity: 0.6; }
            50%       { opacity: 1; }
        }

        #nav-loading-bar {
            display: none;
            position: fixed;
            top: 0; left: 0;
            height: 3px;
            z-index: 99999;
            background: linear-gradient(90deg, #1e3a5f 0%, #38bdf8 50%, #1e3a5f 100%);
            background-size: 200% 100%;
            animation: navBarSlide 1s ease-in-out infinite;
            border-radius: 0 2px 2px 0;
            box-shadow: 0 0 10px rgba(56,189,248,0.5);
        }
        @keyframes navBarSlide {
            0%   { width: 0%;   background-position: 0% 0; }
            30%  { width: 55%;  background-position: 50% 0; }
            70%  { width: 80%;  background-position: 100% 0; }
            100% { width: 95%;  background-position: 150% 0; }
        }
        #nav-loading-bar.is-active { display: block; }
    </style>
</head>
<body>

    <div id="nav-loading-overlay" role="status" aria-label="Memuat halaman...">
        <div class="nav-loading-spinner" aria-hidden="true"></div>
        <p class="nav-loading-text" id="nav-loading-msg">Memuat Surat Masuk...</p>
    </div>
    <div id="nav-loading-bar" aria-hidden="true"></div>

    <header class="topbar">
        <a href="{{ route('tamu.index') }}" class="topbar-logo">
            <img src="{{ route('image.view', 'komdigi-logo.png') }}" alt="Logo BPSDMP">
            <span class="topbar-title">Arsip BPSDMP</span>
        </a>
        <div class="topbar-divider"></div>
        <span class="badge-guest">
            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 6v16l7-4 8 4 7-4V2l-7 4-8-4-7 4z"/><path d="M8 2v16"/><path d="M16 6v16"/></svg>
            Mode Baca Saja
        </span>
        <div class="topbar-spacer"></div>

        <div class="topbar-clock" id="topbar-clock-tamu">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span id="tamu-clock">--:--:--</span>
            <span style="opacity:.4;">|</span>
            <span id="tamu-date"></span>
        </div>
    </header>

    <main class="main-wrapper">

        <div class="page-header">
            <div class="page-header-inner">
                <div>
                    <h1 class="page-title">Arsip Surat Masuk</h1>
                    <p class="page-subtitle">Data arsip surat masuk BPSDMP Kominfo — hanya tampilan, tanpa akses edit</p>
                </div>

                <div class="tamu-date-badge">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <span id="tamu-date-badge-text"></span>
                </div>
            </div>
        </div>

        <div class="readonly-notice">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            Anda mengakses dalam <strong>&nbsp;Mode Tamu (WiFi Tamu)</strong>. Data bersifat read-only. Untuk menginput atau mengelola arsip, gunakan WiFi Internal.
        </div>

        @if($fetchError)
        <div class="error-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
            {{ $fetchError }}
        </div>
        @endif

        <div class="tabs">
            <a href="{{ route('tamu.index') }}" class="tab-btn active">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                Surat Masuk
            </a>
            <a href="{{ route('tamu.surat-keluar') }}" class="tab-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><line x1="10" x2="8" y1="9" y2="9"/></svg>
                Surat Keluar
            </a>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/></svg>
                </div>
                <div>
                    <div class="stat-label">Total Surat Masuk</div>
                    <div class="stat-value">{{ count($rows) }}</div>
                </div>
            </div>
        </div>

        <div class="search-bar">
            <div class="search-input-wrap">
                <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input id="searchInput" type="text" class="search-input" placeholder="Cari arsip surat masuk...">
            </div>
        </div>

        <div class="table-card">
            @if(count($rows) === 0)
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <h3>{{ $fetchError ? 'Gagal memuat data' : 'Belum ada data arsip' }}</h3>
                    <p>{{ $fetchError ? 'Periksa koneksi server Google Sheets.' : 'Data surat masuk akan muncul di sini setelah diinput oleh staff.' }}</p>
                </div>
            @else
                <div class="table-wrapper">
                    <table id="arsipTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode Klas</th>
                                <th>Uraian Berkas</th>
                                <th>Kurun Waktu</th>
                                <th>No Item</th>
                                <th>Uraian Arsip</th>
                                <th>Tanggal Surat</th>
                                <th>Jumlah Lembar</th>
                                <th>Tingkat</th>
                                <th>Lokasi Simpan</th>
                                <th>Klas Keamanan</th>
                                <th>Akses</th>
                                <th>Retensi Aktif</th>
                                <th>Retensi Inaktif</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $item)
                            @php
$d = $item['data'];
@endphp
                            <tr>
                                <td class="no-col">{{ $loop->iteration }}</td>
                                <td class="muted">{{ $d[1] ?? '-' }}</td>
                                <td>{{ $d[2] ?? '-' }}</td>
                                <td class="muted">{{ $d[3] ?? '-' }}</td>
                                <td class="muted">{{ $d[5] ?? '-' }}</td>
                                <td>{{ $d[6] ?? '-' }}</td>
                                <td class="muted">{{ $d[7] ?? '-' }}</td>
                                <td class="muted" style="text-align:center">{{ $d[8] ?? '-' }}</td>
                                <td>
                                    @php
$tingkat = $d[9] ?? '-';
@endphp
                                    @if(strtolower($tingkat) === 'asli')
                                        <span class="badge badge-green">{{ $tingkat }}</span>
                                    @elseif(strtolower($tingkat) === 'foto kopi' || strtolower($tingkat) === 'fotokopi')
                                        <span class="badge badge-blue">{{ $tingkat }}</span>
                                    @else
                                        <span class="badge badge-orange">{{ $tingkat }}</span>
                                    @endif
                                </td>
                                <td class="muted">{{ $d[10] ?? '-' }}</td>
                                <td>
                                    @php
$klas = $d[11] ?? '-';
@endphp
                                    @if(str_contains(strtolower($klas), 'rahasia'))
                                        <span class="badge badge-red">{{ $klas }}</span>
                                    @elseif(str_contains(strtolower($klas), 'biasa'))
                                        <span class="badge badge-blue">{{ $klas }}</span>
                                    @else
                                        <span class="badge badge-orange">{{ $klas }}</span>
                                    @endif
                                </td>
                                <td class="muted">{{ $d[12] ?? '-' }}</td>
                                <td class="muted">{{ $d[13] ?? '-' }}</td>
                                <td class="muted">{{ $d[14] ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="table-collapse-bar" id="collapse-bar-tamu" style="display:none;padding:.85rem 1rem;border-top:1px solid var(--border);display:flex;justify-content:center;">
                    <button type="button" class="btn-toggle-collapse" id="btn-toggle-tamu" style="display:inline-flex;align-items:center;gap:.5rem;padding:.5rem 1.25rem;border-radius:999px;font-size:.82rem;font-weight:600;font-family:inherit;cursor:pointer;background:rgba(99,102,241,0.15);color:#818cf8;border:1px solid rgba(99,102,241,0.3);transition:all .2s;">
                        <span class="btn-toggle-text">Lihat Selengkapnya</span>
                        <svg class="btn-toggle-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="transition:transform .25s;">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>
                </div>
            @endif
        </div>

        <div class="guest-footer">
            <p>Sistem Arsip Digital BPSDMP Kominfo &nbsp;·&nbsp; Mode Tamu (Read-only) &nbsp;·&nbsp; {{ now()->format('d M Y') }}</p>
        </div>

    </main>

    <script>

        const searchInput    = document.getElementById('searchInput');
        const table          = document.getElementById('arsipTable');
        const collapseBar    = document.getElementById('collapse-bar-tamu');
        const toggleBtn      = document.getElementById('btn-toggle-tamu');
        let isExpandedTamu   = false;

        function updateTamuCollapse() {
            if (!table) return;
            const tbody = table.querySelector('tbody');
            if (!tbody) return;
            const allRows = Array.from(tbody.querySelectorAll('tr'));
            const matchingRows = allRows.filter(tr => tr.dataset.matchesSearch !== 'false');
            const limit = 5;

            if (matchingRows.length > limit) {
                if (collapseBar) collapseBar.style.display = 'flex';
                if (!isExpandedTamu) {
                    matchingRows.forEach((tr, idx) => {
                        tr.style.display = idx < limit ? '' : 'none';
                    });
                    const remaining = matchingRows.length - limit;
                    if (toggleBtn) {
                        toggleBtn.querySelector('.btn-toggle-text').textContent = 'Lihat Selengkapnya (' + remaining + ' data lagi)';
                        toggleBtn.querySelector('.btn-toggle-icon').style.transform = 'rotate(0deg)';
                    }
                } else {
                    matchingRows.forEach(tr => tr.style.display = '');
                    if (toggleBtn) {
                        toggleBtn.querySelector('.btn-toggle-text').textContent = 'Sembunyikan Sebagian';
                        toggleBtn.querySelector('.btn-toggle-icon').style.transform = 'rotate(180deg)';
                    }
                }
            } else {
                if (collapseBar) collapseBar.style.display = 'none';
                matchingRows.forEach(tr => tr.style.display = '');
            }

            let no = 1;
            allRows.forEach(tr => {
                if (tr.style.display !== 'none') {
                    const noCol = tr.querySelector('.no-col');
                    if (noCol) noCol.textContent = no++;
                }
            });
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                isExpandedTamu = !isExpandedTamu;
                updateTamuCollapse();
            });
        }

        if (searchInput && table) {
            var searchTimer;
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimer);
                var self = this;
                searchTimer = setTimeout(function () {
                    const keyword = self.value.toLowerCase().trim();
                    const tbody   = table.querySelector('tbody');
                    if (!tbody) return;
                    const rows    = tbody.querySelectorAll('tr');
                    rows.forEach(function (row) {
                        const text = row.textContent.toLowerCase();
                        const show = text.includes(keyword);
                        row.dataset.matchesSearch = show ? 'true' : 'false';
                        if (!show) row.style.display = 'none';
                    });
                    updateTamuCollapse();
                }, 120);
            });
        }

        updateTamuCollapse();

        (function() {
            var BIDS = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            var HIDS = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
            function pad(n) { return String(n).padStart(2,'0'); }

            var clockEl  = document.getElementById('tamu-clock');
            var dateEl   = document.getElementById('tamu-date');
            var badgeEl  = document.getElementById('tamu-date-badge-text');

            function tick() {
                var d = new Date();
                var timeStr = pad(d.getHours())+':'+pad(d.getMinutes())+':'+pad(d.getSeconds());
                var dateStr = HIDS[d.getDay()]+', '+d.getDate()+' '+BIDS[d.getMonth()]+' '+d.getFullYear();
                if (clockEl) clockEl.textContent = timeStr;
                if (dateEl)  dateEl.textContent  = dateStr;
                if (badgeEl) badgeEl.textContent  = dateStr;
            }
            tick();
            setInterval(tick, 1000);
        })();

        (function () {
            'use strict';
            var overlay = document.getElementById('nav-loading-overlay');
            var bar     = document.getElementById('nav-loading-bar');
            var msg     = document.getElementById('nav-loading-msg');

            function getLoadingMsg(href) {
                try {
                    var path = new URL(href, location.origin).pathname;
                    if (path === '/' || path.indexOf('arsip') !== -1) return 'Memuat Surat Masuk...';
                    if (path.indexOf('surat-keluar') !== -1) return 'Memuat Surat Keluar...';
                    if (path.indexOf('dashboard/report') !== -1) return 'Memuat Rekap Laporan...';
                    if (path.indexOf('dashboard') !== -1) return 'Memuat Dashboard...';
                    if (path.indexOf('users') !== -1) return 'Memuat Data Pengguna...';
                    if (path.indexOf('tamu/surat-keluar') !== -1) return 'Memuat Surat Keluar...';
                    if (path.indexOf('tamu') !== -1) return 'Memuat Surat Masuk...';
                    if (path.indexOf('login') !== -1) return 'Memuat Login...';
                } catch (e) {}
                return 'Memuat halaman...';
            }

            function showLoading(hrefOrMsg) {
                if (!overlay || !bar) return;
                if (msg) {
                    if (hrefOrMsg && (hrefOrMsg.indexOf('/') !== -1 || hrefOrMsg.indexOf('.') !== -1)) {
                        msg.textContent = getLoadingMsg(hrefOrMsg);
                    } else {
                        msg.textContent = hrefOrMsg || 'Memproses...';
                    }
                }
                overlay.style.display = 'flex';
                bar.classList.add('is-active');
                requestAnimationFrame(function () {
                    overlay.classList.add('is-visible');
                });
            }

            document.addEventListener('click', function (e) {
                var link = e.target.closest('a[href]');
                if (!link) return;
                var href = link.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('javascript:') ||
                    href.startsWith('mailto:') || href.startsWith('tel:') ||
                    link.target === '_blank' || link.hasAttribute('download') ||
                    link.origin !== location.origin) return;
                if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
                if (link.classList.contains('active')) return;
                if (/\/(pdf|image|view|download)\//i.test(link.pathname)) return;

                e.preventDefault();
                showLoading(href);
                document.body.classList.add('page-leaving');
                setTimeout(function () {
                    location.href = href;
                }, 120);
            });

            window.addEventListener('pageshow', function () {
                if (overlay) {
                    overlay.classList.remove('is-visible');
                    overlay.style.display = 'none';
                }
                if (bar) bar.classList.remove('is-active');
                document.body.classList.remove('page-leaving');
            });
        })();
    </script>
</body>
</html>
