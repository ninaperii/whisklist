<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAllergenRequest;
use App\Http\Requests\Admin\UpdateAllergenRequest;
use App\Models\Allergen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AllergenController extends Controller
{
    public function index(): View
    {
        return view('admin.allergens.index', ['allergens' => Allergen::query()->orderBy('name')->paginate(15)]);
    }

    public function create(): View
    {
        return view('admin.allergens.create');
    }

    public function store(StoreAllergenRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['slug'] = Str::slug($validated['name']);
        Allergen::create($validated);

        return redirect()->route('admin.allergens.index')->with('status', 'Allergen created.');
    }

    public function edit(Allergen $allergen): View
    {
        return view('admin.allergens.edit', compact('allergen'));
    }

    public function update(UpdateAllergenRequest $request, Allergen $allergen): RedirectResponse
    {
        $validated = $request->validated();
        $validated['slug'] = Str::slug($validated['name']);
        $allergen->update($validated);

        return redirect()->route('admin.allergens.index')->with('status', 'Allergen updated.');
    }

    public function destroy(Allergen $allergen): RedirectResponse
    {
        $allergen->delete();

        return redirect()->route('admin.allergens.index')->with('status', 'Allergen deleted.');
    }
}
