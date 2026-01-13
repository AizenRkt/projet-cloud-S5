@extends('layouts.app')

@section('title', 'Détails de la catégorie')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Détails : {{ $category->name ?? 'Catégorie' }}</h1>
    <a href="{{ route('categories.index') }}" class="btn btn-secondary">Retour à la liste</a>
</div>

<!-- Messages flash -->
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-body">
        <h5 class="card-title">{{ $category->name }}</h5>
        <p class="card-text"><strong>Slug :</strong> {{ $category->slug }}</p>
        <p class="card-text">{{ $category->description }}</p>

        <a href="{{ route('categories.edit', $category) }}" class="btn btn-warning">Modifier</a>
        <form action="{{ route('categories.destroy', $category) }}" method="POST" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr ?')">Supprimer</button>
        </form>
    </div>

</div>
@endsection
