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
</head>
<body>
    <!-- Header -->
    <header class="header" id="main-header">
        <div class="header-inner">
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
            </nav>
        </div>
    </header>

    <!-- Body: Sidebar + Content -->
    <div class="layout-body">

        <!-- Sidebar -->
        <aside class="sidebar">
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
