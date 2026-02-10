import React, { useEffect } from 'react';
import ReactDOM from 'react-dom/client';
import L from 'leaflet';
import VisitorNavbar from './components/VisitorNavbar.jsx';
import Sidebar from './components/Sidebar.jsx';
import StatsBar from './components/StatsBar.jsx';
import DetailPanel from './components/DetailPanel.jsx';

/* ───────── State (meme structure que map.jsx) ───────── */
let map;
let markers = [];
let signalements = [];
let currentFilter = 'all';
let selectedSig = null;
let searchText = '';
let dateStart = '';
let dateEnd = '';
let photoModalUrls = [];
let photoModalIndex = 0;

/* ───────── Toast ───────── */
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

/* ───────── Map init ───────── */
function initMap() {
    map = L.map('map').setView([-18.9137, 47.5361], 13);
    L.tileLayer('http://localhost:8081/styles/Basic/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
}

/* ───────── Chargement depuis Firestore via le backend proxy ───────── */
async function loadFromFirestoreProxy() {
    try {
        const res = await fetch('http://localhost:4001/public/signalements');
        const json = await res.json();
        if (!json.success) throw new Error(json.error || 'Erreur serveur');

        signalements = (json.data || []).map((doc) => ({
            id_signalement: doc.firestore_id || doc.id,
            latitude: doc.latitude,
            longitude: doc.longitude,
            description: doc.description || '',
            statut: (doc.status || doc.statut || 'nouveau').toLowerCase().replace(/\s+/g, '_'),
            statut_libelle: doc.status || doc.statut || '',
            type_signalement: doc.typeSignalementNom || doc.type_signalement || 'Non defini',
            entreprise: doc.entrepriseNom || doc.entreprise || '',
            surface_m2: doc.surface_m2 || doc.surface || null,
            budget: doc.budget || null,
            photos: doc.photos || [],
            created_at: doc.created_at || doc.dateSignalement || null
        }));

        renderSignalements();
        renderMarkers();
        updateStats();
        showToast(`${signalements.length} signalement(s) charge(s) depuis Firestore`, 'success');
    } catch (e) {
        const list = document.getElementById('signalementsList');
        if (list) list.innerHTML = `<div style="padding:20px;color:#f85149;">Erreur: ${e.message}</div>`;
        showToast('Erreur de chargement Firestore: ' + e.message, 'error');
    }
}

/* ───────── Filtrage (identique a map.jsx) ───────── */
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

/* ───────── Rendu liste (identique a map.jsx) ───────── */
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
                '" onclick="selectSignalementVis(\'' +
                s.id_signalement +
                '\')">' +
                '<div class="sig-header"><span class="sig-type">' +
                (s.type_signalement || 'Non defini') +
                '</span><span class="sig-status ' +
                s.statut +
                '">' +
                label +
                '</span></div>' +
                '<div class="sig-desc">' +
                (s.description || 'Aucune description') +
                '</div>' +
                '<div class="sig-info">' +
                '<div style="margin-bottom:4px;display:flex;justify-content:space-between;"><span>Avancement</span><span>' + progress + '%</span></div>' +
                '<div style="height:4px;background:#30363d;border-radius:2px;overflow:hidden;margin-bottom:6px;">' +
                '<div style="width:' + progress + '%;height:100%;background:' + (progress === 100 ? '#238636' : (progress === 50 ? '#f0883e' : '#30363d')) + '"></div>' +
                '</div>' +
                '<div style="font-size:0.7rem;color:#8b949e;">' +
                (isNaN(lat) ? '-' : lat.toFixed(4)) + ', ' + (isNaN(lng) ? '-' : lng.toFixed(4)) +
                '</div>' +
                '<div style="font-size:0.7rem;color:#8b949e;">' +
                (s.created_at ? new Date(s.created_at).toLocaleDateString() : '') +
                '</div></div></div>'
            );
        })
        .join('');
    updateStats(filtered);
}

/* ───────── Markers (identique a map.jsx) ───────── */
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
                if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('/')) return path;
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
                        <img src="${primaryPhotoUrl}" alt="Photo" class="photo-thumb" />
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
            marker.bindPopup(tooltipContent, { closeButton: false, autoClose: true, closeOnClick: true, autoPan: false, className: 'rc-popup' });
            marker.on('click', (event) => {
                L.DomEvent.stopPropagation(event);
                marker.openPopup();
                selectSignalementVis(s.id_signalement);
            });
            markers.push(marker);
        }
    });
    map.on('click', () => map.closePopup());
}

