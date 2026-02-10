import React from 'react';

function MapNavbar({ onOpenUsers, onOpenStats, onOpenSync, onOpenPrice, onLogout }) {
    return (
        <nav className="navbar">
            <div className="navbar-brand">
                <span className="logo"></span>
                <span className="title">Road Check</span>
                <span className="subtitle">| Manager nety</span>
            </div>
            <div className="navbar-menu">
                <button className="nav-btn" type="button" onClick={onOpenUsers}>Utilisateurs</button>
                <button className="nav-btn" type="button" onClick={onOpenStats}>Statistiques</button>
                <button className="nav-btn" type="button" onClick={onOpenSync}>Synchronisation</button>
                <button className="nav-btn" type="button" onClick={onOpenPrice}>Prix m²</button>
                <button className="nav-btn" type="button" onClick={onLogout}>Deconnexion</button>
            </div>
        </nav>
    );
}

export default MapNavbar;
