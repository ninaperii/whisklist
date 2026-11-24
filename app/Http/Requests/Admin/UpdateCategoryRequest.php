<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', Rule::exists(Category::class, 'id')],
            'name' => ['required', 'string', 'max:255', Rule::unique(Category::class)->ignore($this->route('category'))],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Prevent a category hierarchy from containing cycles at any depth.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('parent_id') || ! $this->filled('parent_id')) {
                return;
            }

            $category = $this->route('category');
            $candidate = Category::query()->find($this->integer('parent_id'));

            while ($candidate !== null) {
                if ($category instanceof Category && $candidate->is($category)) {
                    $validator->errors()->add('parent_id', 'A category cannot be placed inside one of its own subcategories.');

                    return;
                }

                $candidate = $candidate->parent;
            }
        }];
    }
}
