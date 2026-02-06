import React, { useEffect, useMemo, useState } from 'react';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

async function requestJson(url, options = {}) {
    const response = await fetch(url, options);
    let payload = null;
    try {
        payload = await response.json();
    } catch (error) {
        payload = null;
    }
    if (!response.ok) {
        const message = payload?.message || 'Une erreur est survenue';
        const error = new Error(message);
        error.status = response.status;
        throw error;
    }
    return payload;
}

function SignalementValidationApp() {
    const [signalements, setSignalements] = useState([]);
    const [selectedId, setSelectedId] = useState(null);
    const [validationNote, setValidationNote] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [error, setError] = useState('');

    const pendingSignalements = useMemo(
        () => signalements.filter((item) => item.statut === 'nouveau'),
        [signalements]
    );

    const selectedSignalement = useMemo(
        () => pendingSignalements.find((item) => item.id_signalement === selectedId) || null,
        [pendingSignalements, selectedId]
    );

    const loadSignalements = async () => {
        setIsLoading(true);
        setError('');
        try {
            const data = await requestJson('/api/signalements');
            setSignalements(Array.isArray(data) ? data : []);
            if (!selectedId && data?.length) {
                const firstPending = data.find((item) => item.statut === 'nouveau');
                setSelectedId(firstPending?.id_signalement || null);
            }
        } catch (err) {
            setError(err.message);
        } finally {
            setIsLoading(false);
        }
    };

    useEffect(() => {
        loadSignalements();
    }, []);

    const handleValidate = async () => {
        if (!selectedSignalement) return;
        setIsSubmitting(true);
        setError('');
        try {
            await requestJson(`/api/signalements/${selectedSignalement.id_signalement}/validate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ note: validationNote || null })
            });
            setValidationNote('');
            await loadSignalements();
        } catch (err) {
            setError(err.message);
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="row g-4">
            <div className="col-lg-5">
                <div className="card shadow-sm">
                    <div className="card-header bg-white">
                        <strong>Signalements à valider</strong>
                    </div>
                    <div className="card-body" style={{ maxHeight: 520, overflowY: 'auto' }}>
                        {isLoading && <div className="text-muted">Chargement...</div>}
                        {!isLoading && pendingSignalements.length === 0 && (
                            <div className="text-muted">Aucun signalement en attente.</div>
                        )}
                        {!isLoading && pendingSignalements.length > 0 && (
                            <div className="list-group">
                                {pendingSignalements.map((item) => (
                                    <button
                                        key={item.id_signalement}
                                        type="button"
                                        className={`list-group-item list-group-item-action ${selectedId === item.id_signalement ? 'active' : ''}`}
                                        onClick={() => setSelectedId(item.id_signalement)}
                                    >
                                        <div className="d-flex justify-content-between">
                                            <span>{item.type_signalement || 'Signalement'}</span>
                                            <span className="badge bg-primary">Nouveau</span>
                                        </div>
                                        <small className="text-muted d-block mt-1">
                                            {item.description || 'Aucune description'}
                                        </small>
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>

            <div className="col-lg-7">
                <div className="card shadow-sm">
                    <div className="card-header bg-white">
                        <strong>Détails & validation</strong>
                    </div>
                    <div className="card-body">
                        {error && <div className="alert alert-danger">{error}</div>}
                        {!selectedSignalement && !isLoading && (
                            <div className="text-muted">Sélectionnez un signalement pour voir les détails.</div>
                        )}
                        {selectedSignalement && (
                            <>
                                <div className="mb-3">
                                    <h5 className="mb-1">{selectedSignalement.type_signalement || 'Signalement'}</h5>
                                    <div className="text-muted">
                                        {selectedSignalement.latitude}, {selectedSignalement.longitude}
                                    </div>
                                </div>
                                <div className="mb-3">
                                    <strong>Description</strong>
                                    <p className="mb-0">{selectedSignalement.description || 'Aucune description'}</p>
                                </div>
                                <div className="row mb-3">
                                    <div className="col-md-6">
                                        <strong>Surface</strong>
                                        <div>{selectedSignalement.surface_m2 || '-'} m²</div>
                                    </div>
                                    <div className="col-md-6">
                                        <strong>Budget</strong>
                                        <div>{selectedSignalement.budget || '-'} Ar</div>
                                    </div>
                                </div>
                                <div className="mb-3">
                                    <strong>Entreprise</strong>
                                    <div>{selectedSignalement.entreprise || '-'}</div>
                                </div>
                                <div className="mb-3">
                                    <label className="form-label">Note de validation</label>
                                    <textarea
                                        className="form-control"
                                        rows={3}
                                        value={validationNote}
                                        onChange={(event) => setValidationNote(event.target.value)}
                                        placeholder="Ajouter une note pour l'équipe"
                                    />
                                </div>
                                <button
                                    type="button"
                                    className="btn btn-success"
                                    onClick={handleValidate}
                                    disabled={isSubmitting}
                                >
                                    {isSubmitting ? 'Validation...' : 'Valider le signalement'}
                                </button>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}

export default SignalementValidationApp;
