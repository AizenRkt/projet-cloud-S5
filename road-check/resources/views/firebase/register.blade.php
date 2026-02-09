<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Road Check</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    @viteReactRefresh
    @vite(['resources/js/app.js'])
    <style>
        :root {
            --rc-primary: #2dd4bf;
            --rc-primary-dark: #0f766e;
            --rc-bg: #0b1110;
            --rc-panel: #141c1b;
            --rc-border: #2b3a38;
            --rc-text: #e7f2f1;
            --rc-muted: #9db0ae;
        }
        body {
            font-family: 'Space Grotesk', sans-serif;
            background: radial-gradient(circle at 10% 15%, rgba(45, 212, 191, 0.18), transparent 35%),
                radial-gradient(circle at 85% 5%, rgba(20, 184, 166, 0.22), transparent 40%),
                var(--rc-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--rc-text);
            padding: 2.5rem 1rem;
        }
        .register-card {
            background: var(--rc-panel);
            border-radius: 18px;
            border: 1px solid var(--rc-border);
            box-shadow: 0 18px 40px rgba(0,0,0,0.35);
            padding: 2.75rem;
            max-width: 440px;
            width: 100%;
        }
        .brand-logo {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #0f766e, #2dd4bf);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 12px 20px rgba(45, 212, 191, 0.3);
        }
        .brand-logo i {
            font-size: 28px;
            color: #fff;
        }
        .form-control:focus {
            border-color: var(--rc-primary);
            box-shadow: 0 0 0 3px rgba(45, 212, 191, 0.2);
        }
        .btn-rc {
            background: var(--rc-primary);
            border: none;
            color: #062a25;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-rc:hover {
            background: var(--rc-primary-dark);
            color: #e7f2f1;
        }
        .input-icon {
            position: relative;
        }
        .input-icon i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--rc-muted);
        }
        .input-icon input {
            padding-left: 42px;
        }
        .form-control {
            background: #0f1918;
            border: 1px solid var(--rc-border);
            color: var(--rc-text);
        }
        .form-control::placeholder { color: var(--rc-muted); }
        .alert {
            border-radius: 8px;
            border: none;
        }
        .alert-danger {
            background: rgba(248, 113, 113, 0.15);
            color: #fca5a5;
        }
        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            color: #86efac;
        }
        .text-muted { color: var(--rc-muted) !important; }
        .rc-link { color: var(--rc-primary); }
        .rc-link:hover { color: #99f6e4; }
        .form-row {
            display: flex;
            gap: 1rem;
        }
        .form-row > div {
            flex: 1;
        }
        @media (max-width: 520px) {
            .form-row { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div
        id="register-app"
        data-csrf-token="{{ csrf_token() }}"
        data-register-action="{{ route('register.submit') }}"
        data-login-url="{{ route('login.form') }}"
        data-default-first-name="{{ old('prenom') }}"
        data-default-last-name="{{ old('nom') }}"
        data-default-email="{{ old('email') }}"
        data-success='@json(session('success'))'
        data-errors='@json($errors->all())'
    ></div>
</body>
</html>
