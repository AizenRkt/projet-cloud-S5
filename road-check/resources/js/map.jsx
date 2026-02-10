import React, { useEffect } from 'react';
import ReactDOM from 'react-dom/client';
import L from 'leaflet';
import MapNavbar from './components/MapNavbar.jsx';
import Sidebar from './components/Sidebar.jsx';
import StatsBar from './components/StatsBar.jsx';
import DetailPanel from './components/DetailPanel.jsx';
import MapModals from './components/MapModals.jsx';

let map;
let markers = [];
let signalements = [];
let entreprises = [];
let typeSignalements = [];
let typeStatuts = [];
let utilisateurs = [];
let roles = [];
let currentFilter = 'all';
let selectedSig = null;
let confirmCallback = null;
let searchText = '';
let dateStart = '';
let dateEnd = '';
let photoModalUrls = [];
let photoModalIndex = 0;
const creditsData = [
    {
        fullName: 'Rakotoariso Andy Patrick',
        email: 'andypatrickpro@gmail.com / andypatrick3131@gmail.com',
        number: 'ETU3658',
        gitName: 'AizenRkt',
        phone: '+261 34 83 664 14'
    },
    {
        fullName: 'Rakotoarivony Fenitra Luca',
        email: 'fenitra00@gmail.com',
        number: 'ETU3660',
        gitName: 'Fenitra12',
        phone: '+261 34 24 558 95'
    },
    {
        fullName: 'Rajaonarivelo Rohy Amboara Fifaliana',
        email: 'rohyamboara@gmail.com',
        number: 'ETU3295',
        gitName: 'icecoldfinishing',
        phone: '+261 34 10 121 44'
    },
    {
        fullName: 'Andrianaivoson Hary Sabda',
        email: 'andrianaivosonsanda@gmail.com',
        number: 'ETU3246',
        gitName: 'AHsanda2005',
        phone: '+261 38 85 197 09'
    },
];
let currentPrixM2 = 0;
let priceHistory = [];

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
        const [sigRes, entRes, typeRes, statRes, userRes, roleRes, prixRes] = await Promise.all([
            fetch('/api/signalements').then((r) => r.json()),
            fetch('/api/entreprises').then((r) => r.json()),
            fetch('/api/type-signalements').then((r) => r.json()),
            fetch('/api/type-statuts').then((r) => r.json()),
            fetch('/api/utilisateurs').then((r) => r.json()),
            fetch('/api/roles').then((r) => r.json()),
            fetch('/api/prix-m2').then((r) => r.json())
        ]);
        signalements = sigRes || [];
        entreprises = entRes || [];
        typeSignalements = typeRes || [];
        typeStatuts = statRes || [];
        utilisateurs = userRes || [];
        roles = roleRes || [];
        priceHistory = Array.isArray(prixRes) ? prixRes : (prixRes ? [prixRes] : []);
        currentPrixM2 = priceHistory.length > 0 ? parseFloat(priceHistory[0].valeur) : 5000;
        renderSignalements();
        renderMarkers();
        updateStats();
    } catch (e) {
        const list = document.getElementById('signalementsList');
        if (list) list.innerHTML = `<div style="padding:20px;color:#f85149;">Erreur: ${e.message}</div>`;
    }
}

function getProgress(status) {
    if (status === 'termine') return 100;
    if (status === 'en_cours') return 50;
    return 0;
}

function filterSignalements() {
    const startDate = dateStart ? new Date(dateStart) : null;
    const endDate = dateEnd ? new Date(dateEnd) : null;
    const search = searchText.toLowerCase();

    return signalements.filter((s) => {
        const statusMatch = currentFilter === 'all' || s.statut === currentFilter;

        let searchMatch = true;
        if (search) {
            searchMatch =
                (s.description && s.description.toLowerCase().includes(search)) ||
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
                eDate.setHours(23, 59, 59);
                if (sDate > eDate) dateMatch = false;
            }
        }
        return statusMatch && searchMatch && dateMatch;
    });
}

