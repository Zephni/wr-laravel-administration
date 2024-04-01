<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', '(page title not set)') - WebRegulate Admin</title>

    {{-- Tailwind cdn --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Styles --}}
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,opsz,wght@0,6..12,200..1000;1,6..12,200..1000&display=swap');

        body { font-feature-settings: normal; font-family: "Nunito Sans", sans-serif; -webkit-font-smoothing: antialiased; }

        /* Tailwind overrides */
        .bg-gray-800 { background-color: rgb(30, 41, 59) !important; }
        .bg-gray-900 { background-color: rgb(15, 23, 42) !important; }
        .text-gray-400 { color: rgb(148, 163, 184) !important; }
    </style>

    @stack('styles')
</head>
<body class="h-full bg-gray-900 text-gray-400">

    <div class="flex flex-col gap-8 w-full h-full items-center py-20">
        {{-- Logo --}}
        <img src="{{ asset(config('wr-laravel-administration.logo.auth')) }}" alt="Logo" />

        {{-- Yield content --}}
        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
