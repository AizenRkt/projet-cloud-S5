@extends('layouts.app')
@section('title', 'Catégories')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Catégories</h1>
    <a href="{{ route('categories.create') }}" class="btn btn-primary">Nouvelle catégorie</a>
</div>

<!-- Recherche -->
<form method="GET" action="{{ route('categories.index') }}" class="mb-3">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher..." class="form-control w-25 d-inline">
    <button type="submit" class="btn btn-secondary">Rechercher</button>
</form>

<!-- Messages flash -->
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<table class="table table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Slug</th>
            <th>Description</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($categories as $category)
        <tr>
            <td>{{ $category->id }}</td>
            <td>{{ $category->name }}</td>
            <td>{{ $category->slug }}</td> <!-- Affichage slug -->
            <td>{{ \Illuminate\Support\Str::limit($category->description, 50) }}</td>
            <td>
                <a href="{{ route('categories.show', $category) }}" class="btn btn-sm btn-info">Voir</a>
                <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-warning">Modifier</a>
                <form action="{{ route('categories.destroy', $category) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr ?')">Supprimer</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="5">Aucune catégorie trouvée.</td></tr>
        @endforelse
    </tbody>
</table>


{{ $categories->withQueryString()->links() }}
@endsection
