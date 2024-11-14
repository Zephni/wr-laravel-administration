<x-wrla-modal-layout>
    {{-- Header --}}
    <div class="flex justify-start items-center gap-3 text-xl">
        <i class="fas fa-file-import"></i>
        <span class="relative">Importing into {{ $manageableModelClass::getDisplayName(true) }}</span>
    </div>
    <hr class="border border-gray-300 mt-[6px] mb-3">

    {{-- Form --}}
    {!! view($WRLAHelper::getViewPath('components.forms.input-file'), [
        'label' => '1. Import .csv file',
        'options' => [
            'notes' => 'Please upload a .csv file to import data into the system.',
        ],
        'attributes' => new \Illuminate\View\ComponentAttributeBag([
            'name' => 'file',
            'wire:model.live' => 'file',
            'class' => ''
        ])
    ])->render() !!}

    @dump($file)
</x-wrla-modal-layout>