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

        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(59,130,246,0.2), transparent);
            margin-bottom: 28px;
        }

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

        .login-footer-note {
            margin-top: 24px;
            text-align: center;
            font-size: 0.76rem;
            color: #334155;
        }

        .login-footer-note span { color: #475569; }

        .forgot-link {
            display: block;
            text-align: center;
            margin-top: 16px;
            font-size: 0.8rem;
            color: #60a5fa;
            cursor: pointer;
            background: none;
            border: none;
            text-decoration: underline;
            text-underline-offset: 2px;
            transition: color var(--transition);
            font-family: inherit;
        }
        .forgot-link:hover { color: #93c5fd; }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.65);
            backdrop-filter: blur(6px);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            animation: fadeIn 0.2s ease;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .modal-card {
            background: rgba(15, 23, 42, 0.97);
            border: 1px solid rgba(59, 130, 246, 0.25);
            border-radius: 18px;
            padding: 36px 32px 28px;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 30px 80px rgba(0,0,0,0.6);
            animation: slideUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(24px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .modal-icon {
            width: 48px; height: 48px;
            background: rgba(59, 130, 246, 0.15);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 18px;
        }
        .modal-title {
            font-size: 1.1rem; font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
        }
        .modal-desc {
            font-size: 0.82rem; color: var(--text-muted);
            margin-bottom: 22px; line-height: 1.5;
        }
        .modal-actions {
            display: flex; gap: 10px; margin-top: 20px;
        }
        .btn-modal-cancel {
            flex: 1; padding: 11px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 9px;
            font-size: 0.88rem; font-weight: 600;
            font-family: inherit; color: var(--text-muted);
            cursor: pointer;
            transition: background var(--transition);
        }
        .btn-modal-cancel:hover { background: rgba(255,255,255,0.09); }
        .btn-modal-submit {
            flex: 2; padding: 11px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border: none; border-radius: 9px;
            font-size: 0.88rem; font-weight: 600;
            font-family: inherit; color: #fff;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 7px;
            transition: background var(--transition), transform var(--transition);
            box-shadow: 0 4px 14px rgba(59,130,246,0.35);
        }
        .btn-modal-submit:hover { background: linear-gradient(135deg, #2563eb, #1d4ed8); transform: translateY(-1px); }

        .reset-success {
            background: rgba(5, 150, 105, 0.12);
            border: 1px solid rgba(5, 150, 105, 0.35);
            border-radius: 10px;
            padding: 14px 16px;
            display: flex; gap: 12px; align-items: flex-start;
            margin-bottom: 14px;
        }
        .reset-success-icon { color: #34d399; flex-shrink: 0; margin-top: 1px; }
        .reset-success-text { font-size: 0.83rem; color: #6ee7b7; line-height: 1.5; }

        @media (max-width: 480px) {
            .login-card { padding: 32px 24px 28px; }
            .brand-title { font-size: 1.25rem; }
            .modal-card { padding: 28px 20px 22px; }
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
        <p class="nav-loading-text" id="nav-loading-msg">Memuat...</p>
    </div>
    <div id="nav-loading-bar" aria-hidden="true"></div>

    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <div class="login-wrapper">
        <div class="login-card">

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

            <form action="{{ route('login.post') }}" method="POST" id="login-form" novalidate>
                @csrf

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

                <div class="remember-row">
                    <input type="checkbox" name="remember" id="remember" class="remember-checkbox" value="1">
                    <label for="remember" class="remember-label">Ingat saya di perangkat ini</label>
                </div>

                <button type="submit" class="btn-login" id="btn-login">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                        <polyline points="10 17 15 12 10 7"/>
                        <line x1="15" y1="12" x2="3" y2="12"/>
                    </svg>
                    Masuk ke Sistem
                </button>
            </form>

            <button type="button" class="forgot-link" id="btn-forgot-password">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 9.9-1"/>
                </svg>
                Lupa password?
            </button>

            <p class="login-footer-note" style="margin-top:16px">
                <span>Hanya pengelola yang berwenang dapat mengakses sistem ini.</span>
            </p>
        </div>
    </div>

    <div id="reset-modal" class="modal-backdrop" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="reset-modal-title">
        <div class="modal-card">

            <div class="modal-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>

            @if(session('reset_requested'))

            <h2 class="modal-title" id="reset-modal-title">Permintaan Terkirim</h2>
            <p class="modal-desc">Permintaan reset password Anda telah dicatat. Admin akan segera menindaklanjutinya.</p>
            <div class="reset-success">
                <span class="reset-success-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </span>
                <div class="reset-success-text">
                    <strong>Apa yang harus dilakukan selanjutnya?</strong><br>
                    Hubungi admin atau supervisor Anda dan informasikan bahwa Anda telah mengirim permintaan reset password. Admin akan mereset password Anda melalui panel manajemen pengguna.
                </div>
            </div>
            <button type="button" class="btn-modal-cancel" id="btn-reset-close" style="width:100%;flex:unset">
                Mengerti, Tutup
            </button>
            @else

            <h2 class="modal-title" id="reset-modal-title">Lupa Password</h2>
            <p class="modal-desc">Masukkan username Anda. Permintaan reset akan diteruskan kepada admin untuk ditindaklanjuti secara manual.</p>

            @if($errors->hasBag('reset'))
            <div class="alert-login-error" style="margin-bottom:16px">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>{{ $errors->getBag('reset')->first() }}</span>
            </div>
            @endif

            <form action="{{ route('password.request-reset') }}" method="POST" id="reset-form">
                @csrf
                <div class="form-group">
                    <label for="reset_username" class="form-label">Username</label>
                    <div class="input-wrap">
                        <input
                            type="text"
                            name="reset_username"
                            id="reset_username"
                            class="form-input"
                            placeholder="Masukkan username Anda"
                            value="{{ old('reset_username') }}"
                            autocomplete="username"
                            required
                        >
                        <span class="input-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </span>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" id="btn-reset-cancel">Batal</button>
                    <button type="submit" class="btn-modal-submit" id="btn-reset-submit">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"/>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                        </svg>
                        Kirim Permintaan
                    </button>
                </div>
            </form>
            @endif
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {

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

        const alert = document.getElementById('login-error-alert');
        if (alert) {
            setTimeout(function () {
                alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                alert.style.opacity    = '0';
                alert.style.transform  = 'scale(0.96)';
                setTimeout(function () { alert.remove(); }, 400);
            }, 6000);
        }

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

        const resetModal  = document.getElementById('reset-modal');
        const btnForgot   = document.getElementById('btn-forgot-password');
        const btnCancel   = document.getElementById('btn-reset-cancel');
        const btnClose    = document.getElementById('btn-reset-close');
        const resetForm   = document.getElementById('reset-form');
        const btnReset    = document.getElementById('btn-reset-submit');

        function openResetModal() {
            if (resetModal) {
                resetModal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                const inp = document.getElementById('reset_username');
                if (inp) setTimeout(() => inp.focus(), 100);
            }
        }

        function closeResetModal() {
            if (resetModal) {
                resetModal.style.animation = 'none';
                resetModal.style.opacity = '0';
                resetModal.style.transition = 'opacity 0.2s ease';
                setTimeout(() => {
                    resetModal.style.display = 'none';
                    resetModal.style.opacity = '';
                    resetModal.style.transition = '';
                    document.body.style.overflow = '';
                }, 200);
            }
        }

        if (btnForgot) btnForgot.addEventListener('click', openResetModal);
        if (btnCancel) btnCancel.addEventListener('click', closeResetModal);
        if (btnClose)  btnClose.addEventListener('click', closeResetModal);

        if (resetModal) {
            resetModal.addEventListener('click', function(e) {
                if (e.target === resetModal) closeResetModal();
            });
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && resetModal && resetModal.style.display !== 'none') {
                closeResetModal();
            }
        });

        if (resetForm && btnReset) {
            resetForm.addEventListener('submit', function() {
                btnReset.disabled = true;
                btnReset.innerHTML = `
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 0.8s linear infinite">
                        <line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/>
                    </svg>
                    Mengirim...`;
            });
        }

        @if(session('reset_requested') || $errors->hasBag('reset'))
        openResetModal();
        @endif
    });
    </script>
    <style>
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
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
            return 'Memuat...';
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

            e.preventDefault();
            showLoading(href);
            setTimeout(function () {
                location.href = href;
            }, 120);
        });

        var loginForm = document.getElementById('login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', function (e) {
                setTimeout(function () {
                    if (!e.defaultPrevented) {
                        showLoading('Memproses masuk...');
                    }
                }, 0);
            });
        }

        window.addEventListener('pageshow', function () {
            if (overlay) {
                overlay.classList.remove('is-visible');
                overlay.style.display = 'none';
            }
            if (bar) bar.classList.remove('is-active');
        });
    })();
    </script>
</body>
</html>
