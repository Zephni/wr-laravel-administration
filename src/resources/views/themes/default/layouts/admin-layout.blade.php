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
<body class="h-full" style="background: black;">

    <div class="relative flex flex-row w-full h-full items-center bg-slate-200 text-slate-800 dark:bg-slate-900 dark:text-slate-400">

        {{-- Left panel --}}
        @include('wr-laravel-administration::themes.default.layouts.partials.partial-left-panel')

        {{-- Right container --}}
        @include('wr-laravel-administration::themes.default.layouts.partials.partial-right-container')

    </div>

    {{-- Script stack --}}
    @stack('scripts')
</body>
</html>
