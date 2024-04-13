{{-- Left panel --}}
<div class="hidden md:flex flex-col justify-start items-center w-80 min-w-80 h-full border-r-2 border-slate-300 dark:border-slate-950 bg-slate-100 dark:bg-slate-850 shadow-xl shadow-slate-500 dark:shadow-slate-950 z-10">
    <div class="w-3/4 py-8">
        <img src="{{ asset(config('wr-laravel-administration.logo.light')) }}" title="Light Logo" alt="Light Logo" class="dark:hidden w-full" />
        <img src="{{ asset(config('wr-laravel-administration.logo.dark')) }}" title="Dark Logo" alt="Dark Logo" class="hidden dark:block w-full" />
    </div>

    @include('wr-laravel-administration::themes.default.layouts.partials.partial-navigation')
</div>