/* ───────── Selection & Detail (lecture seule) ───────── */
function selectSignalementVis(id) {
    selectedSig = signalements.find((s) => s.id_signalement === id || s.id_signalement === String(id));
    if (!selectedSig) return;
    renderSignalements();
    openDetailVis();
    const lat = parseFloat(selectedSig.latitude);
    const lng = parseFloat(selectedSig.longitude);
    if (!isNaN(lat) && !isNaN(lng)) map.setView([lat, lng], 16);
}

function openDetailVis() {
    const panel = document.getElementById('detailPanel');
    const s = selectedSig;
    if (!panel || !s) return;

    const statusLabels = {
        en_attente: 'En attente',
        nouveau: 'Validé',
        en_cours: 'En cours de traitement',
        termine: 'Terminé',
        annule: 'Annulé'
    };
    const label = statusLabels[s.statut] || s.statut_libelle || s.statut;
    const progress = getProgress(s.statut);

    const photoUrls = Array.isArray(s.photos) ? s.photos.filter((p) => typeof p === 'string' && p.trim()) : [];
    const resolvePhotoUrl = (path) => {
        if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('/')) return path;
        return `/storage/${path}`;
    };
    const photoHtml = photoUrls.length > 0
        ? `<div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:10px;">
            ${photoUrls.map((path) => `<img src="${resolvePhotoUrl(path)}" alt="Photo" style="width:100px;height:75px;object-fit:cover;border-radius:8px;border:1px solid var(--rc-border);" />`).join('')}
        </div>`
        : '';

    document.getElementById('detailContent').innerHTML = `
        <div class="form-group">
            <label>Type</label>
            <div style="padding:10px;background:#1a2230;border:1px solid var(--rc-border);border-radius:8px;color:var(--rc-text);">${s.type_signalement || 'Non defini'}</div>
        </div>
        <div class="form-group">
            <label>Statut</label>
            <div style="padding:10px;background:#1a2230;border:1px solid var(--rc-border);border-radius:8px;color:var(--rc-text);">
                <span class="sig-status ${s.statut}" style="padding:4px 10px;">${label}</span>
            </div>
        </div>
        <div class="form-group">
            <label>Description</label>
            <div style="padding:10px;background:#1a2230;border:1px solid var(--rc-border);border-radius:8px;color:var(--rc-text);min-height:60px;">${s.description || 'Aucune description'}</div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Surface (m2)</label>
                <div style="padding:10px;background:#1a2230;border:1px solid var(--rc-border);border-radius:8px;color:var(--rc-text);">${s.surface_m2 || '-'}</div>
            </div>
            <div class="form-group">
                <label>Budget (Ar)</label>
                <div style="padding:10px;background:#1a2230;border:1px solid var(--rc-border);border-radius:8px;color:var(--rc-text);">${s.budget || '-'}</div>
            </div>
        </div>
        <div class="form-group">
            <label>Entreprise</label>
            <div style="padding:10px;background:#1a2230;border:1px solid var(--rc-border);border-radius:8px;color:var(--rc-text);">${s.entreprise || '-'}</div>
        </div>
        <div class="form-group">
            <label>Avancement</label>
            <div style="padding:10px;background:#1a2230;border:1px solid var(--rc-border);border-radius:8px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:6px;"><span style="color:var(--rc-text);">${label}</span><span style="color:var(--rc-accent);">${progress}%</span></div>
                <div style="height:6px;background:#30363d;border-radius:3px;overflow:hidden;">
                    <div style="width:${progress}%;height:100%;background:${progress === 100 ? '#238636' : (progress === 50 ? '#f0883e' : '#58a6ff')};transition:width 0.3s;"></div>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label>Coordonnees</label>
            <div style="padding:10px;background:#1a2230;border:1px solid var(--rc-border);border-radius:8px;color:var(--rc-muted);font-size:0.85rem;">${s.latitude}, ${s.longitude}</div>
        </div>
        ${s.created_at ? `<div class="form-group"><label>Date</label><div style="padding:10px;background:#1a2230;border:1px solid var(--rc-border);border-radius:8px;color:var(--rc-muted);font-size:0.85rem;">${new Date(s.created_at).toLocaleString()}</div></div>` : ''}
        ${photoHtml}
        <button type="button" class="btn-save" style="background:#30363d;margin-top:15px;" onclick="closeDetail()">Fermer</button>
    `;
    panel.classList.add('open');
}

