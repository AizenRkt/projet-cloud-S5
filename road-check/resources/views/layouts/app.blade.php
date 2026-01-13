<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Laravel App')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="d-flex flex-column min-vh-100">

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/">Laravel CRUD</a>
        </div>
    </nav>

    <!-- LAYOUT AVEC SIDEBAR -->
    <div class="d-flex" style="min-height: calc(100vh - 56px);">

        <!-- SIDEBAR -->
        @include('layouts.sidebar')

        <!-- CONTENU PRINCIPAL -->
        <main class="flex-grow-1 p-4">

            @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @yield('content')
        </main>

    </div>

    <!-- FOOTER -->
    <footer class="bg-dark text-white text-center py-3 mt-auto">
        <p class="mb-0">© {{ date('Y') }} — Laravel CRUD Project</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
