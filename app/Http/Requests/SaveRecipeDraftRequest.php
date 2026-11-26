<?php

namespace App\Http\Requests;

use App\Enums\RecipeDifficulty;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveRecipeDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        $recipe = $this->route('recipe');

        return $recipe === null || $this->user()->can('update', $recipe);
    }

    public function rules(): array
    {
        return [
            'action' => ['sometimes', Rule::in(['draft', 'publish'])],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'title' => ['required', 'string', 'max:200'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'preparation_minutes' => ['required', 'integer', 'between:0,65535'],
            'cooking_minutes' => ['required', 'integer', 'between:0,65535'],
            'servings' => ['required', 'integer', 'between:1,65535'],
            'difficulty' => ['required', Rule::enum(RecipeDifficulty::class)],
            'categories' => ['sometimes', 'array', 'max:50'],
            'categories.*' => ['required', 'integer', 'distinct', 'exists:categories,id'],
            'dietary_tags' => ['sometimes', 'array', 'max:50'],
            'dietary_tags.*' => ['required', 'integer', 'distinct', 'exists:dietary_tags,id'],
            'ingredients' => ['sometimes', 'array', 'max:100'],
            'ingredients.*' => ['required', 'array:id,new_name,quantity,unit,notes,is_main'],
            'ingredients.*.id' => ['nullable', 'required_without:ingredients.*.new_name', 'prohibits:ingredients.*.new_name', 'integer', 'distinct', 'exists:ingredients,id'],
            'ingredients.*.new_name' => ['nullable', 'required_without:ingredients.*.id', 'string', 'max:80', 'regex:/[\pL\pN]/u'],
            'ingredients.*.quantity' => ['nullable', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999.99'],
            'ingredients.*.unit' => ['nullable', 'string', 'max:50'],
            'ingredients.*.notes' => ['nullable', 'string', 'max:255'],
            'ingredients.*.is_main' => ['sometimes', 'boolean'],
            'steps' => ['sometimes', 'array', 'max:100'],
            'steps.*' => ['required', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'ingredients.*.id' => 'ingredient',
            'ingredients.*.new_name' => 'new ingredient name',
            'ingredients.*.quantity' => 'quantity',
            'ingredients.*.unit' => 'unit',
            'ingredients.*.notes' => 'ingredient notes',
            'ingredients.*.is_main' => 'main ingredient flag',
            'steps.*' => 'step instruction',
        ];
    }
}
