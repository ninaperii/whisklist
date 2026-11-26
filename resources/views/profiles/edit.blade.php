@extends('layouts.app')

@section('title', 'Edit profile | WhiskList')

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200 sm:p-8">
            <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-orange-700">Account settings</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">Edit your profile</h1>
                <p class="mt-2 text-sm text-stone-600">Choose what other WhiskList visitors can see about you.</p>
            </div>

            @if (session('status'))
                <p class="mt-6 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800 ring-1 ring-green-200">
                    {{ session('status') }}
                </p>
            @endif

            <form class="mt-8 space-y-6" method="POST" enctype="multipart/form-data" action="{{ route('profile.update') }}" data-profile-editor @if ($errors->getBag('default')->any()) data-unsaved-input @endif>
                @csrf
                @method('PUT')

                <div class="space-y-2" data-thumbnail-picker>
                    <label class="block font-semibold" for="profile-avatar">Profile photo</label>
                    <div @if (! $user->avatar_path) hidden @endif data-thumbnail-preview>
                        <img class="size-20 rounded-full object-cover" @if ($user->avatar_path) src="{{ route('users.avatar', $user) }}" @endif alt="Profile photo preview">
                    </div>
                    <input class="block w-full rounded-lg border border-stone-300 px-3 py-2" id="profile-avatar" name="avatar" type="file"
                        accept="image/jpeg,image/png,image/webp" aria-describedby="profile-avatar-help profile-avatar-status">
                    <p class="text-sm text-stone-600" id="profile-avatar-help">JPEG, PNG, or WebP, up to 2 MB. Your photo is visible even if your profile is private.</p>
                    <p class="sr-only" id="profile-avatar-status" role="status"></p>
                    @error('avatar')
                        <p class="text-sm text-red-700" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium" for="name">Name</label>
                    <input
                        class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 outline-none focus:border-orange-600 focus:ring-2 focus:ring-orange-200"
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name', $user->name) }}"
                        maxlength="255"
                        required
                    >
                    @error('name')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium" for="bio">Biography</label>
                    <textarea
                        class="mt-2 min-h-36 w-full rounded-lg border border-stone-300 px-3 py-2 outline-none focus:border-orange-600 focus:ring-2 focus:ring-orange-200"
                        id="bio"
                        name="bio"
                        maxlength="1000"
                    >{{ old('bio', $user->bio) }}</textarea>
                    <p class="mt-2 text-sm text-stone-500">Up to 1,000 characters.</p>
                    @error('bio')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-xl bg-stone-50 p-4 ring-1 ring-stone-200">
                    <input name="is_profile_public" type="hidden" value="0">
                    <label class="flex items-start gap-3" for="is_profile_public">
                        <input
                            class="mt-1 rounded border-stone-300 text-orange-700 focus:ring-orange-600"
                            id="is_profile_public"
                            name="is_profile_public"
                            type="checkbox"
                            value="1"
                            @checked(old('is_profile_public', $user->is_profile_public))
                        >
                        <span>
                            <span class="block font-semibold">Public profile</span>
                            <span class="mt-1 block text-sm leading-6 text-stone-600">
                                When disabled, visitors can still see your name, profile picture, and published recipes. Only you and administrators can see your bio.
                            </span>
                        </span>
                    </label>
                    @error('is_profile_public')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-wrap gap-3">
                    <button class="rounded-lg bg-orange-700 px-5 py-2.5 font-semibold text-white hover:bg-orange-800" type="submit">
                        Save profile
                    </button>
                    <a class="rounded-lg border border-stone-300 px-5 py-2.5 font-semibold hover:bg-stone-50" href="{{ route('users.show', $user) }}">
                        View profile
                    </a>
                </div>
            </form>
        </div>

        <section id="recipe-preferences" class="mt-8 space-y-4 rounded-2xl bg-white p-6 ring-1 ring-stone-200">
            @if ($returnToRecipes)
                <a class="inline-block text-sm font-semibold text-orange-700 hover:underline" href="{{ $returnToRecipes }}">← Back to Recipes</a>
            @endif
            <h2 class="text-xl font-semibold">Recipe preferences</h2>
            <p class="text-sm text-stone-600">These choices are private. Apply them using <a class="font-semibold text-orange-700 underline" href="{{ route('recipes.index') }}">Use my preferences</a> on the Recipes page.</p>

            @if (session('recipe_preference_status'))
                <p role="status" class="text-sm text-green-800">{{ session('recipe_preference_status') }}</p>
            @endif
            @if ($errors->recipePreferences->any())
                <ul role="alert" class="list-disc space-y-1 pl-5 text-sm text-red-700">
                    @foreach ($errors->recipePreferences->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <form class="space-y-5" method="POST" action="{{ route('profile.recipe-preferences.update') }}" data-profile-editor @if ($errors->recipePreferences->any()) data-unsaved-input @endif>
                @csrf
                @method('PUT')
                @if ($returnToRecipes)
                    <input type="hidden" name="return_search" value="{{ $returnSearch }}">
                @endif
                @foreach ([
                    'allergens' => ['My allergens', $allergenOptions, $selectedAllergens],
                    'dietary_tags' => ['Preferred dietary tags', $dietaryTagOptions, $preferredTags],
                    'exclude_ingredients' => ['Ingredients to avoid', $ingredientOptions, $excludedIngredients],
                ] as $field => [$label, $options, $selected])
                    <fieldset>
                        <legend class="mb-2 font-semibold">{{ $label }}</legend>
                        @if ($field === 'allergens')
                            <p class="mb-2 text-sm text-stone-600">Show warnings for known matches when you browse recipes.</p>
                        @endif
                        @if ($options->isEmpty())
                            <p class="text-sm text-stone-600">No options available yet.</p>
                        @else
                            <x-search-picker :field="$field" :label="$label" :options="$options" :selected="$errors->recipePreferences->any() ? (array) old($field, []) : $selected" />
                        @endif
                    </fieldset>
                @endforeach
                <div class="flex flex-wrap gap-3">
                    <button class="rounded-lg bg-orange-700 px-5 py-2.5 font-semibold text-white hover:bg-orange-800" type="submit">Save preferences</button>
                    <a class="rounded-lg border border-stone-300 px-5 py-2.5 font-semibold hover:bg-stone-50" href="{{ $returnToRecipes ?? route('users.show', $user) }}">Discard changes</a>
                </div>
            </form>
        </section>
        <x-leave-editor-dialog />
    </section>
@endsection
