import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix for default marker icons missing in Webpack
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
});

document.addEventListener('DOMContentLoaded', () => {
    const mapElement = document.getElementById('map');
    if (mapElement) {
        const map = L.map('map', {
            zoomControl: true,
            attributionControl: true
        }).setView([-18.9369, 47.4481], 12);

        // Tile layer from local tileserver (Raster tiles with style-baked labels)
        const tileUrl = 'http://localhost:8081/styles/Basic/{z}/{x}/{y}.png';

        L.tileLayer(tileUrl, {
            attribution: '&copy; <a href="https://openmaptiles.org/">OpenMapTiles</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 18
        }).addTo(map);

        // Add Scale
        L.control.scale({ imperial: false, position: 'bottomleft' }).addTo(map);

        // Add Marker
        L.marker([-18.9369, 47.4481]).addTo(map)
            .bindPopup('<b>Antananarivo Center</b>')
            .openPopup();
    }
});
