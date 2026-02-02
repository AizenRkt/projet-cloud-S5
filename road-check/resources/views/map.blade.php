<!DOCTYPE html>
<html>
<head>
    <title>Offline Map - Antananarivo</title>

    <link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}">

    <style>
        body { margin: 0; padding: 0; }
        #map { height: 100vh; width: 100%; background: #f1f3f4; }
        #edit-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            background: #1976d2;
            color: white;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 4px;
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
            background: white;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            z-index: 2000;
            border-radius: 8px;
        }
        #add-modal h3, #edit-modal h3 { margin-top: 0; color: #1976d2; }

        label { display: block; margin-top: 10px; font-weight: bold; font-size: 0.9em; color: #555; }
        input, select, textarea {
            display: block;
            width: 100%;
            margin-bottom: 12px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            padding: 8px 15px;
            margin-right: 5px;
            cursor: pointer;
            border: none;
            border-radius: 4px;
            font-weight: bold;
        }
        .btn-save { background: #1976d2; color: white; }
        .btn-cancel { background: #666; color: white; }
        .btn-edit-popup {
            background: #1976d2;
            color: white;
            padding: 4px 8px;
            font-size: 0.8em;
            margin-top: 5px;
        }
    </style>
</head>
<body>

<button id="edit-btn">Enable Add Mode</button>
<div id="map"></div>

<!-- New Signalement Modal -->
<div id="add-modal">
    <h3>New Signalement</h3>
    <form id="add-form">
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
        <button type="submit" class="btn-save">Add</button>
        <button type="button" class="btn-cancel" onclick="closeModals()">Cancel</button>
    </form>
</div>

<!-- Edit Signalement Modal -->
<div id="edit-modal">
    <h3>Edit Signalement</h3>
    <form id="edit-form">
        <input type="hidden" name="id_signalement">
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
                        <b>Surface:</b> ${s.surface_m2 ?? '-'} m²<br>
                        <b>Budget:</b> ${s.budget ?? '-'} Ar<br>
                        <b>Entreprise:</b> ${corpName}<br>
                        <b>Utilisateur:</b> ${userName}<br>
                        <button class="btn-edit-popup" onclick="openEditModal(${s.id_signalement})">Edit Details</button>
                    </div>
                `);
            });
        });
}

// -----------------------------
// Edit Logic
// -----------------------------
window.openEditModal = function(id) {
    const s = allSignalements.find(item => item.id_signalement == id);
    if (!s) return;

    const form = document.getElementById('edit-form');
    form.id_signalement.value = s.id_signalement;
    form.surface_m2.value = s.surface_m2;
    form.budget.value = s.budget;
    form.statut.value = s.statut;
    form.id_entreprise.value = s.id_entreprise || '';
    form.id_utilisateur.value = s.id_utilisateur;
    form.note.value = '';

    document.getElementById('edit-modal').style.display = 'block';
    document.getElementById('add-modal').style.display = 'none';
};

document.getElementById('edit-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = this.id_signalement.value;
    const body = {
        surface_m2: this.surface_m2.value,
        budget: this.budget.value,
        statut: this.statut.value,
        id_entreprise: this.id_entreprise.value || null,
        id_utilisateur: this.id_utilisateur.value,
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
});

map.on('click', (e) => {
    if(!addMode) return;
    currentLat = e.latlng.lat;
    currentLng = e.latlng.lng;
    document.getElementById('add-modal').style.display = 'block';
    document.getElementById('edit-modal').style.display = 'none';
});

document.getElementById('add-form').addEventListener('submit', function(evt) {
    evt.preventDefault();
    const body = {
        lat: currentLat, lng: currentLng,
        surface_m2: this.surface_m2.value,
        budget: this.budget.value,
        statut: this.statut.value,
        id_utilisateur: this.id_utilisateur.value,
        id_entreprise: this.id_entreprise.value || null
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
