<x-wrla-modal-layout>
    <div class="flex justify-start items-center gap-3 text-xl">
        <i class="fas fa-file-import"></i>
        <span class="relative">Importing into {{ $manageableModelClass::getDisplayName(true) }}</span>
    </div>
    <hr class="border border-gray-300 mt-[6px] mb-3">

    {!! view($WRLAHelper::getViewPath('components.forms.label'), [
        'label' => '1. Select a .csv file',
        'attributes' => new \Illuminate\View\ComponentAttributeBag([
            'class' => ''
        ])
    ])->render() !!}
</x-wrla-modal-layout>