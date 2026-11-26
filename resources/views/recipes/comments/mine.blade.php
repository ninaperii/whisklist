@extends('layouts.app')

@section('title', 'My comments | WhiskList')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <a class="font-semibold text-orange-700 hover:underline" href="{{ route('dashboard') }}">← Dashboard</a>
        <h1 class="mb-6 mt-3 text-3xl font-semibold">My comments</h1>

        <ul class="divide-y divide-stone-200 rounded-2xl bg-white ring-1 ring-stone-200">
            @forelse ($comments as $comment)
                <li class="p-5">
                    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                        @if ($comment->recipe)
                            <a class="font-semibold text-orange-700 hover:underline" href="{{ route('recipes.comments.show', [$comment->recipe, $comment]) }}">{{ $comment->recipe->title }}</a>
                        @else
                            <span class="font-semibold text-stone-600">Recipe unavailable</span>
                        @endif
                        <time class="text-sm text-stone-600" datetime="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->format('M j, Y') }}</time>
                    </div>
                    <p class="mt-2 whitespace-pre-wrap break-words text-stone-700">{{ $comment->body }}</p>
                </li>
            @empty
                <li class="p-5 text-stone-600">No comments on this page.</li>
            @endforelse
        </ul>
        <div class="mt-5">{{ $comments->links() }}</div>
    </section>
@endsection
