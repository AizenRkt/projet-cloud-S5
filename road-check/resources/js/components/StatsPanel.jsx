import React, { useEffect, useState } from 'react';
import axios from 'axios';

function StatsPanel({ refreshKey }) {
    const [stats, setStats] = useState(null);
    const [error, setError] = useState(null);

    useEffect(() => {
        const fetchStats = async () => {
            try {
                const response = await axios.get('/api/stats/global');
                setStats(response.data);
                setError(null);
            } catch (err) {
                setError("Impossible de charger les statistiques");
            }
        };

        fetchStats();
    }, [refreshKey]);

    if (error) return <div className="text-red-600">{error}</div>;
    if (!stats) return <div>Chargement des statistiques...</div>;

    return (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div className="bg-white p-4 shadow rounded border">
                <p className="text-sm text-gray-500">Nombre de points</p>
                <p className="text-2xl font-bold">{stats.total_points}</p>
            </div>
            <div className="bg-white p-4 shadow rounded border">
                <p className="text-sm text-gray-500">Surface totale (m²)</p>
                <p className="text-2xl font-bold">{stats.surface_m2}</p>
            </div>
            <div className="bg-white p-4 shadow rounded border">
                <p className="text-sm text-gray-500">Budget total (Ar)</p>
                <p className="text-2xl font-bold">{stats.budget.toLocaleString()}</p>
            </div>
            <div className="bg-white p-4 shadow rounded border">
                <p className="text-sm text-gray-500">Avancement</p>
                <p className="text-2xl font-bold">{stats.progress_percent}%</p>
            </div>
        </div>
    );
}

export default StatsPanel;
