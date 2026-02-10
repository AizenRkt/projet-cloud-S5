import React from 'react';

function Sidebar({ onFilter, onSearch, onDateChange }) {
    return (
        <aside className="sidebar" id="sidebar">
            <div className="sidebar-header">
                <div className="sidebar-title"> Signalements</div>
                <div className="filter-tabs">
                    <button className="filter-tab active" type="button" onClick={(e) => onFilter('all', e.currentTarget)}>Tous</button>
                    <button className="filter-tab" type="button" onClick={(e) => onFilter('nouveau', e.currentTarget)}> Nouveau</button>
                    <button className="filter-tab" type="button" onClick={(e) => onFilter('en_attente', e.currentTarget)}> En attente</button>
                    <button className="filter-tab" type="button" onClick={(e) => onFilter('en_cours', e.currentTarget)}> En cours</button>
                    <button className="filter-tab" type="button" onClick={(e) => onFilter('termine', e.currentTarget)}> Termine</button>
                    <button className="filter-tab" type="button" onClick={(e) => onFilter('annule', e.currentTarget)}> Annule</button>
                    <button className="filter-tab" type="button" onClick={(e) => onFilter('annule', e.currentTarget)}> Annule</button>
                </div>
            </div>
            <div className="search-container">
                <input
                    type="text"
                    id="searchInput"
                    className="search-input"
                    placeholder="Rechercher un signalement..."
                    onInput={(e) => onSearch(e.target.value)}
                />
                <div className="date-filters">
                    <input
                        type="date"
                        id="dateStart"
                        className="search-input"
                        onChange={onDateChange}
                        placeholder="Du"
                    />
                    <input
                        type="date"
                        id="dateEnd"
                        className="search-input"
                        onChange={onDateChange}
                        placeholder="Au"
                    />
                </div>
            </div>
            <div className="sidebar-content" id="signalementsList">
                <div className="loading">Chargement...</div>
            </div>
        </aside>
    );
}

export default Sidebar;
