<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - {{ config('app.name', 'DTEHM Insurance') }}</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/logo.png') }}">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 420px;
            width: 100%;
        }

        .login-header {
            background: #05179F;
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .logo-container {
            margin-bottom: 20px;
        }

        .logo {
            width: 100px;
            height: 100px;
            margin: 0 auto;
            display: block;
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .login-header h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .login-header p {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 400;
        }

        .login-body {
            padding: 35px 30px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .alert-danger {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .alert-icon {
            font-size: 16px;
            margin-top: 2px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.2s ease;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #05179F;
        }

        .form-control.is-invalid {
            border-color: #dc3545;
        }

        .form-control.is-invalid:focus {
            border-color: #dc3545;
        }

        .invalid-feedback {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }

        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #888;
            font-size: 16px;
            padding: 4px;
        }

        .password-toggle:hover {
            color: #05179F;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #05179F;
        }

        .remember-me label {
            font-size: 14px;
            color: #666;
            cursor: pointer;
            user-select: none;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: #05179F;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .btn-login:hover {
            background: #040f70;
        }

        .btn-login:active {
            background: #030b50;
        }

        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .login-footer {
            text-align: center;
            padding: 20px;
            background: #f9f9f9;
            border-top: 1px solid #e0e0e0;
        }

        .login-footer p {
            color: #666;
            font-size: 13px;
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
            margin-right: 6px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-header {
                padding: 30px 20px;
            }

            .login-body {
                padding: 25px 20px;
            }

            .logo {
                width: 80px;
                height: 80px;
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
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <span id="toggleIcon">👁️</span>
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
                toggleIcon.textContent = '👁️‍🗨️';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = '👁️';
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
