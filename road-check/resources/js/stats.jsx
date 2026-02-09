import React, { useState, useEffect } from 'react';

function StatsPanel() {
    const [stats, setStats] = useState(null);
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const [loading, setLoading] = useState(true);
    const [errorMessage, setErrorMessage] = useState('');

    useEffect(() => {
        loadStats();
    }, []);

    async function loadStats(start = '', end = '') {
        setLoading(true);
        setErrorMessage('');
        try {
            let url = '/api/signalements/stats';
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
            setErrorMessage('Erreur de chargement des statistiques');
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

    if (loading) {
        return (
            <div className="stats-panel">
                <div className="stats-loading">Chargement des statistiques...</div>
            </div>
        );
    }

    if (!stats) {
        return (
            <div className="stats-panel">
                <div className="stats-error">{errorMessage || 'Erreur de chargement des statistiques'}</div>
            </div>
        );
    }

    return (
        <div className="stats-panel">
            <div className="stats-panel-header">
                <div>
                    <div className="stats-panel-title">Tableau de Statistiques</div>
                    <div className="stats-panel-subtitle">Filtrer et suivre les indicateurs cles</div>
                </div>
            </div>

            <div className="stats-filter">
                <form onSubmit={handleFilter} className="stats-filter-form">
                    <div className="stats-field">
                        <label htmlFor="start_date">Date de debut</label>
                        <input
                            type="date"
                            id="start_date"
                            value={startDate}
                            onChange={(e) => setStartDate(e.target.value)}
                        />
                    </div>
                    <div className="stats-field">
                        <label htmlFor="end_date">Date de fin</label>
                        <input
                            type="date"
                            id="end_date"
                            value={endDate}
                            onChange={(e) => setEndDate(e.target.value)}
                        />
                    </div>
                    <div className="stats-actions">
                        <button type="submit" className="btn-save" style={{ width: 'auto' }}>Filtrer</button>
                        <button type="button" className="nav-btn" onClick={handleReset}>Reinitialiser</button>
                    </div>
                </form>
            </div>

            <div className="stats-grid">
                <div className="stat-card">
                    <div className="stat-value">{stats.total}</div>
                    <div className="stat-label">Total Signalements</div>
                </div>
                <div className="stat-card">
                    <div className="stat-value" style={{ color: '#1f6feb' }}>{stats.nouveau}</div>
                    <div className="stat-label">Valides</div>
                </div>
                <div className="stat-card">
                    <div className="stat-value" style={{ color: '#f0883e' }}>{stats.en_cours}</div>
                    <div className="stat-label">En cours</div>
                </div>
                <div className="stat-card">
                    <div className="stat-value" style={{ color: '#238636' }}>{stats.termine}</div>
                    <div className="stat-label">Termines</div>
                </div>
                <div className="stat-card">
                    <div className="stat-value stats-highlight">{stats.avancement}%</div>
                    <div className="stat-label">Avancement Global</div>
                </div>
                <div className="stat-card">
                    <div className="stat-value stats-accent">{Number(stats.total_budget || 0).toLocaleString('fr-FR')}</div>
                    <div className="stat-label">Budget total (Ar)</div>
                </div>
            </div>

            <div className="stats-details">
                <div className="stats-details-title">Details</div>
                <div className="stats-details-grid">
                    <div className="stats-detail-item">
                        <span>En attente</span>
                        <strong>{stats.en_attente}</strong>
                    </div>
                    <div className="stats-detail-item">
                        <span>Annules</span>
                        <strong>{stats.annule}</strong>
                    </div>
                    <div className="stats-detail-item">
                        <span>Surface totale (m2)</span>
                        <strong>{Number(stats.total_surface || 0).toLocaleString('fr-FR', { maximumFractionDigits: 2 })}</strong>
                    </div>
                    <div className="stats-detail-item">
                        <span>Budget total</span>
                        <strong>{Number(stats.total_budget || 0).toLocaleString('fr-FR')}</strong>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default StatsPanel;
