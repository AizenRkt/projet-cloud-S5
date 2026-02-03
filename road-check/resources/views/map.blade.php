<!DOCTYPE html>
<html>
<head>
    <title>Offline Map - Antananarivo</title>

    <link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}">

    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --bg: #f5f7fb;
            --text: #0f172a;
            --muted: #64748b;
            --card: #ffffff;
            --border: #e2e8f0;
            --shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            font-family: "Inter", "Segoe UI", Arial, sans-serif;
            color: var(--text);
            background: var(--bg);
        }

        .appHeader {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 24px;
            background: var(--card);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .appTitle {
            font-size: 1.2rem;
            font-weight: 700;
        }
        .appSubtitle {
            font-size: 0.85rem;
            color: var(--muted);
        }

        #edit-btn {
            background: var(--primary);
            color: white;
            border: none;
            width: 42px;
            height: 42px;
            cursor: pointer;
            border-radius: 50%;
            font-size: 1.05rem;
            font-weight: 700;
            box-shadow: var(--shadow);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.15s ease, background 0.2s ease;
        }
        #edit-btn:hover { background: var(--primary-dark); transform: translateY(-1px); }
        #edit-btn.addModeActive { background: #16a34a; }

        .mapWrapper {
            padding: 16px 24px 0;
        }
        #map {
            height: 65vh;
            width: 100%;
            background: #f1f3f4;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }

        /* Modal styles */
        #add-modal, #edit-modal {
            display: none;
            position: absolute;
            top: 50px;
            right: 50px;
            width: 350px;
            max-height: 80vh;
            overflow-y: auto;
            background: var(--card);
            padding: 20px;
            box-shadow: var(--shadow);
            z-index: 2000;
            border-radius: 12px;
            border: 1px solid var(--border);
        }
        #add-modal h3, #edit-modal h3 { margin-top: 0; color: var(--primary); }

        label { display: block; margin-top: 10px; font-weight: 600; font-size: 0.85em; color: var(--muted); }
        input, select, textarea {
            display: block;
            width: 100%;
            margin-bottom: 12px;
            padding: 8px;
            border: 1px solid var(--border);
            border-radius: 8px;
            box-sizing: border-box;
            background: #fff;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        button {
            padding: 8px 15px;
            margin-right: 5px;
            cursor: pointer;
            border: none;
            border-radius: 8px;
            font-weight: bold;
        }
        .btn-save { background: var(--primary); color: white; }
        .btn-cancel { background: #64748b; color: white; }
        .btn-edit-popup {
            background: var(--primary);
            color: white;
            padding: 4px 8px;
            font-size: 0.8em;
            margin-top: 5px;
        }

        /* Summary Table Styles */
        .summaryPanel {
            padding: 12px 24px 16px;
            background: var(--card);
            border-top: 1px solid var(--border);
            margin-top: 12px;
        }
        .summaryTitle {
            font-size: 1em;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
        }
        .statsGrid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }
        .statCard {
            background: #f8fafc;
            padding: 10px 12px;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.06);
        }
        .statLabel {
            font-size: 0.72em;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        .statValue {
            font-size: 1.2em;
            font-weight: bold;
            color: var(--text);
        }
    </style>
</head>
<body>
<div class="appHeader">
    <div>
        <div class="appTitle">Road Check</div>
        <div class="appSubtitle">Cliquez sur la carte pour choisir des coordonnées</div>
    </div>
    <button id="edit-btn" title="Ajouter un signalement" aria-label="Ajouter un signalement">📍</button>
</div>

<div class="mapWrapper">
    <div id="map"></div>
</div>

<div class="summaryPanel">
    <div class="summaryTitle">Tableau de Récapitulation</div>
    <div class="statsGrid">
        <div class="statCard">
            <div class="statLabel">Nb de points</div>
            <div class="statValue" id="stat-points">0</div>
        </div>
        <div class="statCard">
            <div class="statLabel">Total Surface (m²)</div>
            <div class="statValue" id="stat-surface">0</div>
        </div>
        <div class="statCard">
            <div class="statLabel">Avancement (%)</div>
            <div class="statValue" id="stat-progress">0%</div>
        </div>
        <div class="statCard">
            <div class="statLabel">Total Budget (Ar)</div>
            <div class="statValue" id="stat-budget">0</div>
        </div>
    </div>
</div>

<!-- New Signalement Modal -->
<div id="add-modal">
    <h3>New Signalement</h3>
    <form id="add-form">
        <label>Title</label>
        <input type="text" name="title" placeholder="Title" required>
        <label>Description</label>
        <textarea name="description" rows="3" placeholder="Description" required></textarea>
        <label>Surface (m²)</label>
        <input type="number" name="surface_m2" placeholder="Surface" required>
        <label>Budget</label>
        <input type="number" name="budget" placeholder="Budget" required>
        <label>Statut</label>
        <select name="statut">
            <option value="nouveau" selected>nouveau</option>
            <option value="en cours">en cours</option>
            <option value="termine">termine</option>
        </select>
        <label>Assign to User</label>
        <select name="id_utilisateur" class="user-select"></select>
        <label>Assign to Entreprise</label>
        <select name="id_entreprise" class="corp-select">
            <option value="">None</option>
        </select>
        <label>Date</label>
        <input type="date" name="report_date" required>
        <label>Coordinates (Lat, Lng)</label>
        <input type="text" name="coordinates" placeholder="Click on map" readonly>
        <button type="submit" class="btn-save">Add</button>
        <button type="button" class="btn-cancel" onclick="closeModals()">Cancel</button>
    </form>
</div>

<!-- Edit Signalement Modal -->
<div id="edit-modal">
    <h3>Edit Signalement</h3>
    <form id="edit-form">
        <input type="hidden" name="id_signalement">
        <label>Title</label>
        <input type="text" name="title">
        <label>Description</label>
        <textarea name="description" rows="3"></textarea>
        <label>Surface (m²)</label>
        <input type="number" name="surface_m2">
        <label>Budget</label>
        <input type="number" name="budget">
        <label>Statut</label>
        <select name="statut">
            <option value="nouveau">nouveau</option>
            <option value="en cours">en cours</option>
            <option value="termine">termine</option>
        </select>
        <label>Assign to User</label>
        <select name="id_utilisateur" class="user-select"></select>
        <label>Assign to Entreprise</label>
        <select name="id_entreprise" class="corp-select">
            <option value="">None</option>
        </select>
        <label>Date</label>
        <input type="date" name="report_date">
        <label>Coordinates (Lat, Lng)</label>
        <input type="text" name="coordinates" readonly>
        <label>Note (History)</label>
        <textarea name="note" rows="3" placeholder="Add a note about this change..."></textarea>
        <button type="submit" class="btn-save">Save Changes</button>
        <button type="button" class="btn-cancel" onclick="closeModals()">Cancel</button>
    </form>
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="{{ asset('leaflet/leaflet.js') }}"></script>

<script>
const map = L.map('map').setView([-18.89493, 47.49292], 13);
let markersLayer = L.layerGroup().addTo(map);

// Tile layer
L.tileLayer('http://localhost:8081/styles/Basic/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: '© OpenMapTiles © OpenStreetMap contributors'
}).addTo(map);

