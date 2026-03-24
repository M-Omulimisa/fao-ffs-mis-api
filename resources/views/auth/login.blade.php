<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Sign In &mdash; FAO FFS MIS</title>
    <link rel="shortcut icon" href="{{ asset('assets/images/logo.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --blue:     #05179F;
            --blue-dk:  #03107a;
            --gold:     #F4A71D;
            --text:     #0f172a;
            --muted:    #64748b;
            --border:   #e2e8f0;
            --surface:  #f8fafc;
            --white:    #ffffff;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Layout ───────────────────────────────────────────────── */
        .page { display: flex; min-height: 100svh; }

        /* ── Left panel ───────────────────────────────────────────── */
        .panel-left {
            flex: 0 0 48%;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: clamp(28px, 4vw, 52px) clamp(28px, 4vw, 56px);
            /* Solid blue fallback — always visible even before image loads */
            background-color: var(--blue-dk);
        }

        /* Background photo — applied directly with ::before so no child-div z-index conflict */
        .panel-left::before {
            content: '';
            position: absolute; inset: 0; z-index: 0;
            background-image: url('{{ asset("assets/gallery/foster-09.jpg") }}');
            background-size: cover;
            background-position: center 40%;
        }

        /* Blue overlay on top of photo */
        .panel-left::after {
            content: '';
            position: absolute; inset: 0; z-index: 1;
            background: linear-gradient(
                160deg,
                rgba(3,16,122,.92) 0%,
                rgba(5,23,159,.85) 55%,
                rgba(13,47,224,.78) 100%
            );
        }

        /* All direct children sit above the overlays */
        .panel-left > * { position: relative; z-index: 2; }

        /* Brand row */
        .brand {
            display: flex; align-items: center; gap: 12px;
            text-decoration: none;
        }
        .brand-logo {
            width: 46px; height: 46px; flex-shrink: 0;
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.22);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }
        .brand-logo img { width: 28px; height: 28px; object-fit: contain; }
        .brand-name { font-size: .9rem; font-weight: 700; color: #fff; line-height: 1.2; }
        .brand-sub  { font-size: .65rem; color: rgba(255,255,255,.55); text-transform: uppercase; letter-spacing: .06em; }

        /* Centre tagline */
        .left-center { flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .left-tag {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 5px 13px;
            background: rgba(244,167,29,.18);
            border: 1px solid rgba(244,167,29,.38);
            border-radius: 100px;
            font-size: .68rem; font-weight: 700;
            letter-spacing: .08em; text-transform: uppercase;
            color: var(--gold); width: fit-content; margin-bottom: 24px;
        }
        .left-title {
            color: #fff;
            font-size: clamp(1.9rem, 3vw, 2.9rem);
            font-weight: 900; line-height: 1.1;
            letter-spacing: -.04em;
        }
        .left-title span { color: rgba(255,255,255,.45); }
        .left-sub {
            margin-top: 14px;
            font-size: .9rem; color: rgba(255,255,255,.65); line-height: 1.7;
        }

        /* Bottom */
        .left-foot {
            font-size: .72rem; color: rgba(255,255,255,.35);
            display: flex; justify-content: space-between; align-items: center;
        }
        .left-foot a { color: rgba(255,255,255,.4); text-decoration: none; transition: color .2s; }
        .left-foot a:hover { color: rgba(255,255,255,.8); }

        /* ── Right panel ──────────────────────────────────────────── */
        .panel-right {
            flex: 1;
            display: flex; align-items: center; justify-content: center;
            padding: clamp(32px, 5vw, 64px) clamp(24px, 5vw, 60px);
            background: var(--white);
            overflow-y: auto;
        }

        .form-box { width: 100%; max-width: 380px; }

        /* Back link */
        .back-link {
            display: inline-flex; align-items: center; gap: 7px;
            font-size: .78rem; font-weight: 500; color: var(--muted);
            text-decoration: none; margin-bottom: 40px;
            transition: color .2s;
        }
        .back-link:hover { color: var(--blue); }

        /* Heading */
        .form-head { margin-bottom: 28px; }
        .form-head h1 {
            font-size: 1.65rem; font-weight: 800;
            color: var(--text); letter-spacing: -.03em; margin-bottom: 5px;
        }
        .form-head p { font-size: .85rem; color: var(--muted); }

        /* Alerts */
        .alert {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 11px 14px; border-radius: 7px;
            font-size: .83rem; line-height: 1.5;
            margin-bottom: 22px; border-left: 3px solid;
        }
        .alert i { flex-shrink: 0; margin-top: 1px; }
        .alert-danger  { background: #fff5f5; color: #c53030; border-color: #e53e3e; }
        .alert-success { background: #f0fff4; color: #2f855a; border-color: #38a169; }

        /* Fields */
        .form-group  { margin-bottom: 16px; }
        .form-label  { display: block; font-size: .8rem; font-weight: 600; color: var(--text); margin-bottom: 6px; }
        .field-wrap  { position: relative; }
        .field-icon  {
            position: absolute; left: 12px; top: 50%;
            transform: translateY(-50%);
            color: #94a3b8; font-size: .82rem; pointer-events: none;
        }
        .form-control {
            width: 100%; padding: 11px 11px 11px 36px;
            border: 1.5px solid var(--border); border-radius: 7px;
            font-size: .88rem; font-family: inherit;
            color: var(--text); background: var(--surface);
            transition: border-color .2s, background .2s, box-shadow .2s;
        }
        .form-control::placeholder { color: #b0bec9; }
        .form-control:hover  { border-color: #b6c2d4; background: #fff; }
        .form-control:focus  { outline: none; border-color: var(--blue); background: #fff; box-shadow: 0 0 0 3px rgba(5,23,159,.08); }
        .form-control.is-invalid { border-color: #fc8181; background: #fff5f5; }
        .invalid-msg { display: block; font-size: .76rem; font-weight: 500; color: #e53e3e; margin-top: 4px; }

        /* Password toggle */
        .pw-toggle {
            position: absolute; right: 10px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; color: #94a3b8;
            font-size: .88rem; cursor: pointer; padding: 4px 5px;
            transition: color .2s; line-height: 1;
        }
        .pw-toggle:hover { color: var(--blue); }

        /* Remember */
        .remember { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; }
        .remember input  { width: 14px; height: 14px; accent-color: var(--blue); cursor: pointer; }
        .remember label  { font-size: .82rem; color: #475569; cursor: pointer; user-select: none; }

        /* Password field label row with forgot link */
        .label-row {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 6px;
        }
        .label-row .form-label { margin-bottom: 0; }
        .forgot-link {
            font-size: .76rem; font-weight: 600; color: var(--blue);
            text-decoration: none; transition: color .2s;
        }
        .forgot-link:hover { color: var(--blue-dk); text-decoration: underline; }

        /* Submit */
        .btn-submit {
            width: 100%; padding: 13px;
            background: var(--blue); color: #fff;
            border: none; border-radius: 7px;
            font-size: .93rem; font-weight: 700; font-family: inherit;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 4px 16px rgba(5,23,159,.28);
            transition: background .2s, box-shadow .2s, transform .15s;
        }
        .btn-submit:hover    { background: var(--blue-dk); box-shadow: 0 6px 22px rgba(5,23,159,.36); transform: translateY(-1px); }
        .btn-submit:active   { transform: none; }
        .btn-submit:disabled { opacity: .6; cursor: not-allowed; transform: none; }

        .spinner {
            width: 13px; height: 13px; border-radius: 50%;
            border: 2px solid rgba(255,255,255,.3);
            border-top-color: #fff;
            animation: spin .65s linear infinite;
            display: inline-block;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Form footer */
        .form-foot {
            margin-top: 22px; padding-top: 18px;
            border-top: 1px solid var(--border);
            text-align: center;
            font-size: .72rem; color: #94a3b8;
        }
        .form-foot a { color: #94a3b8; text-decoration: none; transition: color .2s; }
        .form-foot a:hover { color: var(--blue); }

        /* ── Responsive ───────────────────────────────────────────── */
        @media (max-width: 800px) {
            .page         { flex-direction: column; }
            .panel-left   { flex: none; min-height: 200px; padding: 28px 24px; }
            .left-center  { display: none; }
            .left-foot    { display: none; }
            .panel-right  { padding: 32px 24px 48px; }
            .back-link    { margin-bottom: 28px; }
        }
        @media (max-width: 480px) {
            .form-box { max-width: none; }
            .panel-left { min-height: 180px; padding: 24px 20px; }
        }
    </style>
</head>
<body>
<div class="page">

    {{-- ── Left panel ──────────────────────────────────────────── --}}
    <div class="panel-left">

        <a href="/" class="brand" aria-label="FAO FFS MIS — Home">
            <div class="brand-logo">
                <img src="{{ asset('assets/images/logo.png') }}" alt="Logo">
            </div>
            <div>
                <div class="brand-name">FAO FFS MIS</div>
                <div class="brand-sub">Karamoja &mdash; Uganda</div>
            </div>
        </a>

        <div class="left-center">
            <div class="left-tag">FOSTER Programme &mdash; Uganda</div>
            <h2 class="left-title">Track. Manage.<br><span>Empower</span> farmers.</h2>
            <p class="left-sub">VSLA &bull; Farmer Field Schools &bull; KPI Monitoring &bull; Market Prices</p>
        </div>

        <div class="left-foot">
            <span>&copy; {{ date('Y') }} FOSTER &mdash; FAO Uganda</span>
            <a href="https://m-omulimisa.com" target="_blank" rel="noopener">M-Omulimisa</a>
        </div>

    </div>

    {{-- ── Right panel ──────────────────────────────────────────── --}}
    <div class="panel-right">
        <div class="form-box">

            <a href="/" class="back-link">
                <i class="fa fa-arrow-left"></i> Back to home
            </a>

            <div class="form-head">
                <h1>Sign in</h1>
                <p>Enter your credentials to continue</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success" role="alert">
                    <i class="fa fa-circle-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <i class="fa fa-circle-exclamation"></i>
                    <span>
                        @if($errors->has('username'))
                            {{ $errors->first('username') }}
                        @elseif($errors->has('password'))
                            {{ $errors->first('password') }}
                        @else
                            {{ $errors->first() }}
                        @endif
                    </span>
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" id="loginForm" novalidate>
                @csrf

                <div class="form-group">
                    <label for="username" class="form-label">Username or Email</label>
                    <div class="field-wrap">
                        <i class="fa fa-user field-icon"></i>
                        <input
                            type="text"
                            name="username"
                            id="username"
                            class="form-control {{ $errors->has('username') ? 'is-invalid' : '' }}"
                            placeholder="Enter your username or email"
                            value="{{ old('username') }}"
                            autocomplete="username"
                            required
                            autofocus
                        >
                    </div>
                    @error('username')
                        <span class="invalid-msg">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <div class="label-row">
                        <label for="password" class="form-label">Password</label>
                        <a href="{{ route('forgot-password') }}" class="forgot-link">Forgot password?</a>
                    </div>
                    <div class="field-wrap">
                        <i class="fa fa-lock field-icon"></i>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="pw-toggle" id="pwToggle" aria-label="Show or hide password">
                            <i class="fa-regular fa-eye" id="pwIcon"></i>
                        </button>
                    </div>
                    @error('password')
                        <span class="invalid-msg">{{ $message }}</span>
                    @enderror
                </div>

                <div class="remember">
                    <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label for="remember">Keep me signed in</label>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    <span id="btnLabel"><i class="fa fa-right-to-bracket"></i> Sign In</span>
                </button>
            </form>

            <div class="form-foot">
                &copy; {{ date('Y') }} {{ config('app.name') }} &mdash;
                <a href="https://m-omulimisa.com" target="_blank" rel="noopener">Powered by M-Omulimisa</a>
            </div>

        </div>
    </div>

</div>

<script>
(function () {
    'use strict';

    var pwInput = document.getElementById('password');
    var pwIcon  = document.getElementById('pwIcon');

    document.getElementById('pwToggle').addEventListener('click', function () {
        var show = pwInput.type === 'password';
        pwInput.type     = show ? 'text' : 'password';
        pwIcon.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
        this.setAttribute('aria-pressed', String(show));
    });

    document.getElementById('loginForm').addEventListener('submit', function () {
        var btn   = document.getElementById('submitBtn');
        var label = document.getElementById('btnLabel');
        btn.disabled    = true;
        label.innerHTML = '<span class="spinner"></span> Signing in&hellip;';
    });

    document.querySelectorAll('.form-control').forEach(function (el) {
        el.addEventListener('input', function () {
            this.classList.remove('is-invalid');
        });
    });
}());
</script>
</body>
</html>
