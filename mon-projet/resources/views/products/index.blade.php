@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="mb-4">Liste des produits ETUU003295</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="mb-3 d-flex justify-content-between">
        <form method="GET" class="d-flex">
            <select name="category_id" class="form-select me-2">
                <option value="">-- Toutes les catégories --</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>

            <input type="text" name="search" class="form-control me-2" placeholder="Recherche..." value="{{ request('search') }}">

            <select name="sort" class="form-select me-2">
                <option value="">-- Trier par prix --</option>
                <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Prix croissant</option>
                <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Prix décroissant</option>
            </select>

            <button type="submit" class="btn btn-primary">Filtrer</button>
        </form>

        <a href="{{ route('products.create') }}" class="btn btn-success">Créer un produit</a>
    </div>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Nom</th>
                <th>Prix</th>
                <th>Catégorie</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
            <tr>
                <td>{{ $product->name }}</td>
                <td>{{ $product->price }} $</td>
                <td>{{ $product->category->name }}</td>
                <td>
                    <a href="{{ route('products.edit', $product->id) }}" class="btn btn-primary btn-sm">Edit</a>
                    <form action="{{ route('products.destroy', $product->id) }}" method="POST" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer ce produit ?')">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4">Aucun produit trouvé.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{ $products->links('pagination::bootstrap-5') }}

    <div class="mt-3">
        @foreach($categories as $category)
            <span class="badge bg-info text-dark me-2">
                {{ $category->name }} : {{ $category->products()->count() }}
            </span>
        @endforeach
    </div>
</div>
@endsection
