<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Road Check - Antananarivo</title>
    <link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: radial-gradient(1200px 800px at 15% 10%, #1e293b 0%, #0b1020 55%, #05080f 100%);
            min-height: 100vh;
            color: #e2e8f0;
        }

        .app-container {
            position: relative;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }

        /* Map Container - Full screen */
        .map-container {
            position: absolute;
            inset: 0;
            z-index: 1;
        }

        #map {
            width: 100%;
            height: 100%;
        }

        body.add-mode #map {
            cursor: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='28' height='28'><text y='22' font-size='22'>📍</text></svg>") 14 22, crosshair;
        }

        /* Sidebar - Floating above map */
        .sidebar {
            position: fixed;
            top: 20px;
            left: 20px;
            width: 340px;
            max-height: calc(100vh - 40px);
            background: rgba(8, 12, 22, 0.95);
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            z-index: 500;
            border-radius: 18px;
            border: 1px solid rgba(59, 130, 246, 0.25);
            backdrop-filter: blur(12px);
            overflow: hidden;
        }

        .sidebar-header {
            padding: 22px 24px;
            background: linear-gradient(135deg, #0f172a 0%, #111827 60%, #0b2a5a 100%);
            color: #e2e8f0;
            border-bottom: 1px solid rgba(59, 130, 246, 0.2);
        }

        .sidebar-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-header p {
            font-size: 0.85rem;
            opacity: 0.9;
            margin-top: 4px;
        }

        /* Stats Cards */
        .stats-section {
            padding: 16px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.15);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(2, 6, 23, 0.95) 100%);
            padding: 14px;
            border-radius: 12px;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.2);
        }

        .stat-card .icon {
            font-size: 1.5rem;
            margin-bottom: 6px;
        }

        .stat-card .value {
            font-size: 1.4rem;
            font-weight: 700;
            color: #e2e8f0;
        }

        .stat-card .label {
            font-size: 0.7rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }

        /* Add Button */
        .add-btn {
            margin: 16px;
            padding: 14px 20px;
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
            color: #fff;
            border: 1px solid rgba(59, 130, 246, 0.45);
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: transform 0.2s, box-shadow 0.2s, background 0.2s;
        }

        .add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.45);
        }

        .add-btn.active {
            background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
            box-shadow: 0 8px 24px rgba(14, 165, 233, 0.45);
        }

        /* Signalements List */
        .list-section {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
        }

        .list-header {
            font-size: 0.8rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }

        .signal-card {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .signal-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.2);
        }

        .signal-card .title {
            font-weight: 600;
            color: #e2e8f0;
            margin-bottom: 4px;
        }

        .signal-card .meta {
            font-size: 0.8rem;
            color: #94a3b8;
        }

        .signal-card .status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-top: 8px;
        }

        .status-nouveau { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .status-en-cours { background: rgba(14, 165, 233, 0.15); color: #38bdf8; }
        .status-termine { background: rgba(37, 99, 235, 0.2); color: #93c5fd; }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal {
            background: #0b1220;
            border-radius: 20px;
            width: 90%;
            max-width: 480px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.45);
            animation: modalIn 0.3s ease;
            border: 1px solid rgba(59, 130, 246, 0.25);
        }

        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .modal-header {
            padding: 20px 24px;
            background: linear-gradient(135deg, #0b1020 0%, #0b2a5a 100%);
            color: #e2e8f0;
            border-radius: 20px 20px 0 0;
            border-bottom: 1px solid rgba(59, 130, 246, 0.2);
        }

        .modal-header h2 {
            font-size: 1.3rem;
            font-weight: 700;
        }

        .modal-header p {
            font-size: 0.85rem;
            opacity: 0.9;
            margin-top: 4px;
        }

        .modal-body {
            padding: 24px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #cbd5f5;
            margin-bottom: 6px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: rgba(2, 6, 23, 0.85);
            color: #e2e8f0;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.18);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .coord-display {
            background: rgba(2, 6, 23, 0.7);
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 0.9rem;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px dashed rgba(59, 130, 246, 0.35);
        }

        .modal-footer {
            padding: 16px 24px 24px;
            display: flex;
            gap: 12px;
        }

        .btn {
            flex: 1;
            padding: 14px 20px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
            color: #fff;
        }

        .btn-primary:hover {
            box-shadow: 0 4px 18px rgba(37, 99, 235, 0.45);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: rgba(148, 163, 184, 0.12);
            color: #cbd5f5;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(148, 163, 184, 0.2);
        }

        /* Custom Popup */
        .leaflet-popup-content-wrapper {
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }

        .leaflet-popup-content {
            margin: 0;
            padding: 0;
        }

        .popup-content {
            padding: 16px;
            min-width: 220px;
        }

        .popup-content .popup-title {
            font-size: 1rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .popup-content .popup-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            padding: 4px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .popup-content .popup-row:last-child {
            border-bottom: none;
        }

        .popup-content .popup-label {
            color: #888;
        }

        .popup-content .popup-value {
            color: #333;
            font-weight: 500;
        }

        .popup-btn {
            width: 100%;
            margin-top: 12px;
            padding: 10px;
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        /* Scrollbar */
        .list-section::-webkit-scrollbar {
            width: 6px;
        }

        .list-section::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.5);
            border-radius: 10px;
        }

        .list-section::-webkit-scrollbar-thumb {
            background: rgba(59, 130, 246, 0.3);
            border-radius: 10px;
        }

        .list-section::-webkit-scrollbar-thumb:hover {
            background: rgba(59, 130, 246, 0.5);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: calc(100% - 40px);
                max-height: 50vh;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Map (full screen background) -->
        <div class="map-container">
            <div id="map"></div>
        </div>

        <!-- Sidebar (floating above map) -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>🛣️ Road Check</h1>
                <p>Signalements routiers - Antananarivo</p>
            </div>

            <!-- Stats -->
            <div class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="icon">📍</div>
                        <div class="value" id="stat-points">0</div>
                        <div class="label">Signalements</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">📐</div>
                        <div class="value" id="stat-surface">0</div>
                        <div class="label">Surface (m²)</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">📊</div>
                        <div class="value" id="stat-progress">0%</div>
                        <div class="label">Avancement</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">💰</div>
                        <div class="value" id="stat-budget">0</div>
                        <div class="label">Budget (Ar)</div>
                    </div>
                </div>
            </div>

            <!-- Add Button -->
            <button class="add-btn" id="add-btn">
                <span>➕</span>
                <span>Nouveau signalement</span>
            </button>

            <!-- List -->
            <div class="list-section">
                <div class="list-header">Signalements récents</div>
                <div id="signals-list"></div>
            </div>
        </aside>
    </div>

    <!-- Add Modal -->
    <div class="modal-overlay" id="add-modal">
        <div class="modal">
            <div class="modal-header">
                <h2>➕ Nouveau signalement</h2>
                <p>Cliquez sur la carte pour définir l'emplacement</p>
            </div>
            <form id="add-form">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Titre</label>
                        <input type="text" name="title" placeholder="Ex: Nid de poule dangereux" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" placeholder="Décrivez le problème en détail..." required></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Surface (m²)</label>
                            <input type="number" name="surface_m2" placeholder="0" required>
                        </div>
                        <div class="form-group">
                            <label>Budget (Ar)</label>
                            <input type="number" name="budget" placeholder="0" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Statut</label>
                            <select name="statut">
                                <option value="nouveau">Nouveau</option>
                                <option value="en cours">En cours</option>
                                <option value="termine">Terminé</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="report_date" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Entreprise</label>
                            <select name="id_entreprise" class="corp-select">
                                <option value="">Aucune</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Responsable</label>
                            <select name="id_utilisateur" class="user-select"></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Coordonnées</label>
                        <div class="coord-display" id="add-coords">
                            <span>📍</span>
                            <span>Cliquez sur la carte...</span>
                        </div>
                        <input type="hidden" name="lat">
                        <input type="hidden" name="lng">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModals()">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal-overlay" id="edit-modal">
        <div class="modal">
            <div class="modal-header">
                <h2>✏️ Modifier signalement</h2>
                <p id="edit-subtitle">Signalement #</p>
            </div>
            <form id="edit-form">
                <input type="hidden" name="id_signalement">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Titre</label>
                        <input type="text" name="title" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" required></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Surface (m²)</label>
                            <input type="number" name="surface_m2" required>
                        </div>
                        <div class="form-group">
                            <label>Budget (Ar)</label>
                            <input type="number" name="budget" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Statut</label>
                            <select name="statut">
                                <option value="nouveau">Nouveau</option>
                                <option value="en cours">En cours</option>
                                <option value="termine">Terminé</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="report_date">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Entreprise</label>
                            <select name="id_entreprise" class="corp-select">
                                <option value="">Aucune</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Responsable</label>
                            <select name="id_utilisateur" class="user-select"></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Note (historique)</label>
                        <textarea name="note" placeholder="Ajoutez une note sur cette modification..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModals()">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('leaflet/leaflet.js') }}"></script>
    <script>
        // Initialize map
        const map = L.map('map').setView([-18.89493, 47.49292], 13);
        let markersLayer = L.layerGroup().addTo(map);

        L.tileLayer('http://localhost:8081/styles/Basic/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '© OpenMapTiles © OpenStreetMap'
        }).addTo(map);

        // Marker icons
        const icons = {
            nouveau: L.icon({ iconUrl: 'leaflet/images/location.png', iconSize: [32,32], iconAnchor:[16,32], popupAnchor:[0,-32] }),
            'en cours': L.icon({ iconUrl: 'leaflet/images/warning.png', iconSize: [32,32], iconAnchor:[16,32], popupAnchor:[0,-32] }),
            termine: L.icon({ iconUrl: 'leaflet/images/cross.png', iconSize: [32,32], iconAnchor:[16,32], popupAnchor:[0,-32] }),
            dangerous: L.icon({ iconUrl: 'leaflet/images/dangerous.png', iconSize: [32,32], iconAnchor:[16,32], popupAnchor:[0,-32] })
        };

        const token = document.querySelector('meta[name="csrf-token"]').content;
        let corporations = [];
        let users = [];
        let allSignalements = [];
        let addMode = false;
        let currentLat = null, currentLng = null;

        // ============ UI Functions ============
        function closeModals() {
            document.getElementById('add-modal').classList.remove('show');
            document.getElementById('edit-modal').classList.remove('show');
            addMode = false;
            document.getElementById('add-btn').classList.remove('active');
        }

        function populateSelects() {
            document.querySelectorAll('.corp-select').forEach(s => {
                const val = s.value;
                s.innerHTML = '<option value="">Aucune</option>';
                corporations.forEach(c => {
                    s.innerHTML += `<option value="${c.id_entreprise}">${c.nom}</option>`;
                });
                s.value = val;
            });

            document.querySelectorAll('.user-select').forEach(s => {
                const val = s.value;
                s.innerHTML = '';
                users.forEach(u => {
                    s.innerHTML += `<option value="${u.id_utilisateur}">${u.prenom} ${u.nom}</option>`;
                });
                s.value = val;
            });
        }

        function renderSignalsList() {
            const container = document.getElementById('signals-list');
            container.innerHTML = allSignalements.map(s => {
                const statusClass = s.statut === 'en cours' ? 'status-en-cours' : `status-${s.statut}`;
                return `
                    <div class="signal-card" onclick="focusSignal(${s.id_signalement})">
                        <div class="title">${s.title || 'Signalement #' + s.id_signalement}</div>
                        <div class="meta">${s.surface_m2 || 0} m² • ${(s.budget || 0).toLocaleString()} Ar</div>
                        <span class="status ${statusClass}">${s.statut}</span>
                    </div>
                `;
            }).join('');
        }

        function updateStats(data) {
            const total = data.length;
            let surface = 0, budget = 0, done = 0;

            data.forEach(s => {
                surface += parseFloat(s.surface_m2 || 0);
                budget += parseFloat(s.budget || 0);
                if (s.statut === 'termine') done++;
            });

            document.getElementById('stat-points').textContent = total;
            document.getElementById('stat-surface').textContent = surface.toLocaleString();
            document.getElementById('stat-progress').textContent = total ? Math.round((done / total) * 100) + '%' : '0%';
            document.getElementById('stat-budget').textContent = budget.toLocaleString();
        }

        // ============ Data Loading ============
        function loadInitialData() {
            Promise.all([
                fetch('/entreprises').then(r => r.json()),
                fetch('/utilisateurs').then(r => r.json())
            ]).then(([corps, usrs]) => {
                corporations = corps;
                users = usrs;
                populateSelects();
            });
        }

        function loadMarkers() {
            fetch('/signalements')
                .then(r => r.json())
                .then(data => {
                    allSignalements = data;
                    markersLayer.clearLayers();

                    data.forEach(s => {
                        const icon = icons[s.statut] ?? icons.nouveau;
                        const marker = L.marker([s.latitude, s.longitude], { icon }).addTo(markersLayer);

                        const corp = corporations.find(c => c.id_entreprise == s.id_entreprise)?.nom ?? '-';
                        const user = users.find(u => u.id_utilisateur == s.id_utilisateur);
                        const userName = user ? `${user.prenom} ${user.nom}` : '-';

                        marker.bindPopup(`
                            <div class="popup-content">
                                <div class="popup-title">${s.title || 'Signalement #' + s.id_signalement}</div>
                                <div class="popup-row"><span class="popup-label">Statut</span><span class="popup-value">${s.statut}</span></div>
                                <div class="popup-row"><span class="popup-label">Surface</span><span class="popup-value">${s.surface_m2 || 0} m²</span></div>
                                <div class="popup-row"><span class="popup-label">Budget</span><span class="popup-value">${(s.budget || 0).toLocaleString()} Ar</span></div>
                                <div class="popup-row"><span class="popup-label">Entreprise</span><span class="popup-value">${corp}</span></div>
                                <div class="popup-row"><span class="popup-label">Responsable</span><span class="popup-value">${userName}</span></div>
                                <button class="popup-btn" onclick="openEditModal(${s.id_signalement})">Modifier</button>
                            </div>
                        `);
                    });

                    renderSignalsList();
                    updateStats(data);
                });
        }

        // ============ Actions ============
        window.focusSignal = function(id) {
            const s = allSignalements.find(x => x.id_signalement == id);
            if (s) {
                map.setView([s.latitude, s.longitude], 16);
                markersLayer.eachLayer(layer => {
                    if (layer.getLatLng().lat == s.latitude && layer.getLatLng().lng == s.longitude) {
                        layer.openPopup();
                    }
                });
            }
        };

        window.openEditModal = function(id) {
            const s = allSignalements.find(x => x.id_signalement == id);
            if (!s) return;

            const form = document.getElementById('edit-form');
            form.id_signalement.value = s.id_signalement;
            form.title.value = s.title || '';
            form.description.value = s.description || '';
            form.surface_m2.value = s.surface_m2 || '';
            form.budget.value = s.budget || '';
            form.statut.value = s.statut;
            form.id_entreprise.value = s.id_entreprise || '';
            form.id_utilisateur.value = s.id_utilisateur || '';
            form.report_date.value = s.report_date || '';
            form.note.value = '';

            document.getElementById('edit-subtitle').textContent = `Signalement #${s.id_signalement}`;
            document.getElementById('edit-modal').classList.add('show');
        };

        // ============ Event Listeners ============
        document.getElementById('add-btn').addEventListener('click', () => {
            addMode = !addMode;
            document.getElementById('add-btn').classList.toggle('active', addMode);
            if (!addMode) closeModals();
        });

        map.on('click', e => {
            if (!addMode) return;
            currentLat = e.latlng.lat;
            currentLng = e.latlng.lng;

            document.getElementById('add-coords').innerHTML = `<span>📍</span><span>${currentLat.toFixed(6)}, ${currentLng.toFixed(6)}</span>`;
            document.querySelector('#add-form input[name="lat"]').value = currentLat;
            document.querySelector('#add-form input[name="lng"]').value = currentLng;

            // Set today's date
            const today = new Date().toISOString().split('T')[0];
            document.querySelector('#add-form input[name="report_date"]').value = today;

            document.getElementById('add-modal').classList.add('show');
        });

        document.getElementById('add-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const body = {
                lat: this.lat.value,
                lng: this.lng.value,
                title: this.title.value,
                description: this.description.value,
                surface_m2: this.surface_m2.value,
                budget: this.budget.value,
                statut: this.statut.value,
                id_utilisateur: this.id_utilisateur.value,
                id_entreprise: this.id_entreprise.value || null,
                report_date: this.report_date.value
            };

            fetch('/signalements', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify(body)
            })
            .then(r => r.json())
            .then(() => {
                closeModals();
                this.reset();
                document.getElementById('add-coords').innerHTML = '<span>📍</span><span>Cliquez sur la carte...</span>';
                loadMarkers();
            })
            .catch(err => alert('Erreur: ' + err));
        });

        document.getElementById('edit-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = this.id_signalement.value;

            const body = {
                title: this.title.value,
                description: this.description.value,
                surface_m2: this.surface_m2.value,
                budget: this.budget.value,
                statut: this.statut.value,
                id_entreprise: this.id_entreprise.value || null,
                id_utilisateur: this.id_utilisateur.value,
                report_date: this.report_date.value,
                note: this.note.value,
                id_utilisateur_modif: 1
            };

            fetch(`/signalements/${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify(body)
            })
            .then(r => r.json())
            .then(() => {
                closeModals();
                loadMarkers();
            })
            .catch(err => alert('Erreur: ' + err));
        });

        // Close modal on overlay click
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', e => {
                if (e.target === overlay) closeModals();
            });
        });

        // ============ Init ============
        loadInitialData();
        loadMarkers();
        setInterval(loadMarkers, 30000);
    </script>
</body>
</html>
