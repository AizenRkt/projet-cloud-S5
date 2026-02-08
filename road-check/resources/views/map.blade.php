<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Road Check - Manager</title>
    <link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}" />
    @viteReactRefresh
    @vite(['resources/js/app.js'])
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0d1117; color: #c9d1d9; height: 100vh; overflow: hidden; }
        .navbar { position: fixed; top: 0; left: 0; right: 0; height: 56px; background: linear-gradient(90deg, #161b22, #21262d); display: flex; align-items: center; justify-content: space-between; padding: 0 20px; z-index: 1000; border-bottom: 1px solid #30363d; }
        .navbar-brand { display: flex; align-items: center; gap: 10px; }
        .navbar-brand .logo { font-size: 1.5rem; }
        .navbar-brand .title { color: #58a6ff; font-weight: 700; }
        .navbar-brand .subtitle { color: #8b949e; }
        .navbar-menu { display: flex; gap: 10px; }
        .nav-btn { padding: 8px 16px; border: 1px solid #30363d; background: #21262d; color: #c9d1d9; border-radius: 6px; cursor: pointer; font-size: 0.85rem; }
        .nav-btn:hover { background: #30363d; border-color: #58a6ff; }
        .main-container { display: flex; height: calc(100vh - 56px); margin-top: 56px; }
        .sidebar { width: 360px; background: #161b22; border-right: 1px solid #30363d; display: flex; flex-direction: column; }
        .sidebar-header { padding: 15px; background: #1c2128; border-bottom: 1px solid #30363d; }
        .sidebar-title { font-size: 1rem; font-weight: 600; color: #58a6ff; margin-bottom: 12px; }
        .filter-tabs { display: flex; gap: 6px; flex-wrap: wrap; }
        .filter-tab { padding: 5px 12px; border: 1px solid #30363d; background: transparent; color: #8b949e; border-radius: 20px; cursor: pointer; font-size: 0.8rem; }
        .filter-tab:hover { border-color: #58a6ff; color: #58a6ff; }
        .filter-tab.active { background: #238636; border-color: #238636; color: #fff; }
        .sidebar-content { flex: 1; overflow-y: auto; padding: 10px; }
        .sig-card { background: #21262d; border: 1px solid #30363d; border-radius: 8px; padding: 12px; margin-bottom: 10px; cursor: pointer; }
        .sig-card:hover { border-color: #58a6ff; }
        .sig-card.selected { border-color: #58a6ff; background: #1c2128; }
        .sig-header { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .sig-type { font-weight: 600; color: #c9d1d9; }
        .sig-status { padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; }
        .sig-status.nouveau { background: #1f6feb; color: #fff; }
        .sig-status.en_attente { background: #d29922; color: #fff; }
        .sig-status.en_cours { background: #f0883e; color: #fff; }
        .sig-status.termine { background: #238636; color: #fff; }
        .sig-status.annule { background: #f85149; color: #fff; }
        .sig-desc { font-size: 0.8rem; color: #8b949e; margin-bottom: 8px; }
        .sig-info { font-size: 0.75rem; color: #8b949e; }
        .map-container { flex: 1; position: relative; }
        #map { width: 100%; height: 100%; }
        .stats-bar { position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); background: rgba(22, 27, 34, 0.95); border: 1px solid #30363d; border-radius: 12px; padding: 12px 24px; display: flex; gap: 30px; z-index: 500; }
        .stat-item { text-align: center; }
        .stat-value { font-size: 1.4rem; font-weight: 700; }
        .stat-value.nouveau { color: #1f6feb; }
        .stat-value.en_attente { color: #d29922; }
        .stat-value.en_cours { color: #f0883e; }
        .stat-value.termine { color: #238636; }
        .stat-value.annule { color: #f85149; }
        .stat-label { font-size: 0.7rem; color: #8b949e; }
        .detail-panel { position: fixed; top: 56px; right: -420px; width: 420px; height: calc(100vh - 56px); background: #161b22; border-left: 1px solid #30363d; z-index: 900; transition: right 0.3s; overflow-y: auto; }
        .detail-panel.open { right: 0; }
        .detail-header { padding: 15px 20px; background: #1c2128; border-bottom: 1px solid #30363d; display: flex; justify-content: space-between; }
        .detail-header h3 { color: #58a6ff; }
        .close-btn { background: none; border: none; color: #8b949e; font-size: 1.5rem; cursor: pointer; }
        .detail-content { padding: 20px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; color: #8b949e; font-size: 0.85rem; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; background: #21262d; border: 1px solid #30363d; border-radius: 6px; color: #c9d1d9; }
        .form-group input:focus, .form-group select:focus { border-color: #58a6ff; outline: none; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .btn-save { width: 100%; padding: 12px; background: #238636; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 2000; display: none; align-items: center; justify-content: center; }
        .modal-overlay.open { display: flex; }
        .modal { background: #161b22; border: 1px solid #30363d; border-radius: 12px; width: 90%; max-width: 700px; max-height: 85vh; overflow: hidden; }
        .modal-header { padding: 15px 20px; background: #1c2128; border-bottom: 1px solid #30363d; display: flex; justify-content: space-between; }
        .modal-header h3 { color: #58a6ff; }
        .modal-body { padding: 20px; overflow-y: auto; }
        .modal-footer { padding: 15px 20px; background: #1c2128; border-top: 1px solid #30363d; display: flex; justify-content: flex-end; gap: 10px; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 10px; text-align: left; border-bottom: 1px solid #30363d; }
        .data-table th { background: #21262d; color: #8b949e; }
        .badge { padding: 3px 8px; border-radius: 12px; font-size: 0.75rem; }
        .badge.manager { background: #8957e5; color: #fff; }
        .badge.utilisateur { background: #1f6feb; color: #fff; }
        .badge.blocked { background: #f85149; color: #fff; }
        .badge.active { background: #238636; color: #fff; }
        .action-btn { padding: 4px 10px; border: 1px solid #30363d; background: transparent; color: #8b949e; border-radius: 4px; cursor: pointer; }
        .action-btn:hover { border-color: #58a6ff; }
        .loading { text-align: center; padding: 40px; color: #8b949e; }

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

        .loading-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(13,17,23,0.85); z-index: 10000; display: none; align-items: center; justify-content: center; flex-direction: column; gap: 20px; }
        .loading-overlay.show { display: flex; }
        .spinner { width: 50px; height: 50px; border: 4px solid #30363d; border-top-color: #58a6ff; border-radius: 50%; animation: spin 1s linear infinite; }
        .loading-text { color: #c9d1d9; font-size: 1rem; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .confirm-modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 10001; display: none; align-items: center; justify-content: center; }
        .confirm-modal.show { display: flex; }
        .confirm-box { background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 24px; max-width: 400px; text-align: center; }
        .confirm-icon { font-size: 3rem; margin-bottom: 15px; }
        .confirm-title { color: #c9d1d9; font-size: 1.1rem; margin-bottom: 20px; }
        .confirm-buttons { display: flex; gap: 12px; justify-content: center; }
        .confirm-btn { padding: 10px 24px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; }
        .confirm-btn.yes { background: #238636; color: #fff; }
        .confirm-btn.yes:hover { background: #2ea043; }
        .confirm-btn.no { background: #21262d; color: #c9d1d9; border: 1px solid #30363d; }
        .confirm-btn.no:hover { background: #30363d; }
    </style>
</head>
<body>
    <div id="map-app" data-success='@json(session('success'))'></div>
</body>
</html>
