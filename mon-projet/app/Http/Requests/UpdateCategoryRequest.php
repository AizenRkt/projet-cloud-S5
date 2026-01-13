<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:50',
                Rule::unique('categories', 'name')->ignore($this->category),
            ],
            'description' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'name.min' => 'Minimum 3 caractères.',
            'name.max' => 'Maximum 50 caractères.',
            'name.unique' => 'Ce nom est déjà utilisé.',
            'description.max' => 'Maximum 255 caractères.',
        ];
    }
}
