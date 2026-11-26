<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreIngredientRequest;
use App\Http\Requests\Admin\UpdateIngredientRequest;
use App\Models\Allergen;
use App\Models\Ingredient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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
        return view('admin.ingredients.create', [
            'allergenOptions' => Allergen::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(StoreIngredientRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        DB::transaction(function () use ($validated): void {
            $ingredient = Ingredient::create(['name' => $validated['name'], 'slug' => Str::slug($validated['name'])]);
            $ingredient->allergens()->sync($validated['allergens'] ?? []);
        });

        return redirect()->route('admin.ingredients.index')->with('status', 'Ingredient created.');
    }

    public function edit(Ingredient $ingredient): View
    {
        return view('admin.ingredients.edit', [
            'ingredient' => $ingredient,
            'allergenOptions' => Allergen::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(UpdateIngredientRequest $request, Ingredient $ingredient): RedirectResponse
    {
        $validated = $request->validated();
        DB::transaction(function () use ($ingredient, $validated): void {
            $ingredient = Ingredient::query()->lockForUpdate()->findOrFail($ingredient->id);
            $ingredient->update(['name' => $validated['name'], 'slug' => Str::slug($validated['name'])]);
            $ingredient->allergens()->sync($validated['allergens'] ?? []);
        });

        return redirect()->route('admin.ingredients.index')->with('status', 'Ingredient updated.');
    }

    public function destroy(Ingredient $ingredient): RedirectResponse
    {
        $ingredient->delete();

        return redirect()->route('admin.ingredients.index')->with('status', 'Ingredient deleted.');
    }
}
