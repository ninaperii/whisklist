@extends('layouts.app')

@section('title', 'My drafts | WhiskList')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <a class="font-semibold text-orange-700" href="{{ route('dashboard') }}">← Dashboard</a>
        <div class="my-6 flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-3xl font-semibold">My drafts</h1>
            <a class="rounded-lg bg-orange-700 px-5 py-2.5 font-semibold text-white" href="{{ route('recipes.create') }}">Create recipe</a>
        </div>
        <ul class="divide-y divide-stone-200 rounded-2xl bg-white ring-1 ring-stone-200">
            @forelse ($drafts as $draft)
                <li><a class="block px-5 py-4 font-semibold text-orange-700 hover:underline" href="{{ route('recipes.edit', $draft) }}">{{ $draft->title }}</a></li>
            @empty
                <li class="p-5 text-stone-600">You have no recipe drafts yet.</li>
            @endforelse
        </ul>
        <div class="mt-6">{{ $drafts->links() }}</div>
    </section>
@endsection
