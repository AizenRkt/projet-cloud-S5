import React, { useState } from 'react';
import axios from 'axios';

function SyncPanel({ onSync }) {
    const [status, setStatus] = useState(null);
    const [loading, setLoading] = useState(false);

    const handleSync = async () => {
        setLoading(true);
        setStatus(null);
        try {
            const response = await axios.post('/api/signalements/sync');
            setStatus(response.data.message + ' ' + response.data.synced_at);
            onSync?.();
        } catch (err) {
            setStatus("Échec de la synchronisation");
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="bg-white p-4 border rounded shadow mt-6">
            <h2 className="text-lg font-semibold mb-2">Synchronisation</h2>
            <p className="text-sm text-gray-600 mb-4">
                Cette action récupère et publie les signalements pour garder le mobile à jour.
            </p>
            <button
                onClick={handleSync}
                disabled={loading}
                className="bg-amber-500 text-white px-4 py-2 rounded hover:bg-amber-600 disabled:opacity-50"
            >
                {loading ? 'Synchronisation...' : 'Lancer la synchronisation'}
            </button>
            {status && <p className="mt-3 text-sm text-gray-700">{status}</p>}
        </div>
    );
}

export default SyncPanel;
