@extends('layouts.app')

<style>
    :root {
        --rc-primary: #0d9488;
        --rc-primary-dark: #0f766e;
        --rc-light: #f0fdfa;
    }
    .edit-container {
        max-width: 500px;
        margin: 0 auto;
    }
    .edit-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        padding: 2rem;
    }
    .edit-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    .edit-icon {
        width: 60px;
        height: 60px;
        background: var(--rc-light);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
    }
    .edit-icon i {
        font-size: 28px;
        color: var(--rc-primary);
    }
    .form-label {
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.5rem;
    }
    .form-label i {
        color: var(--rc-primary);
        margin-right: 0.5rem;
    }
    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        padding: 0.75rem 1rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--rc-primary);
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
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
    .btn-outline-secondary {
        border: 2px solid #e2e8f0;
        color: #64748b;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 500;
        background: transparent;
    }
    .btn-outline-secondary:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #475569;
    }
    .alert {
        border-radius: 10px;
        border: none;
        padding: 1rem;
    }
    .alert-danger {
        background: #fef2f2;
        color: #dc2626;
    }
    .alert-success {
        background: #f0fdf4;
        color: #16a34a;
    }
</style>

<div class="container mt-5 edit-container">
    <div class="edit-card">
        <div class="edit-header">
            <div class="edit-icon">
                <i class="bi bi-pencil-square"></i>
            </div>
            <h4 class="fw-bold mb-1">Modifier mon profil</h4>
            <p class="text-muted mb-0">Mettez à jour vos informations</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger mb-4">
                <i class="bi bi-exclamation-circle me-2"></i>
                @foreach ($errors->all() as $error)
                    {{ $error }}@if(!$loop->last)<br>@endif
                @endforeach
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success mb-4">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label"><i class="bi bi-envelope"></i>Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $utilisateur->email) }}" required>
            </div>
            <div class="row mb-3">
                <div class="col-6">
                    <label class="form-label"><i class="bi bi-person"></i>Prénom</label>
                    <input type="text" name="prenom" class="form-control" value="{{ old('prenom', $utilisateur->prenom) }}" required>
                </div>
                <div class="col-6">
                    <label class="form-label"><i class="bi bi-person-fill"></i>Nom</label>
                    <input type="text" name="nom" class="form-control" value="{{ old('nom', $utilisateur->nom) }}" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label"><i class="bi bi-shield-check"></i>Rôle</label>
                <select name="id_role" class="form-select" required>
                    @foreach($roles as $role)
                        <option value="{{ $role->id_role }}" @if(old('id_role', $utilisateur->id_role) == $role->id_role) selected @endif>{{ $role->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-rc flex-fill">
                    <i class="bi bi-check-lg me-2"></i>Enregistrer
                </button>
                <a href="{{ route('profile') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i>Annuler
                </a>
            </div>
        </form>
    </div>
</div>
