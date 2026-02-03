import React, { useEffect, useMemo, useState } from 'react';
import ReactDOM from 'react-dom/client';
import axios from 'axios';

function useApi(token) {
    return useMemo(() => {
        const client = axios.create({
            headers: {
                Authorization: `Bearer ${token}`,
                'Content-Type': 'application/json',
            },
        });
        return client;
    }, [token]);
}

function ProfileApp({ token }) {
    const api = useApi(token);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [me, setMe] = useState(null);
    const [users, setUsers] = useState([]);
    const [roles, setRoles] = useState([]);
    const [form, setForm] = useState({ id: null, nom: '', prenom: '', email: '', id_role: null, bloque: false, password: '' });

    const isAdmin = me && me.role && ['administrateur', 'moderateur'].includes(me.role.toLowerCase());

    useEffect(() => {
        const fetchData = async () => {
            try {
                const meResp = await api.get('/api/users/me');
                setMe(meResp.data);
                if (meResp.data.role && ['administrateur', 'moderateur'].includes(meResp.data.role.toLowerCase())) {
                    const [usersResp, rolesResp] = await Promise.all([
                        api.get('/api/users'),
                        api.get('/api/roles'),
                    ]);
                    setUsers(usersResp.data);
                    setRoles(rolesResp.data);
                }
                setFormFromUser(meResp.data);
            } catch (err) {
                setError(err.response?.data?.error || err.message);
            } finally {
                setLoading(false);
            }
        };
        fetchData();
    }, [api]);

    const setFormFromUser = (u) => {
        setForm({
            id: u.id,
            nom: u.nom || '',
            prenom: u.prenom || '',
            email: u.email || '',
            id_role: u.id_role || null,
            bloque: Boolean(u.bloque),
            password: '',
        });
    };

    const startCreate = () => {
        setForm({ id: null, nom: '', prenom: '', email: '', id_role: '', bloque: false, password: '' });
    };

    const handleSelectUser = (id) => {
        const target = users.find((u) => u.id === id);
        if (target) {
            setFormFromUser(target);
        }
    };

    const handleDelete = async (id) => {
        if (!window.confirm('Supprimer cet utilisateur ?')) return;
        setError('');
        try {
            await api.delete(`/api/users/${id}`);
            const refreshed = await api.get('/api/users');
            setUsers(refreshed.data);
            if (form.id === id) {
                // reset form to current user
                setFormFromUser(me);
            }
        } catch (err) {
            setError(err.response?.data?.error || err.message);
        }
    };

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setForm((prev) => ({ ...prev, [name]: type === 'checkbox' ? checked : value }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        try {
            const payload = {
                nom: form.nom,
                prenom: form.prenom,
                email: form.email,
            };
            if (isAdmin) {
                payload.id_role = form.id_role;
                payload.bloque = form.bloque;
            }

            if (!form.id) {
                // création
                payload.password = form.password;
                const resp = await api.post('/api/users', payload);
                if (isAdmin) {
                    const refreshed = await api.get('/api/users');
                    setUsers(refreshed.data);
                }
                setFormFromUser(resp.data);
                setError('');
                alert('Utilisateur créé');
            } else {
                const resp = await api.put(`/api/users/${form.id}`, payload);
                setMe(resp.data.id === me.id ? resp.data : me);
                if (isAdmin) {
                    const refreshed = await api.get('/api/users');
                    setUsers(refreshed.data);
                }
                setError('');
                alert('Profil mis à jour');
            }
        } catch (err) {
            setError(err.response?.data?.error || err.message);
        }
    };

    if (loading) return <p className="p-4">Chargement...</p>;
    if (error) return <div className="alert alert-danger m-4">{error}</div>;
    if (!me) return <p className="p-4">Utilisateur introuvable.</p>;

    return (
        <div className="container mt-4">
            <h2 className="mb-3">Gestion des profils</h2>
            <div className="row">
                {isAdmin && (
                    <div className="col-md-5 mb-4">
                        <div className="card">
                            <div className="card-header">Utilisateurs</div>
                            <div className="card-body" style={{ maxHeight: 400, overflowY: 'auto' }}>
                                <div className="d-flex justify-content-end mb-2">
                                    <button className="btn btn-sm btn-success" type="button" onClick={startCreate}>
                                        Créer un utilisateur
                                    </button>
                                </div>
                                <ul className="list-group">
                                    {users.map((u) => (
                                        <li
                                            key={u.id}
                                            className={`list-group-item d-flex justify-content-between align-items-center ${form.id === u.id ? 'active' : ''}`}
                                            role="button"
                                            onClick={() => handleSelectUser(u.id)}
                                        >
                                            <span>
                                                {u.prenom} {u.nom} ({u.role || 'N/A'})
                                            </span>
                                            <div className="d-flex align-items-center gap-2">
                                                {u.bloque && <span className="badge bg-danger">Bloqué</span>}
                                                <button
                                                    type="button"
                                                    className="btn btn-sm btn-outline-danger"
                                                    onClick={(e) => { e.stopPropagation(); handleDelete(u.id); }}
                                                >
                                                    Supprimer
                                                </button>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>
                    </div>
                )}

                <div className={isAdmin ? 'col-md-7' : 'col-md-12'}>
                    <div className="card">
                        <div className="card-header">Éditer le profil</div>
                        <div className="card-body">
                            <form onSubmit={handleSubmit} className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label">Prénom</label>
                                    <input name="prenom" value={form.prenom} onChange={handleChange} className="form-control" required />
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label">Nom</label>
                                    <input name="nom" value={form.nom} onChange={handleChange} className="form-control" required />
                                </div>
                                <div className="col-md-12">
                                    <label className="form-label">Email</label>
                                    <input type="email" name="email" value={form.email} onChange={handleChange} className="form-control" required />
                                </div>
                                {!form.id && (
                                    <div className="col-md-12">
                                        <label className="form-label">Mot de passe</label>
                                        <input type="password" name="password" value={form.password} onChange={handleChange} className="form-control" required />
                                    </div>
                                )}
                                {isAdmin && (
                                    <>
                                        <div className="col-md-6">
                                            <label className="form-label">Rôle</label>
                                            <select name="id_role" value={form.id_role || ''} onChange={handleChange} className="form-select" required>
                                                <option value="" disabled>Choisir...</option>
                                                {roles.map((r) => (
                                                    <option key={r.id_role} value={r.id_role}>{r.nom}</option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="col-md-6 d-flex align-items-end gap-2">
                                            <div className="form-check">
                                                <input className="form-check-input" type="checkbox" id="bloque" name="bloque" checked={form.bloque} onChange={handleChange} />
                                                <label className="form-check-label" htmlFor="bloque">Bloqué</label>
                                            </div>
                                        </div>
                                    </>
                                )}
                                <div className="col-12 d-flex gap-2">
                                    <button className="btn btn-primary" type="submit">Enregistrer</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

const mount = document.getElementById('profile-app');
if (mount) {
    const token = mount.dataset.token;
    ReactDOM.createRoot(mount).render(<ProfileApp token={token} />);
}
