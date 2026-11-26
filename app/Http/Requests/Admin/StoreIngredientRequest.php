<?php

namespace App\Http\Requests\Admin;

use App\Models\Ingredient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIngredientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'allergens' => ['sometimes', 'array', 'max:50'],
            'allergens.*' => ['required', 'integer', 'distinct', 'exists:allergens,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique(Ingredient::class)],
        ];
    }
}
