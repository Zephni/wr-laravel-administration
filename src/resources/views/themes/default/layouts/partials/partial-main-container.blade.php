{{-- Right container --}}
<div
    id="wrla_main_container"
    x-bind:style="'margin-left: ' + (leftPanelOpen ? leftPanelActualWidth : 0) + 'px;'"
    class="flex-1 h-full">
    {{-- Top bar --}}
    <div class="fixed z-10 left-0 flex w-full gap-5 h-9 justify-end items-center border-b-2 border-slate-300 dark:border-slate-700 shadow-md dark:shadow-slate-900 bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-400">
        {{-- Maybe time here? --}}
        <div class="pl-4">

        </div>

        <div class="flex flex-row h-full items-center">
            <div class="relative flex items-center h-full">
                <span class="absolute right-4 text-sm text-slate-500 whitespace-nowrap">
                    Logged in as
                    <i class="fas fa-user text-sm mx-1"></i>
                    {{ $user->name }}
                </span>
            </div>
            <button @click="darkMode = !darkMode" class="flex w-[40px] h-full justify-center items-center border-l border-slate-300 dark:border-slate-700 bg-slate-50 text-slate-800 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-900 dark:text-slate-400">
                <i class="fas fa-sun text-sm text-primary-500 dark:hidden"></i>
                <i class="fas fa-moon text-sm text-primary-500 hidden dark:block"></i>
            </button>
            <a href="{{ route('wrla.logout') }}" class="flex h-full justify-center items-center text-sm gap-2 px-3 border-l border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-900 text-primary-500">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>

    {{-- Top bar gap --}}
    <div class="block w-full h-9"></div>

    {{-- Content container --}}
    <div class="flex flex-row">
        {{-- Gap --}}
        <div class=" w-14 h-full"></div>

        {{-- Yield content --}}
        <div class="relative w-full flex flex-col pt-8 pb-24 pr-10">
            @if(session('success'))
                @themeComponent('alert', ['type' => 'success', 'message' => session('success')])
            @elseif(session('error'))
                @themeComponent('alert', ['type' => 'error', 'message' => session('error')])
            @endif

            @yield('content')
        </div>
    </div>
</div>
