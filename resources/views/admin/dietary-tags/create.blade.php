@extends('layouts.app')
@section('title', 'Add dietary tag | WhiskList Admin')
@section('content')<section class="mx-auto max-w-2xl px-4 py-12"><div class="rounded-2xl bg-white p-8 ring-1 ring-stone-200"><h1 class="text-3xl font-semibold">Add dietary tag</h1><form class="mt-8 space-y-6" method="POST" action="{{ route('admin.dietary-tags.store') }}">@include('admin.dietary-tags._form', ['dietaryTag' => null, 'submitLabel' => 'Create dietary tag'])</form></div></section>@endsection
