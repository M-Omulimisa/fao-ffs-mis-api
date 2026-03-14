<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ config('app.name', 'FAO FFS MIS') }} — Field Farmer School Management Information System</title>
    <meta name="description" content="Digital platform for managing Farmer Field Schools, VSLA groups, and agricultural development across Karamoja Subregion, Uganda.">

    <link rel="shortcut icon" href="{{ asset('assets/images/logo.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --blue:       #05179F;
            --blue-dark:  #03107a;
            --blue-mid:   #0a2fd6;
            --blue-light: #e8ecff;
            --gold:       #F4A71D;
            --text:       #1a1f36;
            --muted:      #6b7280;
            --border:     #e5e7eb;
            --white:      #ffffff;
            --radius:     12px;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text);
            background: var(--white);
            line-height: 1.6;
        }

        /* ── Navbar ───────────────────────────────────────────────────── */
        .nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 5vw;
            height: 68px;
            background: rgba(255,255,255,.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            box-shadow: 0 1px 12px rgba(5,23,159,.07);
        }
        .nav-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .nav-brand img { height: 38px; width: auto; }
        .nav-brand-text { display: flex; flex-direction: column; }
        .nav-brand-name { font-size: .95rem; font-weight: 700; color: var(--blue); line-height: 1.2; }
        .nav-brand-sub  { font-size: .7rem; font-weight: 500; color: var(--muted); letter-spacing: .04em; text-transform: uppercase; }
        .nav-cta {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 22px;
            background: var(--blue); color: var(--white);
            font-size: .875rem; font-weight: 600; text-decoration: none;
            border-radius: 8px;
            transition: background .2s, transform .15s;
        }
        .nav-cta:hover { background: var(--blue-dark); transform: translateY(-1px); }

        /* ── Hero ─────────────────────────────────────────────────────── */
        .hero {
            position: relative;
            min-height: 100vh;
            display: flex; align-items: center;
            background: linear-gradient(135deg, var(--blue) 0%, #0a2fd6 45%, #1a47e8 100%);
            overflow: hidden;
            padding: 100px 5vw 80px;
        }

        /* Dot grid decoration */
        .hero::before {
            content: '';
            position: absolute; top: 0; right: 0; bottom: 0; left: 0;
            background-image: radial-gradient(circle, rgba(255,255,255,.12) 1px, transparent 1px);
            background-size: 28px 28px;
            pointer-events: none;
        }
        /* Large circle accent */
        .hero::after {
            content: '';
            position: absolute; top: -200px; right: -200px;
            width: 700px; height: 700px;
            border-radius: 50%;
            background: rgba(255,255,255,.04);
            pointer-events: none;
        }

        .hero-inner {
            position: relative; z-index: 1;
            max-width: 1200px; margin: 0 auto; width: 100%;
            display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center;
        }

        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 14px;
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 100px;
            font-size: .75rem; font-weight: 600; color: rgba(255,255,255,.9);
            letter-spacing: .06em; text-transform: uppercase;
            margin-bottom: 24px;
        }
        .hero-badge span { width: 6px; height: 6px; border-radius: 50%; background: var(--gold); }

        .hero-title {
            font-size: clamp(2rem, 4vw, 3.2rem);
            font-weight: 800; line-height: 1.15;
            color: var(--white);
            margin-bottom: 20px;
            letter-spacing: -.02em;
        }
        .hero-title em { font-style: normal; color: var(--gold); }

        .hero-desc {
            font-size: 1.05rem; color: rgba(255,255,255,.82);
            line-height: 1.75; margin-bottom: 40px; max-width: 520px;
        }

        .hero-actions { display: flex; gap: 16px; flex-wrap: wrap; }

        .btn-primary {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 15px 32px;
            background: var(--white); color: var(--blue);
            font-size: 1rem; font-weight: 700; text-decoration: none;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,.2);
            transition: transform .2s, box-shadow .2s;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(0,0,0,.25); }

        .btn-outline {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 14px 28px;
            border: 2px solid rgba(255,255,255,.4); color: var(--white);
            font-size: .95rem; font-weight: 600; text-decoration: none;
            border-radius: 10px;
            transition: background .2s, border-color .2s;
        }
        .btn-outline:hover { background: rgba(255,255,255,.12); border-color: rgba(255,255,255,.7); }

        /* Hero stats */
        .hero-stats {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.18);
            border-radius: var(--radius);
            overflow: hidden;
            margin-top: 48px;
        }
        .stat-item {
            padding: 20px 24px;
            background: rgba(255,255,255,.07);
            text-align: center;
        }
        .stat-item:not(:last-child) { border-right: 1px solid rgba(255,255,255,.1); }
        .stat-num { font-size: 1.8rem; font-weight: 800; color: var(--white); line-height: 1; }
        .stat-num small { font-size: 1.1rem; }
        .stat-lbl { font-size: .72rem; color: rgba(255,255,255,.65); text-transform: uppercase; letter-spacing: .06em; margin-top: 4px; }

        /* Hero graphic (right side) */
        .hero-graphic {
            display: flex; align-items: center; justify-content: center;
        }
        .hero-card-stack { position: relative; width: 340px; height: 340px; }
        .hero-card {
            position: absolute;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.2);
            backdrop-filter: blur(8px);
            border-radius: 16px;
            padding: 20px 24px;
            color: var(--white);
        }
        .hero-card-main {
            width: 280px; top: 50%; left: 50%;
            transform: translate(-50%, -50%);
        }
        .hero-card-top {
            width: 240px; top: 10%; left: 50%;
            transform: translateX(-50%) rotate(-6deg);
            opacity: .7;
        }
        .hero-card-bottom {
            width: 240px; bottom: 10%; left: 50%;
            transform: translateX(-50%) rotate(5deg);
            opacity: .7;
        }
        .hc-icon {
            width: 40px; height: 40px; border-radius: 10px;
            background: rgba(255,255,255,.18);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; margin-bottom: 12px;
        }
        .hc-title { font-size: .8rem; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; opacity: .8; }
        .hc-value { font-size: 1.5rem; font-weight: 800; margin-top: 2px; }
        .hc-sub   { font-size: .72rem; opacity: .6; margin-top: 2px; }

        /* ── Features section ─────────────────────────────────────────── */
        .section { padding: 90px 5vw; }
        .section-inner { max-width: 1200px; margin: 0 auto; }

        .section-eyebrow {
            display: inline-block;
            font-size: .75rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
            color: var(--blue); margin-bottom: 12px;
        }
        .section-title {
            font-size: clamp(1.6rem, 3vw, 2.4rem);
            font-weight: 800; line-height: 1.2; color: var(--text);
            letter-spacing: -.02em; margin-bottom: 16px;
        }
        .section-desc { font-size: 1.05rem; color: var(--muted); max-width: 580px; line-height: 1.75; }

        .section-header { margin-bottom: 56px; }

        /* Feature grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
        }
        .feature-card {
            padding: 28px 30px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--white);
            transition: border-color .2s, box-shadow .2s, transform .2s;
        }
        .feature-card:hover {
            border-color: var(--blue);
            box-shadow: 0 8px 32px rgba(5,23,159,.1);
            transform: translateY(-3px);
        }
        .feature-icon {
            width: 52px; height: 52px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.35rem; margin-bottom: 18px;
        }
        .feature-title { font-size: 1.05rem; font-weight: 700; margin-bottom: 8px; color: var(--text); }
        .feature-desc  { font-size: .88rem; color: var(--muted); line-height: 1.7; }

        /* ── Coverage / project strip ─────────────────────────────────── */
        .coverage {
            background: linear-gradient(135deg, var(--blue-dark) 0%, var(--blue) 100%);
            padding: 80px 5vw;
            color: var(--white);
        }
        .coverage-inner {
            max-width: 1200px; margin: 0 auto;
            display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center;
        }
        .coverage h2 {
            font-size: clamp(1.5rem, 2.8vw, 2.2rem);
            font-weight: 800; line-height: 1.2;
            margin-bottom: 20px; letter-spacing: -.02em;
        }
        .coverage p { font-size: .97rem; opacity: .82; line-height: 1.75; margin-bottom: 12px; }
        .tag-list { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 24px; }
        .tag {
            padding: 6px 14px;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 100px;
            font-size: .78rem; font-weight: 600; color: rgba(255,255,255,.9);
        }

        .districts-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;
        }
        .district-chip {
            padding: 10px 14px;
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 8px;
            font-size: .78rem; font-weight: 600; color: rgba(255,255,255,.9);
            display: flex; align-items: center; gap: 6px;
        }
        .district-chip i { font-size: .65rem; color: var(--gold); }

        /* ── Modules overview ─────────────────────────────────────────── */
        .modules { background: #f8f9ff; }

        .module-row {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;
        }
        .module-pill {
            display: flex; align-items: center; gap: 12px;
            padding: 16px 20px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: .875rem; font-weight: 600; color: var(--text);
            transition: border-color .2s, box-shadow .2s;
        }
        .module-pill:hover { border-color: var(--blue); box-shadow: 0 4px 16px rgba(5,23,159,.08); }
        .module-pill-icon {
            width: 36px; height: 36px; border-radius: 8px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: .95rem;
        }

        /* ── CTA strip ────────────────────────────────────────────────── */
        .cta-strip {
            background: var(--blue-light);
            padding: 72px 5vw;
            text-align: center;
        }
        .cta-strip h2 {
            font-size: clamp(1.6rem, 2.8vw, 2.2rem);
            font-weight: 800; color: var(--blue); margin-bottom: 16px;
            letter-spacing: -.02em;
        }
        .cta-strip p { font-size: 1rem; color: var(--muted); max-width: 480px; margin: 0 auto 36px; }

        /* ── Footer ───────────────────────────────────────────────────── */
        footer {
            background: #0d1225;
            color: rgba(255,255,255,.65);
            padding: 48px 5vw;
        }
        .footer-inner {
            max-width: 1200px; margin: 0 auto;
            display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 48px;
        }
        .footer-brand { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 16px; }
        .footer-brand img { height: 40px; filter: brightness(0) invert(1); }
        .footer-brand-text strong { display: block; color: var(--white); font-size: .95rem; font-weight: 700; }
        .footer-brand-text span   { font-size: .78rem; }
        .footer-desc { font-size: .82rem; line-height: 1.7; max-width: 380px; }
        .footer-project {
            font-size: .75rem; margin-top: 16px;
            padding: 10px 14px;
            background: rgba(255,255,255,.05);
            border-radius: 8px; border: 1px solid rgba(255,255,255,.08);
            line-height: 1.6;
        }
        .footer-col h4 { font-size: .8rem; font-weight: 700; color: var(--white); text-transform: uppercase; letter-spacing: .08em; margin-bottom: 16px; }
        .footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 10px; }
        .footer-col ul li a, .footer-col ul li span {
            font-size: .82rem; color: rgba(255,255,255,.55);
            text-decoration: none; transition: color .2s;
        }
        .footer-col ul li a:hover { color: var(--white); }
        .footer-bottom {
            max-width: 1200px; margin: 40px auto 0;
            padding-top: 24px;
            border-top: 1px solid rgba(255,255,255,.08);
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 12px;
            font-size: .78rem;
        }
        .footer-bottom a { color: rgba(255,255,255,.45); text-decoration: none; transition: color .2s; }
        .footer-bottom a:hover { color: var(--white); }

        /* ── Responsive ───────────────────────────────────────────────── */
        @media (max-width: 960px) {
            .hero-inner    { grid-template-columns: 1fr; text-align: center; }
            .hero-graphic  { display: none; }
            .hero-actions  { justify-content: center; }
            .hero-stats    { max-width: 480px; margin: 48px auto 0; }
            .hero-desc     { margin-left: auto; margin-right: auto; }
            .coverage-inner { grid-template-columns: 1fr; }
            .footer-inner  { grid-template-columns: 1fr; gap: 32px; }
            .footer-bottom { flex-direction: column; text-align: center; }
        }
        @media (max-width: 720px) {
            .module-row { grid-template-columns: repeat(2, 1fr); }
            .districts-grid { grid-template-columns: repeat(2, 1fr); }
            .hero-stats { grid-template-columns: 1fr; }
            .stat-item:not(:last-child) { border-right: none; border-bottom: 1px solid rgba(255,255,255,.1); }
        }
        @media (max-width: 480px) {
            .nav-brand-sub { display: none; }
            .module-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    {{-- ── NAVBAR ──────────────────────────────────────────────────────── --}}
    <nav class="nav">
        <a href="/" class="nav-brand">
            <img src="{{ asset('assets/images/logo.png') }}" alt="FAO FFS MIS Logo">
            <div class="nav-brand-text">
                <span class="nav-brand-name">FAO FFS MIS</span>
                <span class="nav-brand-sub">Uganda — Karamoja</span>
            </div>
        </a>
        <a href="{{ url('/auth/login') }}" class="nav-cta">
            <i class="fa fa-sign-in-alt"></i> Login to Dashboard
        </a>
    </nav>

    {{-- ── HERO ─────────────────────────────────────────────────── --}}
    <section class="hero">
        <div class="hero-inner">

            {{-- Left: copy --}}
            <div>
                <div class="hero-badge">
                    <span></span> FOSTER Programme — Karamoja, Uganda
                </div>
                <h1 class="hero-title">
                    Field Farmer School<br>
                    <em>Management</em> &amp;<br>
                    Information System
                </h1>
                <p class="hero-desc">
                    A unified digital platform for managing Farmer Field Schools, Village Savings
                    and Loan Associations, and agricultural support activities across the
                    Karamoja Sub-region — helping facilitators, implementing partners, and
                    programme managers work smarter.
                </p>
                <div class="hero-actions">
                    <a href="{{ url('/auth/login') }}" class="btn-primary">
                        <i class="fa fa-sign-in-alt"></i> Login to Dashboard
                    </a>
                    <a href="#features" class="btn-outline">
                        <i class="fa fa-th-large"></i> Learn More
                    </a>
                </div>

                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-num">9</div>
                        <div class="stat-lbl">Districts Covered</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-num"><small>VSLA</small></div>
                        <div class="stat-lbl">Savings Groups</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-num"><small>FFS</small></div>
                        <div class="stat-lbl">Farmer Field Schools</div>
                    </div>
                </div>
            </div>

            {{-- Right: decorative card stack --}}
            <div class="hero-graphic">
                <div class="hero-card-stack">
                    <div class="hero-card hero-card-top">
                        <div class="hc-icon"><i class="fa fa-users"></i></div>
                        <div class="hc-title">Active Groups</div>
                        <div class="hc-value">VSLA</div>
                        <div class="hc-sub">Savings &amp; Loans</div>
                    </div>
                    <div class="hero-card hero-card-main">
                        <div class="hc-icon"><i class="fa fa-chart-line"></i></div>
                        <div class="hc-title">KPI Tracking</div>
                        <div class="hc-value">Real-time</div>
                        <div class="hc-sub">Facilitator &amp; IP scorecards</div>
                    </div>
                    <div class="hero-card hero-card-bottom">
                        <div class="hc-icon"><i class="fa fa-seedling"></i></div>
                        <div class="hc-title">Training Sessions</div>
                        <div class="hc-value">FFS</div>
                        <div class="hc-sub">Field school management</div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- ── FEATURES ─────────────────────────────────────────────── --}}
    <section class="section" id="features">
        <div class="section-inner">
            <div class="section-header">
                <span class="section-eyebrow">Core Capabilities</span>
                <h2 class="section-title">Everything you need to manage<br>agricultural development programmes</h2>
                <p class="section-desc">From farmer registration and VSLA group management to training sessions, KPI monitoring, and market price tracking — all in one place.</p>
            </div>

            <div class="features-grid">

                <div class="feature-card">
                    <div class="feature-icon" style="background:#eef2ff; color:#4f46e5;">
                        <i class="fa fa-piggy-bank"></i>
                    </div>
                    <div class="feature-title">VSLA Group Management</div>
                    <div class="feature-desc">Manage Village Savings and Loan Associations — members, savings cycles, meetings, loans, repayments, and end-of-cycle shareouts with full double-entry accounting.</div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" style="background:#f0fdf4; color:#16a34a;">
                        <i class="fa fa-seedling"></i>
                    </div>
                    <div class="feature-title">Farmer Field Schools</div>
                    <div class="feature-desc">Track FFS groups, training sessions, session attendance, participant resolutions (GAPs), and AESA livestock observations — all linked to facilitators and IPs.</div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" style="background:#fff7ed; color:#ea580c;">
                        <i class="fa fa-chart-bar"></i>
                    </div>
                    <div class="feature-title">KPI Monitoring</div>
                    <div class="feature-desc">Weekly KPI scorecards for every facilitator and implementing partner. Benchmark targets, trend charts, and performance alerts — real-time visibility for programme managers.</div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" style="background:#f0f9ff; color:#0284c7;">
                        <i class="fa fa-users"></i>
                    </div>
                    <div class="feature-title">Farmer Profiling</div>
                    <div class="feature-desc">Comprehensive member profiles including household data, national ID, roles within groups (chairperson, secretary, treasurer), onboarding steps, and location hierarchy.</div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" style="background:#fdf4ff; color:#9333ea;">
                        <i class="fa fa-tags"></i>
                    </div>
                    <div class="feature-title">Market Price Tracking</div>
                    <div class="feature-desc">Monitor commodity prices across districts and sub-counties. Record daily market prices per unit, with trends that inform farmers on when and where to sell.</div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" style="background:#fff1f2; color:#e11d48;">
                        <i class="fa fa-mobile-alt"></i>
                    </div>
                    <div class="feature-title">Mobile-first Field Collection</div>
                    <div class="feature-desc">Full offline-capable mobile app for facilitators — capture meetings, attendance, AESA data, and profiles in the field without internet, then sync when connected.</div>
                </div>

            </div>
        </div>
    </section>

    {{-- ── MODULES LIST ─────────────────────────────────────────── --}}
    <section class="section modules">
        <div class="section-inner">
            <div class="section-header">
                <span class="section-eyebrow">System Modules</span>
                <h2 class="section-title">A complete programme management toolkit</h2>
            </div>
            <div class="module-row">
                @php
                $modules = [
                    ['fa fa-users',          '#eef2ff', '#4f46e5', 'VSLA Groups'],
                    ['fa fa-coins',          '#f0fdf4', '#16a34a', 'Savings & Loans'],
                    ['fa fa-seedling',       '#fff7ed', '#ea580c', 'Farmer Field Schools'],
                    ['fa fa-chalkboard-teacher', '#f0f9ff', '#0284c7', 'Training Sessions'],
                    ['fa fa-chart-line',     '#fdf4ff', '#9333ea', 'KPI Tracking'],
                    ['fa fa-id-card',        '#fff1f2', '#e11d48', 'Member Profiles'],
                    ['fa fa-hand-holding-usd', '#fffbeb', '#d97706', 'Investments'],
                    ['fa fa-tags',           '#f0fdf4', '#059669', 'Market Prices'],
                    ['fa fa-shield-alt',     '#eff6ff', '#2563eb', 'Insurance'],
                    ['fa fa-building',       '#fef3c7', '#d97706', 'Implementing Partners'],
                    ['fa fa-user-tie',       '#fdf4ff', '#7c3aed', 'Facilitators'],
                    ['fa fa-tachometer-alt', '#fff1f2', '#be123c', 'Programme Dashboard'],
                ];
                @endphp
                @foreach($modules as $m)
                <div class="module-pill">
                    <div class="module-pill-icon" style="background:{{ $m[1] }}; color:{{ $m[2] }};">
                        <i class="{{ $m[0] }}"></i>
                    </div>
                    {{ $m[3] }}
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── COVERAGE ─────────────────────────────────────────────── --}}
    <section class="coverage">
        <div class="coverage-inner">
            <div>
                <h2>Serving 9 Districts in<br>Karamoja Subregion</h2>
                <p>
                    The FOSTER programme (Food Security and Resilience in Karamoja) operates across the
                    entire Karamoja Sub-region — one of Uganda's most food-insecure areas — supporting
                    farmer communities through agricultural training, village savings, and market linkages.
                </p>
                <p>Funded by the European Union and implemented with FAO Uganda under project reference UNJP/UGA/068/EC.</p>
                <div class="tag-list">
                    <span class="tag">UNJP/UGA/068/EC</span>
                    <span class="tag">FAO Uganda</span>
                    <span class="tag">EU Funded</span>
                    <span class="tag">FOSTER Programme</span>
                </div>
            </div>
            <div>
                <div class="districts-grid">
                    @php
                    $districts = ['Abim','Amudat','Kaabong','Karenga','Kotido','Moroto','Nakapiripirit','Napak','Nabilatuk'];
                    @endphp
                    @foreach($districts as $d)
                    <div class="district-chip">
                        <i class="fa fa-map-marker-alt"></i> {{ $d }}
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ── CTA STRIP ────────────────────────────────────────────── --}}
    <section class="cta-strip">
        <h2>Ready to get started?</h2>
        <p>Log in to access your dashboard, manage groups, track KPIs, and monitor programme activities in real-time.</p>
        <a href="{{ url('/auth/login') }}" class="btn-primary" style="display:inline-flex; background:var(--blue); color:var(--white); box-shadow:0 8px 24px rgba(5,23,159,.25);">
            <i class="fa fa-sign-in-alt"></i> Login to Dashboard
        </a>
    </section>

    {{-- ── FOOTER ───────────────────────────────────────────────── --}}
    <footer>
        <div class="footer-inner">
            <div>
                <div class="footer-brand">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="FAO FFS MIS">
                    <div class="footer-brand-text">
                        <strong>FAO FFS MIS</strong>
                        <span>Field Farmer School Management Information System</span>
                    </div>
                </div>
                <p class="footer-desc">
                    A digital platform supporting the FOSTER programme's goal of improving food security
                    and resilience among farming communities in the Karamoja Sub-region of Uganda.
                </p>
                <div class="footer-project">
                    Project: UNJP/UGA/068/EC — FOSTER<br>
                    Food Security and Resilience in Karamoja, Uganda
                </div>
            </div>
            <div class="footer-col">
                <h4>Programme</h4>
                <ul>
                    <li><span>FAO Uganda</span></li>
                    <li><span>European Union</span></li>
                    <li><span>Karamoja Subregion</span></li>
                    <li><span>9 Districts</span></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>System</h4>
                <ul>
                    <li><a href="{{ url('/auth/login') }}">Login to Dashboard</a></li>
                    <li><span>Web Portal</span></li>
                    <li><span>Mobile App (Field)</span></li>
                    <li><a href="https://m-omulimisa.com" target="_blank" rel="noopener">Powered by M-Omulimisa</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; {{ date('Y') }} FAO-FFS MIS — FOSTER Programme, Uganda. All rights reserved.</span>
            <a href="https://m-omulimisa.com" target="_blank" rel="noopener">Powered by M-Omulimisa</a>
        </div>
    </footer>

</body>
</html>