function renderSignalements() {
    const container = document.getElementById('signalementsList');
    if (!container) return;
    const filtered = filterSignalements();

    if (filtered.length === 0) {
        container.innerHTML = '<div style="padding:30px;text-align:center;color:#8b949e;">Aucun signalement</div>';
        return;
    }

    container.innerHTML = filtered
        .map((s) => {
            const lat = parseFloat(s.latitude);
            const lng = parseFloat(s.longitude);
            const progress = getProgress(s.statut);
            const statusLabels = {
                en_attente: 'En attente',
                nouveau: 'Validé',
                en_cours: 'En cours de traitement',
                termine: 'Terminé',
                annule: 'Annulé'
            };
            const label = statusLabels[s.statut] || s.statut_libelle || s.statut;

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
                label +
                '</span></div><div class="sig-desc">' +
                (s.description || 'Aucune description') +
                '</div><div class="sig-info">' +
                '<div style="margin-bottom:4px;display:flex;justify-content:space-between;">' +
                '<span>Avancement</span>' +
                '<span>' + progress + '%</span>' +
                '</div>' +
                '<div style="height:4px;background:#30363d;border-radius:2px;overflow:hidden;margin-bottom:6px;">' +
                '<div style="width:' + progress + '%;height:100%;background:' + (progress === 100 ? '#238636' : (progress === 50 ? '#f0883e' : '#30363d')) + '"></div>' +
                '</div>' +
                '<div style="font-size:0.7rem;color:#8b949e;">' +
                (isNaN(lat) ? '-' : lat.toFixed(4)) +
                ', ' +
                (isNaN(lng) ? '-' : lng.toFixed(4)) +
                '</div>' +
                '<div style="font-size:0.7rem;color:#8b949e;">' +
                (s.created_at ? new Date(s.created_at).toLocaleDateString() : '') +
                '</div>' +
                '</div></div>'
            );
        })
        .join('');
    updateStats(filtered);
}

