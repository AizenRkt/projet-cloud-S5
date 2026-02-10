import React from 'react';

function MapNavbar({ docsUrl, onOpenUsers, onOpenStats, onOpenSync, onLogout }) {
    return (
        <nav className="navbar">
            <div className="navbar-brand">
                <span className="logo"></span>
                <span className="title">Road Check</span>
                <span className="subtitle">| Manager nety</span>
            </div>
            <div className="navbar-menu">
                {docsUrl ? (
                    <a href={docsUrl} className="text-decoration-none rc-link nav-btn" target="_blank" rel="noreferrer">
                        <i className="bi bi-file-earmark-text me-1"></i>Documentation API
                    </a>
                ) : null}
                <button className="nav-btn" type="button" onClick={onOpenUsers}>Utilisateurs</button>
                <button className="nav-btn" type="button" onClick={onOpenStats}>Statistiques</button>
                <button className="nav-btn" type="button" onClick={onOpenSync}>Synchronisation</button>
                <button className="nav-btn" type="button" onClick={onLogout}>Deconnexion</button>
            </div>
        </nav>
    );
}

export default MapNavbar;
