<div class="relative w-full h-full px-4 py-2">
    <div class="absolute top-[6px] right-3">
        <button wire:click="$dispatch('closeModal')" class="text-gray-400 hover:text-gray-500 focus:outline-none focus:text-gray-500">
            <span class="sr-only">Close</span>
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    {{ $slot }}
</div>