import React, { useEffect } from 'react';
import ReactDOM from 'react-dom/client';
import L from 'leaflet';

let map;
let markers = [];
let signalements = [];
let entreprises = [];
let typeSignalements = [];
let typeStatuts = [];
let utilisateurs = [];
let roles = [];
let currentFilter = 'en_attente';
let selectedSig = null;
let confirmCallback = null;

function showToast(message, type = 'info', duration = 4000) {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    const icons = { success: '✓', error: '✗', info: 'ℹ', warning: '⚠' };
    toast.innerHTML = `
        <span class="toast-icon">${icons[type] || 'ℹ'}</span>
        <span class="toast-message">${message}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">×</button>
    `;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

function showLoading(text = 'Chargement...') {
    const loadingText = document.getElementById('loadingText');
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (!loadingText || !loadingOverlay) return;
    loadingText.textContent = text;
    loadingOverlay.classList.add('show');
}

function hideLoading() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (!loadingOverlay) return;
    loadingOverlay.classList.remove('show');
}

function showConfirm(message, icon = '') {
    return new Promise((resolve) => {
        const confirmTitle = document.getElementById('confirmTitle');
        const confirmIcon = document.getElementById('confirmIcon');
        const confirmModal = document.getElementById('confirmModal');
        if (!confirmTitle || !confirmIcon || !confirmModal) {
            resolve(false);
            return;
        }
        confirmTitle.textContent = message;
        confirmIcon.textContent = icon;
        confirmModal.classList.add('show');
        confirmCallback = resolve;
    });
}

function closeConfirm(result) {
    const confirmModal = document.getElementById('confirmModal');
    if (confirmModal) confirmModal.classList.remove('show');
    if (confirmCallback) {
        confirmCallback(result);
        confirmCallback = null;
    }
}

