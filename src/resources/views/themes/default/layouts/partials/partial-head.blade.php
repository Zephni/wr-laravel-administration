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
