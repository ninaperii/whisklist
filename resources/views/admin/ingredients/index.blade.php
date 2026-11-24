@extends('layouts.app')
@section('title', 'Ingredients | WhiskList Admin')
@section('content')
<section class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4"><div><a class="inline-flex items-center gap-2 text-sm font-semibold text-orange-700 hover:text-orange-800" href="{{ route('admin.dashboard') }}"><span aria-hidden="true">←</span> Admin overview</a><h1 class="mt-3 text-3xl font-semibold">Ingredients</h1></div><a class="rounded-lg bg-orange-700 px-4 py-2 font-semibold text-white" href="{{ route('admin.ingredients.create') }}">Add ingredient</a></div>
    @if (session('status'))<p class="mt-6 rounded-lg bg-green-50 px-4 py-3 text-green-800">{{ session('status') }}</p>@endif
    <div class="mt-8 overflow-hidden rounded-2xl bg-white ring-1 ring-stone-200">
        @forelse ($ingredients as $ingredient)
            <div class="flex items-center justify-between gap-4 border-b border-stone-200 p-5 last:border-0"><p class="font-semibold">{{ $ingredient->name }}</p><div class="flex gap-2"><a class="rounded-lg border px-3 py-2 text-sm font-semibold" href="{{ route('admin.ingredients.edit', $ingredient) }}">Edit</a><form method="POST" action="{{ route('admin.ingredients.destroy', $ingredient) }}">@csrf @method('DELETE')<button class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700" type="submit">Delete</button></form></div></div>
        @empty
            <p class="p-8 text-center text-stone-600">No ingredients have been added yet.</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $ingredients->links() }}</div>
</section>
@endsection