// Marker icons
const icons = {
    nouveau: L.icon({ iconUrl: 'leaflet/images/location.png', iconSize: [32,32], iconAnchor:[16,32], popupAnchor:[0,-32] }),
    'en cours': L.icon({ iconUrl: 'leaflet/images/warning.png', iconSize: [32,32], iconAnchor:[16,32], popupAnchor:[0,-32] }),
    termine: L.icon({ iconUrl: 'leaflet/images/cross.png', iconSize: [32,32], iconAnchor:[16,32], popupAnchor:[0,-32] }),
    dangerous: L.icon({ iconUrl: 'leaflet/images/dangerous.png', iconSize: [32,32], iconAnchor:[16,32], popupAnchor:[0,-32] })
};

const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
let corporations = [];
let users = [];
let allSignalements = [];

// -----------------------------
// UI Utilities
// -----------------------------
function closeModals() {
    document.getElementById('add-modal').style.display = 'none';
    document.getElementById('edit-modal').style.display = 'none';
}

function populateSelects() {
    const corpSelects = document.querySelectorAll('.corp-select');
    const userSelects = document.querySelectorAll('.user-select');

    corpSelects.forEach(s => {
        const currentVal = s.value;
        s.innerHTML = '<option value="">None</option>';
        corporations.forEach(c => {
            s.innerHTML += `<option value="${c.id_entreprise}">${c.nom}</option>`;
        });
        s.value = currentVal;
    });

    userSelects.forEach(s => {
        const currentVal = s.value;
        s.innerHTML = '';
        users.forEach(u => {
            s.innerHTML += `<option value="${u.id_utilisateur}">${u.prenom} ${u.nom}</option>`;
        });
        s.value = currentVal;
    });
}

// -----------------------------
// Data Loading
// -----------------------------
function loadInitialData() {
    Promise.all([
        fetch('/entreprises').then(r => r.json()),
        fetch('/utilisateurs').then(r => r.json())
    ]).then(([corpData, userData]) => {
        corporations = corpData;
        users = userData;
        populateSelects();
    });
}

