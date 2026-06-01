<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="Login – Sistem Arsip Surat Digital BPSDMP">
    <title>Login — Arsip Surat BPSDMP</title>
    <link rel="icon" type="image/png" href="{{ route('image.view', 'komdigi-logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ route('image.view', 'komdigi-logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-deep:      #0a0f1e;
            --bg-mid:       #0f172a;
            --accent:       #3b82f6;
            --accent-glow:  rgba(59, 130, 246, 0.35);
            --accent-hover: #2563eb;
            --glass-bg:     rgba(15, 23, 42, 0.72);
            --glass-border: rgba(59, 130, 246, 0.18);
            --text-primary: #f1f5f9;
            --text-muted:   #94a3b8;
            --input-bg:     rgba(30, 41, 59, 0.80);
            --input-border: rgba(71, 85, 105, 0.60);
            --input-focus:  rgba(59, 130, 246, 0.55);
            --error-bg:     rgba(239, 68, 68, 0.12);
            --error-border: rgba(239, 68, 68, 0.45);
            --error-text:   #fca5a5;
            --radius:       14px;
            --transition:   0.22s ease;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', system-ui, sans-serif;
            background-color: var(--bg-deep);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
        }

        /* ── Animated background ────────────────────────────────── */
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 10%, rgba(59,130,246,0.18) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 80% 85%, rgba(99,102,241,0.14) 0%, transparent 55%),
                radial-gradient(ellipse 50% 40% at 50% 50%, rgba(15,23,42,0.9) 0%, transparent 80%);
            pointer-events: none;
            z-index: 0;
        }

        /* Grid dots */
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                radial-gradient(circle, rgba(59,130,246,0.07) 1px, transparent 1px);
            background-size: 32px 32px;
            pointer-events: none;
            z-index: 0;
        }

        /* ── Floating orbs ──────────────────────────────────────── */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            z-index: 0;
            animation: float-orb 8s ease-in-out infinite alternate;
        }
        .orb-1 {
            width: 420px; height: 420px;
            background: radial-gradient(circle, rgba(59,130,246,0.22), transparent 70%);
            top: -120px; left: -100px;
            animation-duration: 9s;
        }
        .orb-2 {
            width: 360px; height: 360px;
            background: radial-gradient(circle, rgba(99,102,241,0.18), transparent 70%);
            bottom: -100px; right: -80px;
            animation-duration: 11s; animation-delay: -3s;
        }
        .orb-3 {
            width: 200px; height: 200px;
            background: radial-gradient(circle, rgba(14,165,233,0.14), transparent 70%);
            top: 50%; right: 25%;
            animation-duration: 13s; animation-delay: -6s;
        }

        @keyframes float-orb {
            from { transform: translate(0, 0) scale(1); }
            to   { transform: translate(30px, 20px) scale(1.08); }
        }

        /* ── Card wrapper ───────────────────────────────────────── */
        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 440px;
            padding: 24px 16px;
            animation: slide-up 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }

        @keyframes slide-up {
            from { opacity: 0; transform: translateY(32px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ── Glass card ─────────────────────────────────────────── */
        .login-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 22px;
            padding: 44px 40px 40px;
            backdrop-filter: blur(24px) saturate(1.4);
            -webkit-backdrop-filter: blur(24px) saturate(1.4);
            box-shadow:
                0 0 0 1px rgba(59,130,246,0.08),
                0 24px 64px rgba(0,0,0,0.5),
                0 8px 24px rgba(0,0,0,0.3),
                inset 0 1px 0 rgba(255,255,255,0.06);
        }

        /* ── Brand / Logo ───────────────────────────────────────── */
        .brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 32px;
            text-align: center;
        }

        .brand-icon-wrap {
            width: auto;
            height: auto;
            background: transparent;
            border: none;
            border-radius: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
            box-shadow: none;
            gap: 0;
        }

        .brand-title {
            font-size: 1.45rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .brand-subtitle {
            font-size: 0.78rem;
            font-weight: 500;
            color: #60a5fa;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .brand-desc {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-top: 8px;
            font-weight: 400;
        }

        /* ── Divider ────────────────────────────────────────────── */
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(59,130,246,0.2), transparent);
            margin-bottom: 28px;
        }

        /* ── Error Alert ────────────────────────────────────────── */
        .alert-login-error {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 22px;
            animation: shake 0.4s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%       { transform: translateX(-6px); }
            40%       { transform: translateX(6px); }
            60%       { transform: translateX(-4px); }
            80%       { transform: translateX(4px); }
        }

        .alert-login-error svg { color: #f87171; flex-shrink: 0; margin-top: 1px; }
        .alert-login-error span { font-size: 0.83rem; color: var(--error-text); line-height: 1.4; }

        /* ── Form ───────────────────────────────────────────────── */
        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 7px;
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #475569;
            pointer-events: none;
            transition: color var(--transition);
        }

        .form-input {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 10px;
            padding: 12px 14px 12px 40px;
            font-size: 0.93rem;
            font-family: inherit;
            color: var(--text-primary);
            outline: none;
            transition: border-color var(--transition), box-shadow var(--transition), background var(--transition);
            -webkit-appearance: none;
        }

        .form-input::placeholder { color: #475569; }

        .form-input:focus {
            border-color: var(--accent);
            background: rgba(30, 41, 59, 0.95);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        .form-input:focus ~ .input-icon,
        .input-wrap:focus-within .input-icon {
            color: #60a5fa;
        }

        /* Input error state */
        .form-input.input-err {
            border-color: rgba(239,68,68,0.6);
            box-shadow: 0 0 0 3px rgba(239,68,68,0.15);
        }

        .field-error {
            font-size: 0.76rem;
            color: #f87171;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Password toggle */
        .pass-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #475569;
            padding: 2px;
            display: flex;
            align-items: center;
            transition: color var(--transition);
        }
        .pass-toggle:hover { color: #94a3b8; }

        /* ── Remember me ────────────────────────────────────────── */
        .remember-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
        }

        .remember-checkbox {
            width: 16px; height: 16px;
            accent-color: var(--accent);
            cursor: pointer;
            border-radius: 4px;
        }

        .remember-label {
            font-size: 0.83rem;
            color: var(--text-muted);
            cursor: pointer;
            user-select: none;
        }

        /* ── Submit Button ──────────────────────────────────────── */
        .btn-login {
            width: 100%;
            padding: 13px 20px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            font-family: inherit;
            color: #ffffff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            letter-spacing: 0.01em;
            transition: transform var(--transition), box-shadow var(--transition), background var(--transition);
            box-shadow:
                0 4px 15px rgba(59,130,246,0.35),
                0 1px 3px rgba(0,0,0,0.2),
                inset 0 1px 0 rgba(255,255,255,0.12);
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            transform: translateY(-1px);
            box-shadow:
                0 8px 25px rgba(59,130,246,0.45),
                0 2px 6px rgba(0,0,0,0.25),
                inset 0 1px 0 rgba(255,255,255,0.12);
        }

        .btn-login:active {
            transform: translateY(0);
            box-shadow: 0 3px 10px rgba(59,130,246,0.25);
        }

        .btn-login svg { flex-shrink: 0; }

        /* ── Footer note ────────────────────────────────────────── */
        .login-footer-note {
            margin-top: 24px;
            text-align: center;
            font-size: 0.76rem;
            color: #334155;
        }

        .login-footer-note span { color: #475569; }

        /* ── Responsive ─────────────────────────────────────────── */
        @media (max-width: 480px) {
            .login-card { padding: 32px 24px 28px; }
            .brand-title { font-size: 1.25rem; }
        }
    </style>
</head>
<body>
    <!-- Floating orbs -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <div class="login-wrapper">
        <div class="login-card">

            <!-- Brand -->
            <div class="brand">
                <div class="brand-icon-wrap">
                    <img src="{{ route('image.view', 'komdigi-logo.png') }}"  alt="Logo Komdigi"
                         style="height:64px;width:auto;object-fit:contain;display:block;flex-shrink:0;">
                    <div style="width:1px;height:52px;background:rgba(255,255,255,0.15);border-radius:1px;margin:0 16px;flex-shrink:0;"></div>
                    <img src="{{ route('image.view', 'blsdm-logo.png') }}" alt="Logo BLSDM Komdigi Manado"
                         style="height:64px;width:auto;object-fit:contain;display:block;flex-shrink:0;">
                </div>
                <h1 class="brand-title">Sistem Arsip Surat</h1>
                <p class="brand-subtitle">BPSDMP</p>
                <p class="brand-desc">Masuk untuk mengelola arsip surat digital</p>
            </div>

            <div class="divider"></div>

            <!-- Error Alert -->
            @if(session('login_error'))
            <div class="alert-login-error" id="login-error-alert">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <span>{{ session('login_error') }}</span>
            </div>
            @endif

            <!-- Form Login -->
            <form action="{{ route('login.post') }}" method="POST" id="login-form" novalidate>
                @csrf

                <!-- Username -->
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-wrap">
                        <input
                            type="text"
                            name="username"
                            id="username"
                            class="form-input @error('username') input-err @enderror"
                            value="{{ old('username') }}"
                            placeholder="Masukkan username Anda"
                            autocomplete="username"
                            autofocus
                            required
                        >
                        <span class="input-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </span>
                    </div>
                    @error('username')
                        <p class="field-error">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrap">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-input @error('password') input-err @enderror"
                            placeholder="Masukkan password Anda"
                            autocomplete="current-password"
                            required
                        >
                        <span class="input-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>
                        <button type="button" class="pass-toggle" id="pass-toggle" title="Tampilkan/sembunyikan password" aria-label="Toggle password visibility">
                            <svg id="eye-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="field-error">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Remember me -->
                <div class="remember-row">
                    <input type="checkbox" name="remember" id="remember" class="remember-checkbox" value="1">
                    <label for="remember" class="remember-label">Ingat saya di perangkat ini</label>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-login" id="btn-login">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                        <polyline points="10 17 15 12 10 7"/>
                        <line x1="15" y1="12" x2="3" y2="12"/>
                    </svg>
                    Masuk ke Sistem
                </button>
            </form>

            <!-- Footer -->
            <p class="login-footer-note">
                <span>Hanya pengelola yang berwenang dapat mengakses sistem ini.</span>
            </p>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Toggle password visibility
        const toggle   = document.getElementById('pass-toggle');
        const passInput = document.getElementById('password');
        const eyeIcon  = document.getElementById('eye-icon');

        const eyeOpen = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
        const eyeOff  = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>`;

        if (toggle) {
            toggle.addEventListener('click', function () {
                const isPassword = passInput.type === 'password';
                passInput.type = isPassword ? 'text' : 'password';
                eyeIcon.innerHTML = isPassword ? eyeOff : eyeOpen;
            });
        }

        // Auto-dismiss error alert after 6s
        const alert = document.getElementById('login-error-alert');
        if (alert) {
            setTimeout(function () {
                alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                alert.style.opacity    = '0';
                alert.style.transform  = 'scale(0.96)';
                setTimeout(function () { alert.remove(); }, 400);
            }, 6000);
        }

        // Button loading state on submit
        const form    = document.getElementById('login-form');
        const btnLogin = document.getElementById('btn-login');
        if (form) {
            form.addEventListener('submit', function () {
                btnLogin.disabled = true;
                btnLogin.innerHTML = `
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 0.8s linear infinite">
                        <line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/>
                        <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/>
                        <line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/>
                        <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/>
                    </svg>
                    Memverifikasi...`;
            });
        }
    });
    </script>
    <style>
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</body>
</html>
