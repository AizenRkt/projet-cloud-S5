import React, { useEffect, useState } from 'react';
import axios from 'axios';

function SignalementForm({ onCreated }) {
    const [entreprises, setEntreprises] = useState([]);
    const [formData, setFormData] = useState({
        id_utilisateur: '',
        latitude: '',
        longitude: '',
        surface_m2: '',
        budget: '',
        id_entreprise: '',
    });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    useEffect(() => {
        const fetchEntreprises = async () => {
            const response = await axios.get('/api/entreprises');
            setEntreprises(response.data);
        };

        fetchEntreprises();
    }, []);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData((prev) => ({ ...prev, [name]: value }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError(null);

        try {
            await axios.post('/api/signalements', {
                ...formData,
                latitude: Number(formData.latitude),
                longitude: Number(formData.longitude),
                surface_m2: formData.surface_m2 ? Number(formData.surface_m2) : null,
                budget: formData.budget ? Number(formData.budget) : null,
                id_entreprise: formData.id_entreprise || null,
            });
            setFormData({
                id_utilisateur: '',
                latitude: '',
                longitude: '',
                surface_m2: '',
                budget: '',
                id_entreprise: '',
            });
            onCreated?.();
        } catch (err) {
            setError("Création impossible. Vérifiez les données saisies.");
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="bg-white p-4 border rounded shadow mb-6">
            <h2 className="text-lg font-semibold mb-4">Déclarer un signalement</h2>
            {error && <div className="text-red-600 mb-3">{error}</div>}
            <form onSubmit={handleSubmit} className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input
                    type="number"
                    name="id_utilisateur"
                    value={formData.id_utilisateur}
                    onChange={handleChange}
                    placeholder="ID utilisateur (optionnel)"
                    className="border px-3 py-2 rounded"
                />
                <input
                    type="number"
                    step="0.0001"
                    name="latitude"
                    value={formData.latitude}
                    onChange={handleChange}
                    placeholder="Latitude"
                    className="border px-3 py-2 rounded"
                    required
                />
                <input
                    type="number"
                    step="0.0001"
                    name="longitude"
                    value={formData.longitude}
                    onChange={handleChange}
                    placeholder="Longitude"
                    className="border px-3 py-2 rounded"
                    required
                />
                <input
                    type="number"
                    name="surface_m2"
                    value={formData.surface_m2}
                    onChange={handleChange}
                    placeholder="Surface en m²"
                    className="border px-3 py-2 rounded"
                />
                <input
                    type="number"
                    name="budget"
                    value={formData.budget}
                    onChange={handleChange}
                    placeholder="Budget (Ar)"
                    className="border px-3 py-2 rounded"
                />
                <select
                    name="id_entreprise"
                    value={formData.id_entreprise}
                    onChange={handleChange}
                    className="border px-3 py-2 rounded"
                >
                    <option value="">Entreprise (optionnel)</option>
                    {entreprises.map((entreprise) => (
                        <option key={entreprise.id_entreprise} value={entreprise.id_entreprise}>
                            {entreprise.nom}
                        </option>
                    ))}
                </select>
                <button
                    type="submit"
                    disabled={loading}
                    className="md:col-span-2 bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 disabled:opacity-50"
                >
                    {loading ? 'Envoi...' : 'Déclarer'}
                </button>
            </form>
        </div>
    );
}

export default SignalementForm;