function loadMarkers() {
    fetch('/signalements')
        .then(res => res.json())
        .then(data => {
            allSignalements = data;
            markersLayer.clearLayers();
            data.forEach(s => {
                const icon = icons[s.statut] ?? icons.nouveau;
                const m = L.marker([s.latitude, s.longitude], { icon })
                 .addTo(markersLayer);

                const corpName = corporations.find(c => c.id_entreprise == s.id_entreprise)?.nom ?? 'None';
                const userName = users.find(u => u.id_utilisateur == s.id_utilisateur)?.prenom ?? 'Unknown';

                m.bindPopup(`
                    <div style="min-width: 150px">
                        <b>Signalement #${s.id_signalement}</b><br>
                        <b>Statut:</b> ${s.statut}<br>
                        <b>Title:</b> ${s.title ?? '-'}<br>
                        <b>Description:</b> ${s.description ?? '-'}<br>
                        <b>Surface:</b> ${s.surface_m2 ?? '-'} m²<br>
                        <b>Budget:</b> ${s.budget ?? '-'} Ar<br>
                        <b>Date:</b> ${s.report_date ?? '-'}<br>
                        <b>Coord:</b> ${s.latitude ?? '-'}, ${s.longitude ?? '-'}<br>
                        <b>Entreprise:</b> ${corpName}<br>
                        <b>Utilisateur:</b> ${userName}<br>
                        <button class="btn-edit-popup" onclick="openEditModal(${s.id_signalement})">Edit Details</button>
                    </div>
                `);
            });
            updateSummary(data);
        });
}

function updateSummary(data) {
    const totalPoints = data.length;
    let totalSurface = 0;
    let totalBudget = 0;
    let finishedCount = 0;

    data.forEach(s => {
        totalSurface += parseFloat(s.surface_m2 || 0);
        totalBudget += parseFloat(s.budget || 0);
        if (s.statut === 'termine') {
            finishedCount++;
        }
    });

    const progress = totalPoints > 0 ? Math.round((finishedCount / totalPoints) * 100) : 0;

    document.getElementById('stat-points').textContent = totalPoints;
    document.getElementById('stat-surface').textContent = totalSurface.toLocaleString();
    document.getElementById('stat-progress').textContent = progress + '%';
    document.getElementById('stat-budget').textContent = totalBudget.toLocaleString();
}

// -----------------------------
// Edit Logic
// -----------------------------
window.openEditModal = function(id) {
    const s = allSignalements.find(item => item.id_signalement == id);
    if (!s) return;

    const form = document.getElementById('edit-form');
    form.id_signalement.value = s.id_signalement;
    form.title.value = s.title || '';
    form.description.value = s.description || '';
    form.surface_m2.value = s.surface_m2;
    form.budget.value = s.budget;
    form.statut.value = s.statut;
    form.id_entreprise.value = s.id_entreprise || '';
    form.id_utilisateur.value = s.id_utilisateur;
    form.report_date.value = s.report_date || '';
    form.coordinates.value = `${s.latitude ?? ''}, ${s.longitude ?? ''}`.trim();
    form.note.value = '';

    document.getElementById('edit-modal').style.display = 'block';
    document.getElementById('add-modal').style.display = 'none';
};

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
        id_utilisateur_modif: 1 // Default modifier for now
    };

    fetch(`/signalements/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token
        },
        body: JSON.stringify(body)
    })
    .then(r => r.json())
    .then(() => {
        closeModals();
        loadMarkers();
    })
    .catch(err => alert('Error saving: ' + err));
});

// -----------------------------
// Setup & Events
// -----------------------------
let addMode = false;
let currentLat = null, currentLng = null;

document.getElementById('edit-btn').addEventListener('click', () => {
    addMode = !addMode;
    document.getElementById('edit-btn').textContent = addMode ? 'Disable Add Mode' : 'Enable Add Mode';
    document.getElementById('edit-btn').classList.toggle('addModeActive', addMode);
});

map.on('click', (e) => {
    if(!addMode) return;
    currentLat = e.latlng.lat;
    currentLng = e.latlng.lng;
    document.getElementById('add-modal').style.display = 'block';
    document.getElementById('edit-modal').style.display = 'none';
    const coordInput = document.querySelector('#add-form input[name="coordinates"]');
    if (coordInput) coordInput.value = `${currentLat.toFixed(6)}, ${currentLng.toFixed(6)}`;
});

document.getElementById('add-form').addEventListener('submit', function(evt) {
    evt.preventDefault();
    const body = {
        lat: currentLat,
        lng: currentLng,
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
    .then(res => res.json())
    .then(() => {
        closeModals();
        this.reset();
        loadMarkers();
    });
});

loadInitialData();
loadMarkers();
setInterval(loadMarkers, 30000); // Refresh every 30s
</script>

</body>
</html>
