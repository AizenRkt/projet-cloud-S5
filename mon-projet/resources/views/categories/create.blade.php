@extends('layouts.app')

@section('title', 'Nouvelle catégorie')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Ajouter une nouvelle catégorie</h1>
    <a href="{{ route('categories.index') }}" class="btn btn-secondary">Retour à la liste</a>
</div>

<!-- Messages flash -->
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<form action="{{ route('categories.store') }}" method="POST">
    @csrf

    <div class="mb-3">
        <label for="name" class="form-label">Nom</label>
        <input type="text" name="name" id="name" value="{{ old('name') }}" class="form-control">
        @error('name')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="mb-3">
        <label for="description" class="form-label">Description</label>
        <textarea name="description" id="description" class="form-control" rows="4">{{ old('description') }}</textarea>
        @error('description')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="mb-3">
        <label for="slug" class="form-label">Slug</label>
        <input type="text" name="slug" id="slug" value="{{ old('slug') }}" class="form-control" placeholder="Optionnel, généré automatiquement si laissé vide">
        @error('slug')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>


    <button type="submit" class="btn btn-success">Créer</button>
</form>
@endsection
