@extends('layouts.app')

@section('title', 'WhiskList | Cook, share, discover')

@section('content')
    <section class="mx-auto grid max-w-7xl gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:items-center lg:px-8 lg:py-28">
        <div>
            <p class="text-sm font-semibold uppercase tracking-widest text-orange-700">Cook, share, discover</p>
            <h1 class="mt-4 text-4xl font-semibold tracking-tight sm:text-6xl">Find your next favorite recipe.</h1>
            <p class="mt-6 max-w-xl text-lg leading-8 text-stone-600">
                WhiskList is a community for discovering recipes, sharing what you cook, and saving dishes you want to try.
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
                @guest
                    <a class="rounded-lg bg-orange-700 px-5 py-3 font-semibold text-white hover:bg-orange-800" href="{{ route('register') }}">
                        Create an account
                    </a>
                    <a class="rounded-lg border border-stone-300 bg-white px-5 py-3 font-semibold hover:bg-stone-50" href="{{ route('login') }}">
                        Log in
                    </a>
                @else
                    <a class="rounded-lg bg-orange-700 px-5 py-3 font-semibold text-white hover:bg-orange-800" href="{{ route('dashboard') }}">
                        Open your dashboard
                    </a>
                @endguest
            </div>
        </div>

        <div class="rounded-3xl bg-orange-100 p-8 ring-1 ring-orange-200 sm:p-12">
            <div class="rounded-2xl bg-white p-8 shadow-sm">
                <p class="text-sm font-semibold text-orange-700">WhiskList is being prepared</p>
                <h2 class="mt-3 text-2xl font-semibold">Recipe discovery comes next</h2>
                <p class="mt-4 leading-7 text-stone-600">
                    Soon you will be able to browse recipes by category and find meals using ingredients you already have.
                </p>
            </div>
        </div>
    </section>
@endsection
