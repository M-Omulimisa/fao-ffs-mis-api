<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - {{ config('app.name', 'FAO FFS MIS') }}</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/logo.png') }}">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Subtle squared box pattern */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                repeating-linear-gradient(0deg, transparent, transparent 39px, rgba(0, 0, 0, 0.08) 39px, rgba(0, 0, 0, 0.08) 40px),
                repeating-linear-gradient(90deg, transparent, transparent 39px, rgba(0, 0, 0, 0.08) 39px, rgba(0, 0, 0, 0.08) 40px);
            background-size: 40px 40px;
        }

        .login-container {
            background: white;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 440px;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .login-header {
            background: #05179F;
            padding: 50px 40px 45px;
            text-align: center;
            color: white;
        }

        .logo-container {
            margin-bottom: 25px;
        }

        .logo {
            width: 110px;
            height: 110px;
            margin: 0 auto;
            display: block;
            background: white;
            padding: 8px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .login-header h1 {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .login-header p {
            font-size: 15px;
            opacity: 0.95;
            font-weight: 400;
            letter-spacing: 0.3px;
        }

        .login-body {
            padding: 45px 40px;
        }

        .alert {
            padding: 14px 18px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            border-left: 4px solid;
        }

        .alert-danger {
            background: #fff5f5;
            color: #c53030;
            border-left-color: #e53e3e;
        }

        .alert-success {
            background: #f0fff4;
            color: #2f855a;
            border-left-color: #38a169;
        }

        .alert-icon {
            font-size: 18px;
            margin-top: 1px;
            flex-shrink: 0;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #2d3748;
            font-size: 14px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .form-control {
            width: 100%;
            padding: 15px 16px;
            border: 2px solid #e2e8f0;
            font-size: 15px;
            transition: all 0.2s ease;
            font-family: inherit;
            background: #f7fafc;
        }

        .form-control:hover {
            border-color: #cbd5e0;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: #05179F;
            background: white;
            box-shadow: 0 0 0 3px rgba(5, 23, 159, 0.1);
        }

        .form-control::placeholder {
            color: #a0aec0;
        }

        .form-control.is-invalid {
            border-color: #fc8181;
            background: #fff5f5;
        }

        .form-control.is-invalid:focus {
            border-color: #e53e3e;
            box-shadow: 0 0 0 3px rgba(229, 62, 62, 0.1);
        }

        .invalid-feedback {
            color: #e53e3e;
            font-size: 13px;
            margin-top: 8px;
            display: block;
            font-weight: 500;
        }

        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #718096;
            font-size: 16px;
            padding: 6px;
            transition: color 0.2s ease;
            line-height: 1;
        }

        .password-toggle:hover {
            color: #05179F;
        }

        .password-toggle i {
            pointer-events: none;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #05179F;
        }

        .remember-me label {
            font-size: 14px;
            color: #4a5568;
            cursor: pointer;
            user-select: none;
            font-weight: 500;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: #05179F;
            color: white;
            border: none;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            box-shadow: 0 4px 14px rgba(5, 23, 159, 0.3);
        }

        .btn-login:hover {
            background: #040f70;
            box-shadow: 0 6px 20px rgba(5, 23, 159, 0.4);
            transform: translateY(-1px);
        }

        .btn-login:active {
            background: #030b50;
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(5, 23, 159, 0.3);
        }

        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .login-footer {
            text-align: center;
            padding: 24px 40px;
            background: #f7fafc;
            border-top: 2px solid #e2e8f0;
        }

        .login-footer p {
            color: #718096;
            font-size: 13px;
            font-weight: 500;
        }

        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-header {
                padding: 40px 30px 35px;
            }

            .login-body {
                padding: 35px 30px;
            }

            .login-footer {
                padding: 20px 30px;
            }

            .logo {
                width: 90px;
                height: 90px;
            }

            .login-header h1 {
                font-size: 23px;
            }

            .form-control {
                padding: 13px 14px;
                font-size: 14px;
            }

            .btn-login {
                padding: 14px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Header -->
        <div class="login-header">
            <div class="logo-container">
                <img src="{{ asset('assets/images/logo.png') }}" alt="{{ config('app.name') }} Logo" class="logo">
            </div>
            <h1>{{ config('app.name', 'DTEHM Insurance') }}</h1>
            <p>Dashboard Login</p>
        </div>

        <!-- Body -->
        <div class="login-body">
            <!-- Success Message -->
            @if(session('success'))
                <div class="alert alert-success">
                    <span class="alert-icon">✓</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Error Messages -->
            @if($errors->any())
                <div class="alert alert-danger">
                    <span class="alert-icon">⚠</span>
                    <div>
                        @if($errors->has('username'))
                            <div>{{ $errors->first('username') }}</div>
                        @elseif($errors->has('password'))
                            <div>{{ $errors->first('password') }}</div>
                        @else
                            <div>{{ $errors->first() }}</div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Login Form -->
            <form action="{{ route('login.post') }}" method="POST" id="loginForm">
                @csrf

                <!-- Username Field -->
                <div class="form-group">
                    <label for="username" class="form-label">Username, Email or Phone</label>
                    <input 
                        type="text" 
                        name="username" 
                        id="username" 
                        class="form-control {{ $errors->has('username') ? 'is-invalid' : '' }}" 
                        placeholder="Enter username, email or phone number"
                        value="{{ old('username') }}"
                        required
                        autofocus
                    >
                    @error('username')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" 
                            placeholder="Enter your password"
                            required
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Toggle password visibility">
                            <i class="fa-regular fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                    @error('password')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="remember-forgot">
                    <div class="remember-me">
                        <input 
                            type="checkbox" 
                            name="remember" 
                            id="remember" 
                            {{ old('remember') ? 'checked' : '' }}
                        >
                        <label for="remember">Remember me</label>
                    </div>
                    <!-- <a href="#" class="forgot-link">Forgot password?</a> -->
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-login" id="submitBtn">
                    <span id="btnText">Sign In</span>
                </button>
            </form>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fa-regular fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fa-regular fa-eye';
            }
        }

        // Form submission with loading state
        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');

        loginForm.addEventListener('submit', function(e) {
            // Disable button and show loading state
            submitBtn.disabled = true;
            btnText.innerHTML = '<span class="spinner"></span> Signing in...';
        });

        // Auto-focus on username field
        window.addEventListener('load', function() {
            document.getElementById('username').focus();
        });

        // Clear error messages on input
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
                const feedback = this.parentElement.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
