@extends('layouts.app')

@section('title', $recipe->exists ? 'Edit recipe | WhiskList' : 'Create recipe | WhiskList')

@section('content')
    @php
        $inputClass = 'mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2';
        $sectionClass = 'space-y-4 rounded-2xl bg-white p-5 ring-1 ring-stone-200 sm:p-6';
        $usingOldInput = session()->hasOldInput();
        $isPublished = $recipe->status === \App\Enums\RecipeStatus::Published;
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
        <a class="font-semibold text-orange-700" href="{{ route('recipes.mine') }}">← My recipes</a>
        <h1 class="mb-6 mt-3 text-3xl font-semibold">{{ $recipe->exists ? 'Edit recipe' : 'Create recipe' }}</h1>

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

        <form class="space-y-6" method="POST" enctype="multipart/form-data" action="{{ $recipe->exists ? route('recipes.update', $recipe) : route('recipes.store') }}" data-recipe-editor @if ($usingOldInput) data-unsaved-input @endif>
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

            <section class="{{ $sectionClass }}" data-thumbnail-picker>
                <h2 class="text-xl font-semibold">Recipe thumbnail</h2>
                <div @if (! $recipe->thumbnail) hidden @endif data-thumbnail-preview>
                    <img class="aspect-video w-full max-w-sm rounded-lg object-cover"
                        @if ($recipe->thumbnail) src="{{ route('recipes.thumbnail', $recipe) }}" @endif
                        alt="Thumbnail preview">
                </div>
                <div>
                    <input class="peer sr-only" id="recipe-thumbnail" type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp" aria-describedby="thumbnail-help thumbnail-status">
                    <label class="inline-block cursor-pointer rounded-lg border border-stone-300 px-4 py-2 font-semibold peer-focus-visible:ring-2 peer-focus-visible:ring-orange-700" for="recipe-thumbnail">
                        Upload a picture
                    </label>
                </div>
                <p id="thumbnail-help" class="text-sm text-stone-600">JPEG, PNG, or WebP, up to 2 MB.</p>
                <p id="thumbnail-status" class="sr-only" role="status"></p>
            </section>

            <div class="flex flex-wrap gap-3">
                @if ($isPublished)
                    <button class="rounded-lg bg-orange-700 px-5 py-2.5 font-semibold text-white" type="submit">Save changes</button>
                @else
                    <button class="rounded-lg border border-stone-300 bg-white px-5 py-2.5 font-semibold" type="submit" name="action" value="draft">Save draft</button>
                @endif
                <a class="rounded-lg border border-stone-300 bg-white px-5 py-2.5 font-semibold text-stone-700 hover:bg-stone-50" href="{{ route('recipes.mine') }}" data-discard-changes>Discard changes</a>
                @unless ($isPublished)
                    <button class="rounded-lg bg-orange-700 px-5 py-2.5 font-semibold text-white" type="submit" name="action" value="publish">Publish recipe</button>
                @endunless
            </div>
            @unless ($isPublished)
                <p class="text-sm text-stone-600">To publish, add a thumbnail and at least one category, ingredient, and instruction step.</p>
            @endunless
        </form>

        @unless ($isPublished)
            <dialog id="publish-recipe" aria-labelledby="publish-recipe-title" class="m-auto w-full max-w-sm rounded-2xl bg-white p-6 text-stone-900 shadow-lg backdrop:bg-stone-900/50">
                <h2 id="publish-recipe-title" class="text-xl font-semibold">Publish this recipe?</h2>
                <p class="mt-2 text-stone-600">Anyone will be able to view this recipe.</p>
                <form method="dialog" class="mt-6 flex flex-wrap gap-3">
                    <button class="rounded-lg border border-stone-300 px-4 py-2 font-semibold" value="cancel" autofocus>Cancel</button>
                    <button class="rounded-lg bg-orange-700 px-4 py-2 font-semibold text-white" value="publish">Publish recipe</button>
                </form>
            </dialog>
        @endunless

        <dialog id="leave-recipe-editor" aria-labelledby="leave-recipe-title" class="m-auto w-full max-w-sm rounded-2xl bg-white p-6 text-stone-900 shadow-lg backdrop:bg-stone-900/50">
            <h2 id="leave-recipe-title" class="text-xl font-semibold">Leave without saving?</h2>
            <p class="mt-2 text-stone-600">Your unsaved changes will be discarded.</p>
            <form method="dialog" class="mt-6 flex flex-wrap gap-3">
                <button class="rounded-lg border border-stone-300 px-4 py-2 font-semibold" value="stay" autofocus>Keep editing</button>
                <button class="rounded-lg bg-orange-700 px-4 py-2 font-semibold text-white" value="leave">Leave page</button>
            </form>
        </dialog>
    </section>
@endsection
