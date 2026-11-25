@extends('layouts.app')

@section('title', $recipe->exists ? 'Edit recipe draft | WhiskList' : 'Create recipe | WhiskList')

@section('content')
    @php
        $inputClass = 'mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2';
        $sectionClass = 'space-y-4 rounded-2xl bg-white p-5 ring-1 ring-stone-200 sm:p-6';
        $usingOldInput = session()->hasOldInput();
        $oldText = static function (string $field, mixed $fallback): string {
            $value = old($field, $fallback);

            return is_scalar($value) ? (string) $value : '';
        };
        $selectedCategories = (array) old('categories', $usingOldInput ? [] : $recipe->categories->modelKeys());
        $selectedTags = (array) old('dietary_tags', $usingOldInput ? [] : $recipe->dietaryTags->modelKeys());
        $ingredientRows = old('ingredients', $usingOldInput ? [] : $recipe->ingredients->map(fn ($ingredient) => ['id' => $ingredient->id] + $ingredient->pivot->only(['quantity', 'unit', 'notes', 'is_main']))->all());
        $stepRows = old('steps', $usingOldInput ? [] : $recipe->steps->pluck('instruction')->all());
    @endphp

    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <a class="font-semibold text-orange-700" href="{{ route('recipes.mine') }}">← My drafts</a>
        <h1 class="mb-6 mt-3 text-3xl font-semibold">{{ $recipe->exists ? 'Edit recipe draft' : 'Create recipe' }}</h1>

        @if (session('status'))
            <p role="status" class="mb-6 rounded-lg bg-green-50 p-4 text-green-800">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <div role="alert" class="mb-6 rounded-lg bg-red-50 p-4 text-red-800">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="space-y-6" method="POST" enctype="multipart/form-data" action="{{ $recipe->exists ? route('recipes.update', $recipe) : route('recipes.store') }}">
            @csrf
            @if ($recipe->exists)
                @method('PUT')
            @endif

            <section class="{{ $sectionClass }}">
                <h2 class="text-xl font-semibold">Recipe details</h2>
                <label class="block">Title
                    <input class="{{ $inputClass }}" name="title" value="{{ $oldText('title', $recipe->title) }}" maxlength="200" required>
                </label>
                <label class="block">Summary
                    <textarea class="{{ $inputClass }}" name="summary" rows="3" maxlength="2000">{{ $oldText('summary', $recipe->summary) }}</textarea>
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach (['preparation_minutes' => ['Prep time (minutes)', 0], 'cooking_minutes' => ['Cook time (minutes)', 0], 'servings' => ['Servings', 1]] as $field => [$label, $minimum])
                        <label>{{ $label }}
                            <input class="{{ $inputClass }}" type="number" name="{{ $field }}" min="{{ $minimum }}" max="65535" value="{{ $oldText($field, $recipe->$field ?? $minimum) }}" required>
                        </label>
                    @endforeach
                    <label>Difficulty
                        <select class="{{ $inputClass }}" name="difficulty">
                            @foreach ($difficulties as $difficulty)
                                <option value="{{ $difficulty->value }}" @selected(old('difficulty', $recipe->difficulty?->value) === $difficulty->value)>{{ $difficulty->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </section>

            @foreach (['categories' => ['Categories', $categories, $selectedCategories], 'dietary_tags' => ['Dietary tags', $dietaryTags, $selectedTags]] as $field => [$label, $options, $selected])
                <section class="{{ $sectionClass }}">
                    <h2 id="draft-{{ $field }}-heading" class="text-xl font-semibold">{{ $label }}</h2>
                    @if ($options->isEmpty())
                        <p class="text-sm text-stone-600">An administrator can add options for you to select here.</p>
                    @else
                        <fieldset class="grid gap-2 sm:grid-cols-2" aria-labelledby="draft-{{ $field }}-heading">
                            @foreach ($options as $option)
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="{{ $field }}[]" value="{{ $option->id }}" @checked(in_array($option->id, $selected))>
                                    {{ $option->name }}
                                </label>
                            @endforeach
                        </fieldset>
                    @endif
                </section>
            @endforeach

            <section class="{{ $sectionClass }}" data-draft-list>
                <h2 class="text-xl font-semibold">Ingredients</h2>
                @if ($ingredients->isEmpty())
                    <p class="text-sm text-stone-600">An administrator can add ingredients for you to select here.</p>
                @else
                    <div class="space-y-4" data-rows>
                        @foreach (array_slice((array) $ingredientRows, 0, 100, true) as $index => $row)
                            @include('recipes.ingredient-row')
                        @endforeach
                    </div>
                    <template data-row-template>
                        @include('recipes.ingredient-row', ['index' => '__index__', 'row' => []])
                    </template>
                    <button class="rounded-lg border border-stone-300 px-4 py-2 font-semibold" type="button" data-add>+ Add ingredient</button>
                @endif
            </section>

            <section class="{{ $sectionClass }}" data-draft-list>
                <h2 class="text-xl font-semibold">Instructions</h2>
                <div class="space-y-4" data-rows>
                    @foreach (array_slice((array) $stepRows, 0, 100, true) as $index => $instruction)
                        @include('recipes.step-row')
                    @endforeach
                </div>
                <template data-row-template>
                    @include('recipes.step-row', ['index' => '__index__', 'instruction' => ''])
                </template>
                <button class="rounded-lg border border-stone-300 px-4 py-2 font-semibold" type="button" data-add>+ Add step</button>
            </section>

            <section class="{{ $sectionClass }}">
                <h2 class="text-xl font-semibold">Recipe thumbnail</h2>
                @if ($recipe->thumbnail)
                    <img class="aspect-video w-full max-w-sm rounded-lg object-cover" src="{{ route('recipes.thumbnail', $recipe) }}" alt="Current thumbnail for {{ $recipe->title }}">
                @endif
                <label class="block">
                    {{ $recipe->thumbnail ? 'Replace thumbnail' : 'Upload a thumbnail' }}
                    <input class="{{ $inputClass }}" type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp">
                </label>
                <p class="text-sm text-stone-600">JPEG, PNG, or WebP. Up to 2 MB.</p>
            </section>

            <div class="flex flex-wrap gap-3">
                <button class="rounded-lg border border-stone-300 bg-white px-5 py-2.5 font-semibold" type="submit" name="action" value="draft">Save draft</button>
                <button class="rounded-lg bg-orange-700 px-5 py-2.5 font-semibold text-white" type="submit" name="action" value="publish">Publish recipe</button>
            </div>
            <p class="text-sm text-stone-600">To publish, add a thumbnail and at least one category, ingredient, and instruction step.</p>
        </form>
    </section>
@endsection
