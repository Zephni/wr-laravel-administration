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
                    colors: {
                        primary: {
                            500: '#00BFA6',
                        }
                    },
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
