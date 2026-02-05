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
            <button class="nav-btn" onclick="openUsersModal()"> Utilisateurs</button>
            <button class="nav-btn" onclick="syncFirebase()"> Sync</button>
            <button class="nav-btn" onclick="logout()"> Deconnexion</button>
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
            <div class="sidebar-content" id="signalementsList"><div class="loading">Chargement...</div></div>
        </aside>
        <div class="map-container">
            <div id="map"></div>
            <div class="stats-bar">
                <div class="stat-item"><div class="stat-value nouveau" id="statNouveau">0</div><div class="stat-label">Nouveaux</div></div>
                <div class="stat-item"><div class="stat-value en_cours" id="statEnCours">0</div><div class="stat-label">En cours</div></div>
                <div class="stat-item"><div class="stat-value termine" id="statTermine">0</div><div class="stat-label">Terminés</div></div>
                <div class="stat-item"><div class="stat-value" id="statTotal">0</div><div class="stat-label">Total</div></div>
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
            <div class="modal-header"><h3> Rôles</h3><button class="close-btn" onclick="closeRolesModal()">&times;</button></div>
            <div class="modal-body" id="rolesModalBody"></div>
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
        function renderSignalements() {
            const container = document.getElementById('signalementsList');
            const filtered = currentFilter === 'all' ? signalements : signalements.filter(s => s.statut === currentFilter);
            if (filtered.length === 0) { container.innerHTML = '<div style="padding:30px;text-align:center;color:#8b949e;">Aucun signalement</div>'; return; }
            container.innerHTML = filtered.map(s => {
                const lat = parseFloat(s.latitude);
                const lng = parseFloat(s.longitude);
                return '<div class="sig-card' + (selectedSig?.id_signalement === s.id_signalement ? ' selected' : '') + '" onclick="selectSignalement(' + s.id_signalement + ')"><div class="sig-header"><span class="sig-type">' + (s.type_signalement || 'Non défini') + '</span><span class="sig-status ' + s.statut + '">' + (s.statut_libelle || 'Nouveau') + '</span></div><div class="sig-desc">' + (s.description || 'Aucune description') + '</div><div class="sig-info"> ' + (isNaN(lat) ? '-' : lat.toFixed(4)) + ', ' + (isNaN(lng) ? '-' : lng.toFixed(4)) + '</div></div>';
            }).join('');
        }
        function renderMarkers() {
            markers.forEach(m => map.removeLayer(m)); markers = [];
            signalements.forEach(s => {
                const lat = parseFloat(s.latitude);
                const lng = parseFloat(s.longitude);
                if (!isNaN(lat) && !isNaN(lng)) {
                    const colors = { nouveau: '#1f6feb', en_cours: '#f0883e', termine: '#238636' };
                    const marker = L.circleMarker([lat, lng], { radius: 10, fillColor: colors[s.statut] || '#1f6feb', color: '#fff', weight: 2, fillOpacity: 0.8 }).addTo(map);

                    // Tooltip for hover (all info)
                    const tooltipContent = `
                        <div style="text-align:left;">
                            <strong>${s.type_signalement || 'Signalement'}</strong><br/>
                            <span style="font-size:0.8rem;color:#8b949e;">${s.statut_libelle}</span><br/>
                            <hr style="border:0;border-top:1px solid #ccc;margin:5px 0;"/>
                            ${s.description || 'Pas de description'}<br/>
                            <small>Surface: ${s.surface_m2 || '-'} m² | Budget: ${s.budget || '-'} Ar</small><br/>
                            <small>Entr: ${s.entreprise || '-'}</small>
                        </div>
                    `;
                    marker.bindTooltip(tooltipContent, { direction: 'top', offset: [0, -10] });

                    marker.bindTooltip(tooltipContent, { direction: 'top', offset: [0, -10] });

                    // Click action: open detail directly
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
            document.getElementById('detailContent').innerHTML = '<form onsubmit="saveSignalement(event)"><div class="form-group"><label>Type</label><select id="editType">' + typeSignalements.map(t => '<option value="' + t.id_type_signalement + '"' + (s.id_type_signalement == t.id_type_signalement ? ' selected' : '') + '>' + t.nom + '</option>').join('') + '</select></div><div class="form-group"><label>Statut</label><select id="editStatut">' + typeStatuts.map(t => '<option value="' + t.code + '"' + (s.statut === t.code ? ' selected' : '') + '>' + t.libelle + '</option>').join('') + '</select></div><div class="form-group"><label>Description</label><textarea id="editDescription">' + (s.description || '') + '</textarea></div><div class="form-row"><div class="form-group"><label>Surface (m²)</label><input type="number" id="editSurface" value="' + (s.surface_m2 || '') + '"></div><div class="form-group"><label>Budget</label><input type="number" id="editBudget" value="' + (s.budget || '') + '"></div></div><div class="form-group"><label>Entreprise</label><select id="editEntreprise"><option value="">--</option>' + entreprises.map(e => '<option value="' + e.id_entreprise + '"' + (s.id_entreprise == e.id_entreprise ? ' selected' : '') + '>' + e.nom + '</option>').join('') + '</select></div><div style="display:flex;gap:10px;"><button type="submit" class="btn-save" style="flex:2;"> Enregistrer</button><button type="button" class="btn-save" style="flex:1;background:#30363d;" onclick="closeDetail()">Annuler</button></div></form>';
            panel.classList.add('open');
        }
        function closeDetail() { document.getElementById('detailPanel').classList.remove('open'); selectedSig = null; renderSignalements(); }
        async function saveSignalement(e) {
            e.preventDefault(); if (!selectedSig) return;
            const data = { id_type_signalement: document.getElementById('editType').value, statut: document.getElementById('editStatut').value, description: document.getElementById('editDescription').value, surface_m2: document.getElementById('editSurface').value || null, budget: document.getElementById('editBudget').value || null, id_entreprise: document.getElementById('editEntreprise').value || null };
            try { const res = await fetch('/api/signalements/' + selectedSig.id_signalement, { method: 'PUT', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify(data) }); if (res.ok) { alert('Mis à jour'); closeDetail(); loadAllData(); } else alert('Erreur'); } catch (err) { alert('Erreur'); }
        }
        function updateStats() {
            document.getElementById('statNouveau').textContent = signalements.filter(s => s.statut === 'nouveau').length;
            document.getElementById('statEnCours').textContent = signalements.filter(s => s.statut === 'en_cours').length;
            document.getElementById('statTermine').textContent = signalements.filter(s => s.statut === 'termine').length;
            document.getElementById('statTotal').textContent = signalements.length;
        }
        function filterBy(filter, btn) { currentFilter = filter; document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active')); btn.classList.add('active'); renderSignalements(); }
        function openUsersModal() { document.getElementById('usersModal').classList.add('open'); renderUsersTable(); }
        function closeUsersModal() { document.getElementById('usersModal').classList.remove('open'); }
        function renderUsersTable() {
            const body = document.getElementById('usersModalBody');
            body.innerHTML = '<table class="data-table"><thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Actions</th></tr></thead><tbody>' + utilisateurs.map(u => '<tr><td>' + (u.prenom || '') + ' ' + (u.nom || '') + '</td><td>' + u.email + '</td><td><span class="badge ' + (u.role || '').toLowerCase() + '">' + (u.role || 'N/A') + '</span></td><td>' + (u.bloque ? '<span class="badge blocked">Bloqué</span>' : '<span class="badge active">Actif</span>') + '</td><td>' + (u.bloque ? '<button class="action-btn" onclick="unblockUser(' + u.id_utilisateur + ')">Débloquer</button>' : '') + '</td></tr>').join('') + '</tbody></table>';
        }
        async function unblockUser(id) { if (!confirm('Débloquer?')) return; try { const res = await fetch('/api/utilisateurs/' + id + '/unblock', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } }); if (res.ok) { alert('Débloqué'); loadAllData(); renderUsersTable(); } } catch (e) { alert('Erreur'); } }
        function openCreateUserForm() { closeUsersModal(); document.getElementById('newUserRole').innerHTML = roles.map(r => '<option value="' + r.id_role + '">' + r.nom + '</option>').join(''); document.getElementById('createUserModal').classList.add('open'); }
        function closeCreateUserModal() { document.getElementById('createUserModal').classList.remove('open'); }
        async function createUser(e) {
            e.preventDefault();
            const data = { email: document.getElementById('newUserEmail').value, nom: document.getElementById('newUserNom').value, prenom: document.getElementById('newUserPrenom').value, password: document.getElementById('newUserPassword').value, id_role: document.getElementById('newUserRole').value };
            try { const res = await fetch('/api/utilisateurs', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify(data) }); if (res.ok) { alert('Créé'); closeCreateUserModal(); loadAllData(); } else { const err = await res.json(); alert(err.message || 'Erreur'); } } catch (e) { alert('Erreur'); }
        }
        function openRolesModal() { document.getElementById('rolesModal').classList.add('open'); document.getElementById('rolesModalBody').innerHTML = '<table class="data-table"><thead><tr><th>ID</th><th>Nom</th></tr></thead><tbody>' + roles.map(r => '<tr><td>' + r.id_role + '</td><td>' + r.nom + '</td></tr>').join('') + '</tbody></table>'; }
        function closeRolesModal() { document.getElementById('rolesModal').classList.remove('open'); }
        async function logout() { try { await fetch('/logout', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } }); window.location.href = '/login'; } catch (e) { alert('Erreur'); } }
        async function syncFirebase() { try { const res = await fetch('/api/sync-users', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } }); const data = await res.json(); alert(data.message); } catch (e) { alert('Erreur de synchronisation'); } }
    </script>
</body>
</html>
