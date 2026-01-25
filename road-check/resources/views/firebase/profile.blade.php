@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <h2>Bienvenue {{ $prenom }} {{ $nom }} !</h2>
    <p>Votre token Firebase : <code>{{ $token }}</code></p>
    <a href="{{ route('login.form') }}" class="btn btn-secondary">Se déconnecter</a>
</div>
@endsection
