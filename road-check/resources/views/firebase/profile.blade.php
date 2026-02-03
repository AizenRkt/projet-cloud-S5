@extends('layouts.app')

<style>
    :root {
        --rc-primary: #0d9488;
        --rc-primary-dark: #0f766e;
        --rc-light: #f0fdfa;
    }
    .profile-container {
        max-width: 600px;
        margin: 0 auto;
    }
    .profile-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    .profile-header {
        background: linear-gradient(135deg, var(--rc-primary) 0%, var(--rc-primary-dark) 100%);
        padding: 2rem;
        text-align: center;
        color: #fff;
    }
    .profile-avatar {
        width: 80px;
        height: 80px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        border: 3px solid rgba(255,255,255,0.3);
    }
    .profile-avatar i {
        font-size: 36px;
    }
    .profile-body {
        padding: 2rem;
    }
    .info-item {
        display: flex;
        align-items: center;
        padding: 1rem;
        background: #f8fafc;
        border-radius: 10px;
        margin-bottom: 0.75rem;
    }
    .info-item i {
        font-size: 20px;
        color: var(--rc-primary);
        margin-right: 1rem;
        width: 24px;
    }
    .info-label {
        font-size: 0.75rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .info-value {
        font-weight: 500;
        color: #1e293b;
    }
    .token-box {
        background: #f1f5f9;
        border-radius: 8px;
        padding: 0.75rem 1rem;
        font-family: monospace;
        font-size: 0.8rem;
        color: #475569;
        word-break: break-all;
        max-height: 80px;
        overflow-y: auto;
    }
    .btn-rc {
        background: var(--rc-primary);
        border: none;
        color: #fff;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 500;
        transition: background 0.2s;
    }
    .btn-rc:hover {
        background: var(--rc-primary-dark);
        color: #fff;
    }
    .btn-outline-rc {
        background: transparent;
        border: 2px solid #e2e8f0;
        color: #64748b;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.2s;
    }
    .btn-outline-rc:hover {
        border-color: #dc2626;
        color: #dc2626;
        background: #fef2f2;
    }
</style>

<div class="container mt-5 profile-container">
    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="bi bi-person-fill"></i>
            </div>
            <h4 class="mb-1">{{ $prenom }} {{ $nom }}</h4>
            <span class="badge" style="background: rgba(255,255,255,0.2); font-weight: 500;">
                <i class="bi bi-shield-check me-1"></i>{{ $role }}
            </span>
        </div>
        <div class="profile-body">
            <div class="info-item">
                <i class="bi bi-person"></i>
                <div>
                    <div class="info-label">Nom complet</div>
                    <div class="info-value">{{ $prenom }} {{ $nom }}</div>
                </div>
            </div>
            <div class="info-item">
                <i class="bi bi-award"></i>
                <div>
                    <div class="info-label">Rôle</div>
                    <div class="info-value">{{ $role }}</div>
                </div>
            </div>
            <div class="mb-4">
                <label class="info-label mb-2 d-block">
                    <i class="bi bi-key me-1" style="color: var(--rc-primary);"></i>Token Firebase
                </label>
                <div class="token-box">{{ $token }}</div>
            </div>
                        <div class="d-flex gap-2">
                                <a href="{{ route('profile.edit') }}" class="btn btn-rc flex-fill">
                                        <i class="bi bi-pencil-square me-2"></i>Modifier
                                </a>
                                <a href="{{ route('login.form') }}" class="btn btn-outline-rc">
                                        <i class="bi bi-box-arrow-right me-1"></i>Déconnexion
                                </a>
                                @if(strtolower($role) === 'administrateur')
                                        <button type="button" class="btn btn-outline-rc" data-bs-toggle="modal" data-bs-target="#unblockModal">
                                                <i class="bi bi-unlock me-1"></i>Débloquer
                                        </button>
                                @endif
                        </div>

                        @if(strtolower($role) === 'administrateur')
                                <!-- Modal -->
                                <div class="modal fade" id="unblockModal" tabindex="-1" aria-labelledby="unblockModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('unblock.submit') }}">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="unblockModalLabel"><i class="bi bi-unlock me-1"></i>Débloquer un utilisateur</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label for="unblock-email" class="form-label">Email de l'utilisateur à débloquer</label>
                                                        <input type="email" class="form-control" id="unblock-email" name="email" required placeholder="ex: user@email.com">
                                                    </div>
                                                    @if(session('success'))
                                                        <div class="alert alert-success">{{ session('success') }}</div>
                                                    @endif
                                                    @if($errors->any())
                                                        <div class="alert alert-danger">
                                                            @foreach($errors->all() as $error)
                                                                <div>{{ $error }}</div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <button type="submit" class="btn btn-rc">Débloquer</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                        @endif
        </div>
    </div>
</div>
@section('content')
<div class="container mt-5">
    <h2>Bienvenue {{ $prenom }} {{ $nom }} !</h2>
    <p><strong>Rôle :</strong> {{ $role }}</p>
    <p>Votre token Firebase : <code>{{ $token }}</code></p>
    <a href="{{ route('profile.edit') }}" class="btn btn-primary">Modifier mon profil</a>
    <a href="{{ route('login.form') }}" class="btn btn-secondary ms-2">Se déconnecter</a>
</div>
@endsection
