<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreIngredientRequest;
use App\Http\Requests\Admin\UpdateIngredientRequest;
use App\Models\Ingredient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class IngredientController extends Controller
{
    public function index(): View
    {
        return view('admin.ingredients.index', [
            'ingredients' => Ingredient::query()->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.ingredients.create');
    }

    public function store(StoreIngredientRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['slug'] = Str::slug($validated['name']);
        Ingredient::create($validated);

        return redirect()->route('admin.ingredients.index')->with('status', 'Ingredient created.');
    }

    public function edit(Ingredient $ingredient): View
    {
        return view('admin.ingredients.edit', compact('ingredient'));
    }

    public function update(UpdateIngredientRequest $request, Ingredient $ingredient): RedirectResponse
    {
        $validated = $request->validated();
        $validated['slug'] = Str::slug($validated['name']);
        $ingredient->update($validated);

        return redirect()->route('admin.ingredients.index')->with('status', 'Ingredient updated.');
    }

    public function destroy(Ingredient $ingredient): RedirectResponse
    {
        $ingredient->delete();

        return redirect()->route('admin.ingredients.index')->with('status', 'Ingredient deleted.');
    }
}