function initMap() {
    map = L.map('map').setView([-18.9137, 47.5361], 13);
    L.tileLayer('http://localhost:8081/styles/Basic/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
}

async function loadAllData() {
    try {
        const [sigRes, entRes, typeRes, statRes, userRes, roleRes] = await Promise.all([
            fetch('/api/signalements').then((r) => r.json()),
            fetch('/api/entreprises').then((r) => r.json()),
            fetch('/api/type-signalements').then((r) => r.json()),
            fetch('/api/type-statuts').then((r) => r.json()),
            fetch('/api/utilisateurs').then((r) => r.json()),
            fetch('/api/roles').then((r) => r.json())
        ]);
        signalements = sigRes || [];
        entreprises = entRes || [];
        typeSignalements = typeRes || [];
        typeStatuts = statRes || [];
        utilisateurs = userRes || [];
        roles = roleRes || [];
        renderSignalements();
        renderMarkers();
        updateStats();
    } catch (e) {
        const list = document.getElementById('signalementsList');
        if (list) list.innerHTML = `<div style="padding:20px;color:#f85149;">Erreur: ${e.message}</div>`;
    }
}

function renderSignalements() {
    const container = document.getElementById('signalementsList');
    if (!container) return;
    const filtered = currentFilter === 'all' ? signalements : signalements.filter((s) => s.statut === currentFilter);
    if (filtered.length === 0) {
        container.innerHTML = '<div style="padding:30px;text-align:center;color:#8b949e;">Aucun signalement</div>';
        return;
    }
    container.innerHTML = filtered
        .map((s) => {
            const lat = parseFloat(s.latitude);
            const lng = parseFloat(s.longitude);
            return (
                '<div class="sig-card' +
                (selectedSig?.id_signalement === s.id_signalement ? ' selected' : '') +
                '" onclick="selectSignalement(' +
                s.id_signalement +
                ')"><div class="sig-header"><span class="sig-type">' +
                (s.type_signalement || 'Non defini') +
                '</span><span class="sig-status ' +
                s.statut +
                '">' +
                (s.statut_libelle || 'Nouveau') +
                '</span></div><div class="sig-desc">' +
                (s.description || 'Aucune description') +
                '</div><div class="sig-info"> ' +
                (isNaN(lat) ? '-' : lat.toFixed(4)) +
                ', ' +
                (isNaN(lng) ? '-' : lng.toFixed(4)) +
                '</div></div>'
            );
        })
        .join('');
}

function renderMarkers() {
    markers.forEach((m) => map.removeLayer(m));
    markers = [];
    signalements.forEach((s) => {
        const lat = parseFloat(s.latitude);
        const lng = parseFloat(s.longitude);
        if (!isNaN(lat) && !isNaN(lng)) {
            const colors = { nouveau: '#1f6feb', en_attente: '#d29922', en_cours: '#f0883e', termine: '#238636', annule: '#f85149' };
            const marker = L.circleMarker([lat, lng], {
                radius: 10,
                fillColor: colors[s.statut] || '#1f6feb',
                color: '#fff',
                weight: 2,
                fillOpacity: 0.8
            }).addTo(map);

            const tooltipContent = `
                <div style="text-align:left;">
                    <strong>${s.type_signalement || 'Signalement'}</strong><br/>
                    <span style="font-size:0.8rem;color:#8b949e;">${s.statut_libelle}</span><br/>
                    <hr style="border:0;border-top:1px solid #ccc;margin:5px 0;"/>
                    ${s.description || 'Pas de description'}<br/>
                    <small>Surface: ${s.surface_m2 || '-'} m2 | Budget: ${s.budget || '-'} Ar</small><br/>
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
    selectedSig = signalements.find((s) => s.id_signalement === id);
    if (!selectedSig) return;
    renderSignalements();
    openDetail();
    const lat = parseFloat(selectedSig.latitude);
    const lng = parseFloat(selectedSig.longitude);
    if (!isNaN(lat) && !isNaN(lng)) map.setView([lat, lng], 16);
}

function openDetail() {
    const panel = document.getElementById('detailPanel');
    const s = selectedSig;
    if (!panel || !s) return;
    document.getElementById('detailContent').innerHTML =
        '<form onsubmit="saveSignalement(event)"><div class="form-group"><label>Type</label><select id="editType">' +
        typeSignalements
            .map(
                (t) =>
                    '<option value="' +
                    t.id_type_signalement +
                    '"' +
                    (s.id_type_signalement == t.id_type_signalement ? ' selected' : '') +
                    '>' +
                    t.nom +
                    '</option>'
            )
            .join('') +
        '</select></div><div class="form-group"><label>Statut</label><select id="editStatut">' +
        typeStatuts
            .map(
                (t) =>
                    '<option value="' +
                    t.code +
                    '"' +
                    (s.statut === t.code ? ' selected' : '') +
                    '>' +
                    t.libelle +
                    '</option>'
            )
            .join('') +
        '</select></div><div class="form-group"><label>Description</label><textarea id="editDescription">' +
        (s.description || '') +
        '</textarea></div><div class="form-row"><div class="form-group"><label>Surface (m2)</label><input type="number" id="editSurface" value="' +
        (s.surface_m2 || '') +
        '"></div><div class="form-group"><label>Budget</label><input type="number" id="editBudget" value="' +
        (s.budget || '') +
        '"></div></div><div class="form-group"><label>Entreprise</label><select id="editEntreprise"><option value="">--</option>' +
        entreprises
            .map(
                (e) =>
                    '<option value="' +
                    e.id_entreprise +
                    '"' +
                    (s.id_entreprise == e.id_entreprise ? ' selected' : '') +
                    '>' +
                    e.nom +
                    '</option>'
            )
            .join('') +
        '</select></div><div style="display:flex;gap:10px;"><button type="submit" class="btn-save" style="flex:2;"> Enregistrer</button><button type="button" class="btn-save" style="flex:1;background:#30363d;" onclick="closeDetail()">Annuler</button></div></form>';
    panel.classList.add('open');
}

function closeDetail() {
    const panel = document.getElementById('detailPanel');
    if (panel) panel.classList.remove('open');
    selectedSig = null;
    renderSignalements();
}

async function saveSignalement(e) {
    e.preventDefault();
    if (!selectedSig) return;
    const nextStatus = document.getElementById('editStatut').value;
    const currentStatus = selectedSig.statut || 'nouveau';
    const statusOrder = { en_attente: 0, annule: 0, nouveau: 1, en_cours: 2, termine: 3 };
    const currentRank = statusOrder[currentStatus];
    const nextRank = statusOrder[nextStatus];
    const getStatusLabel = (code) => {
        const match = typeStatuts.find((t) => t.code === code);
        return match ? match.libelle : code;
    };

    if (currentStatus !== nextStatus && Number.isFinite(currentRank) && Number.isFinite(nextRank)) {
        const isOutOfOrder = nextRank < currentRank || nextRank > currentRank + 1;
        if (isOutOfOrder) {
            const confirmed = await showConfirm(
                `Voulez-vous passer le statut actuel "${getStatusLabel(currentStatus)}" a "${getStatusLabel(nextStatus)}" ?`
            );
            if (!confirmed) return;
        }
    }

    const data = {
        id_type_signalement: document.getElementById('editType').value,
        statut: nextStatus,
        description: document.getElementById('editDescription').value,
        surface_m2: document.getElementById('editSurface').value || null,
        budget: document.getElementById('editBudget').value || null,
        id_entreprise: document.getElementById('editEntreprise').value || null
    };
    showLoading('Mise a jour du signalement...');
    try {
        const res = await fetch(`/api/signalements/${selectedSig.id_signalement}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });
        hideLoading();
        if (res.ok) {
            showToast('Signalement mis a jour avec succes', 'success');
            closeDetail();
            loadAllData();
        } else {
            showToast('Erreur lors de la mise a jour', 'error');
        }
    } catch (err) {
        hideLoading();
        showToast('Erreur de connexion', 'error');
    }
}

function updateStats() {
    document.getElementById('statNouveau').textContent = signalements.filter((s) => s.statut === 'nouveau').length;
    document.getElementById('statEnAttente').textContent = signalements.filter((s) => s.statut === 'en_attente').length;
    document.getElementById('statEnCours').textContent = signalements.filter((s) => s.statut === 'en_cours').length;
    document.getElementById('statTermine').textContent = signalements.filter((s) => s.statut === 'termine').length;
    document.getElementById('statAnnule').textContent = signalements.filter((s) => s.statut === 'annule').length;
    document.getElementById('statTotal').textContent = signalements.length;
}

function filterBy(filter, btn) {
    currentFilter = filter;
    document.querySelectorAll('.filter-tab').forEach((b) => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    renderSignalements();
}

function openUsersModal() {
    document.getElementById('usersModal').classList.add('open');
    renderUsersTable();
}

function closeUsersModal() {
    document.getElementById('usersModal').classList.remove('open');
}

function renderUsersTable() {
    const body = document.getElementById('usersModalBody');
    if (!body) return;
    body.innerHTML =
        '<table class="data-table"><thead><tr><th>Nom</th><th>Email</th><th>Role</th><th>Statut</th><th>Actions</th></tr></thead><tbody>' +
        utilisateurs
            .map((u) => {
                const actionButtons =
                    '<button class="action-btn" onclick="openEditUserForm(' +
                    u.id_utilisateur +
                    ')">Modifier</button>' +
                    (u.bloque ? ' <button class="action-btn" onclick="unblockUser(' + u.id_utilisateur + ')">Debloquer</button>' : '');
                return (
                    '<tr><td>' +
                    (u.prenom || '') +
                    ' ' +
                    (u.nom || '') +
                    '</td><td>' +
                    u.email +
                    '</td><td><span class="badge ' +
                    (u.role || '').toLowerCase() +
                    '">' +
                    (u.role || 'N/A') +
                    '</span></td><td>' +
                    (u.bloque ? '<span class="badge blocked">Bloque</span>' : '<span class="badge active">Actif</span>') +
                    '</td><td>' +
                    actionButtons +
                    '</td></tr>'
                );
            })
            .join('') +
        '</tbody></table>';
}

async function unblockUser(id) {
    const confirmed = await showConfirm('Voulez-vous debloquer cet utilisateur ?');
    if (!confirmed) return;
    showLoading('Deblocage en cours...');
    try {
        const res = await fetch(`/api/utilisateurs/${id}/unblock`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        });
        hideLoading();
        if (res.ok) {
            showToast('Utilisateur debloque avec succes', 'success');
            loadAllData();
            renderUsersTable();
        } else {
            showToast('Erreur lors du deblocage', 'error');
        }
    } catch (e) {
        hideLoading();
        showToast('Erreur de connexion', 'error');
    }
}

function openCreateUserForm() {
    closeUsersModal();
    document.getElementById('newUserRole').innerHTML = roles.map((r) => `<option value="${r.id_role}">${r.nom}</option>`).join('');
    document.getElementById('createUserModal').classList.add('open');
}

function closeCreateUserModal() {
    document.getElementById('createUserModal').classList.remove('open');
}

async function createUser(e) {
    e.preventDefault();
    const data = {
        email: document.getElementById('newUserEmail').value,
        nom: document.getElementById('newUserNom').value,
        prenom: document.getElementById('newUserPrenom').value,
        password: document.getElementById('newUserPassword').value,
        id_role: document.getElementById('newUserRole').value
    };
    showLoading('Creation de l\'utilisateur...');
    try {
        const res = await fetch('/api/utilisateurs', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });
        hideLoading();
        if (res.ok) {
            showToast('Utilisateur cree avec succes', 'success');
            closeCreateUserModal();
            document.getElementById('createUserForm').reset();
            loadAllData();
        } else {
            const err = await res.json();
            showToast(err.message || 'Erreur lors de la creation', 'error');
        }
    } catch (e) {
        hideLoading();
        showToast('Erreur de connexion', 'error');
    }
}

