import React, { useState, useEffect } from 'react';
import ReactDOM from 'react-dom/client';

function StatsApp() {
    const [stats, setStats] = useState(null);
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadStats();
    }, []);

    async function loadStats(start = '', end = '') {
        setLoading(true);
        try {
            let url = '/api/stats/detailed';
            const params = new URLSearchParams();
            if (start) params.append('start_date', start);
            if (end) params.append('end_date', end);
            if (params.toString()) url += '?' + params.toString();

            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            setStats(data);
        } catch (error) {
            console.error('Error loading stats:', error);
            setStats(null);
        } finally {
            setLoading(false);
        }
    }

    function handleFilter(e) {
        e.preventDefault();
        loadStats(startDate, endDate);
    }

    function handleReset() {
        setStartDate('');
        setEndDate('');
        loadStats('', '');
    }

    async function logout() {
        try {
            await fetch('/logout', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            window.location.href = '/login';
        } catch (error) {
            alert('Erreur lors de la déconnexion');
        }
    }

    if (loading) {
        return (
            <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100vh', color: '#58a6ff' }}>
                Chargement des statistiques...
            </div>
        );
    }

    if (!stats) {
        return (
            <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100vh', color: '#f85149' }}>
                Erreur de chargement des statistiques
            </div>
        );
    }

    return (
        <>
            <style dangerouslySetInnerHTML={{__html: `
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Segoe UI', sans-serif; background: #0d1117; color: #c9d1d9; min-height: 100vh; }
                .navbar { position: fixed; top: 0; left: 0; right: 0; height: 56px; background: linear-gradient(90deg, #161b22, #21262d); display: flex; align-items: center; justify-content: space-between; padding: 0 20px; z-index: 1000; border-bottom: 1px solid #30363d; }
                .navbar-brand { display: flex; align-items: center; gap: 10px; }
                .navbar-brand .logo { font-size: 1.5rem; }
                .navbar-brand .title { color: #58a6ff; font-weight: 700; }
                .navbar-brand .subtitle { color: #8b949e; }
                .navbar-menu { display: flex; gap: 10px; }
                .nav-btn { padding: 8px 16px; border: 1px solid #30363d; background: #21262d; color: #c9d1d9; border-radius: 6px; cursor: pointer; font-size: 0.85rem; text-decoration: none; display: inline-block; text-align: center; }
                .nav-btn:hover { background: #30363d; border-color: #58a6ff; }
                .container { margin-top: 80px; padding: 20px; max-width: 1200px; margin-left: auto; margin-right: auto; }
                .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 40px; }
                .stat-card { background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 20px; text-align: center; }
                .stat-value { font-size: 2rem; font-weight: 700; color: #58a6ff; margin-bottom: 8px; }
                .stat-label { font-size: 0.9rem; color: #8b949e; }
                .stats-table { background: #161b22; border: 1px solid #30363d; border-radius: 12px; overflow: hidden; }
                .stats-table h3 { padding: 20px; background: #1c2128; border-bottom: 1px solid #30363d; margin: 0; color: #58a6ff; }
                .data-table { width: 100%; border-collapse: collapse; }
                .data-table th, .data-table td { padding: 15px; text-align: left; border-bottom: 1px solid #30363d; }
                .data-table th { background: #21262d; color: #8b949e; font-weight: 600; }
                .data-table tr:hover { background: #1c2128; }
                .processing-time { color: #f0883e; font-weight: 600; }
            `}} />
            <nav className="navbar">
                <div className="navbar-brand">
                    <span className="logo"></span>
                    <span className="title">Road Check</span>
                    <span className="subtitle">| Statistiques</span>
                </div>
                <div className="navbar-menu">
                    <a href="/map" className="nav-btn">Carte</a>
                    <button className="nav-btn" onClick={logout}>Déconnexion</button>
                </div>
            </nav>

            <div className="container">
                <h1 style={{ color: '#58a6ff', marginBottom: '30px' }}>Tableau de Statistiques</h1>

                {/* Date Filters */}
                <div style={{ background: '#161b22', border: '1px solid #30363d', borderRadius: '12px', padding: '20px', marginBottom: '30px' }}>
                    <h3 style={{ color: '#58a6ff', marginBottom: '15px' }}>Filtres par Date</h3>
                    <form onSubmit={handleFilter} style={{ display: 'flex', gap: '15px', alignItems: 'center', flexWrap: 'wrap' }}>
                        <div>
                            <label htmlFor="start_date" style={{ display: 'block', marginBottom: '5px', color: '#8b949e', fontSize: '0.9rem' }}>Date de début</label>
                            <input
                                type="date"
                                id="start_date"
                                value={startDate}
                                onChange={(e) => setStartDate(e.target.value)}
                                style={{ padding: '8px 12px', background: '#21262d', border: '1px solid #30363d', borderRadius: '6px', color: '#c9d1d9' }}
                            />
                        </div>
                        <div>
                            <label htmlFor="end_date" style={{ display: 'block', marginBottom: '5px', color: '#8b949e', fontSize: '0.9rem' }}>Date de fin</label>
                            <input
                                type="date"
                                id="end_date"
                                value={endDate}
                                onChange={(e) => setEndDate(e.target.value)}
                                style={{ padding: '8px 12px', background: '#21262d', border: '1px solid #30363d', borderRadius: '6px', color: '#c9d1d9' }}
                            />
                        </div>
                        <div style={{ display: 'flex', gap: '10px', alignItems: 'flex-end' }}>
                            <button type="submit" className="nav-btn" style={{ padding: '8px 16px' }}>Filtrer</button>
                            <button type="button" className="nav-btn" style={{ padding: '8px 16px' }} onClick={handleReset}>Réinitialiser</button>
                        </div>
                    </form>
                </div>

                {/* Stats Grid */}
                <div className="stats-grid">
                    <div className="stat-card">
                        <div className="stat-value">{stats.total}</div>
                        <div className="stat-label">Total Signalements</div>
                    </div>
                    <div className="stat-card">
                        <div className="stat-value" style={{ color: '#1f6feb' }}>{stats.nouveau}</div>
                        <div className="stat-label">Validés</div>
                    </div>
                    <div className="stat-card">
                        <div className="stat-value" style={{ color: '#f0883e' }}>{stats.en_cours}</div>
                        <div className="stat-label">En cours</div>
                    </div>
                    <div className="stat-card">
                        <div className="stat-value" style={{ color: '#238636' }}>{stats.termine}</div>
                        <div className="stat-label">Terminés</div>
                    </div>
                    <div className="stat-card">
                        <div className="stat-value processing-time">{stats.average_processing_time} jours</div>
                        <div className="stat-label">Délai Moyen de Traitement</div>
                    </div>
                    <div className="stat-card">
                        <div className="stat-value">{stats.avancement}%</div>
                        <div className="stat-label">Avancement Global</div>
                    </div>
                </div>

                {/* Detailed Stats Table */}
                <div className="stats-table">
                    <h3>Détails des Statistiques</h3>
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>Métrique</th>
                                <th>Valeur</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Total des signalements</td>
                                <td>{stats.total}</td>
                                <td>Nombre total de signalements enregistrés</td>
                            </tr>
                            <tr>
                                <td>Signalements validés</td>
                                <td>{stats.nouveau}</td>
                                <td>Signalements en attente de traitement</td>
                            </tr>
                            <tr>
                                <td>Signalements en cours</td>
                                <td>{stats.en_cours}</td>
                                <td>Signalements actuellement en traitement</td>
                            </tr>
                            <tr>
                                <td>Signalements terminés</td>
                                <td>{stats.termine}</td>
                                <td>Signalements finalisés</td>
                            </tr>
                            <tr>
                                <td>Délai de traitement moyen</td>
                                <td className="processing-time">{stats.average_processing_time} jours</td>
                                <td>Moyenne des jours entre création et achèvement (basé sur {stats.completed_count} signalements terminés)</td>
                            </tr>
                            <tr>
                                <td>Avancement global</td>
                                <td>{stats.avancement}%</td>
                                <td>Pourcentage moyen d'avancement de tous les signalements</td>
                            </tr>
                            <tr>
                                <td>Surface totale</td>
                                <td>{Number(stats.total_surface).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} m²</td>
                                <td>Surface cumulée de tous les signalements</td>
                            </tr>
                            <tr>
                                <td>Budget total</td>
                                <td>{Number(stats.total_budget).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} Ar</td>
                                <td>Budget cumulé de tous les signalements</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

// Mount the React app
const root = ReactDOM.createRoot(document.getElementById('stats-app'));
root.render(<StatsApp />);
