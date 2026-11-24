@extends('layouts.app')
@section('title', 'Edit category | WhiskList Admin')
@section('content')
<section class="mx-auto max-w-2xl px-4 py-12 sm:px-6"><div class="rounded-2xl bg-white p-8 ring-1 ring-stone-200"><h1 class="text-3xl font-semibold">Edit category</h1><form class="mt-8 space-y-6" method="POST" action="{{ route('admin.categories.update', $category) }}">@method('PUT') @include('admin.categories._form', ['category' => $category, 'submitLabel' => 'Save category'])</form></div></section>
@endsection
