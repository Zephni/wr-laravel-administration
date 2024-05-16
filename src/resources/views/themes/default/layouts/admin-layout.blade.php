<!DOCTYPE html>
<html
    lang="en"
    class="h-full"
    x-cloak
    x-data="{ darkMode: $persist({{ $themeData->default_mode == 'dark' ? 'true' : 'false' }}) }"
    :class="{'dark': darkMode === true }">
<head>
    {{-- Meta data --}}
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', '(page title not set)') - WebRegulate Admin</title>

    {{-- Partial head --}}
    @include('wr-laravel-administration::themes.default.layouts.partials.partial-head')
</head>
<body
    class="h-full antialiased font-light bg-slate-200 dark:bg-slate-900">

    {{-- Main container --}}
    <div
        x-data="{
            leftPanelOpen: $persist(true).using(sessionStorage),
            leftPanelWidth: $persist(360),
        }"
        class="relative flex flex-row w-full h-full items-center text-slate-900 dark:text-slate-100">

        {{-- Left panel --}}
        @include('wr-laravel-administration::themes.default.layouts.partials.partial-left-panel')

        {{-- Right container --}}
        @include('wr-laravel-administration::themes.default.layouts.partials.partial-main-container')

    </div>

    {{-- Script stack --}}
    @stack('scripts')
</body>
</html>
