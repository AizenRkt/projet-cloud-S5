<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Road Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --rc-primary: #0d9488;
            --rc-primary-dark: #0f766e;
            --rc-light: #f0fdfa;
        }
        body {
            background: linear-gradient(135deg, #f8fafc 0%, var(--rc-light) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 2.5rem;
            max-width: 400px;
            width: 100%;
        }
        .brand-logo {
            width: 56px;
            height: 56px;
            background: var(--rc-primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        .brand-logo i {
            font-size: 28px;
            color: #fff;
        }
        .form-control:focus {
            border-color: var(--rc-primary);
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
        }
        .btn-rc {
            background: var(--rc-primary);
            border: none;
            color: #fff;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.2s;
        }
        .btn-rc:hover {
            background: var(--rc-primary-dark);
            color: #fff;
        }
        .input-icon {
            position: relative;
        }
        .input-icon i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        .input-icon input {
            padding-left: 42px;
        }
        .alert {
            border-radius: 8px;
            border: none;
        }
        .alert-danger {
            background: #fef2f2;
            color: #dc2626;
        }
        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand-logo">
            <i class="bi bi-car-front-fill"></i>
        </div>
        <h4 class="text-center mb-1 fw-bold">Road Check</h4>
        <p class="text-center text-muted mb-4">Connectez-vous à votre compte</p>

        @if ($errors->any())
            <div class="alert alert-danger mb-3">
                <i class="bi bi-exclamation-circle me-2"></i>
                @foreach ($errors->all() as $error)
                    {{ $error }}@if(!$loop->last)<br>@endif
                @endforeach
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success mb-3">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}">
            @csrf
            <div class="mb-3 input-icon">
                <i class="bi bi-envelope"></i>
                <input type="email" name="email" class="form-control" value="rohy@gmail.com" placeholder="Adresse email" value="{{ old('email') }}" required>
            </div>
            <div class="mb-4 input-icon">
                <i class="bi bi-lock"></i>
                <input type="password" name="password" class="form-control" value="rohy123" placeholder="Mot de passe" required>
            </div>
            <button type="submit" class="btn btn-rc w-100 mb-3">
                <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
            </button>
        </form>

        <p class="text-center text-muted mb-0">
            Pas encore de compte ? <a href="{{ route('register.form') }}" class="text-decoration-none" style="color: var(--rc-primary);">S'inscrire</a>
        </p>
    </div>
</body>
</html>
