import React, { useState, useEffect } from 'react';
import axios from 'axios';

function UserManagement() {
    const [users, setUsers] = useState([]);
    const [roles, setRoles] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [editingUser, setEditingUser] = useState(null);
    const [formData, setFormData] = useState({
        email: '',
        nom: '',
        prenom: '',
        id_role: '',
        bloque: false,
    });

    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        try {
            const [userResponse, roleResponse] = await Promise.all([
                axios.get('/api/users'),
                axios.get('/api/roles'),
            ]);
            setUsers(userResponse.data);
            setRoles(roleResponse.data);
        } catch (err) {
            setError('Erreur lors du chargement des utilisateurs');
        } finally {
            setLoading(false);
        }
    };

    const handleEdit = (user) => {
        setEditingUser(user.id_utilisateur);
        setFormData({
            email: user.email,
            nom: user.nom || '',
            prenom: user.prenom || '',
            id_role: user.id_role,
            bloque: user.bloque,
        });
    };

    const handleDelete = async (id) => {
        if (!window.confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
            return;
        }

        try {
            await axios.delete(`/api/users/${id}`);
            setUsers((prev) => prev.filter((user) => user.id_utilisateur !== id));
        } catch (err) {
            alert('Erreur lors de la suppression');
        }
    };

    const handleSave = async () => {
        try {
            await axios.put(`/api/users/${editingUser}`, {
                ...formData,
                id_role: Number(formData.id_role),
            });
            setEditingUser(null);
            await loadData();
        } catch (err) {
            alert('Erreur lors de la sauvegarde');
        }
    };

    const handleCancel = () => {
        setEditingUser(null);
    };

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setFormData((prev) => ({
            ...prev,
            [name]: type === 'checkbox' ? checked : value,
        }));
    };

    if (loading) return <div>Chargement...</div>;
    if (error) return <div>{error}</div>;

    return (
        <div className="mt-8">
            <h2 className="text-xl font-semibold mb-4">Gestion des utilisateurs</h2>
            <table className="table-auto w-full border-collapse border border-gray-300 text-sm">
                <thead>
                    <tr>
                        <th className="border border-gray-300 px-4 py-2">ID</th>
                        <th className="border border-gray-300 px-4 py-2">Email</th>
                        <th className="border border-gray-300 px-4 py-2">Nom</th>
                        <th className="border border-gray-300 px-4 py-2">Prénom</th>
                        <th className="border border-gray-300 px-4 py-2">Rôle</th>
                        <th className="border border-gray-300 px-4 py-2">Bloqué</th>
                        <th className="border border-gray-300 px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {users.map((user) => (
                        <tr key={user.id_utilisateur}>
                            <td className="border border-gray-300 px-4 py-2">{user.id_utilisateur}</td>
                            <td className="border border-gray-300 px-4 py-2">
                                {editingUser === user.id_utilisateur ? (
                                    <input
                                        type="email"
                                        name="email"
                                        value={formData.email}
                                        onChange={handleChange}
                                        className="w-full px-2 py-1 border"
                                    />
                                ) : (
                                    user.email
                                )}
                            </td>
                            <td className="border border-gray-300 px-4 py-2">
                                {editingUser === user.id_utilisateur ? (
                                    <input
                                        type="text"
                                        name="nom"
                                        value={formData.nom}
                                        onChange={handleChange}
                                        className="w-full px-2 py-1 border"
                                    />
                                ) : (
                                    user.nom
                                )}
                            </td>
                            <td className="border border-gray-300 px-4 py-2">
                                {editingUser === user.id_utilisateur ? (
                                    <input
                                        type="text"
                                        name="prenom"
                                        value={formData.prenom}
                                        onChange={handleChange}
                                        className="w-full px-2 py-1 border"
                                    />
                                ) : (
                                    user.prenom
                                )}
                            </td>
                            <td className="border border-gray-300 px-4 py-2">
                                {editingUser === user.id_utilisateur ? (
                                    <select
                                        name="id_role"
                                        value={formData.id_role}
                                        onChange={handleChange}
                                        className="w-full px-2 py-1 border"
                                    >
                                        {roles.map((role) => (
                                            <option key={role.id_role} value={role.id_role}>
                                                {role.nom}
                                            </option>
                                        ))}
                                    </select>
                                ) : (
                                    user.role?.nom
                                )}
                            </td>
                            <td className="border border-gray-300 px-4 py-2">
                                {editingUser === user.id_utilisateur ? (
                                    <input
                                        type="checkbox"
                                        name="bloque"
                                        checked={formData.bloque}
                                        onChange={handleChange}
                                    />
                                ) : (
                                    user.bloque ? 'Oui' : 'Non'
                                )}
                            </td>
                            <td className="border border-gray-300 px-4 py-2">
                                {editingUser === user.id_utilisateur ? (
                                    <>
                                        <button
                                            onClick={handleSave}
                                            className="bg-green-500 text-white px-2 py-1 rounded mr-2"
                                        >
                                            Sauvegarder
                                        </button>
                                        <button
                                            onClick={handleCancel}
                                            className="bg-gray-500 text-white px-2 py-1 rounded"
                                        >
                                            Annuler
                                        </button>
                                    </>
                                ) : (
                                    <>
                                        <button
                                            onClick={() => handleEdit(user)}
                                            className="bg-blue-500 text-white px-2 py-1 rounded mr-2"
                                        >
                                            Modifier
                                        </button>
                                        <button
                                            onClick={() => handleDelete(user.id_utilisateur)}
                                            className="bg-red-500 text-white px-2 py-1 rounded"
                                        >
                                            Supprimer
                                        </button>
                                    </>
                                )}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

export default UserManagement;
