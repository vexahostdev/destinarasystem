<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#FFFFFF]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Autentikasi') — Destinara</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..24,400,0..1,0" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
    @stack('styles')
</head>
<body class="min-h-screen flex items-center justify-center p-4 antialiased selection:bg-[#1F2B45] selection:text-white bg-[#FFFFFF]">

    <div class="w-full max-w-5xl mx-auto py-6">
        <x-flash />
        <x-form-errors />
        @yield('content')
    </div>

    @stack('scripts')
    @livewireScriptConfig
</body>
</html>
