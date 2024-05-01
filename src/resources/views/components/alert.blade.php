@props(['type' => 'info', 'message'])

<div x-data="{'show': true}"
    x-show="show"
    class="alert mb-4">
    @if ($type == 'success')
        <div class="flex justify-between items-center bg-green-100 border border-green-400 text-green-700 px-3 py-2 rounded relative" role="alert">
            <div class="flex gap-3 items-center">
                <i class="fas fa-check-circle font-bold"></i>
    @elseif ($type == 'warning')
        <div class="flex justify-between items-center bg-yellow-100 border border-yellow-400 text-yellow-700 px-3 py-2 rounded relative" role="alert">
            <div class="flex gap-3 items-center">
                <i class="fas fa-exclamation-triangle font-bold"></i>
    @elseif ($type == 'error')
        <div class="flex justify-between items-center bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded relative" role="alert">
            <div class="flex gap-3 items-center">
                <i class="fas fa-times-circle font-bold"></i>
    @else
        <div class="flex justify-between items-center bg-blue-100 border border-blue-400 text-blue-700 px-3 py-2 rounded relative" role="alert">
            <div class="flex gap-3 items-center">
                <i class="fas fa-info-circle font-bold"></i>
    @endif

            <span>{!! $message !!}</span>
        </div>

        <div>
            <button type="button" class="text-sm text-inherit focus:outline-none" aria-label="Close" @click="show = false">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    @once
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Because there may be multiple alerts on the same page, we need to use querySelectorAll
                document.querySelectorAll('[data-dismiss="alert"]').forEach(function(element) {
                    element.addEventListener('click', function() {
                        this.closest('.alert').remove();
                    });
                });
            });
        </script>
    @endonce
</div>
