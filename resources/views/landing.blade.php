<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    {{-- Primary Meta --}}
    <title>FAO FFS MIS — Farmer Field School Management System | Karamoja, Uganda</title>
    <meta name="description" content="The FAO Farmer Field School Management Information System supports the FOSTER programme across 9 districts of Karamoja, Uganda — managing VSLA groups, training sessions, KPI tracking, and farmer profiles for implementing partners and facilitators.">
    <meta name="keywords" content="FAO, FFS, Farmer Field School, VSLA, Karamoja, Uganda, FOSTER, agricultural development, management information system">
    <meta name="author" content="M-Omulimisa — Connecting Farmers">
    <meta name="robots" content="index, follow">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="FAO FFS MIS — Farmer Field School Management System">
    <meta property="og:description" content="Digital platform for managing Farmer Field Schools, VSLA groups and agricultural development across the Karamoja Sub-region, Uganda.">
    <meta property="og:image" content="{{ asset('assets/gallery/foster-01.jpg') }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="FAO FFS MIS — Farmer Field School Management System">
    <meta name="twitter:description" content="Digital platform for managing Farmer Field Schools, VSLA groups and agricultural development across the Karamoja Sub-region, Uganda.">
    <meta name="twitter:image" content="{{ asset('assets/gallery/foster-01.jpg') }}">

    {{-- Canonical --}}
    <link rel="canonical" href="{{ url('/') }}">
    <link rel="shortcut icon" href="{{ asset('assets/images/logo.png') }}" type="image/png">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Icons --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    {{-- GLightbox --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">

    <style>
        /* ── Reset & Base ─────────────────────────────────────────── */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --blue:       #05179F;
            --blue-dk:    #03107a;
            --blue-md:    #0d2fe0;
            --blue-tint:  #eef1ff;
            --gold:       #F4A71D;
            --gold-dk:    #c8880e;
            --text:       #0f172a;
            --body:       #374151;
            --muted:      #6b7280;
            --border:     #e5e7eb;
            --surface:    #f8f9ff;
            --white:      #ffffff;
            --radius-sm:  6px;
            --radius:     12px;
            --radius-lg:  20px;
            --shadow-sm:  0 1px 4px rgba(0,0,0,.06);
            --shadow:     0 4px 20px rgba(0,0,0,.09);
            --shadow-lg:  0 12px 40px rgba(0,0,0,.13);
        }

        html { scroll-behavior: smooth; font-size: 16px; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--body);
            background: var(--white);
            line-height: 1.65;
            -webkit-font-smoothing: antialiased;
        }

        img { max-width: 100%; height: auto; display: block; }
        a   { color: inherit; }

        /* ── Utilities ─────────────────────────────────────────────── */
        .container {
            max-width: 1180px;
            margin-left: auto;
            margin-right: auto;
            padding-left: clamp(20px, 5vw, 48px);
            padding-right: clamp(20px, 5vw, 48px);
        }

        .section { padding: clamp(64px, 8vw, 100px) 0; }

        .eyebrow {
            display: inline-block;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--blue);
            margin-bottom: 10px;
        }

        .section-title {
            font-size: clamp(1.7rem, 3.5vw, 2.6rem);
            font-weight: 800;
            color: var(--text);
            line-height: 1.15;
            letter-spacing: -.03em;
            margin-bottom: 14px;
        }

        .section-lead {
            font-size: 1.05rem;
            color: var(--muted);
            max-width: 560px;
            line-height: 1.75;
        }

        .section-header { margin-bottom: 52px; }

        /* ── Navbar ─────────────────────────────────────────────────── */
        .nav {
            position: fixed; inset-inline: 0; top: 0; z-index: 900;
            height: 66px;
            display: flex; align-items: center;
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(14px) saturate(180%);
            -webkit-backdrop-filter: blur(14px) saturate(180%);
            border-bottom: 1px solid transparent;
            transition: border-color .3s, box-shadow .3s;
        }
        .nav.scrolled {
            border-color: var(--border);
            box-shadow: 0 2px 16px rgba(5,23,159,.07);
        }
        .nav-inner {
            display: flex; align-items: center;
            justify-content: space-between; gap: 24px;
            width: 100%;
        }
        .nav-brand {
            display: flex; align-items: center; gap: 12px;
            text-decoration: none; flex-shrink: 0;
        }
        .nav-brand img { height: 36px; width: auto; }
        .nav-brand-text {}
        .nav-brand-name {
            font-size: .9rem; font-weight: 700;
            color: var(--blue); line-height: 1.2;
        }
        .nav-brand-sub {
            font-size: .65rem; font-weight: 500;
            color: var(--muted); letter-spacing: .05em;
            text-transform: uppercase;
        }
        .nav-links {
            display: flex; align-items: center; gap: 6px;
            list-style: none;
        }
        .nav-links a {
            font-size: .85rem; font-weight: 500;
            color: var(--body);
            padding: 8px 12px; border-radius: var(--radius-sm);
            text-decoration: none;
            transition: color .2s, background .2s;
        }
        .nav-links a:hover { color: var(--blue); background: var(--blue-tint); }

        .btn-nav {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 22px;
            background: var(--blue); color: var(--white);
            font-size: .85rem; font-weight: 600;
            text-decoration: none; white-space: nowrap;
            border-radius: var(--radius-sm);
            transition: background .2s, box-shadow .2s;
            box-shadow: 0 2px 10px rgba(5,23,159,.25);
        }
        .btn-nav:hover { background: var(--blue-dk); box-shadow: 0 4px 16px rgba(5,23,159,.35); }

        .nav-hamburger {
            display: none; flex-direction: column;
            gap: 5px; cursor: pointer; padding: 6px;
            background: none; border: none;
        }
        .nav-hamburger span {
            display: block; width: 22px; height: 2px;
            background: var(--text); border-radius: 2px;
            transition: all .3s;
        }

        /* ── Hero ──────────────────────────────────────────────────── */
        .hero {
            position: relative;
            min-height: 100svh;
            display: flex; align-items: center;
            overflow: hidden;
            padding-top: 66px;
        }
        .hero-bg {
            position: absolute; inset: 0;
            background-image: url('{{ asset("assets/gallery/foster-05.jpg") }}');
            background-size: cover;
            background-position: center 30%;
            z-index: 0;
        }
        .hero-bg::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(
                110deg,
                rgba(3,16,122,.88) 0%,
                rgba(5,23,159,.75) 50%,
                rgba(13,47,224,.55) 100%
            );
        }
        /* dot grid */
        .hero-bg::before {
            content: '';
            position: absolute; inset: 0; z-index: 1;
            background-image: radial-gradient(circle, rgba(255,255,255,.1) 1px, transparent 1px);
            background-size: 32px 32px;
        }
        .hero-inner {
            position: relative; z-index: 1;
            padding: clamp(60px,10vw,120px) 0 clamp(60px,8vw,100px);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 14px;
            background: rgba(244,167,29,.2);
            border: 1px solid rgba(244,167,29,.5);
            border-radius: 100px;
            font-size: .72rem; font-weight: 700;
            color: var(--gold); letter-spacing: .07em; text-transform: uppercase;
            margin-bottom: 22px;
        }
        .hero-title {
            font-size: clamp(2.1rem, 4.5vw, 3.4rem);
            font-weight: 900;
            color: var(--white);
            line-height: 1.1;
            letter-spacing: -.04em;
            margin-bottom: 22px;
        }
        .hero-title span { color: var(--gold); }
        .hero-desc {
            font-size: 1.05rem;
            color: rgba(255,255,255,.8);
            line-height: 1.8; max-width: 500px;
            margin-bottom: 38px;
        }
        .hero-actions { display: flex; gap: 14px; flex-wrap: wrap; }

        .btn-hero-primary {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 15px 32px;
            background: var(--white); color: var(--blue);
            font-size: .97rem; font-weight: 700;
            text-decoration: none; border-radius: var(--radius-sm);
            box-shadow: 0 8px 28px rgba(0,0,0,.22);
            transition: transform .2s, box-shadow .2s;
        }
        .btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 36px rgba(0,0,0,.28); }

        .btn-hero-outline {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 14px 26px;
            border: 2px solid rgba(255,255,255,.45);
            color: var(--white);
            font-size: .9rem; font-weight: 600;
            text-decoration: none; border-radius: var(--radius-sm);
            transition: background .2s, border-color .2s;
        }
        .btn-hero-outline:hover { background: rgba(255,255,255,.12); border-color: rgba(255,255,255,.75); }

        /* Hero stats */
        .hero-stats {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 0; margin-top: 48px;
            border: 1px solid rgba(255,255,255,.18);
            border-radius: var(--radius);
            overflow: hidden;
            background: rgba(255,255,255,.06);
            backdrop-filter: blur(8px);
        }
        .hero-stat {
            padding: 22px 18px; text-align: center;
        }
        .hero-stat + .hero-stat { border-left: 1px solid rgba(255,255,255,.12); }
        .hero-stat-val { font-size: 2rem; font-weight: 900; color: var(--white); line-height: 1; }
        .hero-stat-lbl { font-size: .68rem; color: rgba(255,255,255,.6); text-transform: uppercase; letter-spacing: .07em; margin-top: 5px; }

        /* Hero right: floating cards */
        .hero-cards {
            display: flex; flex-direction: column; gap: 14px;
        }
        .hero-card {
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.18);
            backdrop-filter: blur(12px);
            border-radius: var(--radius);
            padding: 18px 22px;
            color: var(--white);
            display: flex; align-items: center; gap: 16px;
        }
        .hero-card-icon {
            width: 44px; height: 44px; flex-shrink: 0;
            border-radius: 10px;
            background: rgba(255,255,255,.15);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem;
        }
        .hero-card-name { font-size: .8rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; opacity: .7; }
        .hero-card-val  { font-size: 1.1rem; font-weight: 700; margin-top: 2px; }

        /* ── Features ──────────────────────────────────────────────── */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }
        .feature-card {
            padding: 28px 26px 30px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--white);
            transition: border-color .25s, box-shadow .25s, transform .25s;
        }
        .feature-card:hover {
            border-color: var(--blue);
            box-shadow: 0 8px 32px rgba(5,23,159,.1);
            transform: translateY(-4px);
        }
        .fi {
            width: 50px; height: 50px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem; margin-bottom: 18px;
        }
        .feature-card h3 { font-size: 1rem; font-weight: 700; margin-bottom: 8px; color: var(--text); }
        .feature-card p  { font-size: .875rem; color: var(--muted); line-height: 1.7; }

        /* ── Gallery ───────────────────────────────────────────────── */
        .gallery-section { background: var(--text); }
        .gallery-section .section-title { color: var(--white); }
        .gallery-section .eyebrow { color: var(--gold); }
        .gallery-section .section-lead { color: rgba(255,255,255,.6); }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-auto-rows: 220px;
            gap: 10px;
        }
        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: var(--radius-sm);
            cursor: pointer;
        }
        /* Some items span 2 rows for visual variety */
        .gallery-item:nth-child(1),
        .gallery-item:nth-child(6),
        .gallery-item:nth-child(11) { grid-row: span 2; }

        .gallery-item img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform .5s cubic-bezier(.25,.46,.45,.94);
        }
        .gallery-item::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(to top, rgba(3,16,122,.55) 0%, transparent 60%);
            opacity: 0;
            transition: opacity .3s;
        }
        .gallery-item:hover img { transform: scale(1.06); }
        .gallery-item:hover::after { opacity: 1; }

        .gallery-overlay {
            position: absolute; inset: 0; z-index: 2;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity .3s;
        }
        .gallery-item:hover .gallery-overlay { opacity: 1; }
        .gallery-zoom {
            width: 44px; height: 44px; border-radius: 50%;
            background: rgba(255,255,255,.2);
            border: 2px solid rgba(255,255,255,.6);
            display: flex; align-items: center; justify-content: center;
            color: var(--white); font-size: 1rem;
            backdrop-filter: blur(4px);
            transform: scale(.8); transition: transform .3s;
        }
        .gallery-item:hover .gallery-zoom { transform: scale(1); }

        /* ── Coverage ──────────────────────────────────────────────── */
        .coverage-section {
            background: linear-gradient(135deg, var(--blue-dk) 0%, var(--blue-md) 100%);
            color: var(--white);
        }
        .coverage-section .eyebrow { color: var(--gold); }
        .coverage-inner {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 64px; align-items: start;
        }
        .coverage-section h2 {
            font-size: clamp(1.6rem, 3vw, 2.3rem);
            font-weight: 800; line-height: 1.2;
            letter-spacing: -.03em; margin-bottom: 18px; color: var(--white);
        }
        .coverage-section p { font-size: .95rem; opacity: .8; line-height: 1.8; margin-bottom: 10px; }
        .tag-row { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 24px; }
        .tag {
            padding: 5px 14px;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 100px;
            font-size: .75rem; font-weight: 600; color: rgba(255,255,255,.9);
        }
        .districts-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .district-chip {
            padding: 12px 14px;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: var(--radius-sm);
            font-size: .8rem; font-weight: 600;
            color: rgba(255,255,255,.9);
            display: flex; align-items: center; gap: 8px;
        }
        .district-chip i { color: var(--gold); font-size: .7rem; }

        /* ── M-Omulimisa ───────────────────────────────────────────── */
        .momi-section { background: var(--surface); }
        .momi-inner {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 64px; align-items: center;
        }
        .momi-badge {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: .72rem; font-weight: 700; letter-spacing: .1em;
            text-transform: uppercase; color: var(--blue); margin-bottom: 10px;
        }
        .momi-section h2 { margin-bottom: 18px; }
        .momi-section p  { font-size: .95rem; margin-bottom: 14px; line-height: 1.8; }
        .momi-stats {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 16px; margin: 28px 0;
        }
        .momi-stat {
            padding: 18px 16px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            text-align: center;
        }
        .momi-stat-val { font-size: 1.6rem; font-weight: 800; color: var(--blue); line-height: 1; }
        .momi-stat-lbl { font-size: .72rem; color: var(--muted); margin-top: 4px; }
        .btn-momi {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 13px 26px;
            border: 2px solid var(--blue); color: var(--blue);
            font-size: .9rem; font-weight: 700;
            text-decoration: none; border-radius: var(--radius-sm);
            transition: background .2s, color .2s;
        }
        .btn-momi:hover { background: var(--blue); color: var(--white); }

        .momi-visual {
            background: linear-gradient(135deg, var(--blue) 0%, var(--blue-md) 100%);
            border-radius: var(--radius-lg);
            padding: 40px;
            color: var(--white);
            position: relative; overflow: hidden;
        }
        .momi-visual::before {
            content: '';
            position: absolute; inset: 0;
            background-image: radial-gradient(circle, rgba(255,255,255,.08) 1px, transparent 1px);
            background-size: 24px 24px;
        }
        .momi-visual-content { position: relative; z-index: 1; }
        .momi-visual-logo {
            font-size: 2.5rem; font-weight: 900;
            letter-spacing: -.04em; margin-bottom: 8px;
        }
        .momi-visual-logo span { color: var(--gold); }
        .momi-visual-tag { font-size: .8rem; opacity: .7; font-weight: 500; margin-bottom: 28px; }
        .momi-visual-items { display: flex; flex-direction: column; gap: 12px; }
        .momi-vi {
            display: flex; align-items: center; gap: 12px;
            background: rgba(255,255,255,.1);
            border-radius: var(--radius-sm);
            padding: 12px 16px;
            font-size: .85rem; font-weight: 500;
        }
        .momi-vi i { color: var(--gold); width: 16px; }

        /* ── Trainer Videos ────────────────────────────────────────── */
        .videos-section { background: var(--white); }
        .videos-card {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: var(--radius-lg);
            padding: clamp(32px, 5vw, 56px);
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 40px;
            align-items: center;
            position: relative; overflow: hidden;
        }
        .videos-card::before {
            content: '';
            position: absolute; inset: 0;
            background-image: radial-gradient(circle, rgba(255,255,255,.04) 1px, transparent 1px);
            background-size: 28px 28px;
        }
        .videos-card-content { position: relative; z-index: 1; }
        .videos-card h2 {
            font-size: clamp(1.4rem, 3vw, 2rem);
            font-weight: 800; color: var(--white);
            letter-spacing: -.03em; margin-bottom: 12px;
        }
        .videos-card p { font-size: .95rem; color: rgba(255,255,255,.65); max-width: 500px; line-height: 1.75; }
        .btn-videos {
            position: relative; z-index: 1; flex-shrink: 0;
            display: inline-flex; align-items: center; gap: 12px;
            padding: 16px 30px;
            background: #FF0000; color: var(--white);
            font-size: .95rem; font-weight: 700;
            text-decoration: none; border-radius: var(--radius-sm);
            white-space: nowrap;
            transition: background .2s, transform .2s;
            box-shadow: 0 6px 24px rgba(255,0,0,.35);
        }
        .btn-videos:hover { background: #cc0000; transform: translateY(-2px); }
        .btn-videos i { font-size: 1.2rem; }

        /* ── CTA Strip ─────────────────────────────────────────────── */
        .cta-strip {
            background: var(--blue-tint);
            padding: clamp(56px,7vw,88px) 0;
            text-align: center;
        }
        .cta-strip h2 {
            font-size: clamp(1.7rem, 3vw, 2.4rem);
            font-weight: 800; color: var(--blue);
            letter-spacing: -.03em; margin-bottom: 14px;
        }
        .cta-strip p { font-size: 1rem; color: var(--muted); max-width: 460px; margin: 0 auto 36px; }
        .btn-cta {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 16px 36px;
            background: var(--blue); color: var(--white);
            font-size: 1rem; font-weight: 700;
            text-decoration: none; border-radius: var(--radius-sm);
            box-shadow: 0 8px 28px rgba(5,23,159,.3);
            transition: background .2s, transform .2s, box-shadow .2s;
        }
        .btn-cta:hover { background: var(--blue-dk); transform: translateY(-2px); box-shadow: 0 12px 36px rgba(5,23,159,.38); }

        /* ── Footer ─────────────────────────────────────────────────── */
        .footer { background: #080d1e; color: rgba(255,255,255,.55); padding: 60px 0 0; }
        .footer-grid {
            display: grid;
            grid-template-columns: 2.5fr 1fr 1fr;
            gap: 56px;
            padding-bottom: 48px;
        }
        .footer-brand { display: flex; gap: 14px; align-items: flex-start; margin-bottom: 14px; }
        .footer-brand img { height: 38px; filter: brightness(0) invert(1); flex-shrink: 0; }
        .footer-brand-t strong { display: block; font-size: .9rem; font-weight: 700; color: #fff; }
        .footer-brand-t span   { font-size: .75rem; }
        .footer-about { font-size: .82rem; line-height: 1.75; max-width: 380px; }
        .footer-project {
            margin-top: 18px; font-size: .75rem;
            padding: 12px 14px;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: var(--radius-sm);
            line-height: 1.65; color: rgba(255,255,255,.5);
        }
        .footer-col h4 {
            font-size: .72rem; font-weight: 700; color: rgba(255,255,255,.4);
            text-transform: uppercase; letter-spacing: .1em;
            margin-bottom: 18px;
        }
        .footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 11px; }
        .footer-col ul li { font-size: .82rem; }
        .footer-col ul li a { color: rgba(255,255,255,.5); text-decoration: none; transition: color .2s; }
        .footer-col ul li a:hover { color: #fff; }
        .footer-col ul li span { color: rgba(255,255,255,.35); }
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,.07);
            padding: 22px 0;
            display: flex; justify-content: space-between;
            align-items: center; flex-wrap: wrap; gap: 10px;
            font-size: .77rem;
        }
        .footer-bottom a { color: rgba(255,255,255,.35); text-decoration: none; transition: color .2s; }
        .footer-bottom a:hover { color: rgba(255,255,255,.8); }

        /* ── Responsive ─────────────────────────────────────────────── */
        @media (max-width: 1024px) {
            .hero-inner    { grid-template-columns: 1fr; }
            .hero-cards    { display: none; }
            .hero-desc     { max-width: none; }
            .features-grid { grid-template-columns: repeat(2, 1fr); }
            .coverage-inner { grid-template-columns: 1fr; gap: 40px; }
            .momi-inner    { grid-template-columns: 1fr; }
            .footer-grid   { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .nav-hamburger { display: flex; }
            .gallery-grid  { grid-template-columns: repeat(2, 1fr); grid-auto-rows: 180px; }
            .gallery-item:nth-child(1),
            .gallery-item:nth-child(6),
            .gallery-item:nth-child(11) { grid-row: span 1; }
            .videos-card { grid-template-columns: 1fr; gap: 24px; }
            .districts-grid { grid-template-columns: repeat(2, 1fr); }
            .momi-stats { grid-template-columns: repeat(3, 1fr); }
            .hero-stats { display: none; }
            .footer-grid { grid-template-columns: 1fr; gap: 32px; }
        }
        @media (max-width: 540px) {
            .features-grid { grid-template-columns: 1fr; }
            .gallery-grid  { grid-template-columns: repeat(2, 1fr); grid-auto-rows: 150px; }
            .momi-stats    { grid-template-columns: repeat(3, 1fr); }
            .footer-bottom { flex-direction: column; text-align: center; }
        }

        /* Mobile nav drawer */
        .mobile-nav {
            display: none; position: fixed; inset: 0; z-index: 800;
            background: rgba(8,13,30,.97);
            flex-direction: column; align-items: center; justify-content: center;
            gap: 10px;
        }
        .mobile-nav.open { display: flex; }
        .mobile-nav a {
            font-size: 1.2rem; font-weight: 600; color: var(--white);
            text-decoration: none; padding: 14px 24px; border-radius: var(--radius-sm);
            transition: background .2s;
        }
        .mobile-nav a:hover { background: rgba(255,255,255,.08); }
        .mobile-nav-close {
            position: absolute; top: 20px; right: 24px;
            background: none; border: none; color: rgba(255,255,255,.6);
            font-size: 1.5rem; cursor: pointer;
        }
    </style>
</head>
<body>

    {{-- ── MOBILE MENU ──────────────────────────────────────────── --}}
    <div class="mobile-nav" id="mobileNav">
        <button class="mobile-nav-close" id="mobileClose" aria-label="Close menu">
            <i class="fa fa-times"></i>
        </button>
        <a href="#about"    onclick="closeMobileNav()">About</a>
        <a href="#features" onclick="closeMobileNav()">Features</a>
        <a href="#gallery"  onclick="closeMobileNav()">Gallery</a>
        <a href="#coverage" onclick="closeMobileNav()">Coverage</a>
        <a href="{{ url('/auth/login') }}" style="margin-top:16px; background:var(--blue); padding:14px 36px;">Login to Dashboard</a>
    </div>

    {{-- ── NAVBAR ──────────────────────────────────────────────── --}}
    <nav class="nav" id="mainNav" role="navigation" aria-label="Main navigation">
        <div class="container">
            <div class="nav-inner">
                <a href="/" class="nav-brand" aria-label="FAO FFS MIS Home">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="FAO FFS MIS">
                    <div class="nav-brand-text">
                        <div class="nav-brand-name">FAO FFS MIS</div>
                        <div class="nav-brand-sub">Karamoja, Uganda</div>
                    </div>
                </a>
                <ul class="nav-links" role="list">
                    <li><a href="#about">About</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#gallery">Gallery</a></li>
                    <li><a href="#coverage">Coverage</a></li>
                </ul>
                <a href="{{ url('/auth/login') }}" class="btn-nav">
                    <i class="fa fa-sign-in-alt"></i> Login
                </a>
                <button class="nav-hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </nav>

    {{-- ── HERO ────────────────────────────────────────────────── --}}
    <section class="hero" id="top">
        <div class="hero-bg"></div>
        <div class="container">
            <div class="hero-inner">
                <div>
                    <div class="hero-badge">
                        <i class="fa fa-circle-dot" style="font-size:.6rem"></i>
                        FOSTER Programme &mdash; Karamoja Subregion, Uganda
                    </div>
                    <h1 class="hero-title">
                        Field Farmer School<br>
                        <span>Management</span><br>
                        Information System
                    </h1>
                    <p class="hero-desc">
                        A unified digital platform for implementing partners and facilitators to
                        manage VSLA groups, training sessions, KPI performance, and farmer
                        profiles across 9 districts of Karamoja.
                    </p>
                    <div class="hero-actions">
                        <a href="{{ url('/auth/login') }}" class="btn-hero-primary">
                            <i class="fa fa-sign-in-alt"></i> Login to Dashboard
                        </a>
                        <a href="https://play.google.com/store/apps/details?id=com.momulimisa.ffsmis&hl=en"
                           target="_blank" rel="noopener noreferrer" class="btn-hero-outline">
                            <i class="fa-brands fa-google-play"></i> Get Mobile App
                        </a>
                        <a href="#about" class="btn-hero-outline" style="border-color:rgba(255,255,255,.22); opacity:.7; font-size:.82rem; padding:10px 18px;">
                            <i class="fa fa-chevron-down"></i> Learn More
                        </a>
                    </div>
                    <div class="hero-stats">
                        <div class="hero-stat">
                            <div class="hero-stat-val">9</div>
                            <div class="hero-stat-lbl">Districts</div>
                        </div>
                        <div class="hero-stat">
                            <div class="hero-stat-val">VSLA</div>
                            <div class="hero-stat-lbl">Savings Groups</div>
                        </div>
                        <div class="hero-stat">
                            <div class="hero-stat-val">FFS</div>
                            <div class="hero-stat-lbl">Field Schools</div>
                        </div>
                    </div>
                </div>
                <div class="hero-cards" aria-hidden="true">
                    <div class="hero-card">
                        <div class="hero-card-icon"><i class="fa fa-chart-line"></i></div>
                        <div>
                            <div class="hero-card-name">KPI Tracking</div>
                            <div class="hero-card-val">Real-time scorecards</div>
                        </div>
                    </div>
                    <div class="hero-card">
                        <div class="hero-card-icon"><i class="fa fa-piggy-bank"></i></div>
                        <div>
                            <div class="hero-card-name">VSLA Management</div>
                            <div class="hero-card-val">Savings &amp; Loan cycles</div>
                        </div>
                    </div>
                    <div class="hero-card">
                        <div class="hero-card-icon"><i class="fa fa-mobile-screen-button"></i></div>
                        <div>
                            <div class="hero-card-name">Mobile-first</div>
                            <div class="hero-card-val">Offline field capture</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── ABOUT ───────────────────────────────────────────────── --}}
    <section class="section" id="about">
        <div class="container">
            <div class="section-header">
                <span class="eyebrow">About the System</span>
                <h2 class="section-title">Built for programme managers,<br>facilitators &amp; field teams</h2>
                <p class="section-lead">FAO FFS MIS centralizes data collection, reporting and monitoring for the FOSTER programme — reducing paperwork and giving decision-makers real-time visibility across all implementing partners.</p>
            </div>

            <div class="features-grid" id="features">
                @php
                $features = [
                    ['#eef2ff','#4f46e5','fa fa-piggy-bank','VSLA Group Management','Full lifecycle management of Village Savings and Loan Associations — members, cycles, meetings, loans and end-of-cycle shareouts with double-entry accounting.'],
                    ['#f0fdf4','#16a34a','fa fa-seedling','Farmer Field Schools','Track FFS groups, training sessions, attendance, GAP resolutions and AESA livestock observations — all linked to facilitators and implementing partners.'],
                    ['#fff7ed','#ea580c','fa fa-chart-bar','KPI Monitoring','Weekly scorecards for every facilitator and IP. Benchmark targets, trend charts and performance alerts — real-time visibility for programme managers.'],
                    ['#f0f9ff','#0284c7','fa fa-id-card','Farmer Profiling','Complete member profiles with household data, national ID, group roles, onboarding progress and full location hierarchy from village to district.'],
                    ['#fdf4ff','#9333ea','fa fa-tags','Market Price Tracking','Record and monitor commodity prices across districts and sub-counties, helping farmers and facilitators make informed decisions about selling.'],
                    ['#fff1f2','#e11d48','fa fa-mobile-screen-button','Offline Mobile App','Facilitators capture meetings, attendance and profiles in the field without internet. Data syncs automatically when connectivity is restored.'],
                ];
                @endphp
                @foreach($features as $f)
                <div class="feature-card">
                    <div class="fi" style="background:{{ $f[0] }}; color:{{ $f[1] }}">
                        <i class="{{ $f[2] }}"></i>
                    </div>
                    <h3>{{ $f[3] }}</h3>
                    <p>{{ $f[4] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── GALLERY ─────────────────────────────────────────────── --}}
    <section class="section gallery-section" id="gallery">
        <div class="container">
            <div class="section-header">
                <span class="eyebrow">Field Activities</span>
                <h2 class="section-title">FOSTER Programme in Action</h2>
                <p class="section-lead">Farmer Field Schools, VSLA meetings and agricultural training sessions across Karamoja Subregion.</p>
            </div>

            <div class="gallery-grid" role="list">
                @php
                $photos = range(1, 16);
                $captions = [
                    'VSLA group meeting — Karamoja, Uganda',
                    'Farmer training session facilitated in the field',
                    'Agricultural demonstration for FOSTER participants',
                    'Group savings cycle — member attendance recorded',
                    'Facilitator conducting FFS training with farmers',
                    'Field school participants learning crop management',
                    'Community gathering for programme orientation',
                    'VSLA members reviewing savings records',
                    'Farmer Field School — practical demonstrations',
                    'Training session on sustainable agriculture',
                    'Implementing partner programme activities',
                    'Community engagement in Karamoja Sub-region',
                    'Farmer profiling and registration on mobile app',
                    'VSLA group leadership — chairperson facilitating meeting',
                    'AESA livestock observation and data collection',
                    'FOSTER programme closure and certificate awards',
                ];
                @endphp
                @foreach($photos as $i)
                <a
                    href="{{ asset('assets/gallery/foster-' . str_pad($i, 2, '0', STR_PAD_LEFT) . '.jpg') }}"
                    class="gallery-item glightbox"
                    data-gallery="foster"
                    data-description="{{ $captions[$i-1] }}"
                    role="listitem"
                    aria-label="{{ $captions[$i-1] }}"
                >
                    <img
                        src="{{ asset('assets/gallery/foster-' . str_pad($i, 2, '0', STR_PAD_LEFT) . '.jpg') }}"
                        alt="{{ $captions[$i-1] }}"
                        loading="lazy"
                    >
                    <div class="gallery-overlay">
                        <div class="gallery-zoom"><i class="fa fa-expand"></i></div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── COVERAGE ────────────────────────────────────────────── --}}
    <section class="section coverage-section" id="coverage">
        <div class="container">
            <div class="coverage-inner">
                <div>
                    <span class="eyebrow">Programme Coverage</span>
                    <h2>Serving 9 Districts<br>in Karamoja Subregion</h2>
                    <p>The FOSTER programme (Food Security and Resilience in Karamoja) operates across the entire Karamoja Sub-region of Uganda — one of the country's most food-insecure areas — supporting farming communities through agricultural training, village savings groups and market linkages.</p>
                    <p>Funded by the European Union and implemented by FAO Uganda under project reference <strong style="color:rgba(255,255,255,.9)">UNJP/UGA/068/EC</strong>.</p>
                    <div class="tag-row">
                        <span class="tag">UNJP/UGA/068/EC</span>
                        <span class="tag">FAO Uganda</span>
                        <span class="tag">European Union</span>
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
                            <i class="fa fa-location-dot"></i> {{ $d }}
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── M-OMULIMISA ─────────────────────────────────────────── --}}
    <section class="section momi-section" id="developer">
        <div class="container">
            <div class="momi-inner">
                <div>
                    <div class="momi-badge">
                        <i class="fa fa-code"></i> System Developer &amp; Technical Implementer
                    </div>
                    <h2 class="section-title">Built by M-Omulimisa</h2>
                    <p>
                        <strong>M-Omulimisa</strong> is a Ugandan agri-tech company dedicated to connecting
                        smallholder farmers with digital agricultural services — from AI-powered extension
                        advice and market price information to VSLA digitization and village agent networks.
                    </p>
                    <p>
                        M-Omulimisa designed and built this FAO FFS MIS platform as the principal technical
                        implementer of the FOSTER programme's digital component, delivering tools that
                        help facilitators, implementing partners and programme managers work more
                        effectively in the field.
                    </p>
                    <div class="momi-stats">
                        <div class="momi-stat">
                            <div class="momi-stat-val">13K+</div>
                            <div class="momi-stat-lbl">Farmers</div>
                        </div>
                        <div class="momi-stat">
                            <div class="momi-stat-val">51</div>
                            <div class="momi-stat-lbl">Districts</div>
                        </div>
                        <div class="momi-stat">
                            <div class="momi-stat-val">UG</div>
                            <div class="momi-stat-lbl">Uganda</div>
                        </div>
                    </div>
                    <a href="https://m-omulimisa.com" target="_blank" rel="noopener noreferrer" class="btn-momi">
                        <i class="fa fa-arrow-up-right-from-square"></i> Visit M-Omulimisa
                    </a>
                </div>
                <div class="momi-visual">
                    <div class="momi-visual-content">
                        <div class="momi-visual-logo">M-<span>Omulimisa</span></div>
                        <div class="momi-visual-tag">Connecting Farmers &mdash; Uganda</div>
                        <div class="momi-visual-items">
                            <div class="momi-vi"><i class="fa fa-robot"></i> AI-Powered Extension &amp; Advisory</div>
                            <div class="momi-vi"><i class="fa fa-coins"></i> VSLA Digitization (DigiSave)</div>
                            <div class="momi-vi"><i class="fa fa-chart-line"></i> Market Price &amp; Weather Information</div>
                            <div class="momi-vi"><i class="fa fa-mobile-screen-button"></i> Offline-capable Mobile Applications</div>
                            <div class="momi-vi"><i class="fa fa-users"></i> Village Agent Network</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── TRAINER VIDEOS ──────────────────────────────────────── --}}
    <section class="section videos-section">
        <div class="container">
            <div class="videos-card">
                <div class="videos-card-content">
                    <div class="eyebrow" style="color:var(--gold)">Training Resources</div>
                    <h2>Facilitator Training Videos</h2>
                    <p>A curated playlist of training videos for FAO FFS facilitators covering system usage, field data collection, VSLA meeting recording and mobile app operation. Designed for use during induction and ongoing support.</p>
                </div>
                <a
                    href="https://www.youtube.com/watch?v=TFZT4LEVv8Y&list=PLOR5hj0X3WPe72-07mXzilJZ7kElNPQr2"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="btn-videos"
                    aria-label="Watch facilitator training videos on YouTube"
                >
                    <i class="fa-brands fa-youtube"></i> Watch Playlist
                </a>
            </div>
        </div>
    </section>

    {{-- ── CTA STRIP ───────────────────────────────────────────── --}}
    <section class="cta-strip">
        <div class="container">
            <h2>Ready to get started?</h2>
            <p>Log in to the web dashboard or download the mobile app for offline field work.</p>
            <div style="display:flex; gap:14px; justify-content:center; flex-wrap:wrap;">
                <a href="{{ url('/auth/login') }}" class="btn-cta">
                    <i class="fa fa-sign-in-alt"></i> Login to Dashboard
                </a>
                <a href="https://play.google.com/store/apps/details?id=com.momulimisa.ffsmis&hl=en"
                   target="_blank" rel="noopener noreferrer"
                   class="btn-cta" style="background:#1a1f36; box-shadow:0 8px 28px rgba(0,0,0,.2);">
                    <i class="fa-brands fa-google-play"></i> Download on Google Play
                </a>
            </div>
        </div>
    </section>

    {{-- ── FOOTER ──────────────────────────────────────────────── --}}
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div class="footer-brand">
                        <img src="{{ asset('assets/images/logo.png') }}" alt="FAO FFS MIS">
                        <div class="footer-brand-t">
                            <strong>FAO FFS MIS</strong>
                            <span>Field Farmer School Management Information System</span>
                        </div>
                    </div>
                    <p class="footer-about">
                        A digital platform supporting the FOSTER programme's goal of improving
                        food security and resilience among farming communities in the Karamoja
                        Sub-region of Uganda.
                    </p>
                    <div class="footer-project">
                        Project: UNJP/UGA/068/EC &mdash; FOSTER<br>
                        Food Security and Resilience in Karamoja, Uganda<br>
                        Funded by the European Union &nbsp;|&nbsp; Implemented by FAO Uganda
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Programme</h4>
                    <ul>
                        <li><span>FAO Uganda</span></li>
                        <li><span>European Union</span></li>
                        <li><span>Karamoja Subregion</span></li>
                        <li><span>9 Districts Covered</span></li>
                        <li><span>UNJP/UGA/068/EC</span></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>System</h4>
                    <ul>
                        <li><a href="{{ url('/auth/login') }}">Login to Dashboard</a></li>
                        <li><a href="https://play.google.com/store/apps/details?id=com.momulimisa.ffsmis&hl=en" target="_blank" rel="noopener">Mobile App &mdash; Google Play</a></li>
                        <li><a href="https://www.youtube.com/watch?v=TFZT4LEVv8Y&list=PLOR5hj0X3WPe72-07mXzilJZ7kElNPQr2" target="_blank" rel="noopener">Training Videos</a></li>
                        <li><a href="https://m-omulimisa.com" target="_blank" rel="noopener">M-Omulimisa</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <span>&copy; {{ date('Y') }} FAO FFS MIS &mdash; FOSTER Programme, Uganda. All rights reserved.</span>
                <a href="https://m-omulimisa.com" target="_blank" rel="noopener noreferrer">
                    Powered by M-Omulimisa &mdash; Connecting Farmers
                </a>
            </div>
        </div>
    </footer>

    {{-- GLightbox --}}
    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>

    <script>
    (function () {
        'use strict';

        /* ── Navbar scroll class ─────────────────────────── */
        var nav = document.getElementById('mainNav');
        var onScroll = function () {
            nav.classList.toggle('scrolled', window.scrollY > 20);
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();

        /* ── Mobile nav ──────────────────────────────────── */
        var mobileNav = document.getElementById('mobileNav');
        document.getElementById('hamburger').addEventListener('click', function () {
            mobileNav.classList.add('open');
            this.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
        });
        document.getElementById('mobileClose').addEventListener('click', closeMobileNav);
        mobileNav.addEventListener('click', function (e) {
            if (e.target === mobileNav) closeMobileNav();
        });
        function closeMobileNav() {
            mobileNav.classList.remove('open');
            document.getElementById('hamburger').setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }
        window.closeMobileNav = closeMobileNav;

        /* ── Keyboard trap for mobile nav ────────────────── */
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && mobileNav.classList.contains('open')) {
                closeMobileNav();
            }
        });

        /* ── GLightbox gallery ───────────────────────────── */
        GLightbox({
            selector:        '.glightbox',
            touchNavigation: true,
            loop:            true,
            autoplayVideos:  false,
            openEffect:      'fade',
            closeEffect:     'fade',
            slideEffect:     'slide',
            moreLength:      0,
            touchFollowAxis: true,
            svg: {
                close:   '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
                next:    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>',
                prev:    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>',
            }
        });

        /* ── Smooth anchor scroll ────────────────────────── */
        document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
            anchor.addEventListener('click', function (e) {
                var target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    var top = target.getBoundingClientRect().top + window.scrollY - 76;
                    window.scrollTo({ top: top, behavior: 'smooth' });
                }
            });
        });

        /* ── Fade-in on scroll (Intersection Observer) ───── */
        if ('IntersectionObserver' in window) {
            var fadeStyle = document.createElement('style');
            fadeStyle.textContent = '.fade-in{opacity:0;transform:translateY(24px);transition:opacity .55s ease,transform .55s ease}.fade-in.visible{opacity:1;transform:none}';
            document.head.appendChild(fadeStyle);

            var targets = document.querySelectorAll(
                '.feature-card, .gallery-item, .district-chip, .momi-stat, .hero-card'
            );
            targets.forEach(function (el) { el.classList.add('fade-in'); });

            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12 });

            targets.forEach(function (el) { io.observe(el); });
        }
    })();
    </script>

</body>
</html>
