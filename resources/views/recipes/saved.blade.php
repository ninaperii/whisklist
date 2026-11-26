@extends('layouts.app')

@section('title', 'My WhiskList | WhiskList')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <a class="font-semibold text-orange-700 hover:underline" href="{{ route('dashboard') }}">← Dashboard</a>
        <h1 class="mb-6 mt-3 text-3xl font-semibold tracking-tight">My WhiskList</h1>

        @if (session('status'))
            <p class="mb-6 rounded-lg bg-green-50 p-4 text-green-800" role="status">{{ session('status') }}</p>
        @endif

        @if ($recipes->isEmpty())
            <p class="rounded-2xl bg-white p-6 text-stone-600 ring-1 ring-stone-200">No saved recipes on this page.</p>
            <a class="mt-4 inline-block font-semibold text-orange-700 hover:underline" href="{{ route('recipes.index') }}">Browse recipes</a>
        @else
            <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($recipes as $recipe)
                    <x-recipe-card :recipe="$recipe" heading="h2">
                        <form class="border-t border-stone-200 px-5 py-3" method="POST" action="{{ route('whisklist.destroy', $recipe) }}">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="from" value="whisklist">
                            <button class="text-sm font-semibold text-orange-700 hover:underline" type="submit" aria-label="Remove {{ $recipe->title }} from My WhiskList">Remove</button>
                        </form>
                    </x-recipe-card>
                @endforeach
            </ul>
            <div class="mt-8">{{ $recipes->links() }}</div>
        @endif
    </section>
@endsection
