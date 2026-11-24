<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDietaryTagRequest;
use App\Http\Requests\Admin\UpdateDietaryTagRequest;
use App\Models\DietaryTag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DietaryTagController extends Controller
{
    public function index(): View
    {
        return view('admin.dietary-tags.index', ['dietaryTags' => DietaryTag::query()->orderBy('name')->paginate(15)]);
    }

    public function create(): View
    {
        return view('admin.dietary-tags.create');
    }

    public function store(StoreDietaryTagRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['slug'] = Str::slug($validated['name']);
        DietaryTag::create($validated);

        return redirect()->route('admin.dietary-tags.index')->with('status', 'Dietary tag created.');
    }

    public function edit(DietaryTag $dietaryTag): View
    {
        return view('admin.dietary-tags.edit', compact('dietaryTag'));
    }

    public function update(UpdateDietaryTagRequest $request, DietaryTag $dietaryTag): RedirectResponse
    {
        $validated = $request->validated();
        $validated['slug'] = Str::slug($validated['name']);
        $dietaryTag->update($validated);

        return redirect()->route('admin.dietary-tags.index')->with('status', 'Dietary tag updated.');
    }

    public function destroy(DietaryTag $dietaryTag): RedirectResponse
    {
        $dietaryTag->delete();

        return redirect()->route('admin.dietary-tags.index')->with('status', 'Dietary tag deleted.');
    }
}