function renderMarkers() {
    markers.forEach((m) => map.removeLayer(m));
    markers = [];
    const filtered = filterSignalements();

    filtered.forEach((s) => {
        const lat = parseFloat(s.latitude);
        const lng = parseFloat(s.longitude);
        if (!isNaN(lat) && !isNaN(lng)) {
            const colors = { nouveau: '#1f6feb', en_attente: '#d29922', en_cours: '#f0883e', termine: '#238636', annule: '#f85149' };
            const photoUrls = Array.isArray(s.photos) ? s.photos.filter((p) => typeof p === 'string' && p.trim()) : [];
            const resolvePhotoUrl = (path) => {
                if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('/')) {
                    return path;
                }
                return `/storage/${path}`;
            };
            const escapeJsString = (value) =>
                String(value)
                    .replace(/\\/g, '\\\\')
                    .replace(/'/g, "\\'")
                    .replace(/\n/g, '\\n')
                    .replace(/\r/g, '\\r');
            const escapeHtmlAttribute = (value) =>
                String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/"/g, '&quot;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
            const resolvedPhotoUrls = photoUrls.map((path) => resolvePhotoUrl(path));
            const photoListLiteral = resolvedPhotoUrls.map((url) => `'${escapeJsString(url)}'`).join(',');
            const primaryPhotoUrl = resolvedPhotoUrls.length > 0 ? resolvedPhotoUrls[0] : '';
            const photoHtml =
                resolvedPhotoUrls.length > 0
                    ? `<div class="photo-thumb-wrap">
                        <img src="${primaryPhotoUrl}" alt="Photo signalement" class="photo-thumb" />
                        ${resolvedPhotoUrls.length > 1 ? `<button type="button" class="photo-more-overlay" onclick="openPhotoModal([${photoListLiteral}], 0); return false;">Voir plus (${resolvedPhotoUrls.length})</button>` : ''}
                    </div>`
                    : '';
            const photoLinkHtml =
                primaryPhotoUrl
                    ? `<div style="margin-top:6px;"><a href="#" data-url="${escapeHtmlAttribute(primaryPhotoUrl)}" onclick="openPhotoModal([${photoListLiteral}], 0); return false;" style="font-size:0.8rem;color:#1f6feb;">Cliquer ici pour voir l'image</a></div>`
                    : '';
            const marker = L.circleMarker([lat, lng], {
                radius: 10,
                fillColor: colors[s.statut] || '#1f6feb',
                color: '#fff',
                weight: 2,
                fillOpacity: 0.8
            }).addTo(map);

            const statusLabels = {
                en_attente: 'En attente',
                nouveau: 'Validé',
                en_cours: 'En cours de traitement',
                termine: 'Terminé',
                annule: 'Annulé'
            };
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
                    <small>Surface: ${s.surface_m2 || '-'} m2 | Budget: ${s.budget || '-'} Ar</small><br/>
                    <small>Entr: ${s.entreprise || '-'}</small>
                    ${photoHtml}
                    ${photoLinkHtml}
                </div>
            `;
            marker.bindTooltip(tooltipContent, { direction: 'top', offset: [0, -10], interactive: true, className: 'rc-tooltip' });
            marker.bindPopup(tooltipContent, {
                closeButton: false,
                autoClose: true,
                closeOnClick: true,
                autoPan: false,
                className: 'rc-popup'
            });
            marker.on('click', (event) => {
                L.DomEvent.stopPropagation(event);
                marker.openPopup();
                selectSignalement(s.id_signalement);
            });
            markers.push(marker);
        }
    });
    map.on('click', () => map.closePopup());
}

window.openPriceModal = function () {
    let overlay = document.getElementById('priceModalOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'priceModalOverlay';
        overlay.className = 'modal-overlay';
        document.body.appendChild(overlay);
    }

    const historyList = priceHistory.map((p, index) => `
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #30363d;color:#e6edf3;">
            <span>${new Date(p.date).toLocaleDateString()} ${new Date(p.date).toLocaleTimeString()}</span>
            <span style="font-weight:${index === 0 ? 'bold' : 'normal'};color:${index === 0 ? '#2ea043' : '#8b949e'};">
                ${parseFloat(p.valeur).toLocaleString()} Ar/m² ${index === 0 ? '(Actuel)' : ''}
            </span>
        </div>
    `).join('');

    overlay.innerHTML = `
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Gestion du Prix par m²</h3>
                <button class="modal-close" onclick="closePriceModal()" style="background:none;border:none;color:#8b949e;font-size:1.5rem;cursor:pointer;">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Nouveau Prix (Ar/m²)</label>
                    <div style="display:flex;gap:10px;">
                        <input type="number" id="newPriceValue" class="form-control" placeholder="Ex: 6000" style="flex:1;" />
                        <button class="btn-primary" onclick="savePriceM2()" style="padding:10px 20px;background:#238636;color:white;border:none;border-radius:6px;cursor:pointer;">Ajouter</button>
                    </div>
                </div>

                <h4 style="margin-top:20px;margin-bottom:10px;font-size:1rem;color:#58a6ff;">Historique des prix</h4>
                <div style="max-height:200px;overflow-y:auto;background:#0d1117;padding:10px;border-radius:4px;border:1px solid #30363d;">
                    ${historyList.length > 0 ? historyList : '<div style="color:#8b949e;">Aucun historique</div>'}
                </div>
            </div>
        </div>
    `;
    overlay.classList.add('open');
};

window.closePriceModal = function () {
    const overlay = document.getElementById('priceModalOverlay');
    if (overlay) overlay.classList.remove('open');
};

window.savePriceM2 = async function () {
    const val = parseFloat(document.getElementById('newPriceValue').value);
    if (!val || val <= 0) {
        showToast('Veuillez entrer un prix valide', 'error');
        return;
    }
    showLoading('Mise à jour du prix...');
    try {
        const res = await fetch('/api/prix-m2', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ valeur: val })
        });
        hideLoading();
        if (res.ok) {
            const data = await res.json();
            priceHistory.unshift(data);
            currentPrixM2 = parseFloat(data.valeur);
            showToast('Prix mis à jour', 'success');
            window.openPriceModal();
        } else {
            showToast('Erreur lors de la mise à jour', 'error');
        }
    } catch (e) {
        hideLoading();
        showToast('Erreur de connexion', 'error');
    }
};

function selectSignalement(id) {
    selectedSig = signalements.find((s) => s.id_signalement === id);
    if (!selectedSig) return;
    renderSignalements();
    openDetail();
    const lat = parseFloat(selectedSig.latitude);
    const lng = parseFloat(selectedSig.longitude);
    if (!isNaN(lat) && !isNaN(lng)) map.setView([lat, lng], 16);
}

function calculateBudget() {
    const surface = parseFloat(document.getElementById('editSurface').value) || 0;
    const niveau = parseFloat(document.getElementById('editNiveau').value) || 1;
    if (surface > 0 && currentPrixM2 > 0) {
        const budget = surface * niveau * currentPrixM2;
        document.getElementById('editBudget').value = Math.round(budget);
    }
}



window.savePriceM2 = async function () {
    const val = parseFloat(document.getElementById('newPriceValue').value);
    if (!val || val <= 0) {
        showToast('Veuillez entrer un prix valide', 'error');
        return;
    }
    showLoading('Mise à jour du prix...');
    try {
        const res = await fetch('/api/prix-m2', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ valeur: val })
        });
        hideLoading();
        if (res.ok) {
            const data = await res.json();
            priceHistory.unshift(data);
            currentPrixM2 = parseFloat(data.valeur);
            showToast('Prix mis à jour', 'success');
            window.openPriceModal(); // Refresh modal
        } else {
            showToast('Erreur lors de la mise à jour', 'error');
        }
    } catch (e) {
        hideLoading();
        showToast('Erreur de connexion', 'error');
    }
};

function openDetail() {
    const panel = document.getElementById('detailPanel');
    const s = selectedSig;
    if (!panel || !s) return;
    const statusOptions =
        s.statut === 'en_attente'
            ? [
                { code: 'nouveau', libelle: 'Accepter' },
                { code: 'annule', libelle: 'Annuler' }
            ]
            : typeStatuts;
    const selectedStatus = s.statut === 'en_attente' ? 'nouveau' : s.statut;
    const isEnAttente = s.statut === 'en_attente';

    let historyHtml = '';
    if (s.history && s.history.length > 0) {
        // ... history code ...
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

    const niveauField = isEnAttente ?
        `<div class="form-group"><label>Niveau</label><input type="number" id="editNiveau" value="${s.niveau || '1'}" oninput="calculateBudget()"></div>`
        : `<div class="form-group"><label>Niveau</label><input type="number" value="${s.niveau || '1'}" readonly style="background:#f0f0f0;"></div>`;

    // Logic for auto-calculating budget display on load if needed, but usually we just show estimated.
    // We add a note that budget is estimated.

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
        statusOptions
            .map(
                (t) =>
                    '<option value="' +
                    t.code +
                    '"' +
                    (selectedStatus === t.code ? ' selected' : '') +
                    '>' +
                    t.libelle +
                    '</option>'
            )
            .join('') +
        '</select></div><div class="form-group"><label>Description</label><textarea id="editDescription">' +
        (s.description || '') +
        '</textarea></div><div class="form-row"><div class="form-group"><label>Surface (m2)</label><input type="number" id="editSurface" value="' +
        (s.surface_m2 || '') +
        '" oninput="calculateBudget()"></div>' + niveauField + '<div class="form-group"><label>Budget (Estimé)</label><input type="number" id="editBudget" value="' +
        (s.budget || '') +
        '" readonly style="background:#f0f0f0;cursor:not-allowed;"></div></div><div class="form-group"><label>Entreprise</label><select id="editEntreprise"><option value="">--</option>' +
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
        '</select></div><div style="display:flex;gap:10px;margin-bottom:20px;"><button type="submit" class="btn-save" style="flex:2;"> Enregistrer</button><button type="button" class="btn-save" style="flex:1;background:#30363d;" onclick="closeDetail()">Annuler</button></div></form>' +
        historyHtml;
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
        niveau: document.getElementById('editNiveau').value || 1,
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

function updateStats(filteredData = signalements) {
    const total = filteredData.length;
    const nouveau = filteredData.filter((s) => s.statut === 'nouveau').length;
    const enAttente = filteredData.filter((s) => s.statut === 'en_attente').length;
    const enCours = filteredData.filter((s) => s.statut === 'en_cours').length;
    const termine = filteredData.filter((s) => s.statut === 'termine').length;
    const annule = filteredData.filter((s) => s.statut === 'annule').length;

    document.getElementById('statNouveau').textContent = nouveau;
    document.getElementById('statEnAttente').textContent = enAttente;
    document.getElementById('statEnCours').textContent = enCours;
    document.getElementById('statTermine').textContent = termine;
    document.getElementById('statAnnule').textContent = annule;
    document.getElementById('statTotal').textContent = total;

    // Global Progress
    let totalProgress = 0;
    filteredData.forEach((s) => totalProgress += getProgress(s.statut));
    const avgProgress = total > 0 ? Math.round(totalProgress / total) : 0;

    const globalProgressEl = document.getElementById('statGlobalProgress');
    const globalProgressBarEl = document.getElementById('statGlobalProgressBar');
    if (globalProgressEl) globalProgressEl.textContent = avgProgress + '%';
    if (globalProgressBarEl) globalProgressBarEl.style.width = avgProgress + '%';
}

function filterBy(filter, btn) {
    currentFilter = filter;
    document.querySelectorAll('.filter-tab').forEach((b) => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    renderSignalements();
    renderMarkers();
}

function handleSearch(value) {
    searchText = value;
    renderSignalements();
    renderMarkers();
}

function handleDateChange() {
    dateStart = document.getElementById('dateStart')?.value || '';
    dateEnd = document.getElementById('dateEnd')?.value || '';
    renderSignalements();
    renderMarkers();
}

function openUsersModal() {
    document.getElementById('usersModal').classList.add('open');
    renderUsersTable();
}

function updatePhotoModal() {
    const image = document.getElementById('photoModalImage');
    const counter = document.getElementById('photoModalCounter');
    const prevBtn = document.getElementById('photoModalPrev');
    const nextBtn = document.getElementById('photoModalNext');
    if (!image || photoModalUrls.length === 0) return;
    image.src = photoModalUrls[photoModalIndex];
    if (counter) counter.textContent = `${photoModalIndex + 1} / ${photoModalUrls.length}`;
    if (prevBtn) prevBtn.disabled = photoModalUrls.length <= 1;
    if (nextBtn) nextBtn.disabled = photoModalUrls.length <= 1;
}

function openPhotoModal(photoUrls, startIndex = 0) {
    const modal = document.getElementById('photoModal');
    if (!modal || !Array.isArray(photoUrls) || photoUrls.length === 0) return;
    photoModalUrls = photoUrls;
    photoModalIndex = Math.min(Math.max(startIndex, 0), photoUrls.length - 1);
    updatePhotoModal();
    modal.classList.add('open');
}

function closePhotoModal() {
    const modal = document.getElementById('photoModal');
    const image = document.getElementById('photoModalImage');
    if (modal) modal.classList.remove('open');
    if (image) image.src = '';
    photoModalUrls = [];
    photoModalIndex = 0;
}

function nextPhotoModal() {
    if (photoModalUrls.length <= 1) return;
    photoModalIndex = (photoModalIndex + 1) % photoModalUrls.length;
    updatePhotoModal();
}

function prevPhotoModal() {
    if (photoModalUrls.length <= 1) return;
    photoModalIndex = (photoModalIndex - 1 + photoModalUrls.length) % photoModalUrls.length;
    updatePhotoModal();
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

function renderCreditsTable() {
    const body = document.getElementById('creditsModalBody');
    if (!body) return;
    body.innerHTML =
        '<table class="data-table"><thead><tr><th>Nom</th><th>Email</th><th>Numero</th><th>Nom Git</th><th>Telephone</th></tr></thead><tbody>' +
        creditsData
            .map((c) =>
                '<tr><td>' +
                c.fullName +
                '</td><td>' +
                c.email +
                '</td><td>' +
                c.number +
                '</td><td>' +
                c.gitName +
                '</td><td>' +
                c.phone +
                '</td></tr>'
            )
            .join('') +
        '</tbody></table>';
}

function openCreditsModal() {
    document.getElementById('creditsModal').classList.add('open');
    renderCreditsTable();
}

function closeCreditsModal() {
    document.getElementById('creditsModal').classList.remove('open');
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
    const roleSelect = document.getElementById('newUserRole');
    const userRole = roles.find((r) => String(r.nom || '').toLowerCase() === 'utilisateur');
    if (roleSelect) {
        if (userRole) {
            roleSelect.innerHTML = `<option value="${userRole.id_role}">${userRole.nom}</option>`;
            roleSelect.value = userRole.id_role;
            roleSelect.disabled = true;
        } else {
            roleSelect.innerHTML = '<option value="">Utilisateur</option>';
            roleSelect.value = '';
            roleSelect.disabled = true;
        }
    }
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

function openSyncModal() {
    document.getElementById('syncModal').classList.add('open');
    loadSyncStatus();
}

function closeSyncModal() {
    document.getElementById('syncModal').classList.remove('open');
}

function openStatsModal() {
    document.getElementById('statsModal').classList.add('open');
}

function closeStatsModal() {
    document.getElementById('statsModal').classList.remove('open');
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
    const docsUrl = document.getElementById('map-app')?.dataset?.docsUrl || '/api/documentation';

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
        window.openPhotoModal = openPhotoModal;
        window.closePhotoModal = closePhotoModal;
        window.nextPhotoModal = nextPhotoModal;
        window.prevPhotoModal = prevPhotoModal;
        window.openCreateUserForm = openCreateUserForm;
        window.closeCreateUserModal = closeCreateUserModal;
        window.createUser = createUser;
        window.openEditUserForm = openEditUserForm;
        window.closeEditUserModal = closeEditUserModal;
        window.updateUser = updateUser;
        window.openSyncModal = openSyncModal;
        window.closeSyncModal = closeSyncModal;
        window.openStatsModal = openStatsModal;
        window.closeStatsModal = closeStatsModal;
        window.syncUsersToFirebaseAuth = syncUsersToFirebaseAuth;
        window.syncBidirectional = syncBidirectional;
        window.logout = logout;
        window.filterBy = filterBy;
        window.handleSearch = handleSearch;
        window.handleDateChange = handleDateChange;

        return () => {
            delete window.selectSignalement;
            delete window.closeDetail;
            delete window.saveSignalement;
            delete window.openEditUserForm;
            delete window.unblockUser;
            delete window.closeConfirm;
            delete window.openUsersModal;
            delete window.closeUsersModal;
            delete window.openPhotoModal;
            delete window.closePhotoModal;
            delete window.nextPhotoModal;
            delete window.prevPhotoModal;
            delete window.openCreateUserForm;
            delete window.closeCreateUserModal;
            delete window.createUser;
            delete window.closeEditUserModal;
            delete window.updateUser;
            delete window.openSyncModal;
            delete window.closeSyncModal;
            delete window.openStatsModal;
            delete window.closeStatsModal;
            delete window.syncUsersToFirebaseAuth;
            delete window.syncBidirectional;
            delete window.logout;
            delete window.filterBy;
            delete window.handleSearch;
            delete window.handleDateChange;
        };
    }, []);

    return (
        <div>
            <MapNavbar
                docsUrl={docsUrl}
                onOpenCredits={openCreditsModal}
                onOpenUsers={openUsersModal}
                onOpenStats={openStatsModal}
                onOpenSync={openSyncModal}
                onOpenPrice={() => window.openPriceModal()}
                onLogout={logout}
            />
            <div className="main-container">
                <Sidebar onFilter={filterBy} onSearch={handleSearch} onDateChange={handleDateChange} />
                <div className="map-container">
                    <div id="map"></div>
                    <StatsBar />
                </div>
            </div>
            <DetailPanel onClose={closeDetail} />
            <MapModals
                onCloseCredits={closeCreditsModal}
                onCloseUsers={closeUsersModal}
                onOpenCreateUser={openCreateUserForm}
                onClosePhoto={closePhotoModal}
                onPrevPhoto={prevPhotoModal}
                onNextPhoto={nextPhotoModal}
                onCloseSync={closeSyncModal}
                onCloseStats={closeStatsModal}
                onCloseCreateUser={closeCreateUserModal}
                onCloseEditUser={closeEditUserModal}
                onCloseConfirm={closeConfirm}
                onCreateUser={createUser}
                onUpdateUser={updateUser}
                onSyncBidirectional={syncBidirectional}
                onSyncUsersToFirebaseAuth={syncUsersToFirebaseAuth}
                onLoadSyncStatus={loadSyncStatus}
            />
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
