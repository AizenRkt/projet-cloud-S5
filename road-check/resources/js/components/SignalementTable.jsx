import React, { useEffect, useState } from 'react';
import axios from 'axios';

function SignalementTable({ currentRole, refreshKey, onUpdated }) {
    const [signalements, setSignalements] = useState([]);
    const [entreprises, setEntreprises] = useState([]);
    const [editingId, setEditingId] = useState(null);
    const [formData, setFormData] = useState({
        statut: 'nouveau',
        surface_m2: '',
        budget: '',
        id_entreprise: '',
    });

    const isManager = currentRole === 'manager';

    useEffect(() => {
        fetchData();
    }, [refreshKey]);

    const fetchData = async () => {
        const [signalementResponse, entrepriseResponse] = await Promise.all([
            axios.get('/api/signalements'),
            axios.get('/api/entreprises'),
        ]);
        setSignalements(signalementResponse.data);
        setEntreprises(entrepriseResponse.data);
    };

    const handleEdit = (signalement) => {
        if (!isManager) return;
        setEditingId(signalement.id_signalement);
        setFormData({
            statut: signalement.statut,
            surface_m2: signalement.surface_m2 || '',
            budget: signalement.budget || '',
            id_entreprise: signalement.id_entreprise || '',
        });
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData((prev) => ({ ...prev, [name]: value }));
    };

    const handleSave = async () => {
        try {
            await axios.put(`/api/signalements/${editingId}`, {
                ...formData,
                surface_m2: formData.surface_m2 ? Number(formData.surface_m2) : null,
                budget: formData.budget ? Number(formData.budget) : null,
                id_entreprise: formData.id_entreprise || null,
            });
            setEditingId(null);
            await fetchData();
            onUpdated?.();
        } catch (err) {
            alert('Erreur lors de la mise à jour du signalement');
        }
    };

    return (
        <div className="mt-6">
            <h2 className="text-xl font-semibold mb-4">Signalements</h2>
            <div className="overflow-x-auto">
                <table className="min-w-full border border-gray-200 text-sm">
                    <thead>
                        <tr className="bg-gray-50">
                            <th className="px-3 py-2 border">ID</th>
                            <th className="px-3 py-2 border">Coordonnées</th>
                            <th className="px-3 py-2 border">Statut</th>
                            <th className="px-3 py-2 border">Surface (m²)</th>
                            <th className="px-3 py-2 border">Budget (Ar)</th>
                            <th className="px-3 py-2 border">Entreprise</th>
                            {isManager && <th className="px-3 py-2 border">Actions</th>}
                        </tr>
                    </thead>
                    <tbody>
                        {signalements.map((signalement) => (
                            <tr key={signalement.id_signalement}>
                                <td className="border px-3 py-2">{signalement.id_signalement}</td>
                                <td className="border px-3 py-2">
                                    {signalement.latitude.toFixed(4)}, {signalement.longitude.toFixed(4)}
                                </td>
                                <td className="border px-3 py-2">
                                    {editingId === signalement.id_signalement ? (
                                        <select
                                            name="statut"
                                            value={formData.statut}
                                            onChange={handleChange}
                                            className="border px-2 py-1"
                                        >
                                            <option value="nouveau">Nouveau</option>
                                            <option value="en cours">En cours</option>
                                            <option value="termine">Terminé</option>
                                        </select>
                                    ) : (
                                        signalement.statut
                                    )}
                                </td>
                                <td className="border px-3 py-2">
                                    {editingId === signalement.id_signalement ? (
                                        <input
                                            type="number"
                                            name="surface_m2"
                                            value={formData.surface_m2}
                                            onChange={handleChange}
                                            className="border px-2 py-1 w-full"
                                        />
                                    ) : (
                                        signalement.surface_m2 ?? 'N/A'
                                    )}
                                </td>
                                <td className="border px-3 py-2">
                                    {editingId === signalement.id_signalement ? (
                                        <input
                                            type="number"
                                            name="budget"
                                            value={formData.budget}
                                            onChange={handleChange}
                                            className="border px-2 py-1 w-full"
                                        />
                                    ) : (
                                        signalement.budget ? signalement.budget.toLocaleString() : 'N/A'
                                    )}
                                </td>
                                <td className="border px-3 py-2">
                                    {editingId === signalement.id_signalement ? (
                                        <select
                                            name="id_entreprise"
                                            value={formData.id_entreprise}
                                            onChange={handleChange}
                                            className="border px-2 py-1 w-full"
                                        >
                                            <option value="">Non attribuée</option>
                                            {entreprises.map((entreprise) => (
                                                <option key={entreprise.id_entreprise} value={entreprise.id_entreprise}>
                                                    {entreprise.nom}
                                                </option>
                                            ))}
                                        </select>
                                    ) : (
                                        signalement.entreprise?.nom ?? 'Non attribuée'
                                    )}
                                </td>
                                {isManager && (
                                    <td className="border px-3 py-2">
                                        {editingId === signalement.id_signalement ? (
                                            <button
                                                onClick={handleSave}
                                                className="bg-green-500 text-white px-2 py-1 rounded"
                                            >
                                                Sauvegarder
                                            </button>
                                        ) : (
                                            <button
                                                onClick={() => handleEdit(signalement)}
                                                className="bg-blue-500 text-white px-2 py-1 rounded"
                                            >
                                                Modifier
                                            </button>
                                        )}
                                    </td>
                                )}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

export default SignalementTable;
