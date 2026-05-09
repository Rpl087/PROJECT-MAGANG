<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Arsip Surat Digital BPSDMP - Input dan kelola arsip surat secara efisien">
    <title>@yield('title', 'Arsip Surat') — BPSDMP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
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
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.25);
            border-radius: 7px;
            font-size: 0.78rem;
            font-weight: 500;
            font-family: inherit;
            color: #f87171;
            cursor: pointer;
            transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease;
            white-space: nowrap;
        }
        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.45);
            color: #fca5a5;
        }

        /* ── Drawer Footer (logout di sidebar, user-info hanya di mobile) ── */
        .drawer-footer {
            display: block; /* Tampil di desktop dan mobile */
            margin-top: auto;
            padding-top: 16px;
            border-top: 1px solid var(--color-border, #e2e5ea);
        }
        /* Nama user di drawer: tersembunyi di desktop */
        .drawer-user-info {
            display: none !important;
        }
        /* Sidebar desktop: flex-column agar drawer-footer ke bawah */
        .sidebar {
            display: flex;
            flex-direction: column;
        }
        /* Sembunyikan form logout dari header di desktop */
        .header-logout-form {
            display: none;
        }
        .drawer-user-info {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 4px;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-muted, #94a3b8);
            margin-bottom: 8px;
        }
        .btn-logout-drawer {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 10px 12px;
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            font-family: inherit;
            color: #f87171;
            cursor: pointer;
            transition: background 0.2s ease, border-color 0.2s ease;
        }
        .btn-logout-drawer:hover {
            background: rgba(239, 68, 68, 0.16);
            border-color: rgba(239, 68, 68, 0.35);
        }

        /* ── Burger Button (hanya tampil di mobile/tablet) ── */
        .burger-btn {
            display: none; /* Tersembunyi di desktop */
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            background: transparent;
            border: 1px solid var(--color-border, #e2e5ea);
            border-radius: 8px;
            cursor: pointer;
            color: var(--color-text, #1e293b);
            margin-right: 8px;
            flex-shrink: 0;
            transition: background 0.2s ease, border-color 0.2s ease;
        }
        .burger-btn:hover {
            background: var(--color-primary-light, #e8edf4);
            border-color: var(--color-primary, #1e3a5f);
            color: var(--color-primary, #1e3a5f);
        }
        /* Animasi garis burger → X */
        .burger-bar {
            display: block;
            width: 18px;
            height: 2px;
            background: currentColor;
            border-radius: 2px;
            transition: transform 0.3s ease, opacity 0.3s ease;
            transform-origin: center;
        }
        .burger-icon {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        /* State terbuka: garis 1 & 3 membentuk X */
        .burger-btn.is-open .burger-bar:nth-child(1) {
            transform: translateY(6px) rotate(45deg);
        }
        .burger-btn.is-open .burger-bar:nth-child(2) {
            opacity: 0;
            transform: scaleX(0);
        }
        .burger-btn.is-open .burger-bar:nth-child(3) {
            transform: translateY(-6px) rotate(-45deg);
        }

        /* ── Overlay gelap saat drawer terbuka ── */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            z-index: 88;
            opacity: 0;
            transition: opacity 0.3s ease;
            backdrop-filter: blur(2px);
        }
        .sidebar-overlay.is-visible {
            opacity: 1;
        }

        /* ── Responsive: tampilkan burger, ubah sidebar jadi drawer ── */
        @media (max-width: 768px) {
            .burger-btn {
                display: flex;
            }
            .sidebar-overlay {
                display: block;
            }
            /* Override: sidebar jadi drawer dari kiri */
            .sidebar {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 260px !important;
                height: 100vh !important;
                padding: var(--space-6, 1.5rem) var(--space-5, 1.25rem) !important;
                border-right: 1px solid var(--color-border, #e2e5ea) !important;
                border-bottom: none !important;
                box-shadow: 4px 0 24px rgba(0,0,0,0.12) !important;
                z-index: 90 !important;
                transform: translateX(-100%);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                overflow-y: auto !important;
            }
            .sidebar.is-open {
                transform: translateX(0);
            }
            /* Batalkan sticky top 0 saat header hilang — tidak relevan untuk drawer */
            body.header-is-hidden .sidebar {
                top: 0 !important;
                height: 100vh !important;
            }
            /* Kembalikan sidebar-label & nav ke mode vertikal */
            .sidebar-label {
                display: block !important;
            }
            .sidebar-nav {
                flex-direction: column !important;
                gap: 2px !important;
                padding: 0 !important;
                overflow-x: unset !important;
            }
            .sidebar-link {
                padding: var(--space-3, 0.75rem) var(--space-3, 0.75rem) !important;
                border-radius: 6px !important;
                border-bottom: none !important;
                white-space: normal !important;
                font-size: 0.9375rem !important;
            }
            .sidebar-link-active {
                background: var(--color-primary-light, #e8edf4) !important;
                color: var(--color-primary, #1e3a5f) !important;
                border-bottom-color: transparent !important;
            }
            .sidebar-link-icon {
                display: flex !important;
            }
            /* Main content: tidak ada margin kiri */
            .main-content {
                margin-left: 0 !important;
            }
            /* Sembunyikan nav-label dan header-user di header (sudah ada di drawer) */
            .nav-label, .header-user {
                display: none;
            }
            /* Tampilkan nama user di drawer (di desktop tersembunyi) */
            .drawer-user-info {
                display: flex !important;
            }
            /* Sidebar harus flex-column agar footer selalu di bawah */
            .sidebar {
                display: flex !important;
                flex-direction: column !important;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header" id="main-header">
        <div class="header-inner">
            <!-- Burger Button (muncul di ≤768px) — sebelum logo -->
            <button type="button" class="burger-btn" id="burger-btn" aria-label="Buka menu navigasi" aria-expanded="false" aria-controls="main-sidebar">
                <span class="burger-icon" aria-hidden="true">
                    <span class="burger-bar"></span>
                    <span class="burger-bar"></span>
                    <span class="burger-bar"></span>
                </span>
            </button>
            <div class="brand">
                <div class="brand-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                </div>
                <div>
                    <h1 class="brand-title">Arsip Surat</h1>
                    <p class="brand-subtitle">BPSDMP</p>
                </div>
            </div>
            <nav class="header-nav">
                <span class="nav-indicator"></span>
                <span class="nav-label">Sistem Pengarsipan Digital</span>
                <div class="header-user">
                    <span class="header-user-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </span>
                    <span class="header-user-name">{{ Auth::user()->name }}</span>
                    <form action="{{ route('logout') }}" method="POST" style="margin:0;" class="header-logout-form">
                        @csrf
                        <button type="submit" class="btn-logout" title="Keluar dari sistem">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            Keluar
                        </button>
                    </form>
                </div>
            </nav>
        </div>
    </header>

    <!-- Overlay (mobile/tablet: klik area luar drawer untuk menutup) -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <!-- Body: Sidebar + Content -->
    <div class="layout-body">

        <!-- Sidebar / Mobile Drawer -->
        <aside class="sidebar" id="main-sidebar" aria-label="Menu navigasi">
            <p class="sidebar-label">Menu</p>
            <nav class="sidebar-nav">
                <a href="{{ route('arsip.index') }}"
                   class="sidebar-link {{ request()->routeIs('arsip.*') ? 'sidebar-link-active' : '' }}">
                    <span class="sidebar-link-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/>
                            <path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>
                        </svg>
                    </span>
                    Surat Masuk
                </a>
                <a href="{{ route('surat-keluar.index') }}"
                   class="sidebar-link {{ request()->routeIs('surat-keluar.*') ? 'sidebar-link-active' : '' }}">
                    <span class="sidebar-link-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"/>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                        </svg>
                    </span>
                    Surat Keluar
                </a>
            </nav>

            <!-- Drawer Footer: Info user + Tombol Keluar (hanya tampil di mobile drawer) -->
            <div class="drawer-footer">
                <div class="drawer-user-info">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <span>{{ Auth::user()->name }}</span>
                </div>
                <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn-logout-drawer">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        Keluar
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            @yield('content')
        </main>

    </div>

    <!-- Footer -->
    <footer class="footer" id="main-footer">
        <div class="footer-inner">
            <p>&copy; {{ date('Y') }} BPSDMP — Sistem Arsip Surat Digital</p>
        </div>
    </footer>

    @stack('scripts')

    <script>
        (function () {
            var burgerBtn  = document.getElementById('burger-btn');
            var sidebar    = document.getElementById('main-sidebar');
            var overlay    = document.getElementById('sidebar-overlay');
            var isMobile   = function () { return window.innerWidth <= 768; };

            function openDrawer() {
                sidebar.classList.add('is-open');
                overlay.classList.add('is-visible');
                burgerBtn.classList.add('is-open');
                burgerBtn.setAttribute('aria-expanded', 'true');
                document.body.style.overflow = 'hidden'; // Cegah scroll body
            }

            function closeDrawer() {
                sidebar.classList.remove('is-open');
                overlay.classList.remove('is-visible');
                burgerBtn.classList.remove('is-open');
                burgerBtn.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            }

            // Toggle saat burger diklik
            burgerBtn.addEventListener('click', function () {
                if (sidebar.classList.contains('is-open')) {
                    closeDrawer();
                } else {
                    openDrawer();
                }
            });

            // Tutup saat overlay diklik
            overlay.addEventListener('click', closeDrawer);

            // Tutup saat link sidebar diklik (navigasi)
            sidebar.querySelectorAll('.sidebar-link').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (isMobile()) closeDrawer();
                });
            });

            // Tutup saat Escape ditekan
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && sidebar.classList.contains('is-open')) {
                    closeDrawer();
                    burgerBtn.focus();
                }
            });

            // Tutup drawer otomatis saat resize ke desktop
            window.addEventListener('resize', function () {
                if (!isMobile()) {
                    closeDrawer();
                }
            });
        })();
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // ── Fade-in page ──────────────────────────────────────
            document.body.classList.add('loaded');

            // ── Auto-dismiss flash messages (5 detik) ────────────
            document.querySelectorAll('.alert').forEach(function (alert) {
                setTimeout(function () {
                    alert.classList.add('alert-dismiss');
                    setTimeout(function () { alert.remove(); }, 400);
                }, 5000);
            });

            // ── Scroll Reveal (form sections, header, actions) ──
            (function () {
                if (!window.IntersectionObserver) return;

                var targets = document.querySelectorAll(
                    '.form-section, .page-header, .form-actions'
                );

                // Set initial hidden state (dari bawah)
                targets.forEach(function (el) {
                    el.classList.add('reveal', 'reveal-from-bottom');
                });

                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            // Elemen masuk viewport → tampilkan
                            entry.target.classList.add('is-visible');
                            entry.target.classList.remove(
                                'reveal-from-bottom', 'reveal-from-top'
                            );
                        } else {
                            // Elemen keluar viewport → sembunyikan
                            entry.target.classList.remove('is-visible');

                            // Tentukan arah: di atas atau di bawah viewport?
                            if (entry.boundingClientRect.top > 0) {
                                // Elemen ada di bawah viewport (scroll up)
                                entry.target.classList.add('reveal-from-bottom');
                                entry.target.classList.remove('reveal-from-top');
                            } else {
                                // Elemen ada di atas viewport (scroll down)
                                entry.target.classList.add('reveal-from-top');
                                entry.target.classList.remove('reveal-from-bottom');
                            }
                        }
                    });
                }, {
                    threshold: 0.08,
                    rootMargin: '-40px 0px -40px 0px'
                });

                targets.forEach(function (el) { observer.observe(el); });
            })();

            // ── Auto-hide header on scroll ────────────────────────
            var header  = document.getElementById('main-header');
            var lastY   = window.scrollY;
            var ticking = false;
            var OFFSET  = 80; // px sebelum header mulai disembunyikan

            function handleScroll() {
                var y = window.scrollY;

                if (y > lastY && y > OFFSET) {
                    // Scroll ke bawah → sembunyikan header
                    header.classList.add('is-hidden');
                    document.body.classList.add('header-is-hidden');
                } else if (y < lastY) {
                    // Scroll ke atas → tampilkan kembali header
                    header.classList.remove('is-hidden');
                    document.body.classList.remove('header-is-hidden');
                }

                lastY   = y;
                ticking = false;
            }

            window.addEventListener('scroll', function () {
                if (!ticking) {
                    requestAnimationFrame(handleScroll);
                    ticking = true;
                }
            }, { passive: true });
        });
    </script>
</body>
</html>
