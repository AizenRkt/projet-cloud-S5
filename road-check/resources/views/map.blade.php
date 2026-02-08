<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Road Check - Manager</title>
    <link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}" />
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
        .sig-status.en_cours { background: #f0883e; color: #fff; }
        .sig-status.termine { background: #238636; color: #fff; }
        .sig-desc { font-size: 0.8rem; color: #8b949e; margin-bottom: 8px; }
        .sig-info { font-size: 0.75rem; color: #8b949e; }
        .map-container { flex: 1; position: relative; }
        #map { width: 100%; height: 100%; }
        .stats-bar { position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); background: rgba(22, 27, 34, 0.95); border: 1px solid #30363d; border-radius: 12px; padding: 12px 24px; display: flex; gap: 30px; z-index: 500; }
        .stat-item { text-align: center; }
        .stat-value { font-size: 1.4rem; font-weight: 700; }
        .stat-value.nouveau { color: #1f6feb; }
        .stat-value.en_cours { color: #f0883e; }
        .stat-value.termine { color: #238636; }
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
        
        /* Search & Filter Styles */
        .search-container { padding: 15px; border-bottom: 1px solid #30363d; background: #1c2128; }
        .search-input { width: 100%; padding: 8px 12px; background: #0d1117; border: 1px solid #30363d; border-radius: 6px; color: #c9d1d9; font-size: 0.9rem; transition: border-color 0.2s; }
        .search-input:focus { border-color: #58a6ff; outline: none; }
        .search-input::placeholder { color: #8b949e; }
        .date-filters { display: flex; gap: 8px; margin-top: 10px; }
        .date-filters .search-input { flex: 1; }

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
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <span class="logo"></span>
            <span class="title">Road Check</span>
            <span class="subtitle">| Manager</span>
        </div>
        <div class="navbar-menu">
            <button class="nav-btn" onclick="openUsersModal()">Utilisateurs</button>
            <button class="nav-btn" onclick="openSyncModal()">Synchronisation</button>
            <button class="nav-btn" onclick="logout()">Deconnexion</button>
        </div>
    </nav>
    <div class="main-container">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-title"> Signalements</div>
                <div class="filter-tabs">
                    <button class="filter-tab active" onclick="filterBy('all', this)">Tous</button>
                    <button class="filter-tab" onclick="filterBy('nouveau', this)"> Nouveau</button>
                    <button class="filter-tab" onclick="filterBy('en_cours', this)"> En cours</button>
                    <button class="filter-tab" onclick="filterBy('termine', this)"> Terminé</button>
                </div>
            </div>
            <div class="search-container">
                <input type="text" id="searchInput" class="search-input" placeholder="Rechercher un signalement..." oninput="handleSearch(this.value)">
                <div class="date-filters">
                    <input type="date" id="dateStart" class="search-input" onchange="renderSignalements(); renderMarkers();" placeholder="Du">
                    <input type="date" id="dateEnd" class="search-input" onchange="renderSignalements(); renderMarkers();" placeholder="Au">
                </div>
            </div>
            <div class="sidebar-content" id="signalementsList"><div class="loading">Chargement...</div></div>
        </aside>
        <div class="map-container">
            <div id="map"></div>
            <button class="locate-btn" onclick="locateMe()" title="Ma position">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
            </button>
            <div class="stats-bar">
                <div class="stat-item"><div class="stat-value nouveau" id="statNouveau">0</div><div class="stat-label">Validés</div></div>
                <div class="stat-item"><div class="stat-value en_cours" id="statEnCours">0</div><div class="stat-label">En cours</div></div>
                <div class="stat-item"><div class="stat-value termine" id="statTermine">0</div><div class="stat-label">Terminés</div></div>
                <div class="stat-item"><div class="stat-value" id="statTotal">0</div><div class="stat-label">Total</div></div>
                <div class="stat-item" style="min-width: 100px;">
                    <div class="stat-value" id="statGlobalProgress" style="color: #58a6ff;">0%</div>
                    <div class="stat-label">Avancement Global</div>
                    <div style="height:4px;background:#30363d;border-radius:2px;overflow:hidden;margin-top:4px;width:100%;">
                        <div id="statGlobalProgressBar" style="width:0%;height:100%;background:#58a6ff;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="detail-panel" id="detailPanel">
        <div class="detail-header"><h3> Modifier</h3><button class="close-btn" onclick="closeDetail()">&times;</button></div>
        <div class="detail-content" id="detailContent"></div>
    </div>
    <div class="modal-overlay" id="usersModal">
        <div class="modal">
            <div class="modal-header"><h3> Utilisateurs</h3><button class="close-btn" onclick="closeUsersModal()">&times;</button></div>
            <div class="modal-body" id="usersModalBody"></div>
            <div class="modal-footer"><button class="nav-btn" onclick="openCreateUserForm()"> Nouvel utilisateur</button></div>
        </div>
    </div>
    <div class="modal-overlay" id="rolesModal">
        <div class="modal" style="max-width:500px;">
            <div class="modal-header"><h3>Roles</h3><button class="close-btn" onclick="closeRolesModal()">&times;</button></div>
            <div class="modal-body" id="rolesModalBody"></div>
        </div>
    </div>
    <div class="modal-overlay" id="syncModal">
        <div class="modal" style="max-width:600px;">
            <div class="modal-header"><h3>Synchronisation Firebase</h3><button class="close-btn" onclick="closeSyncModal()">&times;</button></div>
            <div class="modal-body">
                <div id="syncStatus" style="margin-bottom:20px;padding:15px;background:#21262d;border-radius:8px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:10px;">
                        <span>Statut de synchronisation</span>
                        <button class="action-btn" onclick="loadSyncStatus()">Actualiser</button>
                    </div>
                    <div id="syncStatusContent">Chargement...</div>
                </div>
                <div style="display:flex;gap:12px;flex-direction:column;">
                    <div style="padding:15px;background:#21262d;border-radius:8px;">
                        <h4 style="color:#58a6ff;margin-bottom:10px;">Signalements</h4>
                        <p style="font-size:0.85rem;color:#8b949e;margin-bottom:12px;">Synchroniser les signalements locaux vers Firebase Firestore</p>
                        <button class="btn-save" onclick="syncSignalementsToFirebase()">Synchroniser les signalements</button>
                    </div>
                    <div style="padding:15px;background:#21262d;border-radius:8px;">
                        <h4 style="color:#58a6ff;margin-bottom:10px;">Utilisateurs</h4>
                        <p style="font-size:0.85rem;color:#8b949e;margin-bottom:12px;">Synchroniser les utilisateurs locaux vers Firebase Auth</p>
                        <button class="btn-save" style="background:#1f6feb;" onclick="syncUsersToFirebase()">Synchroniser les utilisateurs</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-overlay" id="createUserModal">
        <div class="modal" style="max-width:500px;">
            <div class="modal-header"><h3> Créer utilisateur</h3><button class="close-btn" onclick="closeCreateUserModal()">&times;</button></div>
            <div class="modal-body">
                <form id="createUserForm" onsubmit="createUser(event)">
                    <div class="form-group"><label>Email</label><input type="email" id="newUserEmail" required></div>
                    <div class="form-row">
                        <div class="form-group"><label>Nom</label><input type="text" id="newUserNom" required></div>
                        <div class="form-group"><label>Prénom</label><input type="text" id="newUserPrenom" required></div>
                    </div>
                    <div class="form-group"><label>Mot de passe</label><input type="password" id="newUserPassword" required></div>
                    <div class="form-group"><label>Rôle</label><select id="newUserRole"></select></div>
                    <button type="submit" class="btn-save">Créer</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <div class="loading-text" id="loadingText">Chargement...</div>
    </div>

    <!-- Confirm Modal -->
    <div class="confirm-modal" id="confirmModal">
        <div class="confirm-box">
            <div class="confirm-icon" id="confirmIcon">⚠️</div>
            <div class="confirm-title" id="confirmTitle">Êtes-vous sûr ?</div>
            <div class="confirm-buttons">
                <button class="confirm-btn no" onclick="closeConfirm(false)">Annuler</button>
                <button class="confirm-btn yes" onclick="closeConfirm(true)">Confirmer</button>
            </div>
        </div>
    </div>

    <script src="{{ asset('leaflet/leaflet.js') }}"></script>
    <script>
        let map, markers = [], signalements = [], entreprises = [], typeSignalements = [], typeStatuts = [], utilisateurs = [], roles = [];
        let currentFilter = 'all', selectedSig = null;
        document.addEventListener('DOMContentLoaded', () => { initMap(); loadAllData(); });
        
        function initMap() { map = L.map('map').setView([-18.9137, 47.5361], 13); L.tileLayer('http://localhost:8081/styles/Basic/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map); }
        
        async function loadAllData() {
            try {
                const [sigRes, entRes, typeRes, statRes, userRes, roleRes] = await Promise.all([
                    fetch('/api/signalements').then(r => r.json()), fetch('/api/entreprises').then(r => r.json()),
                    fetch('/api/type-signalements').then(r => r.json()), fetch('/api/type-statuts').then(r => r.json()),
                    fetch('/api/utilisateurs').then(r => r.json()), fetch('/api/roles').then(r => r.json())
                ]);
                signalements = sigRes || []; entreprises = entRes || []; typeSignalements = typeRes || [];
                typeStatuts = statRes || []; utilisateurs = userRes || []; roles = roleRes || [];
                renderSignalements(); renderMarkers(); updateStats();
            } catch (e) { console.error(e); document.getElementById('signalementsList').innerHTML = '<div style="padding:20px;color:#f85149;">Erreur: ' + e.message + '</div>'; }
        }

        function getProgress(status) {
            if (status === 'termine') return 100;
            if (status === 'en_cours') return 50;
            return 0;
        }

        function filterSignalements() {
            const start = document.getElementById('dateStart').value;
            const end = document.getElementById('dateEnd').value;
            const search = document.getElementById('searchInput').value.toLowerCase();
            const startDate = start ? new Date(start) : null;
            const endDate = end ? new Date(end) : null;

            return signalements.filter(s => {
                const statusMatch = currentFilter === 'all' || s.statut === currentFilter;
                
                let searchMatch = true;
                if (search) {
                    searchMatch = (s.description && s.description.toLowerCase().includes(search)) ||
                                 (s.type_signalement && s.type_signalement.toLowerCase().includes(search)) ||
                                 (s.statut && s.statut.toLowerCase().includes(search)) ||
                                 (s.statut_libelle && s.statut_libelle.toLowerCase().includes(search));
                }

                let dateMatch = true;
                if (s.created_at || s.date_signalement) {
                    const sDate = new Date(s.created_at || s.date_signalement);
                    if (startDate && sDate < startDate) dateMatch = false;
                    if (endDate) {
                        const eDate = new Date(endDate);
                        eDate.setHours(23,59,59);
                        if (sDate > eDate) dateMatch = false;
                    }
                }
                return statusMatch && searchMatch && dateMatch;
            });
        }

        function renderSignalements() {
            const container = document.getElementById('signalementsList');
            const filtered = filterSignalements();
            
            if (filtered.length === 0) { container.innerHTML = '<div style="padding:30px;text-align:center;color:#8b949e;">Aucun signalement</div>'; return; }
            
            container.innerHTML = filtered.map(s => {
                const lat = parseFloat(s.latitude);
                const lng = parseFloat(s.longitude);
                const progress = getProgress(s.statut);
                const label = statusLabels[s.statut] || s.statut_libelle || s.statut;
                return `
                    <div class="sig-card${selectedSig?.id_signalement === s.id_signalement ? ' selected' : ''}" onclick="selectSignalement(${s.id_signalement})">
                        <div class="sig-header">
                            <span class="sig-type">${s.type_signalement || 'Non défini'}</span>
                            <span class="sig-status ${s.statut}">${label}</span>
                        </div>
                        <div class="sig-desc">${s.description || 'Aucune description'}</div>
                        <div class="sig-info">
                            <div style="margin-bottom:4px;display:flex;justify-content:space-between;">
                                <span>Avancement</span>
                                <span>${progress}%</span>
                            </div>
                            <div style="height:4px;background:#30363d;border-radius:2px;overflow:hidden;margin-bottom:6px;">
                                <div style="width:${progress}%;height:100%;background:${progress === 100 ? '#238636' : (progress === 50 ? '#f0883e' : '#30363d')}"></div>
                            </div>
                            <div style="font-size:0.7rem;color:#8b949e;">${isNaN(lat) ? '-' : lat.toFixed(4)}, ${isNaN(lng) ? '-' : lng.toFixed(4)}</div>
                            <div style="font-size:0.7rem;color:#8b949e;">${s.created_at ? new Date(s.created_at).toLocaleDateString() : ''}</div>
                        </div>
                    </div>`;
            }).join('');
            updateStats(filtered);
        }

        function renderMarkers() {
            markers.forEach(m => map.removeLayer(m)); markers = [];
            const filtered = filterSignalements();
            
            filtered.forEach(s => {
                const lat = parseFloat(s.latitude);
                const lng = parseFloat(s.longitude);
                if (!isNaN(lat) && !isNaN(lng)) {
                    const colors = { nouveau: '#1f6feb', en_cours: '#f0883e', termine: '#238636', en_attente: '#8b949e', annule: '#f85149' };
                    const marker = L.circleMarker([lat, lng], { radius: 10, fillColor: colors[s.statut] || '#8b949e', color: '#fff', weight: 2, fillOpacity: 0.8 }).addTo(map);

                    const label = statusLabels[s.statut] || s.statut_libelle || s.statut;
                    const progress = getProgress(s.statut);

                    const tooltipContent = `
                        <div style="text-align:left;">
                            <strong>${s.type_signalement || 'Signalement'}</strong><br/>
                            <span style="font-size:0.8rem;color:#8b949e;">${label}</span><br/>
                            <div style="margin:5px 0;">Avancement: ${progress}%</div>
                            <div style="height:4px;background:#ddd;border-radius:2px;overflow:hidden;width:100px;">
                                <div style="width:${progress}%;height:100%;background:${progress === 100 ? '#238636' : (progress === 50 ? '#f0883e' : '#30363d')}"></div>
                            </div>
                            <hr style="border:0;border-top:1px solid #ccc;margin:5px 0;"/>
                            ${s.description || 'Pas de description'}<br/>
                            <small>Surface: ${s.surface_m2 || '-'} m² | Budget: ${s.budget || '-'} Ar</small><br/>
                            <small>Entr: ${s.entreprise || '-'}</small>
                        </div>
                    `;
                    marker.bindTooltip(tooltipContent, { direction: 'top', offset: [0, -10] });
                    marker.on('click', () => selectSignalement(s.id_signalement));
                    markers.push(marker);
                }
            });
        }

        function selectSignalement(id) {
            selectedSig = signalements.find(s => s.id_signalement === id);
            if (!selectedSig) return;
            renderSignalements();
            openDetail();
            const lat = parseFloat(selectedSig.latitude);
            const lng = parseFloat(selectedSig.longitude);
            if (!isNaN(lat) && !isNaN(lng)) map.setView([lat, lng], 16);
        }

        function openDetail() {
            const panel = document.getElementById('detailPanel'); const s = selectedSig;
            let historyHtml = '';
            if (s.history && s.history.length > 0) {
                historyHtml = `
                    <div class="history-section">
                        <div class="history-title">Historique des avancements</div>
                        <div class="history-list">
                            ${s.history.map((h, i) => `
                                <div class="history-item">
                                    <div class="history-dot ${i === s.history.length - 1 ? 'active' : ''}"></div>
                                    <div class="history-info">
                                        <div class="history-label">${h.libelle} (${h.pourcentage}%)</div>
                                        <div class="history-date">${new Date(h.date).toLocaleString()}</div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }

            document.getElementById('detailContent').innerHTML = `
                <form onsubmit="saveSignalement(event)">
                    <div class="form-group"><label>Type</label><select id="editType">${typeSignalements.map(t => `<option value="${t.id_type_signalement}" ${s.id_type_signalement == t.id_type_signalement ? 'selected' : ''}>${t.nom}</option>`).join('')}</select></div>
                    <div class="form-group"><label>Statut</label><select id="editStatut">${typeStatuts.map(t => `<option value="${t.code}" ${s.statut === t.code ? 'selected' : ''}>${t.libelle}</option>`).join('')}</select></div>
                    <div class="form-group"><label>Description</label><textarea id="editDescription">${s.description || ''}</textarea></div>
                    <div class="form-row">
                        <div class="form-group"><label>Surface (m²)</label><input type="number" id="editSurface" value="${s.surface_m2 || ''}"></div>
                        <div class="form-group"><label>Budget</label><input type="number" id="editBudget" value="${s.budget || ''}"></div>
                    </div>
                    <div class="form-group"><label>Entreprise</label><select id="editEntreprise"><option value="">--</option>${entreprises.map(e => `<option value="${e.id_entreprise}" ${s.id_entreprise == e.id_entreprise ? 'selected' : ''}>${e.nom}</option>`).join('')}</select></div>
                    <div style="display:flex;gap:10px;margin-bottom:20px;">
                        <button type="submit" class="btn-save" style="flex:2;">Enregistrer</button>
                        <button type="button" class="btn-save" style="flex:1;background:#30363d;" onclick="closeDetail()">Annuler</button>
                    </div>
                </form>
                ${historyHtml}
            `;
            panel.classList.add('open');
        }

        function closeDetail() { document.getElementById('detailPanel').classList.remove('open'); selectedSig = null; renderSignalements(); }

        async function saveSignalement(e) {
            e.preventDefault(); if (!selectedSig) return;
            const data = { id_type_signalement: document.getElementById('editType').value, statut: document.getElementById('editStatut').value, description: document.getElementById('editDescription').value, surface_m2: document.getElementById('editSurface').value || null, budget: document.getElementById('editBudget').value || null, id_entreprise: document.getElementById('editEntreprise').value || null };
            showLoading('Mise à jour du signalement...');
            try {
                const res = await fetch('/api/signalements/' + selectedSig.id_signalement, { method: 'PUT', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify(data) });
                hideLoading();
                if (res.ok) {
                    showToast('Signalement mis à jour avec succès', 'success');
                    closeDetail();
                    loadAllData();
                } else {
                    showToast('Erreur lors de la mise à jour', 'error');
                }
            } catch (err) {
                hideLoading();
                showToast('Erreur de connexion', 'error');
            }
        }

        function updateStats(filteredData = signalements) {
            const total = filteredData.length;
            const nouveau = filteredData.filter(s => s.statut === 'nouveau').length;
            const enCours = filteredData.filter(s => s.statut === 'en_cours').length;
            const termine = filteredData.filter(s => s.statut === 'termine').length;

            document.getElementById('statNouveau').textContent = nouveau;
            document.getElementById('statEnCours').textContent = enCours;
            document.getElementById('statTermine').textContent = termine;
            document.getElementById('statTotal').textContent = total;

            // Global Progress
            let totalProgress = 0;
            filteredData.forEach(s => totalProgress += getProgress(s.statut));
            const avgProgress = total > 0 ? Math.round(totalProgress / total) : 0;
            
            document.getElementById('statGlobalProgress').textContent = avgProgress + '%';
            document.getElementById('statGlobalProgressBar').style.width = avgProgress + '%';
        }

        function filterBy(filter, btn) { currentFilter = filter; document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active')); btn.classList.add('active'); renderSignalements(); renderMarkers(); }

        function openUsersModal() { document.getElementById('usersModal').classList.add('open'); renderUsersTable(); }
        function closeUsersModal() { document.getElementById('usersModal').classList.remove('open'); }
        function renderUsersTable() {
            const body = document.getElementById('usersModalBody');
            body.innerHTML = '<table class="data-table"><thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Actions</th></tr></thead><tbody>' + utilisateurs.map(u => '<tr><td>' + (u.prenom || '') + ' ' + (u.nom || '') + '</td><td>' + u.email + '</td><td><span class="badge ' + (u.role || '').toLowerCase() + '">' + (u.role || 'N/A') + '</span></td><td>' + (u.bloque ? '<span class="badge blocked">Bloqué</span>' : '<span class="badge active">Actif</span>') + '</td><td>' + (u.bloque ? '<button class="action-btn" onclick="unblockUser(' + u.id_utilisateur + ')">Débloquer</button>' : '') + '</td></tr>').join('') + '</tbody></table>';
        }
        async function unblockUser(id) {
            const confirmed = await showConfirm('Voulez-vous débloquer cet utilisateur ?');
            if (!confirmed) return;
            showLoading('Déblocage en cours...');
            try {
                const res = await fetch('/api/utilisateurs/' + id + '/unblock', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
                hideLoading();
                if (res.ok) {
                    showToast('Utilisateur débloqué avec succès', 'success');
                    loadAllData();
                    renderUsersTable();
                } else {
                    showToast('Erreur lors du déblocage', 'error');
                }
            } catch (e) {
                hideLoading();
                showToast('Erreur de connexion', 'error');
            }
        }
        function openCreateUserForm() { closeUsersModal(); document.getElementById('newUserRole').innerHTML = roles.map(r => '<option value="' + r.id_role + '">' + r.nom + '</option>').join(''); document.getElementById('createUserModal').classList.add('open'); }
        function closeCreateUserModal() { document.getElementById('createUserModal').classList.remove('open'); }
        async function createUser(e) {
            e.preventDefault();
            const data = { email: document.getElementById('newUserEmail').value, nom: document.getElementById('newUserNom').value, prenom: document.getElementById('newUserPrenom').value, password: document.getElementById('newUserPassword').value, id_role: document.getElementById('newUserRole').value };
            showLoading('Création de l\'utilisateur...');
            try {
                const res = await fetch('/api/utilisateurs', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify(data) });
                hideLoading();
                if (res.ok) {
                    showToast('Utilisateur créé avec succès', 'success');
                    closeCreateUserModal();
                    document.getElementById('createUserForm').reset();
                    loadAllData();
                } else {
                    const err = await res.json();
                    showToast(err.message || 'Erreur lors de la création', 'error');
                }
            } catch (e) {
                hideLoading();
                showToast('Erreur de connexion', 'error');
            }
        }
        function openRolesModal() { document.getElementById('rolesModal').classList.add('open'); document.getElementById('rolesModalBody').innerHTML = '<table class="data-table"><thead><tr><th>ID</th><th>Nom</th></tr></thead><tbody>' + roles.map(r => '<tr><td>' + r.id_role + '</td><td>' + r.nom + '</td></tr>').join('') + '</tbody></table>'; }
        function closeRolesModal() { document.getElementById('rolesModal').classList.remove('open'); }
        async function logout() { try { await fetch('/logout', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } }); window.location.href = '/login'; } catch (e) { alert('Erreur'); } }
        function syncFirebase() { alert('Synchronisation...'); }
        function locateMe() {
            if (map) map.locate({setView: true, maxZoom: 16});
        }
        function handleSearch(val) {
             renderSignalements(); 
             renderMarkers();
        }
    </script>
</body>
</html>
