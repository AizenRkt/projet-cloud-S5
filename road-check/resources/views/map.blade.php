<!DOCTYPE html>
<html>
<head>
    <title>Offline Map - Antananarivo</title>

    {{-- Leaflet CSS --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

    <style>
        body { margin: 0; padding: 0; }
        #map {
            height: 100vh;
            width: 100%;
            background: #f1f3f4;
        }
    </style>
</head>
<body>

<div id="map"></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
const map = L.map('map').setView([-18.89493, 47.49292], 13.43);

L.tileLayer('http://localhost:8081/styles/Basic/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: '© OpenMapTiles © OpenStreetMap contributors'
}).addTo(map);

L.control.scale({ imperial: false }).addTo(map);

// Optional: Add a marker for the center
L.marker([-18.9369, 47.4481]).addTo(map)
    .bindPopup('<b>Antananarivo</b>')
    .openPopup();

</script>

</body>
</html>
