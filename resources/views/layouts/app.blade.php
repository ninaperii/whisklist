<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title', config('app.name'))</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col bg-stone-100 text-stone-900 antialiased">
        <x-site-header />

        <main class="flex-1">
            @yield('content')
        </main>

        <x-site-footer />
    </body>
</html>
