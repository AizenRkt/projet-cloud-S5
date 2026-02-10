import React from 'react';

function DetailPanel({ onClose }) {
    return (
        <div className="detail-panel" id="detailPanel">
            <div className="detail-header"><h3> Modifier</h3><button className="close-btn" type="button" onClick={onClose}>&times;</button></div>
            <div className="detail-content" id="detailContent"></div>
        </div>
    );
}

export default DetailPanel;
