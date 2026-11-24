@extends('layouts.app')
@section('title', 'Dietary tags | WhiskList Admin')
@section('content')
<section class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4"><div><a class="font-semibold text-orange-700" href="{{ route('admin.dashboard') }}">← Admin overview</a><h1 class="mt-3 text-3xl font-semibold">Dietary tags</h1></div><a class="rounded-lg bg-orange-700 px-4 py-2 font-semibold text-white" href="{{ route('admin.dietary-tags.create') }}">Add dietary tag</a></div>
    @if (session('status'))<p class="mt-6 rounded-lg bg-green-50 px-4 py-3 text-green-800">{{ session('status') }}</p>@endif
    <div class="mt-8 overflow-hidden rounded-2xl bg-white ring-1 ring-stone-200">@forelse ($dietaryTags as $dietaryTag)<div class="flex items-center justify-between gap-4 border-b border-stone-200 p-5 last:border-0"><div><p class="font-semibold">{{ $dietaryTag->name }}</p><p class="mt-1 text-sm text-stone-600">{{ $dietaryTag->description ?: 'No description.' }}</p></div><div class="flex gap-2"><a class="rounded-lg border px-3 py-2 text-sm font-semibold" href="{{ route('admin.dietary-tags.edit', $dietaryTag) }}">Edit</a><form method="POST" action="{{ route('admin.dietary-tags.destroy', $dietaryTag) }}">@csrf @method('DELETE')<button class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700">Delete</button></form></div></div>@empty<p class="p-8 text-center text-stone-600">No dietary tags have been added yet.</p>@endforelse</div>
    <div class="mt-6">{{ $dietaryTags->links() }}</div>
</section>
@endsection
