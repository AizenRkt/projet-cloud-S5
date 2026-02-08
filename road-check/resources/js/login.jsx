import React from 'react';
import ReactDOM from 'react-dom/client';

function LoginApp({ data }) {
    const errors = data.errors || [];
    const success = data.success || null;
    const firestoreStatus = data.firestoreStatus || null;

    return (
        <div className="login-card">
            {firestoreStatus && (
                <div className="alert alert-info mb-3">
                    <i className="bi bi-cloud-check me-2"></i>
                    Firestore (Kreait) : {firestoreStatus}
                </div>
            )}
            <div className="brand-logo">
                <i className="bi bi-car-front-fill"></i>
            </div>
            <h4 className="text-center mb-1 fw-bold">Road Check</h4>
            <p className="text-center text-muted mb-4">Connectez-vous a votre compte</p>

            {errors.length > 0 && (
                <div className="alert alert-danger mb-3">
                    <i className="bi bi-exclamation-circle me-2"></i>
                    {errors.map((error, index) => (
                        <div key={index}>{error}</div>
                    ))}
                </div>
            )}

            {success && (
                <div className="alert alert-success mb-3">
                    <i className="bi bi-check-circle me-2"></i>
                    {success}
                </div>
            )}

            <form method="POST" action={data.loginAction}>
                <input type="hidden" name="_token" value={data.csrfToken} />
                <div className="mb-3 input-icon">
                    <i className="bi bi-envelope"></i>
                    <input
                        type="email"
                        name="email"
                        className="form-control"
                        defaultValue={data.defaultEmail || ''}
                        placeholder="Adresse email"
                        required
                    />
                </div>
                <div className="mb-4 input-icon">
                    <i className="bi bi-lock"></i>
                    <input
                        type="password"
                        name="password"
                        className="form-control"
                        defaultValue={data.defaultPassword || ''}
                        placeholder="Mot de passe"
                        required
                    />
                </div>
                <button type="submit" className="btn btn-rc w-100 mb-3">
                    <i className="bi bi-box-arrow-in-right me-2"></i>Se connecter
                </button>
            </form>

            <p className="text-center text-muted mb-0">
                Pas encore de compte ?{' '}
                <a href={data.registerUrl} className="text-decoration-none" style={{ color: 'var(--rc-primary)' }}>S'inscrire</a>
            </p>
            <p className="text-center mt-2">
                <a href={data.docsUrl} className="text-decoration-none" style={{ color: 'var(--rc-primary)' }} target="_blank" rel="noreferrer">
                    <i className="bi bi-file-earmark-text me-1"></i>Documentation API
                </a>
            </p>
        </div>
    );
}

const mount = document.getElementById('login-app');
if (mount) {
    const data = {
        csrfToken: mount.dataset.csrfToken,
        loginAction: mount.dataset.loginAction,
        registerUrl: mount.dataset.registerUrl,
        docsUrl: mount.dataset.docsUrl,
        defaultEmail: mount.dataset.defaultEmail,
        defaultPassword: mount.dataset.defaultPassword,
        success: mount.dataset.success ? JSON.parse(mount.dataset.success) : null,
        firestoreStatus: mount.dataset.firestoreStatus ? JSON.parse(mount.dataset.firestoreStatus) : null,
        errors: mount.dataset.errors ? JSON.parse(mount.dataset.errors) : []
    };
    ReactDOM.createRoot(mount).render(<LoginApp data={data} />);
}
