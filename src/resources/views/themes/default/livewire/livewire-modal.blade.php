<div>
    @if($show)
        <div
            wire:transition
            class="fixed flex justify-center items-center top-0 left-0 w-full h-full"
            style="background: rgba(0, 0, 0, 0.25); z-index: 100;">

            {{-- Close area --}}
            <div wire:click="close" class="fixed top-0 left-0 w-full h-full" style="z-index: 101;"></div>

            {{-- Modal --}}
            <div class="w-full m-4 md:w-2/3 lg:w-5/12 bg-white rounded-lg shadow-lg p-3" style="z-index: 102;">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold">Modal Title</h2>
                    <button wire:click="close" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="mt-4">
                    {{ $slot ?? 'No slot passed' }}
                </div>

                <div class="mt-6 flex justify-end">
                    @themeComponent('forms.button', [
                        'text' => 'Cancel',
                        'color' => 'grey',
                        'size' => 'small',
                        'type' => 'button',
                        'attributes' => new \Illuminate\View\ComponentAttributeBag([
                            'wire:click' => 'close'
                        ])
                    ])
                </div>
            
        </div>
    @endif
</div>
