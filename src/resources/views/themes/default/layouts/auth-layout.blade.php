<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    {{-- Meta data --}}
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', '(page title not set)') - WebRegulate Admin</title>

    {{-- Partial head --}}
    @include('wr-laravel-administration::themes.default.layouts.partials.partial-head')
</head>
<body class="h-full">

    <div class="relative flex flex-col gap-8 w-full h-full items-center py-20 bg-slate-200 text-slate-800 dark:bg-slate-900 dark:text-slate-400">
        {{-- Top right fixed corner show dark mode toggle using font awesome icons --}}
        <div class="fixed top-0 right-0">
            <button id="theme-mode-toggle" class="flex w-[50px] h-10 justify-center items-center rounded-bl-lg shadow-md bg-slate-50 text-slate-800 dark:bg-slate-800 dark:text-slate-400">
                <i class="fas fa-sun text-primary-500 dark:hidden"></i>
                <i class="fas fa-moon text-primary-500 hidden dark:block"></i>
            </button>
        </div>

        {{-- Logo --}}
        <a href="{{ route('wrla.dashboard') }}">
            <img src="{{ asset(config('wr-laravel-administration.logo.light')) }}" title="Light Logo" alt="Light Logo" class="dark:hidden" />
            <img src="{{ asset(config('wr-laravel-administration.logo.dark')) }}" title="Dark Logo" alt="Dark Logo" class="hidden dark:block" />
        </a>

        {{-- Yield content --}}
        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
