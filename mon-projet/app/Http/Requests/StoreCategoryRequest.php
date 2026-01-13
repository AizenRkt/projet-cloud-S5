<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Générer le slug automatiquement avant validation
    protected function prepareForValidation()
    {
        if ($this->name) {
            $this->merge([
                'slug' => Str::slug($this->name)
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:50|unique:categories,name',
            'description' => 'nullable|string|max:255',
            'slug' => 'required|unique:categories,slug', // on valide aussi le slug
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la catégorie est obligatoire.',
            'name.min' => 'Le nom doit contenir au moins 3 caractères.',
            'name.max' => 'Le nom ne doit pas dépasser 50 caractères.',
            'name.unique' => 'Cette catégorie existe déjà.',
            'description.max' => 'La description ne doit pas dépasser 255 caractères.',
            'slug.required' => 'Le slug est requis.',
            'slug.unique' => 'Cette catégorie existe déjà (slug).',
        ];
    }
}
