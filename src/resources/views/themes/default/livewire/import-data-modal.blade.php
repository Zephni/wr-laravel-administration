<x-wrla-modal-layout>
    {{-- Header --}}
    <div class="flex justify-start items-center gap-3 text-xl">
        <i class="fas fa-file-import"></i>
        <span class="relative">Importing into {{ $manageableModelClass::getDisplayName(true) }}</span>
    </div>
    <hr class="border border-gray-300 mt-[6px] mb-3">

    {{-- Form --}}
    <div class="flex flex-col gap-4">
        {{-- Back button to go to previous step --}}
        @if($data['currentStep'] > 1)
            <div>
                <button wire:click="$set('currentStep', {{ $data['currentStep'] - 1 }})" class="text-sm">
                    <i class="fas fa-arrow-left text-sm"></i> Back to step {{ $data['currentStep'] - 1 }}
                </button>
            </div>
        @endif

        @if($data['currentStep'] == 1)
            <div class="text-lg border-b border-slate-400 pb-1">
                <b>1. Import a .csv file</b>
            </div>
            <div>
                {!! view($WRLAHelper::getViewPath('components.forms.input-file'), [
                    'options' => [
                        'notes' => '<b>NOTE:</b> The first row of the file MUST be a list of headers.',
                        'chooseFileText' => 'Select a .csv file to import...',
                    ],
                    'attributes' => new \Illuminate\View\ComponentAttributeBag([
                        'name' => 'file',
                        'wire:model.live' => 'file',
                        'class' => ''
                    ])
                ])->render() !!}
            </div>
        @elseif($data['currentStep'] == 2)
            @if(count($headersMappedToColumns) > 0)
                <div class="text-lg font-thin border-b border-slate-400 pb-1">
                    <b>2. Map .csv headings to table columns</b>
                </div>
                <div class="grid grid-cols-5 gap-4">
                    @foreach($data['headers'] as $headerIndex => $header)
                        <div wire:key="mapped-header-{{ $headerIndex }}">
                            {!! view($WRLAHelper::getViewPath('components.forms.input-select'), [
                                'label' => $header,
                                'items' => $data['tableColumns'],
                                'options' => [
                                    
                                ],
                                'attributes' => new \Illuminate\View\ComponentAttributeBag([
                                    'wire:model.live' => "headersMappedToColumns.index_$headerIndex",
                                ])
                            ])->render() !!}
                        </div>
                    @endforeach
                </div>
            @endif
        @endif

    </div>

    <br />

    <p class="py-2 font-medium">Debug: Headers mapped to columns</p>
    @foreach($headersMappedToColumns ?? [] as $key => $value)
        <p>{{ $key }}: {{ $value }}</p>
    @endforeach

    <p class="py-2 font-medium">Debug: Headers</p>
    @foreach($data['headers'] ?? [] as $key => $value)
        @dump([$key => $value])
    @endforeach

    <br />
    <p class="py-2 font-medium">Debug: First 3 rows</p>
    @foreach($data['rows'] ?? [] as $key => $value)
        @break($loop->index == 3)
        @dump($value)
    @endforeach
</x-wrla-modal-layout>