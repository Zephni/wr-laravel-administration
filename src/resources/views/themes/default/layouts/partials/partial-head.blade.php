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
    window.wrla = {
        instanceAction: {
            parameters: {}
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Livewire browserAlert event
        Livewire.on('browserAlert', (event) => {
            setTimeout(() => {
                alert(event.message);
            }, 1);
        });

        // Livewire redirect event
        Livewire.on('redirect', (event) => {
            setTimeout(() => {
                if (event.url) {
                    window.location.href = event.url;
                }
            }, 1);
        });

        // Auto focus
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

    /**
     * Set button to loading state
     *
     * @param {element} element
     * @param {callback} waitUntil
     */
    window.buttonSignifyLoading = async function(element, waitUntil) {
        // Find the <i> icon element and change to spinner
        let iconElement = element.querySelector('i');

        // Get current icon class
        let currentIconClass = iconElement.classList[1];

        // Disable button
        element.disabled = true;

        // Remove the current icon class and replace with spinner
        iconElement.classList.remove(currentIconClass);
        iconElement.classList.add('fa-spinner');
        iconElement.classList.add('animate-spin');

        // Await the waitUntil promise to be resolved
        await waitUntil();

        // Revert to the original icon
        iconElement.classList.remove('fa-spinner');
        iconElement.classList.remove('animate-spin');
        iconElement.classList.add(currentIconClass);

        // Re-enable the button
        element.disabled = false;
    }

    /**
     * Load livewire modal
     * 
     * @param {element} buttonElement
     * @param {string} modalComponent (without wrla. prefix)
     * @param {object} mountData
     */
    window.loadLivewireModal = function(buttonElement, modalComponent, mountData) {
        window.buttonSignifyLoading(buttonElement, () => new Promise((resolve) => {
            // Open the Livewire modal
            Livewire.dispatch('openModal', {
                component: `wrla.${modalComponent}`,
                arguments: mountData
            });

            // Listen for the Livewire 'modalOpened' event
            Livewire.on(`${modalComponent}.opened`, () => resolve());
        }));
    };

    /**
     * The below is for the wrlaInsertTextAtCursor function which is used to insert text at the cursor position in an input or textarea element.
     * 
     * @param {string} text
     */
    let wrlaLastFocusedElement = null;
    document.addEventListener('focusin', (event) => wrlaLastFocusedElement = event.target);
    window.wrlaInsertTextAtCursor = function(text, callback = null) {
        setTimeout(() => {
            const activeElement = wrlaLastFocusedElement;
            
            if (activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA')) {
                const start = activeElement.selectionStart;
                const end = activeElement.selectionEnd;

                activeElement.value = 
                    activeElement.value.slice(0, start) + 
                    text + 
                    activeElement.value.slice(end);

                // Update the cursor position to be after the inserted text
                activeElement.selectionStart = activeElement.selectionEnd = start + text.length;
                
                // Refocus on the element
                activeElement.focus();

                // Run the callback function if provided
                if (callback) {
                    callback(activeElement);
                    return;
                }

                // If no callback provided, dispatch the input event
                activeElement.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }, 0);
    }
</script>

{{-- Font Awesome cdn --}}
{{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" /> --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" />

{{-- Styles --}}
<style>
    @import url('https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,opsz,wght@0,6..12,200..1000;1,6..12,200..1000&display=swap');
    body { font-feature-settings: normal; font-family: "Nunito Sans", sans-serif; -webkit-font-smoothing: antialiased; }
    [x-cloak] { display: none !important; }
    .wrla_no_image { object-fit: fill !important; }

    /* Scrollbars */
    ::-webkit-scrollbar { width: 10px; height: 10px; }
    ::-webkit-scrollbar-track { background-color: #94a3b833; }
    ::-webkit-scrollbar-thumb { background-color: #94a3b888; border-radius: 6px; }
    ::-webkit-scrollbar-thumb:hover { background-color: #94a3b8BB; }
    ::-webkit-scrollbar-corner { background-color: #94a3b855; }

    {{ config('wr-laravel-administration.common_css') }}
</style>

@stack('styles')
