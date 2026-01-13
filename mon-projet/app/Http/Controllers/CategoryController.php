<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;

class CategoryController extends Controller
{
    // Liste des catégories avec pagination et recherche
    public function index(Request $request)
    {
        $query = Category::query();

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('description', 'like', '%' . $request->search . '%')
                ->orWhere('slug', 'like', '%' . $request->search . '%');
            });
        }

        $categories = $query->orderBy('id', 'asc')->paginate(10)->withQueryString();

        return view('categories.index', compact('categories'));
    }

    // Formulaire création
    public function create()
    {
        return view('categories.create');
    }

    // Stocker nouvelle catégorie
    public function store(StoreCategoryRequest $request)
    {
        Category::create($request->validated());
        return redirect()->route('categories.index')
            ->with('success', 'Catégorie créée avec succès.');
    }

    // Afficher une catégorie
    public function show(Category $category)
    {
        return view('categories.show', compact('category'));
    }

    // Formulaire édition
    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    // Mise à jour
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());
        return redirect()->route('categories.index')
            ->with('success', 'Catégorie mise à jour avec succès.');
    }

    // Supprimer
    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->route('categories.index')
            ->with('success', 'Catégorie supprimée avec succès.');
    }
}
