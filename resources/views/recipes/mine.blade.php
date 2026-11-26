@extends('layouts.app')

@section('title', 'My recipes | WhiskList')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <a class="font-semibold text-orange-700" href="{{ route('dashboard') }}">← Dashboard</a>
        <div class="my-6 flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-3xl font-semibold">My recipes</h1>
            <a class="rounded-lg bg-orange-700 px-5 py-2.5 font-semibold text-white" href="{{ route('recipes.create') }}">Create recipe</a>
        </div>
        @if (session('status'))
            <p role="status" class="mb-6 rounded-lg bg-green-50 p-4 text-green-800">{{ session('status') }}</p>
        @endif
        <nav aria-label="My recipe views" class="mb-5 flex gap-4 text-sm font-semibold text-orange-700">
            <a class="{{ $draftsOnly ? 'hover:underline' : 'underline' }}" href="{{ route('recipes.mine') }}" @if (! $draftsOnly) aria-current="page" @endif>All my recipes</a>
            <a class="{{ $draftsOnly ? 'underline' : 'hover:underline' }}" href="{{ route('recipes.mine', ['status' => 'draft']) }}" @if ($draftsOnly) aria-current="page" @endif>Drafts</a>
        </nav>
        <ul class="divide-y divide-stone-200 rounded-2xl bg-white ring-1 ring-stone-200">
            @forelse ($recipes as $recipe)
                <li class="flex flex-wrap items-center justify-between gap-4 px-5 py-4">
                    <div>
                        <p class="font-semibold">{{ $recipe->title }}</p>
                        <p class="mt-1 text-sm text-stone-600">{{ $recipe->status->name }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-4 text-sm font-semibold">
                        @if ($recipe->isPublished())
                            <a class="text-orange-700 hover:underline" href="{{ route('recipes.show', ['recipe' => $recipe, 'origin' => 'mine', 'origin_query' => request()->only('page', 'status')]) }}">View public page</a>
                        @endif
                        <a class="text-orange-700 hover:underline" href="{{ route('recipes.edit', $recipe) }}">Edit</a>
                        @if ($recipe->status === \App\Enums\RecipeStatus::Published)
                            <form method="POST" action="{{ route('recipes.unpublish', $recipe) }}" data-make-private>
                                @csrf
                                @method('PATCH')
                                <button class="text-stone-700 hover:underline" type="submit">Make private</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('recipes.destroy', $recipe) }}" data-confirm-delete="Delete this recipe permanently?">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-700 hover:underline" type="submit">Delete</button>
                        </form>
                    </div>
                </li>
            @empty
                <li class="p-5 text-stone-600">{{ $draftsOnly ? 'You have no recipe drafts on this page.' : 'You have no recipes on this page.' }}</li>
            @endforelse
        </ul>
        <div class="mt-6">{{ $recipes->links() }}</div>

        <dialog id="make-recipe-private" aria-labelledby="make-recipe-private-title" class="m-auto w-full max-w-sm rounded-2xl bg-white p-6 text-stone-900 shadow-lg backdrop:bg-stone-900/50">
            <h2 id="make-recipe-private-title" class="text-xl font-semibold">Make this recipe private?</h2>
            <p class="mt-2 text-stone-600">Visitors will no longer be able to see this recipe. You can publish it again later.</p>
            <form method="dialog" class="mt-6 flex flex-wrap gap-3">
                <button class="rounded-lg border border-stone-300 px-4 py-2 font-semibold" value="cancel" autofocus>Cancel</button>
                <button class="rounded-lg bg-orange-700 px-4 py-2 font-semibold text-white" value="confirm">Make private</button>
            </form>
        </dialog>
    </section>
@endsection
