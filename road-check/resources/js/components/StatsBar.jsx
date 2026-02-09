import React from 'react';

function StatsBar() {
    return (
        <div className="stats-bar">
            <div className="stat-item"><div className="stat-value nouveau" id="statNouveau">0</div><div className="stat-label">Nouveaux</div></div>
            <div className="stat-item"><div className="stat-value en_attente" id="statEnAttente">0</div><div className="stat-label">En attente</div></div>
            <div className="stat-item"><div className="stat-value en_cours" id="statEnCours">0</div><div className="stat-label">En cours</div></div>
            <div className="stat-item"><div className="stat-value termine" id="statTermine">0</div><div className="stat-label">Termines</div></div>
            <div className="stat-item"><div className="stat-value annule" id="statAnnule">0</div><div className="stat-label">Annules</div></div>
            <div className="stat-item"><div className="stat-value" id="statTotal">0</div><div className="stat-label">Total</div></div>
            <div className="stat-item" style={{ minWidth: '120px' }}>
                <div className="stat-value" id="statGlobalProgress" style={{ color: '#58a6ff' }}>0%</div>
                <div className="stat-label">Avancement Global</div>
                <div style={{ height: '4px', background: '#30363d', borderRadius: '2px', overflow: 'hidden', marginTop: '4px', width: '100%' }}>
                    <div id="statGlobalProgressBar" style={{ width: '0%', height: '100%', background: '#58a6ff' }}></div>
                </div>
            </div>
        </div>
    );
}

export default StatsBar;
