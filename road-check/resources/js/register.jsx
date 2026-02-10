import React from 'react';
import ReactDOM from 'react-dom/client';

function RegisterApp({ data }) {
    const errors = data.errors || [];
    const success = data.success || null;

    return (
        <div className="register-card">
            <div className="brand-logo">
                <i className="bi bi-car-front-fill"></i>
            </div>
            <h4 className="text-center mb-1 fw-bold">Road Check</h4>
            <p className="text-center text-muted mb-4">Créez votre compte</p>

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

            <form method="POST" action={data.registerAction}>
                <input type="hidden" name="_token" value={data.csrfToken} />
                <div className="form-row mb-3">
                    <div className="input-icon">
                        <i className="bi bi-person"></i>
                        <input
                            type="text"
                            name="prenom"
                            className="form-control"
                            placeholder="Prenom"
                            defaultValue={data.defaultFirstName || ''}
                            required
                        />
                    </div>
                    <div className="input-icon">
                        <i className="bi bi-person-fill"></i>
                        <input
                            type="text"
                            name="nom"
                            className="form-control"
                            placeholder="Nom"
                            defaultValue={data.defaultLastName || ''}
                            required
                        />
                    </div>
                </div>
                <div className="mb-3 input-icon">
                    <i className="bi bi-envelope"></i>
                    <input
                        type="email"
                        name="email"
                        className="form-control"
                        placeholder="Adresse email"
                        defaultValue={data.defaultEmail || ''}
                        required
                    />
                </div>
                <div className="mb-4 input-icon">
                    <i className="bi bi-lock"></i>
                    <input
                        type="password"
                        name="password"
                        className="form-control"
                        placeholder="Mot de passe"
                        required
                    />
                </div>
                <button type="submit" className="btn btn-rc w-100 mb-3">
                    <i className="bi bi-person-plus me-2"></i>S'inscrire
                </button>
            </form>

            <p className="text-center text-muted mb-0">
                Deja un compte ?{' '}
                <a href={data.loginUrl} className="text-decoration-none rc-link">Se connecter</a>
            </p>
        </div>
    );
}

const mount = document.getElementById('register-app');
if (mount) {
    const data = {
        csrfToken: mount.dataset.csrfToken,
        registerAction: mount.dataset.registerAction,
        loginUrl: mount.dataset.loginUrl,
        defaultFirstName: mount.dataset.defaultFirstName,
        defaultLastName: mount.dataset.defaultLastName,
        defaultEmail: mount.dataset.defaultEmail,
        success: mount.dataset.success ? JSON.parse(mount.dataset.success) : null,
        errors: mount.dataset.errors ? JSON.parse(mount.dataset.errors) : []
    };
    ReactDOM.createRoot(mount).render(<RegisterApp data={data} />);
}
