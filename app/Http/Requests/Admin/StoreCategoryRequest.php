<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', Rule::exists(Category::class, 'id')],
            'name' => ['required', 'string', 'max:255', Rule::unique(Category::class)],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
