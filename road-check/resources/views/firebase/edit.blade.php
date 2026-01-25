@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <h2>Modifier mon profil</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}">
        @csrf
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $utilisateur->email) }}" required>
        </div>
        <div class="mb-3">
            <label>Nom</label>
            <input type="text" name="nom" class="form-control" value="{{ old('nom', $utilisateur->nom) }}" required>
        </div>
        <div class="mb-3">
            <label>Prénom</label>
            <input type="text" name="prenom" class="form-control" value="{{ old('prenom', $utilisateur->prenom) }}" required>
        </div>
        <div class="mb-3">
            <label>Rôle</label>
            <select name="id_role" class="form-control" required>
                @foreach($roles as $role)
                    <option value="{{ $role->id_role }}" @if(old('id_role', $utilisateur->id_role) == $role->id_role) selected @endif>{{ $role->nom }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="{{ route('profile') }}" class="btn btn-secondary">Annuler</a>
    </form>
</div>
@endsection
