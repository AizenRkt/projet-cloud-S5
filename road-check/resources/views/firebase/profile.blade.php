@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h3 class="mb-2">Bienvenue {{ $prenom }} {{ $nom }} !</h3>
                    <p class="text-muted mb-4">Heureux de vous revoir.</p>

                    <div class="mb-3">
                        <span class="text-muted">Rôle</span>
                        <div class="mt-1"><span class="badge bg-primary">{{ $role }}</span></div>
                    </div>

                    <div class="mb-3">
                        <span class="text-muted">Token Firebase</span>
                        <pre class="bg-light rounded p-3 mb-0" style="white-space: nowrap; overflow-x: auto;">{{ $token }}</pre>
                    </div>

                    <div class="d-grid gap-2 d-sm-flex mt-4">
                        <a href="{{ route('profile.edit') }}" class="btn btn-primary">Modifier mon profil</a>
                        <a href="{{ route('login.form') }}" class="btn btn-outline-secondary">Se déconnecter</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
