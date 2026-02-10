<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Road Check</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    @viteReactRefresh
    @vite(['resources/js/app.js'])
    <style>
        :root {
            --rc-primary: #58a6ff;
            --rc-primary-dark: #3b82f6;
            --rc-bg: #0b0f14;
            --rc-panel: #141922;
            --rc-border: #293241;
            --rc-text: #e6edf3;
            --rc-muted: #9aa6b2;
        }
        body {
            font-family: 'Space Grotesk', sans-serif;
            background: radial-gradient(circle at 20% 20%, rgba(88, 166, 255, 0.12), transparent 35%),
                radial-gradient(circle at 80% 10%, rgba(59, 130, 246, 0.18), transparent 40%),
                var(--rc-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--rc-text);
        }
        .login-card {
            background: var(--rc-panel);
            border-radius: 18px;
            border: 1px solid var(--rc-border);
            box-shadow: 0 18px 40px rgba(0,0,0,0.35);
            padding: 2.5rem;
            max-width: 400px;
            width: 100%;
        }
        .brand-logo {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #1f6feb, #58a6ff);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 12px 20px rgba(88, 166, 255, 0.3);
        }
        .brand-logo i {
            font-size: 28px;
            color: #fff;
        }
        .form-control:focus {
            border-color: var(--rc-primary);
            box-shadow: 0 0 0 3px rgba(88, 166, 255, 0.2);
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
            color: var(--rc-muted);
        }
        .input-icon input {
            padding-left: 42px;
        }
        .form-control {
            background: #0f1621;
            border: 1px solid var(--rc-border);
            color: var(--rc-text);
        }
        .form-control::placeholder { color: var(--rc-muted); }
        .alert {
            border-radius: 8px;
            border: none;
        }
        .alert-danger {
            background: rgba(248, 81, 73, 0.15);
            color: #f87171;
        }
        .alert-success {
            background: rgba(46, 160, 67, 0.15);
            color: #4ade80;
        }
        .text-muted { color: var(--rc-muted) !important; }
        .rc-link { color: var(--rc-primary); }
        .rc-link:hover { color: #7dd3fc; }
    </style>
</head>
<body>
    <div
        id="login-app"
        data-csrf-token="{{ csrf_token() }}"
        data-login-action="{{ route('login.submit') }}"
        data-register-url="{{ route('register.form') }}"
        data-docs-url="http://localhost:8082/"
        data-default-email="admin@gmail.com"
        data-default-password="password123"
        data-success='@json(session('success'))'
        data-firestore-status='@json($firestoreStatus ?? null)'
        data-errors='@json($errors->all())'
    ></div>
</body>
</html>
