<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — {{ config('app.name', 'FAO FFS MIS') }}</title>

    <link rel="shortcut icon" href="{{ asset('assets/images/logo.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --blue:      #05179F;
            --blue-dark: #040f70;
            --blue-mid:  #0a2fd6;
            --blue-soft: rgba(5,23,159,.08);
        }

        html, body {
            height: 100%;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f0f4ff;
        }

        /* ── Layout ─────────────────────────────────────────────── */
        .page {
            display: flex;
            min-height: 100vh;
        }

        /* ── Left panel ──────────────────────────────────────────── */
        .panel-left {
            flex: 0 0 52%;
            position: relative;
            background: var(--blue);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 56px;
        }

        /* decorative circles */
        .panel-left::before,
        .panel-left::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: .12;
            background: white;
        }
        .panel-left::before {
            width: 480px; height: 480px;
            bottom: -140px; right: -120px;
        }
        .panel-left::after {
            width: 260px; height: 260px;
            top: -80px; left: -60px;
        }

        .inner-circle {
            position: absolute;
            border-radius: 50%;
            background: white;
            opacity: .07;
            width: 320px; height: 320px;
            bottom: 100px; right: -80px;
        }

        /* grid dot pattern overlay */
        .panel-left .dots {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, rgba(255,255,255,.18) 1px, transparent 1px);
            background-size: 28px 28px;
            z-index: 0;
        }

        .panel-left > * { position: relative; z-index: 1; }

        /* brand mark */
        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-logo {
            width: 54px;
            height: 54px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .brand-logo img {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        .brand-name {
            color: white;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: .6px;
            text-transform: uppercase;
            line-height: 1.25;
        }
        .brand-name span {
            display: block;
            font-size: 11px;
            font-weight: 400;
            opacity: .75;
            text-transform: none;
            letter-spacing: .3px;
        }

        /* hero copy */
        .hero {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 40px 0 20px;
        }

        .hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,.15);
            color: rgba(255,255,255,.9);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .8px;
            text-transform: uppercase;
            padding: 6px 14px;
            border-radius: 2px;
            margin-bottom: 28px;
            width: fit-content;
        }

        .hero h1 {
            color: white;
            font-size: 42px;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -1px;
            margin-bottom: 20px;
        }

        .hero h1 em {
            font-style: normal;
            color: #7eb8ff;
        }

        .hero p {
            color: rgba(255,255,255,.72);
            font-size: 15.5px;
            line-height: 1.7;
            max-width: 380px;
        }

        /* stat pills */
        .stats {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 36px;
        }

        .stat {
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 2px;
            padding: 14px 20px;
            text-align: center;
            min-width: 120px;
        }

        .stat-value {
            color: white;
            font-size: 22px;
            font-weight: 700;
            display: block;
            line-height: 1;
            margin-bottom: 4px;
        }

        .stat-label {
            color: rgba(255,255,255,.6);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .6px;
        }

        /* left footer */
        .panel-left-footer {
            color: rgba(255,255,255,.45);
            font-size: 12px;
        }

        /* ── Right panel ─────────────────────────────────────────── */
        .panel-right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 40px;
            background: white;
        }

        .form-card {
            width: 100%;
            max-width: 420px;
        }

        .form-card-header {
            margin-bottom: 36px;
        }

        .form-card-header h2 {
            font-size: 27px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -.5px;
            margin-bottom: 8px;
        }

        .form-card-header p {
            color: #64748b;
            font-size: 14.5px;
        }

        /* alerts */
        .alert {
            padding: 12px 16px;
            margin-bottom: 24px;
            font-size: 13.5px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            border-radius: 2px;
            border-left: 3px solid;
        }

        .alert-danger  { background:#fff5f5; color:#c53030; border-color:#e53e3e; }
        .alert-success { background:#f0fff4; color:#2f855a; border-color:#38a169; }

        /* form elements */
        .form-group { margin-bottom: 20px; }

        .form-label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
            color: #0f172a;
            font-size: 13px;
            letter-spacing: .2px;
        }

        .input-wrap { position: relative; }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 14px;
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            padding: 13px 14px 13px 40px;
            border: 1.5px solid #e2e8f0;
            border-radius: 2px;
            font-size: 14.5px;
            font-family: inherit;
            color: #0f172a;
            background: #f8fafc;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }

        .form-control:hover { border-color: #b6c2d4; background: white; }

        .form-control:focus {
            outline: none;
            border-color: var(--blue);
            background: white;
            box-shadow: 0 0 0 3px rgba(5,23,159,.08);
        }

        .form-control::placeholder { color: #b0bfd0; }

        .form-control.is-invalid { border-color: #fc8181; background: #fff5f5; }

        .invalid-feedback {
            color: #e53e3e;
            font-size: 12.5px;
            margin-top: 6px;
            display: block;
            font-weight: 500;
        }

        .password-toggle {
            position: absolute;
            right: 12px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            cursor: pointer;
            color: #94a3b8;
            font-size: 15px;
            padding: 4px 6px;
            transition: color .2s;
            line-height: 1;
        }
        .password-toggle:hover { color: var(--blue); }

        /* remember row */
        .remember-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
        }

        .remember-row input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: var(--blue);
            cursor: pointer;
        }

        .remember-row label {
            font-size: 13.5px;
            color: #4b5563;
            cursor: pointer;
            user-select: none;
        }

        /* submit */
        .btn-login {
            width: 100%;
            padding: 14px;
            background: var(--blue);
            color: white;
            border: none;
            border-radius: 2px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            letter-spacing: .4px;
            transition: background .2s, box-shadow .2s, transform .15s;
            box-shadow: 0 4px 14px rgba(5,23,159,.28);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-login:hover {
            background: var(--blue-dark);
            box-shadow: 0 6px 18px rgba(5,23,159,.36);
            transform: translateY(-1px);
        }

        .btn-login:active { transform: translateY(0); }
        .btn-login:disabled { opacity: .6; cursor: not-allowed; transform: none; }

        /* divider */
        .form-footer {
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid #f1f5f9;
            text-align: center;
            color: #94a3b8;
            font-size: 12px;
        }

        /* spinner */
        .spinner {
            display: inline-block;
            width: 14px; height: 14px;
            border: 2px solid rgba(255,255,255,.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin .6s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Mobile ──────────────────────────────────────────────── */
        @media (max-width: 860px) {
            .page { flex-direction: column; }

            .panel-left {
                flex: 0 0 auto;
                padding: 36px 30px;
            }

            .hero h1 { font-size: 28px; }
            .hero p  { font-size: 14px; }
            .stats   { display: none; }

            .panel-right {
                padding: 40px 24px;
                background: #f8fafc;
            }
        }

        @media (max-width: 480px) {
            .panel-left { padding: 28px 24px; }
            .hero { padding: 24px 0 12px; }
        }
    </style>
</head>
<body>
<div class="page">

    <!-- ── Left brand panel ── -->
    <div class="panel-left">
        <div class="dots"></div>
        <div class="inner-circle"></div>

        <!-- Brand -->
        <div class="brand">
            <div class="brand-logo">
                <img src="{{ asset('assets/images/logo.png') }}" alt="FAO Logo">
            </div>
            <div class="brand-name">
                {{ config('app.name', 'FAO FFS MIS') }}
                <span>Management Information System</span>
            </div>
        </div>

        <!-- Hero -->
        <div class="hero">
            <div class="hero-tag">
                <i class="fas fa-seedling"></i>
                Farmer Field School
            </div>

            <h1>Track. Manage.<br><em>Empower</em> farmers.</h1>

            <p>
                A unified platform for monitoring savings groups, loan cycles,
                field activities, and member performance across all implementing partners.
            </p>

            <div class="stats">
                <div class="stat">
                    <span class="stat-value"><i class="fas fa-users" style="font-size:18px"></i></span>
                    <span class="stat-label">Members</span>
                </div>
                <div class="stat">
                    <span class="stat-value"><i class="fas fa-people-group" style="font-size:18px"></i></span>
                    <span class="stat-label">Groups</span>
                </div>
                <div class="stat">
                    <span class="stat-value"><i class="fas fa-coins" style="font-size:18px"></i></span>
                    <span class="stat-label">Savings</span>
                </div>
            </div>
        </div>

        <div class="panel-left-footer">
            &copy; {{ date('Y') }} Food and Agriculture Organization of the United Nations
        </div>
    </div>

    <!-- ── Right form panel ── -->
    <div class="panel-right">
        <div class="form-card">

            <div class="form-card-header">
                <h2>Welcome back</h2>
                <p>Sign in to your account to continue</p>
            </div>

            {{-- Alerts --}}
            @if(session('success'))
                <div class="alert alert-success">
                    <i class="fas fa-check-circle" style="margin-top:2px;flex-shrink:0"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle" style="margin-top:2px;flex-shrink:0"></i>
                    <div>
                        @if($errors->has('username'))
                            {{ $errors->first('username') }}
                        @elseif($errors->has('password'))
                            {{ $errors->first('password') }}
                        @else
                            {{ $errors->first() }}
                        @endif
                    </div>
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" id="loginForm">
                @csrf

                <!-- Username -->
                <div class="form-group">
                    <label for="username" class="form-label">Username, Email or Phone</label>
                    <div class="input-wrap">
                        <i class="fas fa-user input-icon"></i>
                        <input
                            type="text"
                            name="username"
                            id="username"
                            class="form-control {{ $errors->has('username') ? 'is-invalid' : '' }}"
                            placeholder="Enter username, email or phone"
                            value="{{ old('username') }}"
                            required
                            autofocus
                        >
                    </div>
                    @error('username')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock input-icon"></i>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                            placeholder="Enter your password"
                            required
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Toggle password">
                            <i class="fa-regular fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                    @error('password')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Remember me -->
                <div class="remember-row">
                    <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label for="remember">Keep me signed in</label>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-login" id="submitBtn">
                    <span id="btnContent">
                        <i class="fas fa-arrow-right-to-bracket"></i>
                        Sign In
                    </span>
                </button>
            </form>

            <div class="form-footer">
                &copy; {{ date('Y') }} {{ config('app.name') }} &mdash; All rights reserved
            </div>
        </div>
    </div>

</div>

<script>
    function togglePassword() {
        const input = document.getElementById('password');
        const icon  = document.getElementById('toggleIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fa-regular fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'fa-regular fa-eye';
        }
    }

    document.getElementById('loginForm').addEventListener('submit', function () {
        const btn = document.getElementById('submitBtn');
        const content = document.getElementById('btnContent');
        btn.disabled = true;
        content.innerHTML = '<span class="spinner"></span> Signing in…';
    });

    document.querySelectorAll('.form-control').forEach(el => {
        el.addEventListener('input', function () {
            this.classList.remove('is-invalid');
            const fb = this.closest('.input-wrap')?.nextElementSibling;
            if (fb && fb.classList.contains('invalid-feedback')) fb.style.display = 'none';
        });
    });
</script>
</body>
</html>
