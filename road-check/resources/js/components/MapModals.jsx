import React from 'react';
import StatsPanel from '../stats.jsx';

function MapModals({
    onCloseUsers,
    onOpenCreateUser,
    onClosePhoto,
    onPrevPhoto,
    onNextPhoto,
    onCloseSync,
    onCloseStats,
    onCloseCreateUser,
    onCloseEditUser,
    onCloseConfirm,
    onCreateUser,
    onUpdateUser,
    onSyncBidirectional,
    onSyncUsersToFirebaseAuth,
    onLoadSyncStatus
}) {
    return (
        <>
            <div className="modal-overlay" id="usersModal">
                <div className="modal">
                    <div className="modal-header"><h3> Utilisateurs</h3><button className="close-btn" type="button" onClick={onCloseUsers}>&times;</button></div>
                    <div className="modal-body" id="usersModalBody"></div>
                    <div className="modal-footer"><button className="nav-btn" type="button" onClick={onOpenCreateUser}> Nouvel utilisateur</button></div>
                </div>
            </div>
            <div className="modal-overlay" id="photoModal">
                <div className="modal" style={{ maxWidth: '900px' }}>
                    <div className="modal-header"><h3>Photo</h3><button className="close-btn" type="button" onClick={onClosePhoto}>&times;</button></div>
                    <div className="modal-body" id="photoModalBody" style={{ display: 'flex', justifyContent: 'center', alignItems: 'center' }}>
                        <img id="photoModalImage" alt="Photo signalement" style={{ width: '100%', height: 'auto', maxHeight: '70vh', objectFit: 'contain', background: '#0d1117' }} />
                        <div className="photo-modal-nav">
                            <button id="photoModalPrev" className="photo-modal-btn" type="button" onClick={onPrevPhoto}>&lt;</button>
                            <span id="photoModalCounter" className="photo-modal-counter">0 / 0</span>
                            <button id="photoModalNext" className="photo-modal-btn" type="button" onClick={onNextPhoto}>&gt;</button>
                        </div>
                    </div>
                </div>
            </div>
            <div className="modal-overlay" id="syncModal">
                <div className="modal" style={{ maxWidth: '600px' }}>
                    <div className="modal-header"><h3>Synchronisation Firebase</h3><button className="close-btn" type="button" onClick={onCloseSync}>&times;</button></div>
                    <div className="modal-body">
                        <div id="syncStatus" style={{ marginBottom: '20px', padding: '15px', background: '#21262d', borderRadius: '8px' }}>
                            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '10px' }}>
                                <span>Statut de synchronisation</span>
                                <button className="action-btn" type="button" onClick={onLoadSyncStatus}>Actualiser</button>
                            </div>
                            <div id="syncStatusContent">Chargement...</div>
                        </div>
                        <div style={{ display: 'flex', gap: '12px', flexDirection: 'column' }}>
                            <div style={{ padding: '15px', background: '#21262d', borderRadius: '8px', border: '1px solid #58a6ff' }}>
                                <h4 style={{ color: '#58a6ff', marginBottom: '10px' }}>Synchronisation bidirectionnelle</h4>
                                <p style={{ fontSize: '0.85rem', color: '#8b949e', marginBottom: '8px' }}>PostgreSQL → Firestore puis Firestore → PostgreSQL</p>
                                <p style={{ fontSize: '0.8rem', color: '#8b949e', marginBottom: '12px' }}>Ordre: entreprises → types_signalement → utilisateurs → signalements → tentatives_connexion</p>
                                <button className="btn-save" style={{ background: '#238636', fontSize: '1rem', padding: '12px 24px', width: '100%' }} type="button" onClick={onSyncBidirectional}>Synchroniser (PostgreSQL ↔ Firestore)</button>
                            </div>
                            <div style={{ padding: '15px', background: '#21262d', borderRadius: '8px' }}>
                                <h4 style={{ color: '#1f6feb', marginBottom: '10px' }}>Utilisateurs → Firebase Auth</h4>
                                <p style={{ fontSize: '0.85rem', color: '#8b949e', marginBottom: '12px' }}>Creer les comptes email/password dans Firebase Authentication</p>
                                <button className="btn-save" style={{ background: '#1f6feb' }} type="button" onClick={onSyncUsersToFirebaseAuth}>Synchroniser les utilisateurs vers Firebase Auth</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div className="modal-overlay" id="statsModal">
                <div className="modal" style={{ maxWidth: '760px' }}>
                    <div className="modal-header"><h3>Statistiques</h3><button className="close-btn" type="button" onClick={onCloseStats}>&times;</button></div>
                    <div className="modal-body">
                        <StatsPanel />
                    </div>
                </div>
            </div>
            <div className="modal-overlay" id="createUserModal">
                <div className="modal" style={{ maxWidth: '500px' }}>
                    <div className="modal-header"><h3> Creer utilisateur</h3><button className="close-btn" type="button" onClick={onCloseCreateUser}>&times;</button></div>
                    <div className="modal-body">
                        <form id="createUserForm" onSubmit={onCreateUser}>
                            <div className="form-group"><label>Email</label><input type="email" id="newUserEmail" required /></div>
                            <div className="form-row">
                                <div className="form-group"><label>Nom</label><input type="text" id="newUserNom" required /></div>
                                <div className="form-group"><label>Prenom</label><input type="text" id="newUserPrenom" required /></div>
                            </div>
                            <div className="form-group"><label>Mot de passe</label><input type="password" id="newUserPassword" required /></div>
                            <div className="form-group"><label>Role</label><select id="newUserRole"></select></div>
                            <button type="submit" className="btn-save">Creer</button>
                        </form>
                    </div>
                </div>
            </div>
            <div className="modal-overlay" id="editUserModal">
                <div className="modal" style={{ maxWidth: '520px' }}>
                    <div className="modal-header"><h3> Modifier utilisateur</h3><button className="close-btn" type="button" onClick={onCloseEditUser}>&times;</button></div>
                    <div className="modal-body">
                        <form id="editUserForm" onSubmit={onUpdateUser}>
                            <input type="hidden" id="editUserId" />
                            <div className="form-group"><label>Email</label><input type="email" id="editUserEmail" disabled /></div>
                            <div className="form-row">
                                <div className="form-group"><label>Nom</label><input type="text" id="editUserNom" required /></div>
                                <div className="form-group"><label>Prenom</label><input type="text" id="editUserPrenom" required /></div>
                            </div>
                            <div className="form-group"><label>Role</label><select id="editUserRole"></select></div>
                            <div className="form-group" style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                                <input type="checkbox" id="editUserBloque" />
                                <label htmlFor="editUserBloque" style={{ margin: 0 }}>Bloque</label>
                            </div>
                            <button type="submit" className="btn-save">Enregistrer</button>
                        </form>
                    </div>
                </div>
            </div>

            <div className="toast-container" id="toastContainer"></div>

            <div className="loading-overlay" id="loadingOverlay">
                <div className="spinner"></div>
                <div className="loading-text" id="loadingText">Chargement...</div>
            </div>

            <div className="confirm-modal" id="confirmModal">
                <div className="confirm-box">
                    <div className="confirm-icon" id="confirmIcon">⚠️</div>
                    <div className="confirm-title" id="confirmTitle">Etes-vous sur ?</div>
                    <div className="confirm-buttons">
                        <button className="confirm-btn no" type="button" onClick={() => onCloseConfirm(false)}>Annuler</button>
                        <button className="confirm-btn yes" type="button" onClick={() => onCloseConfirm(true)}>Confirmer</button>
                    </div>
                </div>
            </div>
        </>
    );
}

export default MapModals;
