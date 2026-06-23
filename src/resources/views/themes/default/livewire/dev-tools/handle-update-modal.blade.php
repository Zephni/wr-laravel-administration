<x-wrla-modal-layout title="Update WRLA" icon='fa-solid fa-bolt'>
    
    {{-- Back button (wire elements modal) --}}
    <div class="flex justify-between items-center mb-4">
        <button wire:click="$dispatch('closeModal')" class="text-gray-500 hover:text-gray-700">
            <i class="fa-solid fa-arrow-left"></i> Back
        </button>
    </div>

    {{-- While a live update is running, poll for the latest console output --}}
    @if($running)
        <div wire:poll.1000ms="pollOutput"></div>
    @endif

    <div class="bg-slate-900 text-slate-200 p-4 rounded-lg mt-4">
        <h3 class="text-lg font-semibold mb-2 flex items-center gap-2">
            Console Output:
            @if($running)
                <span class="text-amber-400 text-sm font-normal"><i class="fa-solid fa-hourglass fa-spin"></i> running...</span>
            @endif
        </h3>
        <div x-data="{
                scrollToBottom() {
                    this.$el.scrollTop = this.$el.scrollHeight;
                }
            }"
            x-init="
                scrollToBottom();
                new MutationObserver(() => scrollToBottom()).observe($el, { childList: true, subtree: true, characterData: true });
            "
            class="w-full max-h-96 overflow-auto">
            <pre class="whitespace-pre-wrap">{{ $consoleOutput }}</pre>
        </div>
        <div class="flex gap-4 mt-4">
            <button wire:click="runCommand" wire:loading.attr="disabled" wire:target="runCommand"
                @disabled($running) class="whitespace-nowrap disabled:opacity-50">
                <span wire:loading.remove wire:target="runCommand">@if($running)<i class="fa-solid fa-hourglass fa-spin"></i> Running...@else✅ Run updates@endif</span>
                <span wire:loading wire:target="runCommand"><i class="fa-solid fa-hourglass fa-spin inline-block"></i> Starting...</span>
            </button>
        </div>
    </div>

    <br />

</x-wrla-modal-layout>
