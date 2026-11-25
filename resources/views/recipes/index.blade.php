@extends('layouts.app')

@section('title', 'Recipes | WhiskList')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <h1 class="mb-6 text-3xl font-semibold tracking-tight">Recipes</h1>

        @if ($recipes->isEmpty())
            <p class="rounded-2xl bg-white p-6 text-stone-600 ring-1 ring-stone-200">{{ $recipes->currentPage() === 1 ? 'No recipes have been published yet.' : 'No recipes on this page.' }}</p>
            @if ($recipes->currentPage() > 1)
                <a class="mt-4 inline-block font-semibold text-orange-700 hover:underline" href="{{ route('recipes.index') }}">Back to the first page</a>
            @endif
        @else
            <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($recipes as $recipe)
                    <x-recipe-card :recipe="$recipe" heading="h2" />
                @endforeach
            </ul>
            <div class="mt-8">{{ $recipes->links() }}</div>
        @endif
    </section>
@endsection
