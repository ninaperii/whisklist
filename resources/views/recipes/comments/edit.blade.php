@extends('layouts.app')

@section('title', 'Edit comment | WhiskList')

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-12 sm:px-6">
        <a class="font-semibold text-orange-700 hover:underline" href="{{ route('recipes.comments.show', ['recipe' => $recipe, 'comment' => $comment] + $recipeOrigin['parameters']) }}">← Back to comment</a>
        <h1 class="my-6 text-3xl font-semibold">Edit comment</h1>
        <form class="space-y-4 rounded-2xl bg-white p-6 ring-1 ring-stone-200" method="POST" action="{{ route('recipes.comments.update', ['recipe' => $recipe, 'comment' => $comment] + $recipeOrigin['parameters']) }}">
            @csrf
            @method('PATCH')
            @include('recipes.comments.field', ['commentBody' => $comment->body])
            <button class="rounded-lg bg-orange-700 px-4 py-2 font-semibold text-white" type="submit">Save comment</button>
        </form>
    </section>
@endsection
