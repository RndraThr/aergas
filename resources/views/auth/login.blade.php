<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - AERGAS System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #2d3748 0%, #4a5568 50%, #1a202c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Animated Background */
        .bg-animation {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .bg-animation::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }

        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            animation: floatUp 6s ease-in-out infinite;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            left: 10%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 60px;
            height: 60px;
            left: 20%;
            animation-delay: 2s;
        }

        .shape:nth-child(3) {
            width: 100px;
            height: 100px;
            left: 70%;
            animation-delay: 4s;
        }

        .shape:nth-child(4) {
            width: 40px;
            height: 40px;
            left: 85%;
            animation-delay: 1s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        @keyframes floatUp {
            0% {
                opacity: 0;
                bottom: -100px;
                transform: translateX(0px) rotate(0deg);
            }
            50% {
                opacity: 1;
                transform: translateX(100px) rotate(180deg);
            }
            100% {
                opacity: 0;
                bottom: 100vh;
                transform: translateX(-100px) rotate(360deg);
            }
        }

        /* Login Container */
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(30, 58, 95, 0.3);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 10;
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: slideIn 0.8s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .logo-container {
            text-align: center;
            margin-bottom: 30px;
            animation: fadeInDown 1s ease-out 0.3s both;
        }

        .logo {
            width: 200px;
            height: auto;
            margin-bottom: 20px;
            filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.3));
            background: rgba(255, 255, 255, 0.9);
            padding: 15px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .system-title {
            font-size: 28px;
            font-weight: 700;
            color: #1e3a5f;
            margin-bottom: 5px;
            background: linear-gradient(135deg, #1e3a5f, #ff6b35);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .system-subtitle {
            font-size: 16px;
            color: #718096;
            font-weight: 500;
            margin-top: 10px;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-form {
            animation: fadeInUp 1s ease-out 0.5s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 15px 20px 15px 50px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            color: #2d3748;
        }

        .form-input:focus {
            outline: none;
            border-color: #ff6b35;
            background: rgba(255, 255, 255, 1);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
            transform: translateY(-2px);
        }

        .form-input::placeholder {
            color: #a0aec0;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 16px;
            transition: color 0.3s ease;
        }

        .form-input:focus + .input-icon {
            color: #ff6b35;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #a0aec0;
            cursor: pointer;
            font-size: 16px;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #ff6b35;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #ff6b35;
        }

        .forgot-password {
            color: #ff6b35;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-password:hover {
            color: #e55a2b;
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #ff6b35 0%, #e55a2b 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 107, 53, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-btn.loading {
            pointer-events: none;
            opacity: 0.8;
        }

        .login-btn .btn-text {
            transition: opacity 0.3s ease;
        }

        .login-btn.loading .btn-text {
            opacity: 0;
        }

        .spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .login-btn.loading .spinner {
            opacity: 1;
        }

        @keyframes spin {
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .error-message {
            background: #fed7d7;
            color: #c53030;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #e53e3e;
            animation: slideInRight 0.3s ease-out;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            color: #718096;
            font-size: 12px;
            animation: fadeIn 1s ease-out 1s both;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 30px 25px;
            }

            .system-title {
                font-size: 24px;
            }

            .logo {
                width: 180px;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .login-container {
                background: rgba(26, 32, 44, 0.95);
                color: #e2e8f0;
            }

            .system-title {
                color: #e2e8f0;
            }

            .form-input {
                background: rgba(45, 55, 72, 0.9);
                border-color: #4a5568;
                color: #e2e8f0;
            }

            .form-input:focus {
                background: rgba(45, 55, 72, 1);
            }
        }
    </style>
</head>
<body>
    <div class="bg-animation"></div>

    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="login-container">
        <div class="logo-container">
            <img src="{{ asset('build/assets/AERGAS PNG.png') }}" alt="AERGAS Logo" class="logo">
            <div class="system-subtitle">Gas Installation Management System</div>
        </div>

        <form class="login-form" id="loginForm" method="POST" action="{{ route('login.post') }}">
            @csrf
            <div id="errorMessage" class="error-message" style="display: none;"></div>

            @if ($errors->any())
                <div class="error-message">
                    @foreach ($errors->all() as $error)
                        {{ $error }}
                    @endforeach
                </div>
            @endif

            <div class="form-group">
                <input type="text" id="username" name="username" class="form-input" placeholder="Username" value="{{ old('username') }}" required>
                <i class="fas fa-user input-icon"></i>
            </div>

            <div class="form-group">
                <input type="password" id="password" name="password" class="form-input" placeholder="Password" required>
                <i class="fas fa-lock input-icon"></i>
                <button type="button" class="password-toggle" id="togglePassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <div class="remember-forgot">
                <label class="remember-me">
                    <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <span>Remember me</span>
                </label>
                <a href="#" class="forgot-password">Forgot Password?</a>
            </div>

            <button type="submit" class="login-btn" id="loginBtn">
                <span class="btn-text">Sign In</span>
                <div class="spinner"></div>
            </button>
        </form>

        <div class="footer">
            <p>&copy; 2024 AERGAS System. All rights reserved.</p>
        </div>
    </div>

    <script>
        // CSRF Token Setup
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Password Toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('password');
        const toggleIcon = togglePassword.querySelector('i');

        togglePassword.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);

            if (type === 'password') {
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            } else {
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            }
        });

        // Form Submission
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const errorMessage = document.getElementById('errorMessage');

        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Show loading state
            loginBtn.classList.add('loading');
            hideError();

            const formData = new FormData(loginForm);
            const loginData = {
                username: formData.get('username'),
                password: formData.get('password'),
                remember: formData.get('remember') ? true : false
            };

            try {
                const response = await fetch('{{ route("login.post") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(loginData)
                });

                const result = await response.json();

                if (result.success) {
                    // Show success and redirect
                    showSuccess('Login successful! Redirecting...');

                    setTimeout(() => {
                        window.location.href = '{{ route("dashboard") }}';
                    }, 1500);
                } else {
                    showError(result.message || 'Login failed. Please try again.');
                }
            } catch (error) {
                console.error('Login error:', error);
                showError('Network error. Please check your connection and try again.');
            } finally {
                loginBtn.classList.remove('loading');
            }
        });

        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';
            errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function hideError() {
            errorMessage.style.display = 'none';
        }

        function showSuccess(message) {
            errorMessage.style.background = '#c6f6d5';
            errorMessage.style.color = '#276749';
            errorMessage.style.borderLeftColor = '#38a169';
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';
        }

        // Auto-focus on first input
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });

        // Input animations
        const inputs = document.querySelectorAll('.form-input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentNode.classList.add('focused');
            });

            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentNode.classList.remove('focused');
                }
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.ctrlKey) {
                loginForm.dispatchEvent(new Event('submit'));
            }
        });

        // Check if already logged in
        window.addEventListener('load', function() {
            // Check authentication via Laravel session
            fetch('{{ route("auth.check") }}', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => response.json())
            .then(result => {
                if (result.success && result.authenticated) {
                    window.location.href = '{{ route("dashboard") }}';
                }
            })
            .catch(() => {
                // Continue with login page
            });
        });
    </script>
</body>
</html>
