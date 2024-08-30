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

{{-- Custom JS  --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            // If field exists with "autofocus" attribute, set focus on it
            const autofocusField = document.querySelector('[autofocus]');
            if (autofocusField) {
                autofocusField.focus();
                autofocusField.setSelectionRange(autofocusField.value.length, autofocusField.value.length);
                return;
            }

            // Otherwise set focus on first input if exists
            var inputElement = document.querySelector('input');

            if (inputElement) {
                inputElement.focus();
            }
        }, 100);
    });
</script>

{{-- Font Awesome cdn --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.12.0-2/css/all.min.css" />

{{-- Styles --}}
<style>
    @import url('https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,opsz,wght@0,6..12,200..1000;1,6..12,200..1000&display=swap');
    body { font-feature-settings: normal; font-family: "Nunito Sans", sans-serif; -webkit-font-smoothing: antialiased; }
    [x-cloak] { display: none !important; }
    .wrla_no_image { object-fit: fill !important; }

    /* {{ config('wr-laravel-administration.common_css') }} */
</style>

@stack('styles')