function openEditUserForm(id) {
    const user = utilisateurs.find((u) => u.id_utilisateur === id);
    if (!user) return;
    closeUsersModal();
    document.getElementById('editUserId').value = user.id_utilisateur;
    document.getElementById('editUserEmail').value = user.email || '';
    document.getElementById('editUserNom').value = user.nom || '';
    document.getElementById('editUserPrenom').value = user.prenom || '';
    document.getElementById('editUserRole').innerHTML = roles.map((r) => `<option value="${r.id_role}">${r.nom}</option>`).join('');
    document.getElementById('editUserRole').value = user.id_role || '';
    document.getElementById('editUserBloque').checked = Boolean(user.bloque);
    document.getElementById('editUserModal').classList.add('open');
}

function closeEditUserModal() {
    document.getElementById('editUserModal').classList.remove('open');
}

async function updateUser(e) {
    e.preventDefault();
    const id = document.getElementById('editUserId').value;
    const data = {
        nom: document.getElementById('editUserNom').value,
        prenom: document.getElementById('editUserPrenom').value,
        id_role: document.getElementById('editUserRole').value,
        bloque: document.getElementById('editUserBloque').checked
    };
    showLoading('Mise a jour de l\'utilisateur...');
    try {
        const res = await fetch(`/api/utilisateurs/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });
        hideLoading();
        if (res.ok) {
            showToast('Utilisateur mis a jour', 'success');
            closeEditUserModal();
            await loadAllData();
            openUsersModal();
        } else {
            const err = await res.json();
            showToast(err.message || 'Erreur lors de la mise a jour', 'error');
        }
    } catch (e) {
        hideLoading();
        showToast('Erreur de connexion', 'error');
    }
}

function openRolesModal() {
    document.getElementById('rolesModal').classList.add('open');
    document.getElementById('rolesModalBody').innerHTML =
        '<table class="data-table"><thead><tr><th>ID</th><th>Nom</th></tr></thead><tbody>' +
        roles.map((r) => `<tr><td>${r.id_role}</td><td>${r.nom}</td></tr>`).join('') +
        '</tbody></table>';
}

function closeRolesModal() {
    document.getElementById('rolesModal').classList.remove('open');
}

function openSyncModal() {
    document.getElementById('syncModal').classList.add('open');
    loadSyncStatus();
}

function closeSyncModal() {
    document.getElementById('syncModal').classList.remove('open');
}

function openStatsModal() {
    document.getElementById('statsModal').classList.add('open');
    loadStats();
}

function closeStatsModal() {
    document.getElementById('statsModal').classList.remove('open');
}

function resetStatsFilters() {
    document.getElementById('statsStartDate').value = '';
    document.getElementById('statsEndDate').value = '';
    loadStats();
}

async function loadStats(e) {
    if (e) e.preventDefault();
    const startDate = document.getElementById('statsStartDate').value;
    const endDate = document.getElementById('statsEndDate').value;
    const params = new URLSearchParams();
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    const url = `/api/signalements/stats${params.toString() ? `?${params.toString()}` : ''}`;

    document.getElementById('statsContent').innerHTML = 'Chargement...';
    try {
        const res = await fetch(url);
        if (!res.ok) {
            const err = await res.json();
            document.getElementById('statsContent').innerHTML = `<div style="color:#f85149;">${err.message || 'Erreur de chargement'}</div>`;
            return;
        }
        const data = await res.json();
        const formatNumber = (value) => {
            const num = Number(value || 0);
            return Number.isFinite(num) ? num.toLocaleString('fr-FR') : '0';
        };
        document.getElementById('statsContent').innerHTML = `
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
                <div style="background:#21262d;border:1px solid #30363d;border-radius:8px;padding:12px;">
                    <div style="font-size:0.75rem;color:#8b949e;">Total</div>
                    <div style="font-size:1.4rem;font-weight:700;">${formatNumber(data.total)}</div>
                </div>
                <div style="background:#21262d;border:1px solid #30363d;border-radius:8px;padding:12px;">
                    <div style="font-size:0.75rem;color:#8b949e;">Nouveaux</div>
                    <div style="font-size:1.4rem;font-weight:700;color:#1f6feb;">${formatNumber(data.nouveau)}</div>
                </div>
                <div style="background:#21262d;border:1px solid #30363d;border-radius:8px;padding:12px;">
                    <div style="font-size:0.75rem;color:#8b949e;">En attente</div>
                    <div style="font-size:1.4rem;font-weight:700;color:#d29922;">${formatNumber(data.en_attente)}</div>
                </div>
                <div style="background:#21262d;border:1px solid #30363d;border-radius:8px;padding:12px;">
                    <div style="font-size:0.75rem;color:#8b949e;">En cours</div>
                    <div style="font-size:1.4rem;font-weight:700;color:#f0883e;">${formatNumber(data.en_cours)}</div>
                </div>
                <div style="background:#21262d;border:1px solid #30363d;border-radius:8px;padding:12px;">
                    <div style="font-size:0.75rem;color:#8b949e;">Termines</div>
                    <div style="font-size:1.4rem;font-weight:700;color:#238636;">${formatNumber(data.termine)}</div>
                </div>
                <div style="background:#21262d;border:1px solid #30363d;border-radius:8px;padding:12px;">
                    <div style="font-size:0.75rem;color:#8b949e;">Annules</div>
                    <div style="font-size:1.4rem;font-weight:700;color:#f85149;">${formatNumber(data.annule)}</div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:12px;">
                <div style="background:#21262d;border:1px solid #30363d;border-radius:8px;padding:12px;">
                    <div style="font-size:0.75rem;color:#8b949e;">Surface totale (m2)</div>
                    <div style="font-size:1.2rem;font-weight:700;">${formatNumber(data.total_surface)}</div>
                </div>
                <div style="background:#21262d;border:1px solid #30363d;border-radius:8px;padding:12px;">
                    <div style="font-size:0.75rem;color:#8b949e;">Budget total</div>
                    <div style="font-size:1.2rem;font-weight:700;">${formatNumber(data.total_budget)} Ar</div>
                </div>
                <div style="background:#21262d;border:1px solid #30363d;border-radius:8px;padding:12px;">
                    <div style="font-size:0.75rem;color:#8b949e;">Avancement moyen</div>
                    <div style="font-size:1.2rem;font-weight:700;">${formatNumber(data.avancement)}%</div>
                </div>
            </div>
        `;
    } catch (err) {
        document.getElementById('statsContent').innerHTML = '<div style="color:#f85149;">Erreur de connexion</div>';
    }
}

async function loadSyncStatus() {
    try {
        const res = await fetch('/api/sync/status');
        const data = await res.json();
        document.getElementById('syncStatusContent').innerHTML = `
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:15px;text-align:center;">
                <div><div style="font-size:1.5rem;font-weight:700;color:#c9d1d9;">${data.total}</div><div style="font-size:0.75rem;color:#8b949e;">Total</div></div>
                <div><div style="font-size:1.5rem;font-weight:700;color:#238636;">${data.synced}</div><div style="font-size:0.75rem;color:#8b949e;">Synchronises</div></div>
                <div><div style="font-size:1.5rem;font-weight:700;color:#f0883e;">${data.pending}</div><div style="font-size:0.75rem;color:#8b949e;">En attente</div></div>
                <div><div style="font-size:1.5rem;font-weight:700;color:#f85149;">${data.with_errors}</div><div style="font-size:0.75rem;color:#8b949e;">Erreurs</div></div>
            </div>
            <div style="margin-top:12px;background:#30363d;border-radius:4px;height:8px;overflow:hidden;">
                <div style="background:#238636;height:100%;width:${data.sync_percentage}%;transition:width 0.3s;"></div>
            </div>
            <div style="text-align:center;margin-top:8px;font-size:0.8rem;color:#8b949e;">${data.sync_percentage}% synchronise</div>
        `;
    } catch (e) {
        document.getElementById('syncStatusContent').innerHTML = '<div style="color:#f85149;">Erreur de chargement du statut</div>';
    }
}

async function syncUsersToFirebaseAuth() {
    showLoading('Synchronisation des utilisateurs vers Firebase Auth...');
    try {
        const res = await fetch('/api/sync-users', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        });
        const data = await res.json();
        hideLoading();
        if (res.ok) {
            showToast(data.message || 'Synchronisation reussie', 'success');
        } else {
            showToast(data.message || 'Erreur de synchronisation', 'error');
        }
    } catch (e) {
        hideLoading();
        showToast('Erreur de connexion a Firebase Auth', 'error');
    }
}

async function syncBidirectional() {
    const confirmed = await showConfirm(
        'Lancer la synchronisation bidirectionnelle ?\n\n1. PostgreSQL -> Firestore\n2. Firestore -> PostgreSQL\n\nOrdre: roles -> entreprises -> types -> utilisateurs -> signalements -> tentatives'
    );
    if (!confirmed) return;
    showLoading('Synchronisation bidirectionnelle en cours...');
    try {
        const res = await fetch('/api/sync/bidirectional', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        });
        const data = await res.json();
        hideLoading();

        showToast(data.message, data.success ? 'success' : 'warning', 6000);
        if (data.pg_to_firestore) {
            const pg = data.pg_to_firestore;
            const totalPg = Object.values(pg).reduce((a, b) => a + b, 0);
            if (totalPg > 0) {
                showToast(`PG->Firestore: ${totalPg} document(s) envoye(s)`, 'info', 5000);
            }
        }
        if (data.firestore_to_pg) {
            for (const [col, info] of Object.entries(data.firestore_to_pg)) {
                if (info.inserted > 0 || info.updated > 0) {
                    showToast(`FS->PG ${col}: ${info.inserted} insere(s), ${info.updated} mis a jour`, 'info', 5000);
                }
                if (info.errors && info.errors.length > 0) {
                    showToast(`${col}: ${info.errors.length} erreur(s)`, 'warning', 6000);
                    info.errors.forEach((err) => {
                        console.error(`Sync erreur ${col}:`, err);
                        showToast(`⚠ ${col}: ${err}`, 'error', 8000);
                    });
                }
            }
        }
        loadSyncStatus();
        loadAllData();
    } catch (e) {
        hideLoading();
        showToast('Erreur de connexion au service de synchronisation', 'error');
    }
}

async function logout() {
    const confirmed = await showConfirm('Voulez-vous vraiment vous deconnecter ?');
    if (!confirmed) return;
    showLoading('Deconnexion...');
    try {
        await fetch('/logout', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        });
        window.location.href = '/login';
    } catch (e) {
        hideLoading();
        showToast('Erreur lors de la deconnexion', 'error');
    }
}

function MapApp() {
    useEffect(() => {
        initMap();
        loadAllData();

        window.selectSignalement = selectSignalement;
        window.closeDetail = closeDetail;
        window.saveSignalement = saveSignalement;
        window.openEditUserForm = openEditUserForm;
        window.unblockUser = unblockUser;
        window.closeConfirm = closeConfirm;
        window.openUsersModal = openUsersModal;
        window.closeUsersModal = closeUsersModal;
        window.openCreateUserForm = openCreateUserForm;
        window.closeCreateUserModal = closeCreateUserModal;
        window.createUser = createUser;
        window.openEditUserForm = openEditUserForm;
        window.closeEditUserModal = closeEditUserModal;
        window.updateUser = updateUser;
        window.openRolesModal = openRolesModal;
        window.closeRolesModal = closeRolesModal;
        window.openSyncModal = openSyncModal;
        window.closeSyncModal = closeSyncModal;
        window.openStatsModal = openStatsModal;
        window.closeStatsModal = closeStatsModal;
        window.resetStatsFilters = resetStatsFilters;
        window.loadStats = loadStats;
        window.syncUsersToFirebaseAuth = syncUsersToFirebaseAuth;
        window.syncBidirectional = syncBidirectional;
        window.logout = logout;
        window.filterBy = filterBy;

        return () => {
            delete window.selectSignalement;
            delete window.closeDetail;
            delete window.saveSignalement;
            delete window.openEditUserForm;
            delete window.unblockUser;
            delete window.closeConfirm;
            delete window.openUsersModal;
            delete window.closeUsersModal;
            delete window.openCreateUserForm;
            delete window.closeCreateUserModal;
            delete window.createUser;
            delete window.closeEditUserModal;
            delete window.updateUser;
            delete window.openRolesModal;
            delete window.closeRolesModal;
            delete window.openSyncModal;
            delete window.closeSyncModal;
            delete window.openStatsModal;
            delete window.closeStatsModal;
            delete window.resetStatsFilters;
            delete window.loadStats;
            delete window.syncUsersToFirebaseAuth;
            delete window.syncBidirectional;
            delete window.logout;
            delete window.filterBy;
        };
    }, []);

    return (
        <div>
            <nav className="navbar">
                <div className="navbar-brand">
                    <span className="logo"></span>
                    <span className="title">Road Check</span>
                    <span className="subtitle">| Manager</span>
                </div>
                <div className="navbar-menu">
                    <button className="nav-btn" type="button" onClick={openUsersModal}>Utilisateurs</button>
                    <button className="nav-btn" type="button" onClick={openStatsModal}>Statistiques</button>
                    <button className="nav-btn" type="button" onClick={openSyncModal}>Synchronisation</button>
                    <button className="nav-btn" type="button" onClick={logout}>Deconnexion</button>
                </div>
            </nav>
            <div className="main-container">
                <aside className="sidebar" id="sidebar">
                    <div className="sidebar-header">
                        <div className="sidebar-title"> Signalements</div>
                        <div className="filter-tabs">
                            <button className="filter-tab" type="button" onClick={(e) => filterBy('all', e.currentTarget)}>Tous</button>
                            <button className="filter-tab" type="button" onClick={(e) => filterBy('nouveau', e.currentTarget)}> Nouveau</button>
                            <button className="filter-tab active" type="button" onClick={(e) => filterBy('en_attente', e.currentTarget)}> En attente</button>
                            <button className="filter-tab" type="button" onClick={(e) => filterBy('en_cours', e.currentTarget)}> En cours</button>
                            <button className="filter-tab" type="button" onClick={(e) => filterBy('termine', e.currentTarget)}> Termine</button>
                            <button className="filter-tab" type="button" onClick={(e) => filterBy('annule', e.currentTarget)}> Annule</button>
                        </div>
                    </div>
                    <div className="sidebar-content" id="signalementsList">
                        <div className="loading">Chargement...</div>
                    </div>
                </aside>
                <div className="map-container">
                    <div id="map"></div>
                    <div className="stats-bar">
                        <div className="stat-item"><div className="stat-value nouveau" id="statNouveau">0</div><div className="stat-label">Nouveaux</div></div>
                        <div className="stat-item"><div className="stat-value en_attente" id="statEnAttente">0</div><div className="stat-label">En attente</div></div>
                        <div className="stat-item"><div className="stat-value en_cours" id="statEnCours">0</div><div className="stat-label">En cours</div></div>
                        <div className="stat-item"><div className="stat-value termine" id="statTermine">0</div><div className="stat-label">Termines</div></div>
                        <div className="stat-item"><div className="stat-value annule" id="statAnnule">0</div><div className="stat-label">Annules</div></div>
                        <div className="stat-item"><div className="stat-value" id="statTotal">0</div><div className="stat-label">Total</div></div>
                    </div>
                </div>
            </div>
            <div className="detail-panel" id="detailPanel">
                <div className="detail-header"><h3> Modifier</h3><button className="close-btn" type="button" onClick={closeDetail}>&times;</button></div>
                <div className="detail-content" id="detailContent"></div>
            </div>
            <div className="modal-overlay" id="usersModal">
                <div className="modal">
                    <div className="modal-header"><h3> Utilisateurs</h3><button className="close-btn" type="button" onClick={closeUsersModal}>&times;</button></div>
                    <div className="modal-body" id="usersModalBody"></div>
                    <div className="modal-footer"><button className="nav-btn" type="button" onClick={openCreateUserForm}> Nouvel utilisateur</button></div>
                </div>
            </div>
            <div className="modal-overlay" id="rolesModal">
                <div className="modal" style={{ maxWidth: '500px' }}>
                    <div className="modal-header"><h3>Roles</h3><button className="close-btn" type="button" onClick={closeRolesModal}>&times;</button></div>
                    <div className="modal-body" id="rolesModalBody"></div>
                </div>
            </div>
            <div className="modal-overlay" id="syncModal">
                <div className="modal" style={{ maxWidth: '600px' }}>
                    <div className="modal-header"><h3>Synchronisation Firebase</h3><button className="close-btn" type="button" onClick={closeSyncModal}>&times;</button></div>
                    <div className="modal-body">
                        <div id="syncStatus" style={{ marginBottom: '20px', padding: '15px', background: '#21262d', borderRadius: '8px' }}>
                            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '10px' }}>
                                <span>Statut de synchronisation</span>
                                <button className="action-btn" type="button" onClick={loadSyncStatus}>Actualiser</button>
                            </div>
                            <div id="syncStatusContent">Chargement...</div>
                        </div>
                        <div style={{ display: 'flex', gap: '12px', flexDirection: 'column' }}>
                            <div style={{ padding: '15px', background: '#21262d', borderRadius: '8px', border: '1px solid #58a6ff' }}>
                                <h4 style={{ color: '#58a6ff', marginBottom: '10px' }}>Synchronisation bidirectionnelle</h4>
                                <p style={{ fontSize: '0.85rem', color: '#8b949e', marginBottom: '8px' }}>PostgreSQL -> Firestore puis Firestore -> PostgreSQL</p>
                                <p style={{ fontSize: '0.8rem', color: '#8b949e', marginBottom: '12px' }}>Ordre: entreprises -> types_signalement -> utilisateurs -> signalements -> tentatives_connexion</p>
                                <button className="btn-save" style={{ background: '#238636', fontSize: '1rem', padding: '12px 24px', width: '100%' }} type="button" onClick={syncBidirectional}>Synchroniser (PostgreSQL ↔ Firestore)</button>
                            </div>
                            <div style={{ padding: '15px', background: '#21262d', borderRadius: '8px' }}>
                                <h4 style={{ color: '#1f6feb', marginBottom: '10px' }}>Utilisateurs -> Firebase Auth</h4>
                                <p style={{ fontSize: '0.85rem', color: '#8b949e', marginBottom: '12px' }}>Creer les comptes email/password dans Firebase Authentication</p>
                                <button className="btn-save" style={{ background: '#1f6feb' }} type="button" onClick={syncUsersToFirebaseAuth}>Synchroniser les utilisateurs vers Firebase Auth</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div className="modal-overlay" id="statsModal">
                <div className="modal" style={{ maxWidth: '700px' }}>
                    <div className="modal-header"><h3>Statistiques</h3><button className="close-btn" type="button" onClick={closeStatsModal}>&times;</button></div>
                    <div className="modal-body">
                        <form id="statsFilterForm" onSubmit={loadStats}>
                            <div className="form-row">
                                <div className="form-group"><label>Date debut</label><input type="date" id="statsStartDate" /></div>
                                <div className="form-group"><label>Date fin</label><input type="date" id="statsEndDate" /></div>
                            </div>
                            <div style={{ display: 'flex', gap: '10px' }}>
                                <button type="submit" className="btn-save" style={{ flex: 2 }}>Appliquer</button>
                                <button type="button" className="btn-save" style={{ flex: 1, background: '#30363d' }} onClick={resetStatsFilters}>Reinitialiser</button>
                            </div>
                        </form>
                        <div id="statsContent" style={{ marginTop: '16px' }}>Chargement...</div>
                    </div>
                </div>
            </div>
            <div className="modal-overlay" id="createUserModal">
                <div className="modal" style={{ maxWidth: '500px' }}>
                    <div className="modal-header"><h3> Creer utilisateur</h3><button className="close-btn" type="button" onClick={closeCreateUserModal}>&times;</button></div>
                    <div className="modal-body">
                        <form id="createUserForm" onSubmit={createUser}>
                            <div className="form-group"><label>Email</label><input type="email" id="newUserEmail" required /></div>
                            <div className="form-row">
                                <div className="form-group"><label>Nom</label><input type="text" id="newUserNom" required /></div>
                                <div className="form-group"><label>Prenom</label><input type="text" id="newUserPrenom" required /></div>
                            </div>
                            <div className="form-group"><label>Mot de passe</label><input type="password" id="newUserPassword" required /></div>
                            <div className="form-group"><label>Role</label><select id="newUserRole"></select></div>
                            <button type="submit" className="btn-save">Creer</button>
                        </form>
                    </div>
                </div>
            </div>
            <div className="modal-overlay" id="editUserModal">
                <div className="modal" style={{ maxWidth: '520px' }}>
                    <div className="modal-header"><h3> Modifier utilisateur</h3><button className="close-btn" type="button" onClick={closeEditUserModal}>&times;</button></div>
                    <div className="modal-body">
                        <form id="editUserForm" onSubmit={updateUser}>
                            <input type="hidden" id="editUserId" />
                            <div className="form-group"><label>Email</label><input type="email" id="editUserEmail" disabled /></div>
                            <div className="form-row">
                                <div className="form-group"><label>Nom</label><input type="text" id="editUserNom" required /></div>
                                <div className="form-group"><label>Prenom</label><input type="text" id="editUserPrenom" required /></div>
                            </div>
                            <div className="form-group"><label>Role</label><select id="editUserRole"></select></div>
                            <div className="form-group" style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                                <input type="checkbox" id="editUserBloque" />
                                <label htmlFor="editUserBloque" style={{ margin: 0 }}>Bloque</label>
                            </div>
                            <button type="submit" className="btn-save">Enregistrer</button>
                        </form>
                    </div>
                </div>
            </div>

            <div className="toast-container" id="toastContainer"></div>

            <div className="loading-overlay" id="loadingOverlay">
                <div className="spinner"></div>
                <div className="loading-text" id="loadingText">Chargement...</div>
            </div>

            <div className="confirm-modal" id="confirmModal">
                <div className="confirm-box">
                    <div className="confirm-icon" id="confirmIcon">⚠️</div>
                    <div className="confirm-title" id="confirmTitle">Etes-vous sur ?</div>
                    <div className="confirm-buttons">
                        <button className="confirm-btn no" type="button" onClick={() => closeConfirm(false)}>Annuler</button>
                        <button className="confirm-btn yes" type="button" onClick={() => closeConfirm(true)}>Confirmer</button>
                    </div>
                </div>
            </div>
        </div>
    );
}

const mount = document.getElementById('map-app');
if (mount) {
    ReactDOM.createRoot(mount).render(<MapApp />);
    if (mount.dataset.success) {
        try {
            const successMessage = JSON.parse(mount.dataset.success);
            if (successMessage) {
                showToast(successMessage, 'success');
            }
        } catch (e) {
            // ignore invalid payload
        }
    }
}
