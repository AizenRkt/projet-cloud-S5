<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Statistiques - Road Check</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0d1117; color: #c9d1d9; min-height: 100vh; }
        .navbar { position: fixed; top: 0; left: 0; right: 0; height: 56px; background: linear-gradient(90deg, #161b22, #21262d); display: flex; align-items: center; justify-content: space-between; padding: 0 20px; z-index: 1000; border-bottom: 1px solid #30363d; }
        .navbar-brand { display: flex; align-items: center; gap: 10px; }
        .navbar-brand .logo { font-size: 1.5rem; }
        .navbar-brand .title { color: #58a6ff; font-weight: 700; }
        .navbar-brand .subtitle { color: #8b949e; }
        .navbar-menu { display: flex; gap: 10px; }
        .nav-btn { padding: 8px 16px; border: 1px solid #30363d; background: #21262d; color: #c9d1d9; border-radius: 6px; cursor: pointer; font-size: 0.85rem; text-decoration: none; display: inline-block; text-align: center; }
        .nav-btn:hover { background: #30363d; border-color: #58a6ff; }
        .container { margin-top: 80px; padding: 20px; max-width: 1200px; margin-left: auto; margin-right: auto; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 20px; text-align: center; }
        .stat-value { font-size: 2rem; font-weight: 700; color: #58a6ff; margin-bottom: 8px; }
        .stat-label { font-size: 0.9rem; color: #8b949e; }
        .stats-table { background: #161b22; border: 1px solid #30363d; border-radius: 12px; overflow: hidden; }
        .stats-table h3 { padding: 20px; background: #1c2128; border-bottom: 1px solid #30363d; margin: 0; color: #58a6ff; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 15px; text-align: left; border-bottom: 1px solid #30363d; }
        .data-table th { background: #21262d; color: #8b949e; font-weight: 600; }
        .data-table tr:hover { background: #1c2128; }
        .processing-time { color: #f0883e; font-weight: 600; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <span class="logo"></span>
            <span class="title">Road Check</span>
            <span class="subtitle">| Statistiques</span>
        </div>
        <div class="navbar-menu">
            <a href="{{ route('map') }}" class="nav-btn">Carte</a>
            <button class="nav-btn" onclick="logout()">Deconnexion</button>
        </div>
    </nav>
    <div class="container">
        <h1 style="color: #58a6ff; margin-bottom: 30px;">Tableau de Statistiques</h1>

        <!-- Date Filters -->
        <div style="background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 20px; margin-bottom: 30px;">
            <h3 style="color: #58a6ff; margin-bottom: 15px;">Filtres par Date</h3>
            <form method="GET" action="{{ route('stats') }}" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                <div>
                    <label for="start_date" style="display: block; margin-bottom: 5px; color: #8b949e; font-size: 0.9rem;">Date de début</label>
                    <input type="date" id="start_date" name="start_date" value="{{ request('start_date') }}" style="padding: 8px 12px; background: #21262d; border: 1px solid #30363d; border-radius: 6px; color: #c9d1d9;">
                </div>
                <div>
                    <label for="end_date" style="display: block; margin-bottom: 5px; color: #8b949e; font-size: 0.9rem;">Date de fin</label>
                    <input type="date" id="end_date" name="end_date" value="{{ request('end_date') }}" style="padding: 8px 12px; background: #21262d; border: 1px solid #30363d; border-radius: 6px; color: #c9d1d9;">
                </div>
                <div style="display: flex; gap: 10px; align-items: flex-end;">
                    <button type="submit" class="nav-btn" style="padding: 8px 16px;">Filtrer</button>
                    <a href="{{ route('stats') }}" class="nav-btn" style="padding: 8px 16px; text-decoration: none;">Réinitialiser</a>
                </div>
            </form>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">{{ $stats['total'] }}</div>
                <div class="stat-label">Total Signalements</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #1f6feb;">{{ $stats['nouveau'] }}</div>
                <div class="stat-label">Validés</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #f0883e;">{{ $stats['en_cours'] }}</div>
                <div class="stat-label">En cours</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #238636;">{{ $stats['termine'] }}</div>
                <div class="stat-label">Terminés</div>
            </div>
            <div class="stat-card">
                <div class="stat-value processing-time">{{ $stats['average_processing_time'] }} jours</div>
                <div class="stat-label">Délai Moyen de Traitement</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $stats['avancement'] }}%</div>
                <div class="stat-label">Avancement Global</div>
            </div>
        </div>

        <div class="stats-table">
            <h3>Détails des Statistiques</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Métrique</th>
                        <th>Valeur</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Total des signalements</td>
                        <td>{{ $stats['total'] }}</td>
                        <td>Nombre total de signalements enregistrés</td>
                    </tr>
                    <tr>
                        <td>Signalements validés</td>
                        <td>{{ $stats['nouveau'] }}</td>
                        <td>Signalements en attente de traitement</td>
                    </tr>
                    <tr>
                        <td>Signalements en cours</td>
                        <td>{{ $stats['en_cours'] }}</td>
                        <td>Signalements actuellement en traitement</td>
                    </tr>
                    <tr>
                        <td>Signalements terminés</td>
                        <td>{{ $stats['termine'] }}</td>
                        <td>Signalements finalisés</td>
                    </tr>
                    <tr>
                        <td>Délai de traitement moyen</td>
                        <td class="processing-time">{{ $stats['average_processing_time'] }} jours</td>
                        <td>Moyenne des jours entre création et achèvement (basé sur {{ $stats['completed_count'] }} signalements terminés)</td>
                    </tr>
                    <tr>
                        <td>Avancement global</td>
                        <td>{{ $stats['avancement'] }}%</td>
                        <td>Pourcentage moyen d'avancement de tous les signalements</td>
                    </tr>
                    <tr>
                        <td>Surface totale</td>
                        <td>{{ number_format($stats['total_surface'], 2) }} m²</td>
                        <td>Surface cumulée de tous les signalements</td>
                    </tr>
                    <tr>
                        <td>Budget total</td>
                        <td>{{ number_format($stats['total_budget'], 2) }} Ar</td>
                        <td>Budget cumulé de tous les signalements</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function logout() {
            fetch('/logout', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }).then(() => window.location.href = '/login');
        }
    </script>
</body>
</html>
