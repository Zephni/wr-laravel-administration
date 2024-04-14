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
</script>

{{-- Font Awesome cdn --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.12.0-2/css/all.min.css" />

{{-- Styles --}}
<style>
    @import url('https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,opsz,wght@0,6..12,200..1000;1,6..12,200..1000&display=swap');
    body { font-feature-settings: normal; font-family: "Nunito Sans", sans-serif; -webkit-font-smoothing: antialiased; }
    [x-cloak] { display: none !important; }
</style>

@stack('styles')
