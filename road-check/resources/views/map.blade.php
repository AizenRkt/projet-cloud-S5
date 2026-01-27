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
        #add-modal {
            display: none;
            position: absolute;
            top: 50px;
            right: 50px;
            width: 300px;
            background: white;
            padding: 15px;
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
            z-index: 2000;
            border-radius: 6px;
        }
        #add-modal h3 { margin-top: 0; }

        #add-modal input, #add-modal select {
            display: block;
            width: 100%;
            margin-bottom: 8px;
            padding: 4px 6px;
        }
        #add-modal button {
            padding: 6px 10px;
            margin-right: 5px;
            cursor: pointer;
            border: none;
            border-radius: 4px;
        }
        #add-btn { background: #1976d2; color: white; }
        #cancel-btn { background: #aaa; color: white; }
    </style>
</head>
<body>

<button id="edit-btn">Enable Add Mode</button>
<div id="map"></div>

<!-- Modal form -->
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
            <option value="dangerous">dangerous</option>
        </select>
        <button type="submit" id="add-btn">Add</button>
        <button type="button" id="cancel-btn">Cancel</button>
    </form>
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="{{ asset('leaflet/leaflet.js') }}"></script>

<script>
const map = L.map('map').setView([-18.89493, 47.49292], 13);

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

// -----------------------------
// Toggle Add Mode
// -----------------------------
let addMode = false;
let currentLat = null;
let currentLng = null;

document.getElementById('edit-btn').addEventListener('click', () => {
    addMode = !addMode;
    document.getElementById('edit-btn').textContent = addMode ? 'Disable Add Mode' : 'Enable Add Mode';
    console.log('Add Mode:', addMode);
});

// -----------------------------
// Load existing markers from DB
// -----------------------------
function loadMarkers() {
    fetch('/signalements')
        .then(res => res.json())
        .then(data => {
            data.forEach(s => {
                const icon = icons[s.statut] ?? icons.nouveau;
                L.marker([s.latitude, s.longitude], { icon })
                 .addTo(map)
                 .bindPopup(`<b>Signalement #${s.id_signalement}</b><br>Statut: ${s.statut}<br>Surface: ${s.surface_m2 ?? '-'} m²<br>Budget: ${s.budget ?? '-'}`);
            });
        });
}

// Initial load
loadMarkers();

setInterval(loadMarkers, 10000);

// -----------------------------
// Map click to open modal
// -----------------------------
map.on('click', function(e) {
    if(!addMode) return;

    currentLat = e.latlng.lat;
    currentLng = e.latlng.lng;

    document.getElementById('add-modal').style.display = 'block';
});

// -----------------------------
// Cancel modal
// -----------------------------
document.getElementById('cancel-btn').addEventListener('click', () => {
    document.getElementById('add-modal').style.display = 'none';
});

// -----------------------------
// Submit modal form
// -----------------------------
document.getElementById('add-form').addEventListener('submit', function(evt) {
    evt.preventDefault();

    const form = evt.target;
    const surface_m2 = form.surface_m2.value;
    const budget = form.budget.value;
    const statut = form.statut.value;

    fetch('/signalements', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json', 
            'X-CSRF-TOKEN': token 
        },
        body: JSON.stringify({ lat: currentLat, lng: currentLng, surface_m2, budget, statut })
    })
    .then(res => res.json())
    .then(s => {
        const icon = icons[s.statut] ?? icons.nouveau;
        L.marker([s.latitude, s.longitude], { icon })
         .addTo(map)
         .bindPopup(`<b>Signalement #${s.id_signalement}</b><br>Statut: ${s.statut}<br>Surface: ${s.surface_m2} m²<br>Budget: ${s.budget}`);

        // hide modal & reset form
        document.getElementById('add-modal').style.display = 'none';
        form.reset();
    })
    .catch(err => {
        console.error('POST error:', err);
        alert('Failed to add signalement. Check console.');
    });
});
</script>

</body>
</html>
