<x-wrla-modal-layout title="Update WRLA" icon='fa-solid fa-bold'>
    
    {{-- Back button (wire elements modal) --}}
    <div class="flex justify-between items-center mb-4">
        <button wire:click="$dispatch('closeModal')" class="text-gray-500 hover:text-gray-700">
            <i class="fa-solid fa-arrow-left"></i> Back
        </button>
    </div>

    <div class="bg-slate-900 text-slate-200 p-4 rounded-lg mt-4">
        <h3 class="text-lg font-semibold mb-2">Command Output:</h3>
        <div class="w-full max-h-96 overflow-auto">
            <pre class="whitespace-pre-wrap">{{ $consoleOutput }}</pre>
        </div>
        <div class="flex gap-4 mt-4">
            <input wire:model="command" 
                wire:keydown.enter="runCommand"
                class="w-full px-2 py-1 border-t border-gray-300 bg-slate-900 text-white focus:!ring-0"
                placeholder="Input" />
            {{-- <button wire:click="runCommand" class="whitespace-nowrap">✅ Run command</button> --}}
        </div>
    </div>

    <br />

</x-wrla-modal-layout>
