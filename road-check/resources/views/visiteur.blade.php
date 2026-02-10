<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Road Check - Visiteur</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}" />
    @viteReactRefresh
    @vite(['resources/js/visiteur.jsx'])
    <style>
        :root {
            --rc-bg: #0b0f14;
            --rc-panel: #141922;
            --rc-panel-strong: #1b2330;
            --rc-border: #293241;
            --rc-text: #e6edf3;
            --rc-muted: #9aa6b2;
            --rc-accent: #58a6ff;
            --rc-success: #2ea043;
            --rc-warning: #d29922;
            --rc-danger: #f85149;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Space Grotesk', sans-serif;
            background: radial-gradient(circle at 20% 20%, rgba(88, 166, 255, 0.08), transparent 35%),
                radial-gradient(circle at 80% 10%, rgba(14, 165, 233, 0.12), transparent 40%),
                var(--rc-bg);
            color: var(--rc-text);
            height: 100vh;
            overflow: hidden;
        }
        .navbar { position: fixed; top: 0; left: 0; right: 0; height: 56px; background: linear-gradient(90deg, #121820, #1f2633); display: flex; align-items: center; justify-content: space-between; padding: 0 20px; z-index: 1000; border-bottom: 1px solid var(--rc-border); box-shadow: 0 6px 20px rgba(0,0,0,0.2); }
        .navbar-brand { display: flex; align-items: center; gap: 10px; }
        .navbar-brand .logo { font-size: 1.5rem; }
        .navbar-brand .title { color: var(--rc-accent); font-weight: 700; letter-spacing: 0.3px; }
        .navbar-brand .subtitle { color: var(--rc-muted); }
        .navbar-menu { display: flex; gap: 10px; }
        .nav-btn { padding: 8px 16px; border: 1px solid var(--rc-border); background: rgba(25, 31, 43, 0.9); color: var(--rc-text); border-radius: 8px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s ease; text-decoration: none; }
        .nav-btn:hover { background: rgba(35, 45, 60, 0.9); border-color: var(--rc-accent); transform: translateY(-1px); }
        .main-container { display: flex; height: calc(100vh - 56px); margin-top: 56px; }
        .sidebar { width: 360px; background: var(--rc-panel); border-right: 1px solid var(--rc-border); display: flex; flex-direction: column; }
        .sidebar-header { padding: 15px; background: var(--rc-panel-strong); border-bottom: 1px solid var(--rc-border); }
        .sidebar-title { font-size: 1rem; font-weight: 600; color: var(--rc-accent); margin-bottom: 12px; }
        .filter-tabs { display: flex; gap: 6px; flex-wrap: wrap; }
        .filter-tab { padding: 6px 12px; border: 1px solid var(--rc-border); background: rgba(16, 22, 31, 0.8); color: var(--rc-muted); border-radius: 999px; cursor: pointer; font-size: 0.78rem; transition: all 0.2s ease; }
        .filter-tab:hover { border-color: var(--rc-accent); color: var(--rc-accent); }
        .filter-tab.active { background: var(--rc-success); border-color: var(--rc-success); color: #fff; }
        .sidebar-content { flex: 1; overflow-y: auto; padding: 10px; }
        .sig-card { background: rgba(24, 31, 42, 0.9); border: 1px solid var(--rc-border); border-radius: 12px; padding: 12px; margin-bottom: 10px; cursor: pointer; transition: all 0.2s ease; }
        .sig-card:hover { border-color: var(--rc-accent); transform: translateY(-1px); }
        .sig-card.selected { border-color: var(--rc-accent); background: rgba(28, 36, 50, 0.95); }
        .sig-header { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .sig-type { font-weight: 600; color: var(--rc-text); }
        .sig-status { padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; }
        .sig-status.nouveau { background: #1f6feb; color: #fff; }
        .sig-status.en_attente { background: #d29922; color: #fff; }
        .sig-status.en_cours { background: #f0883e; color: #fff; }
        .sig-status.termine { background: #238636; color: #fff; }
        .sig-status.annule { background: #f85149; color: #fff; }
        .sig-desc { font-size: 0.8rem; color: var(--rc-muted); margin-bottom: 8px; }
        .sig-info { font-size: 0.75rem; color: var(--rc-muted); }
        .map-container { flex: 1; position: relative; }
        #map { width: 100%; height: 100%; }
        .stats-bar { position: absolute; bottom: 20px; left: 20px; background: rgba(17, 24, 39, 0.92); border: 1px solid var(--rc-border); border-radius: 14px; padding: 12px 24px; display: flex; gap: 30px; z-index: 500; max-width: calc(100% - 460px); flex-wrap: wrap; backdrop-filter: blur(12px); box-shadow: 0 12px 30px rgba(0,0,0,0.3); }
        .stat-item { text-align: center; }
        .stat-value { font-size: 1.4rem; font-weight: 700; }
        .stat-value.nouveau { color: #1f6feb; }
        .stat-value.en_attente { color: #d29922; }
        .stat-value.en_cours { color: #f0883e; }
        .stat-value.termine { color: #238636; }
        .stat-value.annule { color: #f85149; }
        .stat-label { font-size: 0.7rem; color: #8b949e; }
        .detail-panel { position: fixed; top: 56px; right: -420px; width: 420px; height: calc(100vh - 56px); background: var(--rc-panel); border-left: 1px solid var(--rc-border); z-index: 900; transition: right 0.3s; overflow-y: auto; }
        .detail-panel.open { right: 0; }
        .detail-header { padding: 15px 20px; background: var(--rc-panel-strong); border-bottom: 1px solid var(--rc-border); display: flex; justify-content: space-between; }
        .detail-header h3 { color: var(--rc-accent); }
        .close-btn { background: none; border: none; color: #8b949e; font-size: 1.5rem; cursor: pointer; }
        .detail-content { padding: 20px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; color: #8b949e; font-size: 0.85rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .btn-save { width: 100%; padding: 12px; background: var(--rc-success); color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }

        .search-container { padding: 15px; border-bottom: 1px solid var(--rc-border); background: var(--rc-panel-strong); }
        .search-input { width: 100%; padding: 8px 12px; background: #0f1621; border: 1px solid var(--rc-border); border-radius: 8px; color: var(--rc-text); font-size: 0.9rem; transition: border-color 0.2s; }
        .search-input:focus { border-color: var(--rc-accent); outline: none; }
        .search-input::placeholder { color: #8b949e; }
        .date-filters { display: flex; gap: 8px; margin-top: 10px; }
        .date-filters .search-input { flex: 1; }

        .toast-container { position: fixed; top: 70px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; }
        .toast { padding: 14px 20px; border-radius: 8px; display: flex; align-items: center; gap: 12px; min-width: 300px; max-width: 450px; animation: slideIn 0.3s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
        .toast.success { background: linear-gradient(135deg, #238636, #2ea043); color: #fff; border: 1px solid #2ea043; }
        .toast.error { background: linear-gradient(135deg, #da3633, #f85149); color: #fff; border: 1px solid #f85149; }
        .toast.info { background: linear-gradient(135deg, #1f6feb, #388bfd); color: #fff; border: 1px solid #388bfd; }
        .toast.warning { background: linear-gradient(135deg, #9e6a03, #d29922); color: #fff; border: 1px solid #d29922; }
        .toast-icon { font-size: 1.2rem; }
        .toast-message { flex: 1; font-size: 0.9rem; }
        .toast-close { background: none; border: none; color: rgba(255,255,255,0.7); cursor: pointer; font-size: 1.2rem; padding: 0; }
        .toast-close:hover { color: #fff; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }

        .rc-tooltip {
            background: rgba(18, 24, 35, 0.95) !important;
            border: 1px solid var(--rc-border) !important;
            color: var(--rc-text) !important;
            border-radius: 12px !important;
            padding: 10px 12px !important;
            box-shadow: 0 12px 24px rgba(0,0,0,0.35) !important;
            backdrop-filter: blur(10px);
        }
        .rc-tooltip a { color: var(--rc-accent); text-decoration: none; }
        .rc-popup .leaflet-popup-content-wrapper {
            background: rgba(18, 24, 35, 0.98);
            color: var(--rc-text);
            border: 1px solid var(--rc-border);
            border-radius: 14px;
            box-shadow: 0 18px 30px rgba(0,0,0,0.4);
        }
        .rc-popup .leaflet-popup-content { margin: 12px 14px; }
        .rc-popup .leaflet-popup-tip { background: rgba(18, 24, 35, 0.98); border: 1px solid var(--rc-border); }

        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #161b22; }
        ::-webkit-scrollbar-thumb { background: #30363d; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #58a6ff; }
    </style>
</head>
<body>
    <div id="visiteur-app"></div>
    <script src="{{ asset('leaflet/leaflet.js') }}"></script>
</body>
</html>
