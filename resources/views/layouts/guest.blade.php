<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dompetra — Masuk</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

    <style>
        :root {
            --bg-dark: #111318;
            --bg-panel: #ffffff;
            --bg-input: #f8f9fb;
            --color-ink: #0d1117;
            --color-muted: #6b7280;
            --color-subtle: #9ca3af;
            --color-border: #e4e7ec;
            --color-accent: #1d6a4a;
            --color-accent-hover: #166534;
            --color-accent-glow: rgba(29, 106, 74, .2);
            --color-income: #15803d;
            --color-income-bg: #f0fdf4;
            --color-expense: #b91c1c;
            --color-expense-bg: #fff1f2;
            --radius-input: 10px;
            --ease: 0.18s ease;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            height: 100%;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            font-size: 14px;
            line-height: 1.55;
            background: var(--bg-dark);
            display: flex;
            align-items: stretch;
            min-height: 100vh;
            overflow: hidden;
        }

        .auth-shell {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* ── Left Panel ─────────────────────────────────────────── */
        .auth-left {
            flex: 1;
            background: var(--bg-dark);
            position: relative;
            display: flex;
            flex-direction: column;
            padding: 44px 52px;
            overflow: hidden;
        }

        .auth-left::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, rgba(255, 255, 255, .055) 1px, transparent 1px);
            background-size: 28px 28px;
            pointer-events: none;
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
        }

        .orb-1 {
            width: 380px;
            height: 380px;
            background: radial-gradient(circle, rgba(29, 106, 74, .4), transparent 70%);
            top: -100px;
            left: -100px;
        }

        .orb-2 {
            width: 280px;
            height: 280px;
            background: radial-gradient(circle, rgba(8, 145, 178, .2), transparent 70%);
            bottom: 40px;
            right: -60px;
        }

        .orb-3 {
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(21, 128, 61, .2), transparent 70%);
            bottom: 200px;
            left: 80px;
        }

        .auth-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            z-index: 2;
            text-decoration: none;
            animation: fadeUp .5s ease both;
        }

        .auth-brand .brand-icon {
            width: 38px;
            height: 38px;
            background: var(--color-accent);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-brand .brand-icon i {
            color: #fff;
            font-size: 18px;
        }

        .auth-brand .brand-name {
            font-size: 20px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -.4px;
        }

        .auth-brand .brand-name span {
            color: #6ee7b7;
        }

        .auth-hero {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            z-index: 2;
        }

        .hero-eyebrow {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #6ee7b7;
            margin-bottom: 16px;
            animation: fadeUp .5s .1s ease both;
        }

        .hero-title {
            font-size: clamp(26px, 3vw, 38px);
            font-weight: 800;
            color: #fff;
            line-height: 1.2;
            letter-spacing: -.6px;
            margin-bottom: 18px;
            animation: fadeUp .5s .15s ease both;
        }

        .hero-title em {
            font-style: normal;
            color: #6ee7b7;
        }

        .hero-desc {
            font-size: 14px;
            color: rgba(255, 255, 255, .45);
            max-width: 340px;
            line-height: 1.7;
            margin-bottom: 40px;
            animation: fadeUp .5s .2s ease both;
        }

        .stats-row {
            display: flex;
            gap: 28px;
            animation: fadeUp .5s .28s ease both;
        }

        .stat-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 22px;
            font-weight: 500;
            color: #fff;
            letter-spacing: -.5px;
        }

        .stat-label {
            font-size: 11.5px;
            color: rgba(255, 255, 255, .35);
            margin-top: 2px;
            font-weight: 500;
        }

        /* Floating cards */
        .floating-cards {
            position: absolute;
            right: -20px;
            bottom: 60px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            animation: fadeLeft .6s .4s ease both;
            z-index: 2;
        }

        .fc {
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 12px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            backdrop-filter: blur(8px);
            width: 228px;
        }

        .fc-icon {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }

        .fc-info {
            flex: 1;
            overflow: hidden;
        }

        .fc-name {
            font-size: 12px;
            font-weight: 700;
            color: rgba(255, 255, 255, .8);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .fc-cat {
            font-size: 11px;
            color: rgba(255, 255, 255, .32);
            margin-top: 1px;
        }

        .fc-amt {
            font-family: 'JetBrains Mono', monospace;
            font-size: 12.5px;
            font-weight: 500;
            flex-shrink: 0;
        }

        .auth-left-footer {
            position: relative;
            z-index: 2;
            font-size: 12px;
            color: rgba(255, 255, 255, .18);
            animation: fadeUp .5s .35s ease both;
        }

        /* ── Right Panel ─────────────────────────────────────────── */
        .auth-right {
            width: 480px;
            min-width: 420px;
            background: #f0f2f5;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 40px;
            position: relative;
            overflow-y: auto;
        }

        .auth-right::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--color-accent), #6ee7b7);
        }

        .auth-form-wrap {
            width: 100%;
            max-width: 360px;
            animation: fadeUp .5s .1s ease both;
        }

        .form-head {
            margin-bottom: 28px;
        }

        .form-greeting {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--color-accent);
            margin-bottom: 8px;
        }

        .form-head h2 {
            font-size: 26px;
            font-weight: 800;
            color: var(--color-ink);
            letter-spacing: -.4px;
            margin-bottom: 6px;
        }

        .form-head p {
            font-size: 13.5px;
            color: var(--color-muted);
        }

        /* Tabs */
        .auth-tabs {
            display: flex;
            background: #e2e5e9;
            border-radius: 10px;
            padding: 4px;
            margin-bottom: 26px;
        }

        .auth-tab {
            flex: 1;
            text-align: center;
            padding: 8px;
            border-radius: 7px;
            font-size: 13.5px;
            font-weight: 700;
            color: var(--color-muted);
            cursor: pointer;
            transition: background var(--ease), color var(--ease), box-shadow var(--ease);
            user-select: none;
        }

        .auth-tab.active {
            background: var(--bg-panel);
            color: var(--color-ink);
            box-shadow: 0 1px 4px rgba(0, 0, 0, .1);
        }

        /* Fields */
        .field-group {
            margin-bottom: 16px;
        }

        .field-label {
            display: block;
            font-size: 11.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--color-muted);
            margin-bottom: 6px;
        }

        .field-wrap {
            position: relative;
        }

        .field-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--color-subtle);
            font-size: 15px;
            pointer-events: none;
            transition: color var(--ease);
        }

        .field-icon-right {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--color-subtle);
            font-size: 15px;
            cursor: pointer;
            transition: color var(--ease);
        }

        .field-icon-right:hover {
            color: var(--color-ink);
        }

        .field-wrap:focus-within .field-icon {
            color: var(--color-accent);
        }

        .finia-input {
            width: 100%;
            background: var(--bg-panel);
            border: 1.5px solid var(--color-border);
            border-radius: var(--radius-input);
            padding: 11px 14px 11px 40px;
            font-size: 14px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--color-ink);
            outline: none;
            transition: border-color var(--ease), box-shadow var(--ease);
        }

        .finia-input::placeholder {
            color: var(--color-subtle);
        }

        .finia-input:focus {
            border-color: var(--color-accent);
            box-shadow: 0 0 0 3.5px var(--color-accent-glow);
        }

        .finia-input.error {
            border-color: var(--color-expense);
        }

        .finia-input.error:focus {
            box-shadow: 0 0 0 3.5px rgba(185, 28, 28, .15);
        }

        .field-error {
            font-size: 12px;
            color: var(--color-expense);
            margin-top: 5px;
            display: none;
        }

        .field-error.visible {
            display: block;
        }

        /* Strength bar */
        #strength-bar-wrap {
            display: none;
            margin-top: 8px;
        }

        #strength-bar {
            height: 100%;
            border-radius: 10px;
            transition: width .3s ease, background .3s ease;
            width: 0%;
        }

        /* Options */
        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
        }

        .custom-check {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .custom-check input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--color-accent);
            cursor: pointer;
            border-radius: 4px;
        }

        .custom-check span {
            font-size: 13px;
            color: var(--color-muted);
            font-weight: 500;
        }

        .forgot-link {
            font-size: 13px;
            font-weight: 700;
            color: var(--color-accent);
            text-decoration: none;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        /* Submit */
        .btn-submit {
            width: 100%;
            background: var(--color-accent);
            border: none;
            color: #fff;
            font-size: 14.5px;
            font-weight: 800;
            font-family: 'Plus Jakarta Sans', sans-serif;
            border-radius: var(--radius-input);
            padding: 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background var(--ease), box-shadow var(--ease), transform var(--ease);
            position: relative;
            overflow: hidden;
        }

        .btn-submit::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, .08), transparent);
            pointer-events: none;
        }

        .btn-submit:hover {
            background: var(--color-accent-hover);
            box-shadow: 0 6px 20px rgba(29, 106, 74, .35);
            transform: translateY(-1px);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit.loading {
            opacity: .75;
            pointer-events: none;
        }

        .spinner {
            width: 17px;
            height: 17px;
            border: 2.5px solid rgba(255, 255, 255, .35);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .7s linear infinite;
            display: none;
        }

        .btn-submit.loading .spinner {
            display: block;
        }

        .btn-submit.loading .btn-label {
            display: none;
        }

        /* Divider */
        .auth-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 22px 0;
            color: var(--color-subtle);
            font-size: 12px;
            font-weight: 600;
        }

        .auth-divider::before,
        .auth-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--color-border);
        }

        /* Social */
        .social-btn {
            width: 100%;
            background: var(--bg-panel);
            border: 1.5px solid var(--color-border);
            border-radius: var(--radius-input);
            padding: 11px;
            font-size: 13.5px;
            font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--color-ink);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background var(--ease), border-color var(--ease), box-shadow var(--ease);
        }

        .social-btn:hover {
            background: #f8f9fb;
            border-color: #c5c9d1;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .06);
        }

        /* Alert */
        .form-alert {
            border-radius: 8px;
            padding: 11px 14px;
            font-size: 13px;
            font-weight: 600;
            display: none;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
        }

        .form-alert.visible {
            display: flex;
        }

        .form-alert.alert-error {
            background: var(--color-expense-bg);
            color: #991b1b;
            border: 1px solid #fecdd3;
        }

        .form-alert.alert-success {
            background: var(--color-income-bg);
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        /* Switch */
        .auth-switch {
            text-align: center;
            margin-top: 24px;
            font-size: 13.5px;
            color: var(--color-muted);
        }

        .auth-switch a {
            color: var(--color-accent);
            font-weight: 800;
            text-decoration: none;
        }

        .auth-switch a:hover {
            text-decoration: underline;
        }



        /* Animations */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeLeft {
            from {
                opacity: 0;
                transform: translateX(20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 900px) {
            .auth-left {
                display: none;
            }

            .auth-right {
                width: 100%;
                min-width: unset;
                padding: 40px 24px;
            }
        }

        @media (max-width: 480px) {
            .auth-right {
                padding: 32px 20px;
            }
        }
    </style>
</head>

<body>
    <div class="auth-shell">

        <!-- LEFT PANEL -->
        <div class="auth-left">
            <div class="orb orb-1"></div>
            <div class="orb orb-2"></div>
            <div class="orb orb-3"></div>

            <a href="#" class="auth-brand">
                <div class="brand-icon"><i class="bi bi-wallet-fill"></i></div>
                <span class="brand-name">Dom<span>petra</span></span>
            </a>

            <div class="auth-hero">
                <div class="hero-eyebrow">Manajemen Keuangan Pribadi</div>
                <h1 class="hero-title">
                    Kendali penuh atas<br /><em>keuanganmu</em>.
                </h1>
                <p class="hero-desc">
                    Catat pemasukan dan pengeluaran, pantau tabungan, dan analisis kebiasaan belanjamu dalam satu
                    platform yang simpel.
                </p>

                <div class="stats-row">
                    <div class="stat-item">
                        <div class="stat-value">Rp24,5 jt</div>
                        <div class="stat-label">Total Saldo</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" style="color:#6ee7b7;">+12%</div>
                        <div class="stat-label">Tabungan bulan ini</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">24</div>
                        <div class="stat-label">Transaksi tercatat</div>
                    </div>
                </div>

                <div class="floating-cards">
                    <div class="fc">
                        <div class="fc-icon" style="background:rgba(21,128,61,.15);">
                            <i class="bi bi-arrow-down-left" style="color:#6ee7b7;"></i>
                        </div>
                        <div class="fc-info">
                            <div class="fc-name">Gaji Bulanan</div>
                            <div class="fc-cat">Gaji · 27 Jun 2025</div>
                        </div>
                        <div class="fc-amt" style="color:#6ee7b7;">+Rp8,5 jt</div>
                    </div>
                    <div class="fc">
                        <div class="fc-icon" style="background:rgba(245,158,11,.12);">
                            <i class="bi bi-basket" style="color:#fbbf24;"></i>
                        </div>
                        <div class="fc-info">
                            <div class="fc-name">Hero Supermarket</div>
                            <div class="fc-cat">Makanan · 28 Jun 2025</div>
                        </div>
                        <div class="fc-amt" style="color:#f87171;">−Rp285 rb</div>
                    </div>
                    <div class="fc">
                        <div class="fc-icon" style="background:rgba(8,145,178,.12);">
                            <i class="bi bi-piggy-bank-fill" style="color:#67e8f9;"></i>
                        </div>
                        <div class="fc-info">
                            <div class="fc-name">Tabungan Bersih</div>
                            <div class="fc-cat">Juni 2025</div>
                        </div>
                        <div class="fc-amt" style="color:#67e8f9;">Rp3,27 jt</div>
                    </div>
                </div>
            </div>

            <div class="auth-left-footer">© 2025 Dompetra · Hak cipta dilindungi</div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="auth-right">
            {{ $slot }}
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        'use strict';

        function togglePass(inputId, iconId) {
            const inp = document.getElementById(inputId), ico = document.getElementById(iconId);
            if (!inp || !ico) return;
            inp.type = inp.type === 'password' ? 'text' : 'password';
            ico.className = inp.type === 'password' ? 'bi bi-eye field-icon-right' : 'bi bi-eye-slash field-icon-right';
        }

        function checkStrength() {
            const val = document.getElementById('regPass').value;
            const wrap = document.getElementById('strength-bar-wrap'), bar = document.getElementById('strength-bar'), lbl = document.getElementById('strength-label');
            if (!val) { wrap.style.display = 'none'; return; }
            wrap.style.display = 'block';
            let s = 0;
            if (val.length >= 8) s++; if (/[A-Z]/.test(val)) s++; if (/[0-9]/.test(val)) s++; if (/[^A-Za-z0-9]/.test(val)) s++;
            const lvl = [{ p: '25%', c: '#ef4444', t: 'Lemah' }, { p: '50%', c: '#f59e0b', t: 'Cukup' }, { p: '75%', c: '#3b82f6', t: 'Kuat' }, { p: '100%', c: '#15803d', t: 'Sangat Kuat' }][Math.max(0, s - 1)];
            bar.style.cssText = `height:100%;border-radius:10px;transition:width .3s ease,background .3s ease;width:${lvl.p};background:${lvl.c};`;
            lbl.textContent = lvl.t; lbl.style.color = lvl.c;
        }

        function clearError(fid) {
            const inp = document.getElementById(fid), err = document.getElementById(fid + '-err');
            if (inp) inp.classList.remove('error');
            if (err) err.classList.remove('visible');
        }

        document.addEventListener('DOMContentLoaded', () => {
            const em = document.getElementById('loginEmail');
            if (em) em.addEventListener('keydown', e => { if (e.key === 'Enter') document.getElementById('loginPass').focus(); });
        });
    </script>
</body>

</html>