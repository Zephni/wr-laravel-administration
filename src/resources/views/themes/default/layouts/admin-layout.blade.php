<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', '(page title not set)') - WebRegulate Admin</title>

    {{-- Tailwind cdn --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        /* Tailwind custom configuration
        ------------------------------------------------------------*/
        tailwind.config = {
            darkMode: 'class',
            // Theme overrides
            theme: {
                extend: {
                    colors: @php echo json_encode(config('wr-laravel-administration.colors')) @endphp
                }
            }
        };

        /* WR Laravel Administration - Global JavaScript
        ------------------------------------------------------------*/
        document.addEventListener('DOMContentLoaded', () => {

            /* Dark mode toggle
            ------------------------------------------------------------*/
            // First, check if dark mode has ever been set atall
            if (localStorage.getItem('theme-mode') === null) {
                // Set default theme mode based on config
                localStorage.setItem('theme-mode', '{{ config("wr-laravel-administration.default_theme_mode") }}');
            }

            // Check if dark mode is enabled in local storage
            if (localStorage.getItem('theme-mode') === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }

            // Get the toggle button and if exists, add event listener
            const themeModeToggle = document.getElementById('theme-mode-toggle');
            if (themeModeToggle) {
                themeModeToggle.addEventListener('click', () => {
                    // Toggle dark mode class
                    document.documentElement.classList.toggle('dark');

                    // Save dark mode state to local storage
                    if (document.documentElement.classList.contains('dark')) {
                        localStorage.setItem('theme-mode', 'dark');
                    } else {
                        localStorage.setItem('theme-mode', 'light');
                    }
                });
            }
            });
    </script>

    {{-- Font Awesome cdn --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.12.0-2/css/all.min.css" />

    {{-- Styles --}}
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,opsz,wght@0,6..12,200..1000;1,6..12,200..1000&display=swap');
        body { font-feature-settings: normal; font-family: "Nunito Sans", sans-serif; -webkit-font-smoothing: antialiased; }
    </style>

    @stack('styles')
</head>
<body class="h-full">

    <div class="relative flex flex-row w-full h-full items-center bg-slate-200 text-slate-800 dark:bg-slate-900 dark:text-slate-400">
        {{-- Left panel --}}
        <div class="flex flex-col justify-start items-center gap-3 w-80 h-full border-r-2 border-slate-300 dark:border-slate-950 bg-slate-100 dark:bg-slate-850 shadow-xl shadow-slate-500 dark:shadow-slate-950 z-10">
            <div class="w-3/4 py-8">
                <img src="{{ asset(config('wr-laravel-administration.logo.light')) }}" title="Light Logo" alt="Light Logo" class="dark:hidden w-full" />
                <img src="{{ asset(config('wr-laravel-administration.logo.dark')) }}" title="Dark Logo" alt="Dark Logo" class="hidden dark:block w-full" />
            </div>
        </div>

        {{-- Right container --}}
        <div class="flex-1 h-full">
            {{-- Top bar --}}
            <div class="flex flex-row gap-5 h-10 justify-end items-center border-b-2 border-slate-300 dark:border-slate-950 shadow-md dark:shadow-slate-900 bg-slate-100 text-slate-800 dark:bg-slate-850 dark:text-slate-400">
                {{-- Maybe time here? --}}
                <div class="pl-4">

                </div>

                <div class="flex flex-row h-full items-center">
                    <span class="text-sm pr-4">
                        Logged in as
                        <i class="fas fa-user text-xs mx-1"></i>
                        {{ $user->name }}
                    </span>
                    <button id="theme-mode-toggle" class="flex w-[50px] h-full justify-center items-center shadow-md border-l border-slate-300 dark:border-slate-950 shadow-slate-300 dark:shadow-slate-900 bg-slate-50 text-slate-800 dark:bg-slate-800 dark:text-slate-400">
                        <i class="fas fa-sun text-primary-500 dark:hidden"></i>
                        <i class="fas fa-moon text-primary-500 hidden dark:block"></i>
                    </button>
                    <a href="{{ route('wrla.logout') }}" class="flex h-full justify-center items-center gap-2 px-5 shadow-md border-l border-slate-300 dark:border-slate-950 shadow-slate-300 dark:shadow-slate-900 bg-slate-50 dark:bg-slate-800 text-primary-500">
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
    </div>

    @stack('scripts')
</body>
</html>
