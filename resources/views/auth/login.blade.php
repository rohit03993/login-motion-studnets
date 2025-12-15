<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Attendance CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --brand-bg: #f3f4f6;
            --brand-card: #ffffff;
            --brand-accent: #f1c40f;
            --brand-accent-2: #e74c3c;
            --brand-text: #0f172a;
            --brand-muted: #6b7280;
            --brand-border: #e5e7eb;
        }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            max-width: 420px;
            width: 100%;
        }
        .login-card {
            background: var(--brand-card);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-logo {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--brand-accent-2), #c0392b);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            color: white;
            font-size: 28px;
        }
        .login-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--brand-text);
            margin-bottom: 8px;
        }
        .login-subtitle {
            color: var(--brand-muted);
            font-size: 14px;
        }
        .form-label {
            font-weight: 600;
            color: var(--brand-text);
            margin-bottom: 8px;
            font-size: 14px;
        }
        .form-control {
            border: 2px solid var(--brand-border);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 15px;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: var(--brand-accent-2);
            box-shadow: 0 0 0 4px rgba(231,76,60,0.1);
        }
        .input-group-text {
            background: #f9fafb;
            border: 2px solid var(--brand-border);
            border-right: none;
            border-radius: 12px 0 0 12px;
            color: var(--brand-muted);
        }
        .input-group .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
        }
        .btn-login {
            background: linear-gradient(135deg, var(--brand-accent-2), #c0392b);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            font-size: 16px;
            color: white;
            width: 100%;
            transition: all 0.3s;
            margin-top: 8px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(231,76,60,0.4);
            color: white;
        }
        .btn-login:active {
            transform: translateY(0);
        }
        .form-check-input:checked {
            background-color: var(--brand-accent-2);
            border-color: var(--brand-accent-2);
        }
        .alert {
            border-radius: 12px;
            border: none;
        }
        @media (max-width: 480px) {
            .login-card {
                padding: 24px;
            }
            .login-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <h1 class="login-title">Welcome Back</h1>
                <p class="login-subtitle">Sign in to Attendance CRM</p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="bi bi-envelope"></i> Email Address
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-envelope"></i>
                        </span>
                        <input 
                            type="email" 
                            class="form-control @error('email') is-invalid @enderror" 
                            id="email" 
                            name="email" 
                            value="{{ old('email') }}" 
                            placeholder="admin@example.com"
                            required 
                            autofocus
                        >
                    </div>
                    @error('email')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="bi bi-lock"></i> Password
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input 
                            type="password" 
                            class="form-control @error('password') is-invalid @enderror" 
                            id="password" 
                            name="password" 
                            placeholder="Enter your password"
                            required
                        >
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">
                            Remember me
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="bi bi-box-arrow-in-right"></i> Sign In
                </button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

