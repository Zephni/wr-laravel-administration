<x-wrla-modal-layout>
    {{-- Header --}}
    <div class="flex justify-start items-center gap-3 text-xl">
        <i class="fas fa-file-import"></i>
        <span class="relative">Importing into {{ $manageableModelClass::getDisplayName(true) }}</span>
    </div>
    <hr class="border border-gray-300 mt-[6px] mb-3">

    {{-- Form --}}
    <div class="flex flex-col gap-4">
        <div>
            {!! view($WRLAHelper::getViewPath('components.forms.input-file'), [
                'label' => 'Import .csv file',
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

        {{-- If associate with columns data is set, we allow the user to associate the columns with table --}}
        @if(count($data['headersMappedToColumns']) > 0)
            <div class="grid grid-cols-4 gap-4">
                @foreach($data['headers'] as $headerIndex => $header)
                    {!! view($WRLAHelper::getViewPath('components.forms.input-select'), [
                        'label' => $header,
                        'items' => $data['tableColumns'],
                        'options' => [
                            
                        ],
                        'attributes' => new \Illuminate\View\ComponentAttributeBag([
                            'wire:key' => "mapped-header-$headerIndex",
                            'wire:model.live' => "data.headersMappedToColumns.index_$headerIndex",
                        ])
                    ])->render() !!}
                @endforeach
            </div>
        @endif
    </div>

    <br />

    <p class="py-2 font-medium">Debug: Headers mapped to columns</p>
    @foreach($debugInfo['headersMappedToColumns'] ?? [] as $key => $value)
        @dump([$key => $value])
    @endforeach

    <p class="py-2 font-medium">Debug: Headers</p>
    @foreach($debugInfo['headers'] ?? [] as $key => $value)
        @dump([$key => $value])
    @endforeach

    <br />
    <p class="py-2 font-medium">Debug: First 3 rows</p>
    @foreach($debugInfo['rows'] ?? [] as $key => $value)
        @break($loop->index == 3)
        @dump($value)
    @endforeach
</x-wrla-modal-layout>