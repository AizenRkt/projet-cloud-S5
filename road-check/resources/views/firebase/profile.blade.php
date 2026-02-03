@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <h2>Bienvenue {{ $prenom }} {{ $nom }} !</h2>
    <p><strong>Rôle :</strong> {{ $role }}</p>
    <p>Votre token Firebase : <code>{{ $token }}</code></p>
    <a href="{{ route('profile.edit') }}" class="btn btn-primary">Modifier mon profil</a>
    <a href="{{ route('login.form') }}" class="btn btn-secondary ms-2">Se déconnecter</a>
</div>
@endsection
