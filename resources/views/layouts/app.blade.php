<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="Sistem Arsip Surat Digital BPSDMP - Input dan kelola arsip surat secara efisien">
    <title>@yield('title', 'Arsip Surat') — BPSDMP</title>
    <link rel="icon" type="image/png" href="{{ route('image.view', 'komdigi-logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ route('image.view', 'komdigi-logo.png') }}">
    {{-- Preconnect untuk font Google (non-blocking) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {{-- Google Fonts: non-blocking via media trick (mencegah render-blocking / FOUT) --}}
    <link rel="preload" as="style"
          href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
          onload="this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    </noscript>
    {{-- CSS Utama (render-blocking secara sengaja agar tidak ada FOUC dari design system) --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    {{-- CSS dari child views (tabel, modal, flatpickr, dll.) — diinjek di <head> agar tidak FOUC --}}
    @stack('styles')
    <style>
        .header-user {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
        }
        .header-user-icon {
            display: flex;
            align-items: center;
            color: var(--text-muted, #94a3b8);
        }
        .header-user-name {
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--text-muted, #94a3b8);
            white-space: nowrap;
        }
        .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 11px;
            background: rgba(239, 68, 68, 0.13);
            border: 1px solid rgba(239, 68, 68, 0.38);
            border-radius: 7px;
            font-size: 0.78rem;
            font-weight: 600;
            font-family: inherit;
            color: #dc2626;
            cursor: pointer;
            transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease;
            white-space: nowrap;
        }
        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.22);
            border-color: rgba(239, 68, 68, 0.55);
            color: #b91c1c;
        }

        /* ══════════════════════════════════════════════════════════
           BURGER BUTTON — tampil di semua ukuran layar, semua menu
           ══════════════════════════════════════════════════════════ */
        .burger-btn {
            display: flex; /* SELALU tampil — global di semua halaman */
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            flex-shrink: 0;
            background: transparent;
            border: 1px solid var(--color-border, #e2e5ea);
            border-radius: 8px;
            cursor: pointer;
            color: var(--color-text, #1e293b);
            margin-right: 8px;
            transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }
        .burger-btn:hover {
            background: var(--color-primary-light, #e8edf4);
            border-color: var(--color-primary, #1e3a5f);
            color: var(--color-primary, #1e3a5f);
        }
        .burger-icon { display: flex; flex-direction: column; gap: 4px; }
        .burger-bar {
            display: block; width: 18px; height: 2px;
            background: currentColor; border-radius: 2px;
            transition: transform 0.3s ease, opacity 0.3s ease, width 0.3s ease;
            transform-origin: center;
        }
        /* Animasi X saat drawer mobile terbuka */
        .burger-btn.is-open .burger-bar:nth-child(1) { transform: translateY(6px) rotate(45deg); }
        .burger-btn.is-open .burger-bar:nth-child(2) { opacity: 0; transform: scaleX(0); }
        .burger-btn.is-open .burger-bar:nth-child(3) { transform: translateY(-6px) rotate(-45deg); }
        /* is-collapsed: tidak ada animasi — burger selalu tampil sebagai ☰ */

        /* ══════════════════════════════════════════════════════════
           DESKTOP (>768px): Sidebar collapse via burger
           ══════════════════════════════════════════════════════════ */
        .sidebar {
            transition: transform 0.32s cubic-bezier(0.4,0,0.2,1);
            will-change: transform;
        }
        .main-content {
            transition: margin-left 0.32s cubic-bezier(0.4,0,0.2,1);
            will-change: margin-left;
        }
        .footer {
            transition: margin-left 0.32s cubic-bezier(0.4,0,0.2,1);
            will-change: margin-left;
        }

        /* Kompensasi saat header tersembunyi (scroll ke bawah) */
        body.header-is-hidden .layout-body {
            padding-top: 0;
        }

        /* State: sidebar tersembunyi (desktop) */
        body.sidebar-collapsed .sidebar   { transform: translateX(calc(-1 * var(--sidebar-width, 240px))); }
        body.sidebar-collapsed .main-content { margin-left: 0 !important; }
        body.sidebar-collapsed .footer       { margin-left: 0 !important; }

        /* ══════════════════════════════════════════════════════════
           CENTERING — content di split-screen desktop
           ══════════════════════════════════════════════════════════ */
        .main-content-wrap {
            max-width: 980px;
            margin-left: auto;
            margin-right: auto;
            width: 100%;
        }
        body.sidebar-collapsed .main-content-wrap { max-width: 1080px; }

        /* ══════════════════════════════════════════════════════════
           SPLIT-SCREEN DESKTOP (769px - 1100px)
           Auto-collapse sidebar agar konten punya ruang cukup
           ══════════════════════════════════════════════════════════ */
        @media (min-width: 769px) and (max-width: 1100px) {
            /* Auto collapse sidebar di split-screen */
            .sidebar { transform: translateX(calc(-1 * var(--sidebar-width, 240px))); }
            .main-content { margin-left: 0 !important; }
            .footer       { margin-left: 0 !important; }
            /* Burger tampil dalam mode ‘collapsed’ secara default */
            .burger-btn { border-color: var(--color-primary, #1e3a5f); }
            /* Padding lebih kecil — extend konten ke kiri */
            .main-content { padding-left: 1rem !important; padding-right: 1rem !important; }
            .main-content-wrap { max-width: 100%; }
        }
        /* Override: jika user membuka sidebar di split-screen */
        @media (min-width: 769px) and (max-width: 1100px) {
            body.sidebar-open .sidebar { transform: translateX(0); }
            body.sidebar-open .main-content { margin-left: var(--sidebar-width, 240px) !important; }
            body.sidebar-open .footer       { margin-left: var(--sidebar-width, 240px) !important; }
        }

        /* ══════════════════════════════════════════════════════════
           OVERLAY — untuk drawer mobile
           ══════════════════════════════════════════════════════════ */
        .sidebar-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(15,23,42,0.5);
            z-index: 88;
            opacity: 0; pointer-events: none;
            transition: opacity 0.3s ease;
            backdrop-filter: blur(2px);
        }
        .sidebar-overlay.is-visible { opacity: 1; pointer-events: auto; }

        /* ══════════════════════════════════════════════════════════
           MOBILE (≤768px): Sidebar jadi DRAWER dari kiri
           ══════════════════════════════════════════════════════════ */
        @media (max-width: 768px) {
            /* Aktifkan overlay */
            .sidebar-overlay { display: block; pointer-events: none; }
            /* drawer-footer-fixed: visible di mobile, dikontrol JS (display:none/block) */
            .drawer-footer-fixed { display: none; } /* JS akan set display:block saat openDrawer */

            /* ── Sidebar = drawer fixed dari kiri ── */
            .sidebar {
                position: fixed !important;
                top: var(--header-height, 64px) !important; left: 0 !important;
                width: 260px !important;
                height: calc(100dvh - var(--header-height, 64px)) !important;
                height: calc(100vh - var(--header-height, 64px)) !important;
                padding: 1.25rem 1.25rem 0 !important;
                border-right: 1px solid var(--color-border, #e2e5ea) !important;
                box-shadow: 4px 0 24px rgba(0,0,0,0.14) !important;
                z-index: 90 !important;
                transform: translateX(-100%) !important;
                transition: transform 0.3s cubic-bezier(0.4,0,0.2,1) !important;
                overflow: hidden !important;
                /* overflow:hidden diperlukan agar tidak ada scroll horizontal,
                   tapi footer absolute bisa tampil karena sidebar = fixed container */ 
                display: flex !important;
                flex-direction: column !important;
            }
            /* Drawer terbuka */
            .sidebar.is-open { transform: translateX(0) !important; }

            /* ── Label "Menu": selalu di atas, tidak bisa dikecilkan ── */
            .sidebar-label {
                display: block !important;
                flex-shrink: 0 !important;
                margin-bottom: 0.5rem !important;
            }

            /* ── Nav: mengisi sisa ruang, scroll internal jika item terlalu banyak ── */
            .sidebar-nav {
                flex: 1 !important;
                min-height: 0 !important;
                overflow-y: auto !important;
                flex-direction: column !important;
                gap: 2px !important;
                padding: 0 !important;
                overflow-x: hidden !important;
            }
            .sidebar-link {
                padding: 0.75rem !important;
                border-radius: 6px !important; border-bottom: none !important;
                white-space: normal !important; font-size: 0.9375rem !important;
            }
            .sidebar-link-icon { display: flex !important; }

            /* ── Drawer footer mobile: FIXED terpisah dari sidebar ── */
            /* Ini satu-satunya cara andal agar tidak terpotong overflow:hidden sidebar */
            .drawer-footer-fixed {
                display: none; /* disembunyikan secara default */
                position: fixed !important;
                bottom: 0 !important;
                left: 0 !important;
                width: 260px !important;
                background: var(--color-surface, #ffffff) !important;
                padding: 12px 1.25rem !important;
                padding-bottom: max(1rem, env(safe-area-inset-bottom, 1rem)) !important;
                border-top: 1px solid var(--color-border, #e2e5ea) !important;
                border-right: 1px solid var(--color-border, #e2e5ea) !important;
                z-index: 91 !important; /* di atas sidebar (z-index:90) */
                pointer-events: auto !important;
                transform: translateX(-100%) !important;
                transition: transform 0.3s cubic-bezier(0.4,0,0.2,1) !important;
            }
            /* Tampil & slide masuk bersamaan dengan drawer */
            .drawer-footer-fixed.is-open {
                transform: translateX(0) !important;
            }
            /* ── Drawer footer desktop: sembunyikan di mobile (gunakan drawer-footer-fixed) ── */
            #drawer-footer-desktop {
                display: none !important;
            }
            .drawer-user-info { display: flex !important; }
            .btn-logout-drawer {
                display: flex !important;
                pointer-events: auto !important;
            }

            /* ── Main content: tanpa margin kiri ── */
            .main-content { margin-left: 0 !important; padding-left: 1rem !important; padding-right: 1rem !important; }
            .footer { margin-left: 0 !important; }
            .main-content-wrap { max-width: 100%; }
            /* Header */
            .header-inner { padding: 0 12px !important; gap: 8px; }
            .nav-label { display: none; }
            .brand-subtitle { display: none; }
            .theme-toggle { display: inline-flex !important; flex-shrink: 0; }
        }

        /* Drawer user-info: tampil di sidebar */
        .drawer-user-info {
            display: flex;
            align-items: center; gap: 8px; padding: 8px 4px;
            font-size: 0.85rem; font-weight: 500;
            color: var(--color-text-muted, #94a3b8); margin-bottom: 8px;
        }
        .btn-logout-drawer {
            display: flex; align-items: center; gap: 8px; width: 100%;
            padding: 10px 12px;
            background: rgba(239,68,68,0.12);
            border: 1px solid rgba(239,68,68,0.32);
            border-radius: 8px; font-size: 0.875rem; font-weight: 600;
            font-family: inherit; color: #dc2626; cursor: pointer;
            transition: background 0.2s ease, border-color 0.2s ease;
        }
        .btn-logout-drawer:hover { background: rgba(239,68,68,0.22); border-color: rgba(239,68,68,0.5); color: #b91c1c; }

        /* Sidebar flex-column agar footer ke bawah (desktop) */
        .sidebar { display: flex; flex-direction: column; }

        /* ── Header-left: burger + brand berdampingan ── */
        .header-left {
            display: flex;
            align-items: center;
            gap: 0;
            min-width: 0;
        }

        /* Drawer footer — global: flex-shrink:0 agar selalu tampil */
        .drawer-footer {
            flex-shrink: 0;
            padding-top: 16px;
            border-top: 1px solid var(--color-border, #e2e5ea);
        }

        /* drawer-footer-fixed: hanya tampil di mobile via JS */
        .drawer-footer-fixed { display: none; }

            /* Mode display/kiosk: navbar dan dashboard terkunci tepat satu viewport. */
            body.navbar-only .burger-btn,
            body.navbar-only .sidebar,
            body.navbar-only .sidebar-overlay,
            body.navbar-only .drawer-footer-fixed,
            body.navbar-only .footer { display: none !important; }
            html:has(body.navbar-only),
            body.navbar-only {
                width: 100%;
                height: 100%;
                min-height: 100%;
                overflow: hidden !important;
                overscroll-behavior: none;
            }
            body.navbar-only .header {
                position: fixed;
                inset: 0 0 auto 0;
                width: 100%;
            }
            body.navbar-only .layout-body {
                width: 100vw;
                height: 100dvh;
                min-height: 0;
                padding-top: var(--header-height, 64px);
                overflow: hidden;
            }
            body.navbar-only .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
                width: 100%;
                height: calc(100dvh - var(--header-height, 64px));
                min-height: 0;
                overflow: hidden;
            }
            body.navbar-only .main-content-wrap {
                max-width: none !important;
                width: 100%;
                height: calc(100dvh - var(--header-height, 64px));
                min-height: 0;
                overflow: hidden;
            }

    </style>

    {{-- ── Page Transition (fade antar halaman) ── --}}
    <style>
    /* Fade-out saat navigasi keluar */
    body.page-leaving {
        opacity: 0;
        transition: opacity 0.18s ease;
        pointer-events: none;
    }
    /* Fade-in saat halaman baru tiba */
    body {
        animation: pageFadeIn 0.22s ease both;
    }
    @keyframes pageFadeIn {
        from { opacity: 0; }
        to   { opacity: 1; }
    }
    /* Toast container tidak ikut fade — harus selalu opacity 1 agar toast tampil */
    #toast-container {
        animation: none !important;
        opacity: 1 !important;
    }
    </style>
    <script>
    // Intercept semua klik link internal — tambah class page-leaving sebelum navigasi
    document.addEventListener('click', function (e) {
        var link = e.target.closest('a[href]');
        if (!link) return;
        var href = link.getAttribute('href');
        // Abaikan: anchor (#), external, mailto:, tel:, target=_blank, download
        if (!href || href.startsWith('#') || href.startsWith('mailto:') ||
            href.startsWith('tel:') || link.target === '_blank' ||
            link.hasAttribute('download') || link.origin !== location.origin) return;
        // Abaikan klik dengan modifier key (buka tab baru)
        if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
        e.preventDefault();
        document.body.classList.add('page-leaving');
        setTimeout(function () { location.href = href; }, 180);
    });
    </script>


    <style>
    /* Toast container: pojok kanan bawah */
    #toast-container {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
        pointer-events: none;
    }
    .toast {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        min-width: 300px;
        max-width: 420px;
        padding: 0.875rem 1rem;
        border-radius: 12px;
        border: 1px solid transparent;
        box-shadow: 0 8px 30px rgba(0,0,0,0.18);
        font-size: 0.875rem;
        font-weight: 500;
        line-height: 1.45;
        pointer-events: auto;
        cursor: default;
        /* Slide-in dari kanan */
        transform: translateX(120%);
        opacity: 0;
        transition: transform 0.35s cubic-bezier(0.34,1.56,0.64,1), opacity 0.3s ease;
        backdrop-filter: blur(8px);
    }
    .toast.toast-show {
        transform: translateX(0);
        opacity: 1;
    }
    .toast.toast-hide {
        transform: translateX(120%);
        opacity: 0;
        transition: transform 0.3s ease, opacity 0.3s ease;
    }
    /* Sukses */
    .toast-success {
        background: #059669;
        border-color: #047857;
        color: #ffffff;
    }
    .toast-success .toast-icon { color: #a7f3d0; flex-shrink: 0; margin-top: 1px; }
    /* Error */
    .toast-error {
        background: #dc2626;
        border-color: #b91c1c;
        color: #ffffff;
    }
    .toast-error .toast-icon { color: #fecaca; flex-shrink: 0; margin-top: 1px; }
    /* Body & close */
    .toast-body { flex: 1; }
    .toast-title { font-weight: 700; margin-bottom: 2px; font-size: 0.82rem; letter-spacing: 0.01em; text-transform: uppercase; opacity: 0.75; }
    .toast-msg { font-size: 0.9rem; }
    .toast-close {
        background: none; border: none; cursor: pointer; padding: 0;
        color: inherit; opacity: 0.5; flex-shrink: 0; line-height: 1;
        transition: opacity 0.2s;
    }
    .toast-close:hover { opacity: 1; }
    /* Progress bar */
    .toast-progress {
        position: absolute;
        bottom: 0; left: 0;
        height: 3px;
        border-radius: 0 0 12px 12px;
        width: 100%;
        transform-origin: left;
        animation: toastProgress 5s linear forwards;
    }
    .toast-success .toast-progress { background: #10b981; }
    .toast-error   .toast-progress { background: #ef4444; }
    .toast { position: relative; overflow: hidden; }
    @keyframes toastProgress {
        from { transform: scaleX(1); }
        to   { transform: scaleX(0); }
    }
    @media (max-width: 480px) {
        #toast-container { bottom: 1rem; right: 1rem; left: 1rem; }
        .toast { min-width: unset; max-width: 100%; }
    }
    </style>
    {{-- ── Styles dari child views ── --}}
    @stack('styles')
</head>
<body class="@hasSection('navbarOnly') navbar-only @endif">
    {{-- Inisialisasi tema sebelum render (mencegah flash of wrong theme) --}}
    <script>
        (function(){
            var saved = localStorage.getItem('theme');
            if (saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
    {{-- Page Loading Bar: tampil selama halaman belum fully loaded, hilang di DOMContentLoaded --}}
    <div id="page-loading-bar" role="progressbar" aria-label="Memuat halaman..."></div>
    <!-- Header -->
    <header class="header" id="main-header">
        <div class="header-inner">
            <!-- Kiri: Burger + Brand -->
            <div class="header-left">
                <button type="button" class="burger-btn" id="burger-btn" aria-label="Buka menu navigasi" aria-expanded="false" aria-controls="main-sidebar">
                    <span class="burger-icon" aria-hidden="true">
                        <span class="burger-bar"></span>
                        <span class="burger-bar"></span>
                        <span class="burger-bar"></span>
                    </span>
                </button>
                <div class="brand">
                    <div class="brand-icon" style="background:transparent;border:none;box-shadow:none;padding:0;display:flex;align-items:center;">
                        <img src="{{ route('image.view', 'komdigi-logo.png') }}" alt="Logo Komdigi" style="height:36px;width:auto;object-fit:contain;display:block;">
                    </div>
                    <div>
                        <h1 class="brand-title">Arsip Surat BPSDMP</h1>
                    </div>
                </div>
            </div>

            <!-- Kanan: Status + Theme Toggle -->
            <nav class="header-nav">
                <span class="nav-indicator"></span>
                <span class="nav-label">Sistem Pengarsipan Digital</span>

                {{-- ── Realtime Clock (Global) ── --}}
                <div class="header-clock" id="header-clock" aria-label="Jam dan tanggal saat ini">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span id="header-clock-time">--:--:--</span>
                    <span class="header-clock-sep">|</span>
                    <span id="header-clock-date"></span>
                </div>

                {{-- Dark / Light Toggle --}}
                <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Toggle dark mode" title="Ganti tema">
                    <!-- Moon icon (tampil di light mode) -->
                    <svg class="icon-moon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>
                    <!-- Sun icon (tampil di dark mode) -->
                    <svg class="icon-sun" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="5"/>
                        <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                        <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                    </svg>
                </button>
            </nav>
        </div>
    </header>

    <!-- Overlay (mobile/tablet: klik area luar drawer untuk menutup) -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <!-- Toast Notification Container -->
    {{-- Pre-render toast dari session flash agar tampil tanpa bergantung JS dinamis --}}
    <div id="toast-container" aria-live="polite" aria-atomic="false">
        @if(session('success'))
        <div class="toast toast-success toast-show" role="alert" id="flash-toast-success">
            <span class="toast-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </span>
            <div class="toast-body">
                <div class="toast-title">Berhasil</div>
                <div class="toast-msg">{{ session('success') }}</div>
            </div>
            <button class="toast-close" aria-label="Tutup notifikasi" id="flash-toast-close-ok">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
            <div class="toast-progress"></div>
        </div>
        @endif
        @if(session('error') || $errors->any())
        <div class="toast toast-error toast-show" role="alert" id="flash-toast-error">
            <span class="toast-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
            </span>
            <div class="toast-body">
                <div class="toast-title">Gagal</div>
                <div class="toast-msg">{{ session('error') ?: $errors->first() }}</div>
            </div>
            <button class="toast-close" aria-label="Tutup notifikasi" id="flash-toast-close-err">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
            <div class="toast-progress"></div>
        </div>
        @endif
    </div>

    <!-- Body: Sidebar + Content -->
    <div class="layout-body">

        <!-- Sidebar / Mobile Drawer -->
        <aside class="sidebar" id="main-sidebar" aria-label="Menu navigasi">
            <p class="sidebar-label">Menu</p>
            <nav class="sidebar-nav" id="sidebar-nav">
                {{-- ── Menu Dinamis dari Database (dikelola oleh akun Maintenance) ── --}}
                @forelse($menuItems as $item)
                    @if($item->isVisibleTo(Auth::user()->role))
                        @php
                            $isExternalOrPath = preg_match('/^(https?:\/\/|\/)/i', $item->route_name);
                            $isActive = false;
                            $linkHref = '#';
                            $shouldRender = false;

                            if ($isExternalOrPath) {
                                $linkHref = $item->route_name;
                                $isActive = request()->getRequestUri() === $item->route_name || request()->url() === $item->route_name;
                                $shouldRender = true;
                            } elseif ($item->route_name && \Route::has($item->route_name)) {
                                $linkHref = route($item->route_name);
                                $isActive = request()->routeIs($item->route_name . '*');
                                $shouldRender = true;
                            }
                        @endphp
                        @if($shouldRender)
                        <a href="{{ $linkHref }}"
                           class="sidebar-link {{ $isActive ? 'sidebar-link-active' : '' }}"
                           @if($item->confirm_title)
                               data-confirm-nav="{{ $isExternalOrPath ? $item->route_name : route($item->route_name) }}"
                               data-confirm-title="{{ $item->confirm_title }}"
                               data-confirm-desc="{{ $item->confirm_desc }}"
                               data-confirm-label="{{ $item->label }}"
                           @endif>
                            <span class="sidebar-link-icon">
                                @include('layouts._icon', ['icon' => $item->icon])
                            </span>
                            {{ $item->label }}
                        </a>
                        @endif
                    @endif
                @empty
                    {{-- Fallback jika tabel menu kosong --}}
                    <a href="{{ route('arsip.index') }}" class="sidebar-link {{ request()->routeIs('arsip.*') ? 'sidebar-link-active' : '' }}">
                        <span class="sidebar-link-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                        </span>
                        Surat Masuk
                    </a>
                @endforelse
            </nav>

            <!-- Drawer Footer Desktop: di dalam sidebar (disembunyikan di mobile) -->
            <div class="drawer-footer" id="drawer-footer-desktop">
                <div class="drawer-user-info">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <span style="text-transform:capitalize;letter-spacing:.04em;">{{ Auth::user()->name }}</span>
                </div>
                <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn-logout-drawer">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- ═══════════════════════════════════════════════════════
             Drawer Footer Mobile: FIXED terpisah dari sidebar
             Tidak terpengaruh overflow:hidden sidebar
             ═══════════════════════════════════════════════════════ -->
        <div class="drawer-footer-fixed" id="drawer-footer-fixed">
            <div class="drawer-user-info">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <span style="text-transform:capitalize;letter-spacing:.04em;">{{ Auth::user()->name }}</span>
            </div>
            <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                @csrf
                <button type="submit" class="btn-logout-drawer" id="btn-logout-mobile">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    Logout
                </button>
            </form>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <div class="main-content-wrap">
                @yield('content')
            </div>
        </main>

    </div>

    {{-- ── Modal Konfirmasi Navigasi ke Dashboard / Report ── --}}
    <div id="modal-nav-confirm" class="modal-overlay" style="display:none;">
        <div id="modal-nav-confirm-box" class="modal-box" style="max-width:420px;width:90%;text-align:left;">
            {{-- Icon --}}
            <div style="
                width:52px;height:52px;border-radius:14px;
                background:rgba(59,130,246,0.13);
                display:flex;align-items:center;justify-content:center;
                margin-bottom:1.1rem;
            ">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </div>
            {{-- Title --}}
            <h4 id="modal-nav-confirm-title" style="
                margin:0 0 .45rem;
                font-size:1.05rem; font-weight:700;
                color:var(--color-text,#1e293b);
            ">Buka Dashboard?</h4>
            {{-- Description --}}
            <p id="modal-nav-confirm-desc" style="
                margin:0 0 1.5rem;
                font-size:.875rem; line-height:1.55;
                color:var(--color-text-muted,#64748b);
            ">Anda akan diarahkan ke halaman Dashboard. Pastikan semua perubahan sudah tersimpan sebelum berpindah.</p>
            {{-- Actions --}}
            <div style="display:flex;gap:.65rem;justify-content:flex-end;">
                <button type="button" id="modal-nav-cancel" class="btn btn-secondary">Batal</button>
                <button type="button" id="modal-nav-confirm-btn" class="btn btn-primary" style="background:#3b82f6;border-color:#2563eb;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12l5 5L20 7"/>
                    </svg>
                    Ya, Lanjutkan
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer" id="main-footer">
        <div class="footer-inner">
            <p>&copy; {{ date('Y') }} BPSDMP — Sistem Arsip Surat Digital</p>
        </div>
    </footer>

    <script>
    // ══════════════════════════════════════════════════════════════════
    //  BURGER — Global, semua halaman, semua ukuran layar
    //  Desktop >1100px  : toggle sidebar collapse (margin-left)
    //  Split-screen 769-1100px : toggle sidebar collapse juga
    //  Mobile ≤0768px  : toggle drawer (is-open + overlay)
    // ══════════════════════════════════════════════════════════════════
    (function () {
        var btn         = document.getElementById('burger-btn');
        var sidebar     = document.getElementById('main-sidebar');
        var overlay     = document.getElementById('sidebar-overlay');
        var drawerFooter = document.getElementById('drawer-footer-fixed');
        var body        = document.body;
        var STORAGE     = 'sidebarCollapsed';

        function isMobile() { return window.innerWidth <= 768; }
        function isSplitScreen() { return window.innerWidth > 768 && window.innerWidth <= 1100; }

        // ── Inisialisasi state desktop berdasarkan localStorage ──
        function initDesktopState() {
            if (localStorage.getItem(STORAGE) === 'true') {
                body.classList.add('sidebar-collapsed');
            } else {
                body.classList.remove('sidebar-collapsed');
            }
            // Burger selalu tampil sebagai ☰ di desktop
        }

        // Split-screen: default collapsed, kecuali user pernah buka
        function initSplitScreenState() {
            if (localStorage.getItem('splitSidebarOpen') === 'true') {
                body.classList.add('sidebar-open');
            } else {
                body.classList.remove('sidebar-open');
            }
            // Burger selalu tampil sebagai ☰ di split-screen
        }

        // ── Open / Close drawer (mobile) ──
        function openDrawer() {
            sidebar.classList.add('is-open');
            if (overlay) overlay.classList.add('is-visible');
            if (drawerFooter) {
                drawerFooter.style.display = 'block';
                // Trigger reflow sebelum menambah class agar transisi berjalan
                drawerFooter.getBoundingClientRect();
                drawerFooter.classList.add('is-open');
            }
            btn.classList.add('is-open');
            btn.setAttribute('aria-expanded', 'true');
            body.style.overflow = 'hidden';
        }
        function closeDrawer() {
            sidebar.classList.remove('is-open');
            if (overlay) overlay.classList.remove('is-visible');
            if (drawerFooter) {
                drawerFooter.classList.remove('is-open');
                // Sembunyikan setelah transisi selesai (300ms)
                setTimeout(function () {
                    if (!drawerFooter.classList.contains('is-open')) {
                        drawerFooter.style.display = 'none';
                    }
                }, 320);
            }
            btn.classList.remove('is-open');
            // Mobile: pastikan burger button TIDAK menampilkan panah ← (is-collapsed hanya untuk desktop)
            if (isMobile()) btn.classList.remove('is-collapsed');
            btn.setAttribute('aria-expanded', 'false');
            body.style.overflow = '';
        }

        // ── Inisialisasi saat load ──
        if (!isMobile()) {
            if (isSplitScreen()) {
                initSplitScreenState();
            } else {
                initDesktopState();
            }
        } else {
            // Mobile: pastikan burger button selalu tampil sebagai ☰, bukan ←
            if (btn) btn.classList.remove('is-collapsed');
        }

        // ── Klik burger ──
        if (btn) {
            btn.addEventListener('click', function () {
                if (isMobile()) {
                    // Mobile: toggle drawer
                    sidebar.classList.contains('is-open') ? closeDrawer() : openDrawer();

                } else if (isSplitScreen()) {
                    // Split-screen: toggle sidebar-open (sidebar tersembunyi by default)
                    var isOpen = body.classList.toggle('sidebar-open');
                    btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    localStorage.setItem('splitSidebarOpen', isOpen);

                } else {
                    // Desktop lebar: toggle sidebar-collapsed
                    var isCollapsed = body.classList.toggle('sidebar-collapsed');
                    btn.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
                    localStorage.setItem(STORAGE, isCollapsed);
                }
            });
        }

        // ── Overlay klik = tutup drawer ──
        if (overlay) overlay.addEventListener('click', closeDrawer);

        // ── Tutup drawer saat link diklik (mobile) ──
        if (sidebar) {
            sidebar.querySelectorAll('.sidebar-link').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (isMobile()) closeDrawer();
                });
            });
        }

        // ── Escape = tutup drawer ──
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sidebar && sidebar.classList.contains('is-open')) {
                closeDrawer();
                if (btn) btn.focus();
            }
        });

        // ── Resize: transisi antar mode ──
        var resizeTimer;
        window.addEventListener('resize', function () {
            // Debounce resize agar tidak firing tiap pixel
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                if (isMobile()) {
                    // Pindah ke mobile: bersihkan state desktop, pastikan burger tetap burger
                    body.classList.remove('sidebar-collapsed', 'sidebar-open');
                    if (btn) btn.classList.remove('is-collapsed');
                    // Pastikan overlay tertutup saat pindah ke mobile
                    if (overlay) overlay.classList.remove('is-visible');
                    closeDrawer();
                } else if (isSplitScreen()) {
                    // Pindah ke split-screen: tutup drawer, inisialisasi split-screen
                    closeDrawer();
                    body.classList.remove('sidebar-collapsed');
                    // Pastikan overlay bersih
                    if (overlay) overlay.classList.remove('is-visible');
                    initSplitScreenState();
                } else {
                    // Pindah ke desktop lebar: tutup drawer, inisialisasi desktop
                    closeDrawer();
                    body.classList.remove('sidebar-open');
                    // Pastikan overlay bersih & drawer footer tersembunyi
                    if (overlay) overlay.classList.remove('is-visible');
                    if (drawerFooter) { drawerFooter.classList.remove('is-open'); drawerFooter.style.display = 'none'; }
                    initDesktopState();
                }
            }, 80);
        });
    })();
    </script>

    <script>
        // ── Dark / Light Theme Toggle ─────────────────────────────
        (function () {
            var btn  = document.getElementById('theme-toggle');
            var root = document.documentElement;
            function applyTheme(isDark) {
                if (isDark) {
                    root.setAttribute('data-theme', 'dark');
                    localStorage.setItem('theme', 'dark');
                } else {
                    root.removeAttribute('data-theme');
                    localStorage.setItem('theme', 'light');
                }
            }
            if (btn) {
                btn.addEventListener('click', function () {
                    applyTheme(root.getAttribute('data-theme') !== 'dark');
                });
            }
        })();
    </script>

    <script>
        // ── Global Realtime Clock (Header) ──────────────────────
        (function () {
            var BULAN = ['Januari','Februari','Maret','April','Mei','Juni',
                         'Juli','Agustus','September','Oktober','November','Desember'];
            var HARI  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
            function pad(n) { return String(n).padStart(2, '0'); }
            var elTime = document.getElementById('header-clock-time');
            var elDate = document.getElementById('header-clock-date');
            function tick() {
                var now = new Date();
                if (elTime) elTime.textContent = pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
                if (elDate) elDate.textContent = HARI[now.getDay()] + ', ' + now.getDate() + ' ' + BULAN[now.getMonth()] + ' ' + now.getFullYear();
            }
            tick();
            setInterval(tick, 1000);
        })();
    </script>

    <script>
        // ── Toast Notification API (global) ──────────────────────────
        window.showToast = function (message, type, title) {
            type  = type  || 'success';
            title = title || (type === 'success' ? 'Berhasil' : 'Gagal');

            var icons = {
                success: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
                error:   '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>'
            };

            var toast = document.createElement('div');
            toast.className = 'toast toast-' + type;
            toast.setAttribute('role', 'alert');
            toast.innerHTML =
                '<span class="toast-icon">' + (icons[type] || icons.success) + '</span>' +
                '<div class="toast-body">' +
                    '<div class="toast-title">' + title + '</div>' +
                    '<div class="toast-msg">' + message + '</div>' +
                '</div>' +
                '<button class="toast-close" aria-label="Tutup notifikasi">' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
                '</button>' +
                '<div class="toast-progress"></div>';

            var container = document.getElementById('toast-container');
            container.appendChild(toast);

            // Trigger slide-in
            requestAnimationFrame(function () {
                requestAnimationFrame(function () { toast.classList.add('toast-show'); });
            });

            function dismissToast() {
                toast.classList.add('toast-hide');
                toast.classList.remove('toast-show');
                setTimeout(function () { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 350);
            }

            // Auto-dismiss 5 detik
            var timer = setTimeout(dismissToast, 5000);

            // Tombol close manual
            toast.querySelector('.toast-close').addEventListener('click', function () {
                clearTimeout(timer);
                dismissToast();
            });
        };

        // ── Tampilkan flash session sebagai Toast ─────────────────────
        // Dipanggil SEGERA (tidak menunggu DOMContentLoaded) dengan requestAnimationFrame
        // untuk memastikan toast muncul meski body masih dalam pageFadeIn animation.
        (function () {
            function dismissToast(toast) {
                toast.classList.add('toast-hide');
                toast.classList.remove('toast-show');
                setTimeout(function () { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 350);
            }
            function initPreRenderedToast(toastId, closeId) {
                var toast = document.getElementById(toastId);
                if (!toast) return;
                // Double rAF: tunggu 2 frame render agar CSS transition berjalan
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        toast.classList.add('toast-show');
                    });
                });
                // Auto-dismiss setelah 5 detik
                var timer = setTimeout(function () { dismissToast(toast); }, 5500);
                // Tombol close manual
                var closeBtn = document.getElementById(closeId);
                if (closeBtn) {
                    closeBtn.addEventListener('click', function () {
                        clearTimeout(timer);
                        dismissToast(toast);
                    });
                }
            }
            initPreRenderedToast('flash-toast-success', 'flash-toast-close-ok');
            initPreRenderedToast('flash-toast-error',   'flash-toast-close-err');
        })();

        document.addEventListener('DOMContentLoaded', function () {

            // ── Sembunyikan Page Loading Bar ──────────────────────────────
            (function () {
                var bar = document.getElementById('page-loading-bar');
                if (!bar) return;
                bar.classList.add('is-done');
                // Hapus dari DOM setelah transisi selesai
                setTimeout(function () {
                    if (bar.parentNode) bar.parentNode.removeChild(bar);
                }, 400);
            })();

            // ── Scroll Reveal — Bidirectional (fade in/out saat scroll) ────────
            // PENTING: Elemen menggunakan class .pre-hide yang diset di HTML
            // (opacity:0 dari CSS), sehingga tidak pernah ada kondisi
            // "terlihat dulu, disembunyikan JS, terlihat lagi" (FOUC lama).
            // JS hanya menambah/hapus .is-visible saat elemen masuk/keluar viewport.
            (function () {
                if (!window.IntersectionObserver) {
                    // Fallback: langsung tampilkan semua jika IO tidak tersedia
                    document.querySelectorAll('.pre-hide').forEach(function (el) {
                        el.classList.add('is-visible');
                    });
                    return;
                }

                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            // Elemen masuk viewport → fade IN
                            entry.target.classList.add('is-visible');
                        } else {
                            // Elemen keluar viewport → fade OUT
                            // (animasi bisa re-trigger saat scroll balik)
                            entry.target.classList.remove('is-visible');
                        }
                    });
                }, {
                    threshold: 0.06,
                    rootMargin: '0px 0px -40px 0px'
                });

                document.querySelectorAll('.pre-hide').forEach(function (el) {
                    observer.observe(el);
                });
            })();

            // ── Auto-hide header on scroll ────────────────────────
            var header  = document.getElementById('main-header');
            var lastY   = window.scrollY;
            var OFFSET  = 60;   // px pertama: header tidak disembunyikan
            var DELTA   = 12;   // minimum scroll delta sebelum trigger hide/show
            var hidden  = false;

            function handleScroll() {
                var y     = window.scrollY;
                var delta = y - lastY;

                // Selalu tampilkan saat di atas halaman
                if (y <= OFFSET) {
                    if (hidden) {
                        header.classList.remove('is-hidden');
                        document.body.classList.remove('header-is-hidden');
                        hidden = false;
                    }
                    lastY = y;
                    return;
                }

                // Hanya trigger jika delta cukup besar (menghindari micro-jitter)
                if (Math.abs(delta) < DELTA) return;

                if (delta > 0) {
                    // Scroll ke bawah → sembunyikan
                    if (!hidden) {
                        header.classList.add('is-hidden');
                        document.body.classList.add('header-is-hidden');
                        hidden = true;
                    }
                } else {
                    // Scroll ke atas → tampilkan langsung
                    if (hidden) {
                        header.classList.remove('is-hidden');
                        document.body.classList.remove('header-is-hidden');
                        hidden = false;
                    }
                }

                lastY = y;
            }

            // Langsung di scroll event (tanpa ticking/rAF) agar responsif
            window.addEventListener('scroll', handleScroll, { passive: true });
        });
    </script>
    <script>
    // ── Modal Konfirmasi Navigasi ke Dashboard / Report ──────────────────────
    (function () {
        var modal     = document.getElementById('modal-nav-confirm');
        var modalBox  = document.getElementById('modal-nav-confirm-box');
        var titleEl   = document.getElementById('modal-nav-confirm-title');
        var descEl    = document.getElementById('modal-nav-confirm-desc');
        var cancelBtn = document.getElementById('modal-nav-cancel');
        var confirmBtn = document.getElementById('modal-nav-confirm-btn');
        var pendingHref = null;

        if (!modal) return;

        function openModal(href, title, desc) {
            pendingHref = href;
            if (titleEl) titleEl.textContent = title;
            if (descEl)  descEl.textContent  = desc;

            // Tampilkan overlay dengan smooth open
            modal.style.display = 'flex';
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    modal.classList.add('is-open');
                });
            });
            // Fokus ke tombol batal agar aksesibel
            if (cancelBtn) setTimeout(function () { cancelBtn.focus(); }, 260);
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.classList.remove('is-open');
            pendingHref = null;
            setTimeout(function () {
                if (!modal.classList.contains('is-open')) {
                    modal.style.display = 'none';
                }
                document.body.style.overflow = '';
            }, 240);
        }

        // Intercept klik pada link dengan data-confirm-nav
        document.querySelectorAll('a[data-confirm-nav]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                // Abaikan jika sudah di halaman yg sama (link aktif)
                if (link.classList.contains('sidebar-link-active')) return;

                e.preventDefault();
                e.stopPropagation(); // cegah page-leaving transition global

                var href  = link.getAttribute('href');
                var title = link.getAttribute('data-confirm-title') || 'Pindah halaman?';
                var desc  = link.getAttribute('data-confirm-desc')  || 'Anda akan meninggalkan halaman ini.';
                openModal(href, title, desc);
            });
        });

        // Tombol Batal
        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeModal);
        }

        // Tombol Ya, Lanjutkan — navigasi sebenarnya
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function () {
                if (!pendingHref) return;
                var target = pendingHref;
                pendingHref = null;
                document.body.style.overflow = '';
                // Tutup modal dulu, lalu jalankan page-leaving
                modal.classList.remove('is-open');
                setTimeout(function () {
                    modal.style.display = 'none';
                    document.body.classList.add('page-leaving');
                    setTimeout(function () { location.href = target; }, 180);
                }, 220);
            });
        }

        // Klik overlay = tutup
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });

        // Escape = tutup
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal && modal.style.display === 'flex') {
                closeModal();
            }
        });
    })();
    </script>

    {{-- ── Script dari child views (modal, file upload, flatpickr, dll.) ── --}}
    @stack('scripts')

    {{-- ── Realtime Password Reset Notification via SSE (khusus admin) ── --}}
    {{-- Admin mendapat notifikasi toast realtime saat internship submit permintaan reset password. --}}
    @auth
    @if(Auth::user()->isAdmin())
    <style>
    @keyframes resetToastSlide {
        from { opacity:0; transform:translateX(-50%) translateY(-18px); }
        to   { opacity:1; transform:translateX(-50%) translateY(0); }
    }
    @keyframes badgePulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.5); }
        50%       { box-shadow: 0 0 0 6px rgba(239,68,68,0); }
    }
    #reset-badge {
        display: none;
        position: fixed;
        top: 14px;
        right: 60px;
        z-index: 9999;
        background: #ef4444;
        color: #fff;
        border-radius: 99px;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 3px 8px;
        min-width: 22px;
        text-align: center;
        animation: badgePulse 1.6s ease-in-out infinite;
        cursor: pointer;
        text-decoration: none;
        letter-spacing: .02em;
    }
    #reset-badge.is-visible { display: inline-block; }
    </style>

    {{-- Badge merah di header: klik menuju halaman kelola pengguna --}}
    <a href="{{ route('admin.users.index') }}" id="reset-badge" title="Ada permintaan reset password baru"></a>

    <script>
    (function() {
        'use strict';

        var currentVersion  = null;
        var currentPending  = 0;
        var sseUrl  = '{{ route("sse.reset-requests") }}';
        var pollUrl = '{{ route("api.reset-requests-version") }}';

        // ── Update badge di header ────────────────────────────────────────────
        function updateBadge(count) {
            var badge = document.getElementById('reset-badge');
            if (!badge) return;
            if (count > 0) {
                badge.textContent = count;
                badge.classList.add('is-visible');
            } else {
                badge.classList.remove('is-visible');
            }
        }

        // ── Tampilkan toast notifikasi ────────────────────────────────────────
        function showResetToast(pendingCount) {
            // Hapus toast lama jika ada
            var old = document.getElementById('reset-notify-toast');
            if (old) old.remove();

            var toast = document.createElement('div');
            toast.id = 'reset-notify-toast';
            toast.style.cssText = [
                'position:fixed', 'top:1rem', 'left:50%', 'transform:translateX(-50%)',
                'z-index:99999', 'background:#1e293b', 'border:1px solid #ef4444',
                'border-radius:12px', 'padding:14px 18px', 'box-shadow:0 8px 30px rgba(0,0,0,0.35)',
                'color:#f1f5f9', 'font-size:.875rem', 'font-family:inherit',
                'display:flex', 'align-items:center', 'gap:12px', 'max-width:400px',
                'animation:resetToastSlide .4s cubic-bezier(.34,1.56,.64,1) both',
                'cursor:pointer'
            ].join(';');

            toast.innerHTML = [
                '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0">',
                '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
                '<div style="flex:1">',
                '<div style="font-weight:700;color:#fca5a5;margin-bottom:2px">Permintaan Reset Password</div>',
                '<div style="font-size:.8rem;color:#94a3b8">' + pendingCount + ' permintaan menunggu tindak lanjut</div>',
                '</div>',
                '<button id="reset-toast-close" style="background:none;border:none;color:#64748b;cursor:pointer;padding:4px;font-size:1.1rem;line-height:1;flex-shrink:0">&times;</button>'
            ].join('');

            // Klik toast → buka halaman kelola pengguna
            toast.addEventListener('click', function(e) {
                if (e.target.id !== 'reset-toast-close') {
                    window.location.href = '{{ route("admin.users.index") }}';
                }
            });

            document.body.appendChild(toast);

            // Tombol tutup
            document.getElementById('reset-toast-close').addEventListener('click', function(e) {
                e.stopPropagation();
                toast.remove();
            });

            // Auto-hilang setelah 8 detik
            setTimeout(function() { if (toast.parentNode) toast.remove(); }, 8000);
        }

        // ── Proses event dari SSE / polling ───────────────────────────────────
        function handleUpdate(data) {
            updateBadge(data.pending || 0);
            // Tampilkan toast hanya jika ada perubahan versi (bukan inisialisasi)
            if (currentVersion !== null && data.version !== currentVersion && data.pending > 0) {
                showResetToast(data.pending);
            }
            currentVersion = data.version;
            currentPending = data.pending || 0;
        }

        // ── Koneksi SSE ───────────────────────────────────────────────────────
        function connectSSE() {
            if (!window.EventSource) { startPolling(); return; }

            try {
                var source = new EventSource(sseUrl);

                source.addEventListener('connected', function(e) {
                    handleUpdate(JSON.parse(e.data));
                });

                source.addEventListener('reset_updated', function(e) {
                    handleUpdate(JSON.parse(e.data));
                });

                source.addEventListener('reconnect', function() {
                    source.close();
                    setTimeout(connectSSE, 1000);
                });

                source.onerror = function() {
                    source.close();
                    setTimeout(startPolling, 5000);
                };
            } catch(e) {
                startPolling();
            }
        }

        // ── Fallback: Polling setiap 15 detik ────────────────────────────────
        function startPolling() {
            setInterval(function() {
                fetch(pollUrl, { credentials: 'same-origin' })
                    .then(function(r) { return r.json(); })
                    .then(handleUpdate)
                    .catch(function() {});
            }, 15000);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', connectSSE);
        } else {
            connectSSE();
        }
    })();
    </script>
    @endif
    @endauth
</body>
</html>
