{{-- Right container --}}
<div class="flex-1 h-full">
    {{-- Top bar --}}
    <div class="flex gap-5 h-9 justify-end items-center border-b-2 border-slate-300 dark:border-slate-950 shadow-md dark:shadow-slate-900 bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-400">
        {{-- Maybe time here? --}}
        <div class="pl-4">

        </div>

        <div class="flex flex-row h-full items-center">
            <div class="relative flex items-center h-full">
                <span class="absolute right-4 text-xs text-slate-500 whitespace-nowrap">
                    Logged in as
                    <i class="fas fa-user text-xs mx-1"></i>
                    {{ $user->name }}
                </span>
            </div>
            <button id="theme-mode-toggle" class="flex w-[40px] h-full justify-center items-center shadow-md border-l border-slate-300 dark:border-slate-950 shadow-slate-300 dark:shadow-slate-900 bg-slate-50 text-slate-800 dark:bg-slate-800 dark:text-slate-400">
                <i class="fas fa-sun text-sm text-primary-500 dark:hidden"></i>
                <i class="fas fa-moon text-sm text-primary-500 hidden dark:block"></i>
            </button>
            <a href="{{ route('wrla.logout') }}" class="flex h-full justify-center items-center text-xs gap-2 px-3 shadow-md border-l border-slate-300 dark:border-slate-950 shadow-slate-300 dark:shadow-slate-900 bg-slate-50 dark:bg-slate-800 text-primary-500">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>

    {{-- Content container --}}
    <div class="flex flex-row">
        {{-- Gap --}}
        <div class=" w-16 h-full"></div>

        {{-- Yield content --}}
        <div class="relative flex flex-col pt-8 pb-96">
            @yield('content')
        </div>
    </div>
</div>
