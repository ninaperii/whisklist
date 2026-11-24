<?php

namespace App\Http\Requests\Admin;

use App\Models\DietaryTag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDietaryTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255', Rule::unique(DietaryTag::class)], 'description' => ['nullable', 'string', 'max:2000']];
    }
}