function closeDetail() {
    const panel = document.getElementById('detailPanel');
    if (panel) panel.classList.remove('open');
    selectedSig = null;
    renderSignalements();
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

/* ───────── Stats (identique a map.jsx) ───────── */
function updateStats(filteredData = signalements) {
    const total = filteredData.length;
    const nouveau = filteredData.filter((s) => s.statut === 'nouveau').length;
    const enAttente = filteredData.filter((s) => s.statut === 'en_attente').length;
    const enCours = filteredData.filter((s) => s.statut === 'en_cours').length;
    const termine = filteredData.filter((s) => s.statut === 'termine').length;
    const annule = filteredData.filter((s) => s.statut === 'annule').length;

    const el = (id) => document.getElementById(id);
    if (el('statNouveau')) el('statNouveau').textContent = nouveau;
    if (el('statEnAttente')) el('statEnAttente').textContent = enAttente;
    if (el('statEnCours')) el('statEnCours').textContent = enCours;
    if (el('statTermine')) el('statTermine').textContent = termine;
    if (el('statAnnule')) el('statAnnule').textContent = annule;
    if (el('statTotal')) el('statTotal').textContent = total;

    let totalProgress = 0;
    filteredData.forEach((s) => totalProgress += getProgress(s.statut));
    const avgProgress = total > 0 ? Math.round(totalProgress / total) : 0;

    if (el('statGlobalProgress')) el('statGlobalProgress').textContent = avgProgress + '%';
    if (el('statGlobalProgressBar')) el('statGlobalProgressBar').style.width = avgProgress + '%';
}

/* ───────── Filtres & Recherche (identique a map.jsx) ───────── */
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

/* ───────── Composant React principal ───────── */
function VisitorApp() {
    useEffect(() => {
        initMap();
        loadFromFirestoreProxy();

        // Expose au DOM (onclick dans le HTML genere)
        window.selectSignalementVis = selectSignalementVis;
        window.closeDetail = closeDetail;
        window.openPhotoModal = openPhotoModal;
        window.closePhotoModal = closePhotoModal;
        window.nextPhotoModal = nextPhotoModal;
        window.prevPhotoModal = prevPhotoModal;
        window.filterBy = filterBy;
        window.handleSearch = handleSearch;
        window.handleDateChange = handleDateChange;

        return () => {
            delete window.selectSignalementVis;
            delete window.closeDetail;
            delete window.openPhotoModal;
            delete window.closePhotoModal;
            delete window.nextPhotoModal;
            delete window.prevPhotoModal;
            delete window.filterBy;
            delete window.handleSearch;
            delete window.handleDateChange;
        };
    }, []);

    return (
        <div>
            <VisitorNavbar />
            <div className="main-container">
                <Sidebar onFilter={filterBy} onSearch={handleSearch} onDateChange={handleDateChange} />
                <div className="map-container">
                    <div id="map"></div>
                    <StatsBar />
                </div>
            </div>
            <div className="modal-overlay" id="photoModal">
                <div className="modal" style={{ maxWidth: '900px' }}>
                    <div className="modal-header">
                        <h3>Photo</h3>
                        <button className="close-btn" type="button" onClick={closePhotoModal}>&times;</button>
                    </div>
                    <div className="modal-body" id="photoModalBody" style={{ display: 'flex', justifyContent: 'center', alignItems: 'center' }}>
                        <img id="photoModalImage" alt="Photo signalement" style={{ width: '100%', height: 'auto', maxHeight: '70vh', objectFit: 'contain', background: '#0d1117' }} />
                        <div className="photo-modal-nav">
                            <button id="photoModalPrev" className="photo-modal-btn" type="button" onClick={prevPhotoModal}>&lt;</button>
                            <span id="photoModalCounter" className="photo-modal-counter">0 / 0</span>
                            <button id="photoModalNext" className="photo-modal-btn" type="button" onClick={nextPhotoModal}>&gt;</button>
                        </div>
                    </div>
                </div>
            </div>
            <DetailPanel onClose={closeDetail} title="Détails" />
            <div className="toast-container" id="toastContainer"></div>
        </div>
    );
}

/* ───────── Mount ───────── */
const mount = document.getElementById('visiteur-app');
if (mount) {
    ReactDOM.createRoot(mount).render(<VisitorApp />);
}
