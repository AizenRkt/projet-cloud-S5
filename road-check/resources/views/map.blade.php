<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Road Check - Manager</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}" />
    @viteReactRefresh
    @vite(['resources/js/app.js'])
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
        .nav-btn { padding: 8px 16px; border: 1px solid var(--rc-border); background: rgba(25, 31, 43, 0.9); color: var(--rc-text); border-radius: 8px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s ease; }
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
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; background: #1a2230; border: 1px solid var(--rc-border); border-radius: 8px; color: var(--rc-text); }
        .form-group input:focus, .form-group select:focus { border-color: var(--rc-accent); outline: none; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .btn-save { width: 100%; padding: 12px; background: var(--rc-success); color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 2000; display: none; align-items: center; justify-content: center; }
        .modal-overlay.open { display: flex; }
        .modal { background: var(--rc-panel); border: 1px solid var(--rc-border); border-radius: 14px; width: 90%; max-width: 700px; max-height: 85vh; overflow: hidden; box-shadow: 0 22px 40px rgba(0,0,0,0.45); }
        .modal-header { padding: 15px 20px; background: var(--rc-panel-strong); border-bottom: 1px solid var(--rc-border); display: flex; justify-content: space-between; }
        .modal-header h3 { color: var(--rc-accent); }
        .modal-body { padding: 20px; overflow-y: auto; }
        .photo-thumb-wrap { position: relative; width: 80px; height: 60px; margin-top: 6px; }
        .photo-thumb { width: 100%; height: 100%; object-fit: cover; border-radius: 6px; border: 1px solid #ccc; display: block; }
        .photo-more-overlay { position: absolute; inset: 0; border-radius: 6px; border: 1px solid #30363d; background: rgba(13, 17, 23, 0.65); color: #e6edf3; font-size: 0.72rem; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; backdrop-filter: blur(6px); }
        .photo-more-overlay:hover { border-color: var(--rc-accent); color: var(--rc-accent); }
        .photo-modal-nav { position: absolute; bottom: 16px; left: 50%; transform: translateX(-50%); display: flex; align-items: center; gap: 10px; background: rgba(13, 17, 23, 0.75); padding: 6px 12px; border-radius: 999px; border: 1px solid #30363d; }
        .photo-modal-btn { background: transparent; color: var(--rc-text); border: 1px solid #30363d; border-radius: 999px; width: 32px; height: 32px; cursor: pointer; }
        .photo-modal-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .photo-modal-counter { font-size: 0.8rem; color: #8b949e; }
        .modal-footer { padding: 15px 20px; background: var(--rc-panel-strong); border-top: 1px solid var(--rc-border); display: flex; justify-content: flex-end; gap: 10px; }
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

        /* Search & Filter Styles */
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

        .loading-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(13,17,23,0.85); z-index: 10000; display: none; align-items: center; justify-content: center; flex-direction: column; gap: 20px; }
        /* Scrollbar Styling */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #161b22; }
        ::-webkit-scrollbar-thumb { background: #30363d; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #58a6ff; }

        /* History Styles */
        .history-section { margin-top: 20px; padding-top: 15px; border-top: 1px solid #30363d; }
        .history-title { font-size: 0.9rem; font-weight: 600; color: #58a6ff; margin-bottom: 10px; }
        .history-list { display: flex; flex-direction: column; gap: 8px; }
        .history-item { display: flex; align-items: flex-start; gap: 12px; }
        .history-dot { width: 8px; height: 8px; border-radius: 50%; background: #30363d; margin-top: 5px; flex-shrink: 0; }
        .history-dot.active { background: #238636; box-shadow: 0 0 5px #238636; }
        .history-info { display: flex; flex-direction: column; }
        .history-label { font-size: 0.85rem; color: #c9d1d9; }
        .history-date { font-size: 0.75rem; color: #8b949e; }

        /* Toast Styles */
        .toast-container { position: fixed; bottom: 20px; right: 20px; z-index: 3000; display: flex; flex-direction: column; gap: 10px; }
        .toast { padding: 12px 20px; border-radius: 6px; background: #21262d; border: 1px solid #30363d; color: #fff; transform: translateX(120%); transition: transform 0.3s; box-shadow: 0 4px 12px rgba(0,0,0,0.5); }
        .toast.show { transform: translateX(0); }
        .toast.success { border-left: 4px solid #238636; }
        .toast.error { border-left: 4px solid #f85149; }

        /* Loading Overlay */
        .loading-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 4000; display: none; flex-direction: column; align-items: center; justify-content: center; }
        .loading-overlay.show { display: flex; }
        .spinner { width: 40px; height: 40px; border: 4px solid #30363d; border-top-color: #58a6ff; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loading-text { margin-top: 15px; color: #58a6ff; font-weight: 500; }

        .confirm-modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 10001; display: none; align-items: center; justify-content: center; }
        .confirm-modal.show { display: flex; }
        .confirm-box { background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 24px; max-width: 400px; text-align: center; }
        .confirm-icon { font-size: 3rem; margin-bottom: 15px; }
        .confirm-title { color: #c9d1d9; font-size: 1.1rem; margin-bottom: 20px; }
        .confirm-buttons { display: flex; gap: 12px; justify-content: center; }
        .confirm-btn { padding: 10px 24px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; }
        /* Confirm Modal */
        .confirm-modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 3500; display: none; align-items: center; justify-content: center; }
        .confirm-modal.open { display: flex; }
        .confirm-box { background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 25px; width: 90%; max-width: 400px; text-align: center; }
        .confirm-title { font-size: 1.1rem; color: #c9d1d9; margin: 15px 0 20px; }
        .confirm-buttons { display: flex; gap: 10px; justify-content: center; }
        .confirm-btn { padding: 8px 20px; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; }
        .confirm-btn.yes { background: #238636; color: #fff; }
        .confirm-btn.no { background: #30363d; color: #c9d1d9; }

        /* Tooltip & Popup */
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
        .rc-tooltip a:hover { text-decoration: underline; }
        .rc-popup .leaflet-popup-content-wrapper {
            background: rgba(18, 24, 35, 0.98);
            color: var(--rc-text);
            border: 1px solid var(--rc-border);
            border-radius: 14px;
            box-shadow: 0 18px 30px rgba(0,0,0,0.4);
        }
        .rc-popup .leaflet-popup-content {
            margin: 12px 14px;
        }
        .rc-popup .leaflet-popup-tip {
            background: rgba(18, 24, 35, 0.98);
            border: 1px solid var(--rc-border);
        }

        /* Stats panel */
        .stats-panel { display: flex; flex-direction: column; gap: 16px; }
        .stats-panel-header { display: flex; justify-content: space-between; align-items: center; }
        .stats-panel-title { font-size: 1.1rem; font-weight: 600; color: var(--rc-accent); }
        .stats-panel-subtitle { font-size: 0.85rem; color: var(--rc-muted); }
        .stats-filter { background: #1a2230; border: 1px solid var(--rc-border); border-radius: 12px; padding: 14px; }
        .stats-filter-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; align-items: end; }
        .stats-field label { display: block; margin-bottom: 6px; color: var(--rc-muted); font-size: 0.8rem; }
        .stats-field input { width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid var(--rc-border); background: #0f1621; color: var(--rc-text); }
        .stats-actions { display: flex; gap: 10px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; }
        .stat-card { background: #141b26; border: 1px solid var(--rc-border); border-radius: 12px; padding: 14px; text-align: left; }
        .stat-value { font-size: 1.4rem; font-weight: 700; }
        .stat-label { font-size: 0.75rem; color: var(--rc-muted); }
        .stats-highlight { color: var(--rc-accent); }
        .stats-accent { color: #7dd3fc; }
        .stats-details { background: #141b26; border: 1px solid var(--rc-border); border-radius: 12px; padding: 14px; }
        .stats-details-title { color: var(--rc-accent); font-weight: 600; margin-bottom: 10px; }
        .stats-details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; }
        .stats-detail-item { display: flex; justify-content: space-between; align-items: center; background: #0f1621; border-radius: 10px; padding: 10px 12px; color: var(--rc-text); }
        .stats-detail-item span { color: var(--rc-muted); font-size: 0.78rem; }
        .stats-loading { padding: 24px; text-align: center; color: var(--rc-accent); }
        .stats-error { padding: 24px; text-align: center; color: var(--rc-danger); }
    </style>
</head>
<body>
    <div id="map-app" data-success='@json(session('success'))' data-docs-url="http://localhost:8082/"></div>

    <script src="{{ asset('leaflet/leaflet.js') }}"></script>
</body>
</html>
