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
        // Center on Antananarivo city center
        // Coordinates from generated MBTiles: -18.906286, 47.515869
        const map = L.map('map').setView([-18.906286, 47.515869], 14);

        // Tile layer from local tileserver
        const tileUrl = 'http://localhost:8081/styles/Basic/{z}/{x}/{y}.png';

        L.tileLayer(tileUrl, {
            attribution: '&copy; <a href="https://www.maptiler.com/">MapTiler</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 18
        }).addTo(map);

        L.marker([-18.906286, 47.515869]).addTo(map)
            .bindPopup('Antananarivo')
            .openPopup();
    }
});
