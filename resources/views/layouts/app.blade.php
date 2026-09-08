<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="Sistem Arsip Surat Digital BPSDMP - Input dan kelola arsip surat secara efisien">
    <title>@yield('title', 'Arsip Surat') — BPSDMP</title>
    <link rel="icon" type="image/png" href="{{ route('image.view', 'komdigi-logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ route('image.view', 'komdigi-logo.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link rel="preload" as="style"
          href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
          onload="this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    </noscript>

    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) ?: '1' }}">

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

        .burger-btn {
            display: flex;
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

        .burger-btn.is-open .burger-bar:nth-child(1) { transform: translateY(6px) rotate(45deg); }
        .burger-btn.is-open .burger-bar:nth-child(2) { opacity: 0; transform: scaleX(0); }
        .burger-btn.is-open .burger-bar:nth-child(3) { transform: translateY(-6px) rotate(-45deg); }

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

        body.header-is-hidden .layout-body {
            padding-top: 0;
        }

        body.sidebar-collapsed .sidebar   { transform: translateX(calc(-1 * var(--sidebar-width, 240px))); }
        body.sidebar-collapsed .main-content { margin-left: 0 !important; }
        body.sidebar-collapsed .footer       { margin-left: 0 !important; }

        .main-content-wrap {
            max-width: 980px;
            margin-left: auto;
            margin-right: auto;
            width: 100%;
        }
        body.sidebar-collapsed .main-content-wrap { max-width: 1080px; }

        @media (min-width: 769px) and (max-width: 1100px) {

            .sidebar { transform: translateX(calc(-1 * var(--sidebar-width, 240px))); }
            .main-content { margin-left: 0 !important; }
            .footer       { margin-left: 0 !important; }

            .burger-btn { border-color: var(--color-primary, #1e3a5f); }

            .main-content { padding-left: 1rem !important; padding-right: 1rem !important; }
            .main-content-wrap { max-width: 100%; }
        }

        @media (min-width: 769px) and (max-width: 1100px) {
            body.sidebar-open .sidebar { transform: translateX(0); }
            body.sidebar-open .main-content { margin-left: var(--sidebar-width, 240px) !important; }
            body.sidebar-open .footer       { margin-left: var(--sidebar-width, 240px) !important; }
        }

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

        @media (max-width: 768px) {

            .sidebar-overlay { display: block; pointer-events: none; }

            .drawer-footer-fixed { display: none; }

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

                display: flex !important;
                flex-direction: column !important;
            }

            .sidebar.is-open { transform: translateX(0) !important; }

            .sidebar-label {
                display: block !important;
                flex-shrink: 0 !important;
                margin-bottom: 0.5rem !important;
            }

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

            .drawer-footer-fixed {
                display: none;
                position: fixed !important;
                bottom: 0 !important;
                left: 0 !important;
                width: 260px !important;
                background: var(--color-surface, #ffffff) !important;
                padding: 12px 1.25rem !important;
                padding-bottom: max(1rem, env(safe-area-inset-bottom, 1rem)) !important;
                border-top: 1px solid var(--color-border, #e2e5ea) !important;
                border-right: 1px solid var(--color-border, #e2e5ea) !important;
                z-index: 91 !important;
                pointer-events: auto !important;
                transform: translateX(-100%) !important;
                transition: transform 0.3s cubic-bezier(0.4,0,0.2,1) !important;
            }

            .drawer-footer-fixed.is-open {
                transform: translateX(0) !important;
            }

            #drawer-footer-desktop {
                display: none !important;
            }
            .drawer-user-info { display: flex !important; }
            .btn-logout-drawer {
                display: flex !important;
                pointer-events: auto !important;
            }

            .main-content { margin-left: 0 !important; padding-left: 1rem !important; padding-right: 1rem !important; }
            .footer { margin-left: 0 !important; }
            .main-content-wrap { max-width: 100%; }

            .header-inner { padding: 0 12px !important; gap: 8px; }
            .nav-label { display: none; }
            .brand-subtitle { display: none; }
            .theme-toggle { display: inline-flex !important; flex-shrink: 0; }
        }

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

        .sidebar { display: flex; flex-direction: column; }

        .header-left {
            display: flex;
            align-items: center;
            gap: 0;
            min-width: 0;
        }

        .drawer-footer {
            flex-shrink: 0;
            padding-top: 16px;
            border-top: 1px solid var(--color-border, #e2e5ea);
        }

        .drawer-footer-fixed { display: none; }

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

    <style>

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

    #toast-container {
        animation: none !important;
        opacity: 1 !important;
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

    <style>

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

    .toast-success {
        background: #059669;
        border-color: #047857;
        color: #ffffff;
    }
    .toast-success .toast-icon { color: #a7f3d0; flex-shrink: 0; margin-top: 1px; }

    .toast-error {
        background: #dc2626;
        border-color: #b91c1c;
        color: #ffffff;
    }
    .toast-error .toast-icon { color: #fecaca; flex-shrink: 0; margin-top: 1px; }

    .toast-body { flex: 1; }
    .toast-title { font-weight: 700; margin-bottom: 2px; font-size: 0.82rem; letter-spacing: 0.01em; text-transform: uppercase; opacity: 0.75; }
    .toast-msg { font-size: 0.9rem; }
    .toast-close {
        background: none; border: none; cursor: pointer; padding: 0;
        color: inherit; opacity: 0.5; flex-shrink: 0; line-height: 1;
        transition: opacity 0.2s;
    }
    .toast-close:hover { opacity: 1; }

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
</head>
<body class="@hasSection('navbarOnly') navbar-only @endif @if(request()->routeIs('dashboard.index')) sidebar-collapsed @endif">

    <script>
        (function(){
            var saved = localStorage.getItem('theme');
            if (saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>

    <div id="page-loading-bar" role="progressbar" aria-label="Memuat halaman..."></div>

    <div id="nav-loading-overlay" role="status" aria-label="Memuat halaman...">
        <div class="nav-loading-spinner" aria-hidden="true"></div>
        <p class="nav-loading-text" id="nav-loading-msg">Memuat halaman...</p>
    </div>
    <div id="nav-loading-bar" aria-hidden="true"></div>

    <script>
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
            if (link.hasAttribute('data-confirm-nav')) return;
            if (link.classList.contains('sidebar-link-active')) return;
            if (link.classList.contains('btn-pdf')) return;
            if (/\/(pdf|image|view|download)\//i.test(link.pathname)) return;

            e.preventDefault();
            showLoading(href);
            document.body.classList.add('page-leaving');
            setTimeout(function () {
                location.href = href;
            }, 120);
        });

        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form || form.tagName !== 'FORM') return;
            setTimeout(function () {
                if (!e.defaultPrevented) {
                    showLoading('Memproses...');
                }
            }, 0);
        }, true);

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

    <header class="header" id="main-header">
        <div class="header-inner">

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

            <nav class="header-nav">
                @php
$clientIp       = request()->ip();
                    $internalSubnet = config('network.internal_subnet', '192.168.252.0/22');
                    $guestSubnet    = config('network.guest_subnet', '10.0.0.0/24');
                    $isLocalhost    = in_array($clientIp, ['127.0.0.1', '::1', 'localhost']);

                    $inSubnet = function($ip, $cidr) {
                        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return false;
                        [$sub, $pref] = explode('/', $cidr, 2);
                        $mask = (int)$pref === 0 ? 0 : (~0 << (32 - (int)$pref));
                        return (ip2long($ip) & $mask) === (ip2long($sub) & $mask);
                    };

                    if ($isLocalhost) {
                        $netLabel = 'Server / Localhost';
                        $netBadgeClass = 'badge-net-local';
                    } elseif ($inSubnet($clientIp, $internalSubnet)) {
                        $netLabel = 'WiFi Internal';
                        $netBadgeClass = 'badge-net-internal';
                    } elseif ($inSubnet($clientIp, $guestSubnet)) {
                        $netLabel = 'WiFi Tamu (Read-Only)';
                        $netBadgeClass = 'badge-net-guest';
                    } else {
                        $netLabel = 'Sistem Pengarsipan Digital';
                        $netBadgeClass = 'badge-net-local';
                    }
@endphp

                <div class="network-badge {{ $netBadgeClass }}" title="Terkoneksi dari IP: {{ $clientIp }}">
                    <span class="net-dot"></span>
                    <span class="net-text">{{ $netLabel }}</span>
                </div>

                <div class="header-clock" id="header-clock" aria-label="Jam dan tanggal saat ini">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span id="header-clock-time">--:--:--</span>
                    <span class="header-clock-sep">|</span>
                    <span id="header-clock-date"></span>
                </div>

                <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Toggle dark mode" title="Ganti tema">

                    <svg class="icon-moon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>

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

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

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

    <div class="layout-body">

        <aside class="sidebar" id="main-sidebar" aria-label="Menu navigasi">
            <p class="sidebar-label">Menu</p>
            <nav class="sidebar-nav" id="sidebar-nav">
                @if(Auth::user()?->isAdmin())
                    <a href="{{ route('dashboard.index') }}" class="sidebar-link {{ request()->routeIs('dashboard.index') ? 'sidebar-link-active' : '' }}">
                        <span class="sidebar-link-icon">
                            @include('layouts._icon', ['icon' => 'grid'])
                        </span>
                        Dashboard
                    </a>
                    <a href="{{ route('dashboard.report') }}" class="sidebar-link {{ request()->routeIs('dashboard.report') ? 'sidebar-link-active' : '' }}">
                        <span class="sidebar-link-icon">
                            @include('layouts._icon', ['icon' => 'file-text'])
                        </span>
                        Rekap Laporan
                    </a>
                @endif

                <a href="{{ route('arsip.index') }}" class="sidebar-link {{ request()->routeIs('arsip.*') ? 'sidebar-link-active' : '' }}">
                    <span class="sidebar-link-icon">
                        @include('layouts._icon', ['icon' => 'inbox'])
                    </span>
                    Surat Masuk
                </a>

                <a href="{{ route('surat-keluar.index') }}" class="sidebar-link {{ request()->routeIs('surat-keluar.*') ? 'sidebar-link-active' : '' }}">
                    <span class="sidebar-link-icon">
                        @include('layouts._icon', ['icon' => 'send'])
                    </span>
                    Surat Keluar
                </a>

                @if(Auth::user()?->isAdmin())
                    <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'sidebar-link-active' : '' }}">
                        <span class="sidebar-link-icon">
                            @include('layouts._icon', ['icon' => 'users'])
                        </span>
                        <span>Kelola Pengguna</span>
                        <span id="reset-badge" title="Ada permintaan reset password baru"></span>
                    </a>
                @endif
            </nav>

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

        <main class="main-content">
            <div class="main-content-wrap">
                @yield('content')
            </div>
        </main>

    </div>

    <div id="modal-nav-confirm" class="modal-overlay" style="display:none;">
        <div id="modal-nav-confirm-box" class="modal-box" style="max-width:420px;width:90%;text-align:left;">

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

            <h4 id="modal-nav-confirm-title" style="
                margin:0 0 .45rem;
                font-size:1.05rem; font-weight:700;
                color:var(--color-text,#1e293b);
            ">Buka Dashboard?</h4>

            <p id="modal-nav-confirm-desc" style="
                margin:0 0 1.5rem;
                font-size:.875rem; line-height:1.55;
                color:var(--color-text-muted,#64748b);
            ">Anda akan diarahkan ke halaman Dashboard. Pastikan semua perubahan sudah tersimpan sebelum berpindah.</p>

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

    <footer class="footer" id="main-footer">
        <div class="footer-inner">
            <p>&copy; {{ date('Y') }} BPSDMP — Sistem Arsip Surat Digital</p>
        </div>
    </footer>

    <script>

    (function () {
        var btn         = document.getElementById('burger-btn');
        var sidebar     = document.getElementById('main-sidebar');
        var overlay     = document.getElementById('sidebar-overlay');
        var drawerFooter = document.getElementById('drawer-footer-fixed');
        var body        = document.body;
        var STORAGE     = 'sidebarCollapsed';

        function isMobile() { return window.innerWidth <= 768; }
        function isSplitScreen() { return window.innerWidth > 768 && window.innerWidth <= 1100; }

        function initDesktopState() {
            var isDashboard = window.location.pathname.indexOf('dashboard') !== -1 && window.location.pathname.indexOf('report') === -1;
            if (isDashboard) {
                body.classList.add('sidebar-collapsed');
                return;
            }
            if (localStorage.getItem(STORAGE) === 'true') {
                body.classList.add('sidebar-collapsed');
            } else {
                body.classList.remove('sidebar-collapsed');
            }
        }

        function initSplitScreenState() {
            var isDashboard = window.location.pathname.indexOf('dashboard') !== -1 && window.location.pathname.indexOf('report') === -1;
            if (isDashboard) {
                body.classList.remove('sidebar-open');
                return;
            }
            if (localStorage.getItem('splitSidebarOpen') === 'true') {
                body.classList.add('sidebar-open');
            } else {
                body.classList.remove('sidebar-open');
            }
        }

        function openDrawer() {
            sidebar.classList.add('is-open');
            if (overlay) overlay.classList.add('is-visible');
            if (drawerFooter) {
                drawerFooter.style.display = 'block';

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

                setTimeout(function () {
                    if (!drawerFooter.classList.contains('is-open')) {
                        drawerFooter.style.display = 'none';
                    }
                }, 320);
            }
            btn.classList.remove('is-open');

            if (isMobile()) btn.classList.remove('is-collapsed');
            btn.setAttribute('aria-expanded', 'false');
            body.style.overflow = '';
        }

        if (!isMobile()) {
            if (isSplitScreen()) {
                initSplitScreenState();
            } else {
                initDesktopState();
            }
        } else {

            if (btn) btn.classList.remove('is-collapsed');
        }

        if (btn) {
            btn.addEventListener('click', function () {
                if (isMobile()) {

                    sidebar.classList.contains('is-open') ? closeDrawer() : openDrawer();

                } else if (isSplitScreen()) {

                    var isOpen = body.classList.toggle('sidebar-open');
                    btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    localStorage.setItem('splitSidebarOpen', isOpen);

                } else {

                    var isCollapsed = body.classList.toggle('sidebar-collapsed');
                    btn.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
                    localStorage.setItem(STORAGE, isCollapsed);
                }
            });
        }

        if (overlay) overlay.addEventListener('click', closeDrawer);

        if (sidebar) {
            sidebar.querySelectorAll('.sidebar-link').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (isMobile()) closeDrawer();
                });
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sidebar && sidebar.classList.contains('is-open')) {
                closeDrawer();
                if (btn) btn.focus();
            }
        });

        var resizeTimer;
        window.addEventListener('resize', function () {

            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                if (isMobile()) {

                    body.classList.remove('sidebar-collapsed', 'sidebar-open');
                    if (btn) btn.classList.remove('is-collapsed');

                    if (overlay) overlay.classList.remove('is-visible');
                    closeDrawer();
                } else if (isSplitScreen()) {

                    closeDrawer();
                    body.classList.remove('sidebar-collapsed');

                    if (overlay) overlay.classList.remove('is-visible');
                    initSplitScreenState();
                } else {

                    closeDrawer();
                    body.classList.remove('sidebar-open');

                    if (overlay) overlay.classList.remove('is-visible');
                    if (drawerFooter) { drawerFooter.classList.remove('is-open'); drawerFooter.style.display = 'none'; }
                    initDesktopState();
                }
            }, 80);
        });
    })();
    </script>

    <script>

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

            requestAnimationFrame(function () {
                requestAnimationFrame(function () { toast.classList.add('toast-show'); });
            });

            function dismissToast() {
                toast.classList.add('toast-hide');
                toast.classList.remove('toast-show');
                setTimeout(function () { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 350);
            }

            var timer = setTimeout(dismissToast, 5000);

            toast.querySelector('.toast-close').addEventListener('click', function () {
                clearTimeout(timer);
                dismissToast();
            });
        };

        (function () {
            function dismissToast(toast) {
                toast.classList.add('toast-hide');
                toast.classList.remove('toast-show');
                setTimeout(function () { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 350);
            }
            function initPreRenderedToast(toastId, closeId) {
                var toast = document.getElementById(toastId);
                if (!toast) return;

                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        toast.classList.add('toast-show');
                    });
                });

                var timer = setTimeout(function () { dismissToast(toast); }, 5500);

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

            (function () {
                var bar = document.getElementById('page-loading-bar');
                if (!bar) return;
                bar.classList.add('is-done');

                setTimeout(function () {
                    if (bar.parentNode) bar.parentNode.removeChild(bar);
                }, 400);
            })();

            (function () {
                if (!window.IntersectionObserver) {

                    document.querySelectorAll('.pre-hide').forEach(function (el) {
                        el.classList.add('is-visible');
                    });
                    return;
                }

                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {

                            entry.target.classList.add('is-visible');
                            observer.unobserve(entry.target);
                        }
                    });
                }, {
                    threshold: 0.01,
                    rootMargin: '50px 0px 0px 0px'
                });

                document.querySelectorAll('.pre-hide').forEach(function (el) {
                    observer.observe(el);
                });
            })();

            var header  = document.getElementById('main-header');
            var lastY   = window.scrollY;
            var OFFSET  = 60;
            var DELTA   = 12;
            var hidden  = false;

            function handleScroll() {
                var y     = window.scrollY;
                var delta = y - lastY;

                if (y <= OFFSET) {
                    if (hidden) {
                        header.classList.remove('is-hidden');
                        document.body.classList.remove('header-is-hidden');
                        hidden = false;
                    }
                    lastY = y;
                    return;
                }

                if (Math.abs(delta) < DELTA) return;

                if (delta > 0) {

                    if (!hidden) {
                        header.classList.add('is-hidden');
                        document.body.classList.add('header-is-hidden');
                        hidden = true;
                    }
                } else {

                    if (hidden) {
                        header.classList.remove('is-hidden');
                        document.body.classList.remove('header-is-hidden');
                        hidden = false;
                    }
                }

                lastY = y;
            }

            window.addEventListener('scroll', handleScroll, { passive: true });
        });
    </script>
    <script>

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

            modal.style.display = 'flex';
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    modal.classList.add('is-open');
                });
            });

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

        document.querySelectorAll('a[data-confirm-nav]').forEach(function (link) {
            link.addEventListener('click', function (e) {

                if (link.classList.contains('sidebar-link-active')) return;

                e.preventDefault();
                e.stopPropagation();

                var href  = link.getAttribute('href');
                var title = link.getAttribute('data-confirm-title') || 'Pindah halaman?';
                var desc  = link.getAttribute('data-confirm-desc')  || 'Anda akan meninggalkan halaman ini.';
                openModal(href, title, desc);
            });
        });

        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeModal);
        }

        if (confirmBtn) {
            confirmBtn.addEventListener('click', function () {
                if (!pendingHref) return;
                var target = pendingHref;
                pendingHref = null;
                document.body.style.overflow = '';

                modal.classList.remove('is-open');
                setTimeout(function () {
                    modal.style.display = 'none';
                    var loadingOverlay = document.getElementById('nav-loading-overlay');
                    var loadingBar = document.getElementById('nav-loading-bar');
                    var loadingMsg = document.getElementById('nav-loading-msg');
                    if (loadingMsg) loadingMsg.textContent = 'Memuat Dashboard...';
                    if (loadingOverlay) {
                        loadingOverlay.style.display = 'flex';
                        if (loadingBar) loadingBar.classList.add('is-active');
                        requestAnimationFrame(function () {
                            loadingOverlay.classList.add('is-visible');
                        });
                    }
                    document.body.classList.add('page-leaving');
                    setTimeout(function () { location.href = target; }, 120);
                }, 200);
            });
        }

        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal && modal.style.display === 'flex') {
                closeModal();
            }
        });
    })();
    </script>

    @stack('scripts')

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
        background: #ef4444;
        color: #fff;
        border-radius: 99px;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 2px 7px;
        min-width: 20px;
        height: 20px;
        line-height: 1;
        align-items: center;
        justify-content: center;
        margin-left: auto;
        animation: badgePulse 1.6s ease-in-out infinite;
        cursor: pointer;
        text-decoration: none;
        letter-spacing: .02em;
    }
    #reset-badge.is-visible { display: inline-flex; }
    </style>

    <script>
    (function() {
        'use strict';

        var currentVersion  = null;
        var currentPending  = 0;
        var sseUrl  = '{{ route("sse.reset-requests") }}';
        var pollUrl = '{{ route("api.reset-requests-version") }}';

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

        function showResetToast(pendingCount) {

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

            toast.addEventListener('click', function(e) {
                if (e.target.id !== 'reset-toast-close') {
                    window.location.href = '{{ route("admin.users.index") }}';
                }
            });

            document.body.appendChild(toast);

            document.getElementById('reset-toast-close').addEventListener('click', function(e) {
                e.stopPropagation();
                toast.remove();
            });

            setTimeout(function() { if (toast.parentNode) toast.remove(); }, 8000);
        }

        function handleUpdate(data) {
            updateBadge(data.pending || 0);

            if (currentVersion !== null && data.version !== currentVersion && data.pending > 0) {
                showResetToast(data.pending);
            }
            currentVersion = data.version;
            currentPending = data.pending || 0;
        }

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
