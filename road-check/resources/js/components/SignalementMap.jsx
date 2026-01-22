import React, { useEffect, useState } from 'react';
import { MapContainer, TileLayer, Marker, Popup } from 'react-leaflet';
import L from 'leaflet';
import axios from 'axios';
import 'leaflet/dist/leaflet.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

function SignalementMap({ refreshKey }) {
    const [signalements, setSignalements] = useState([]);

    useEffect(() => {
        const fetchSignalements = async () => {
            const response = await axios.get('/api/signalements');
            setSignalements(response.data);
        };

        fetchSignalements();
    }, [refreshKey]);

    const center = [-18.8792, 47.5079];

    return (
        <div className="mb-6">
            <h2 className="text-xl font-semibold mb-2">Carte des signalements</h2>
            <MapContainer center={center} zoom={12} style={{ height: '400px', width: '100%' }}>
                <TileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />
                {signalements.map((signalement) => (
                    <Marker
                        key={signalement.id_signalement}
                        position={[signalement.latitude, signalement.longitude]}
                    >
                        <Popup>
                            <div>
                                <p className="font-semibold">Statut: {signalement.statut}</p>
                                <p>Date: {new Date(signalement.date_signalement).toLocaleDateString()}</p>
                                <p>Surface: {signalement.surface_m2 ?? 'N/A'} m²</p>
                                <p>Budget: {signalement.budget ? `${signalement.budget.toLocaleString()} Ar` : 'N/A'}</p>
                                <p>Entreprise: {signalement.entreprise?.nom ?? 'Non attribuée'}</p>
                            </div>
                        </Popup>
                    </Marker>
                ))}
            </MapContainer>
        </div>
    );
}

export default SignalementMap;
