<x-wrla-modal-layout>
    {{-- Header --}}
    <div class="flex justify-start items-center gap-3 text-xl">
        <i class="{{ $manageableModelClass::getIcon() }}"></i>
        <span class="relative">Importing into {{ $manageableModelClass::getDisplayName(true) }}</span>
    </div>
    <hr class="border border-gray-300 mt-[6px] mb-3">

    {{-- Form --}}
    <div class="flex flex-col gap-4">
        {{-- Back button to go to previous step --}}
        @if($data['currentStep'] > 1)
            <div>
                <button wire:click="goToStep({{ $data['currentStep'] - 1 }})" class="text-sm">
                    <div wire:loading.remove>
                        <i class="fas fa-arrow-left text-sm"></i> Back to step {{ $data['currentStep'] - 1 }}
                    </div>
                    <div wire:loading>
                        <i class="fas fa-spinner fa-spin pr-2"></i> Please wait...
                    </div>
                </button>
            </div>
        @endif

        {{-- STEP 1 - Import a .csv file --}}
        @if($data['currentStep'] == 1)

            <div class="text-lg border-b border-slate-400 pb-1">
                <b>
                    <i class="fas fa-file-import text-slate-600 dark:text-slate-300 pr-1"></i>
                    1. Import a .csv file
                </b>
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

        {{-- STEP 2 - Map .csv headings to table columns --}}
        @elseif($data['currentStep'] == 2)

            <div class="text-lg font-thin border-b border-slate-400 pb-1">
                <b>
                    <i class="fas fa-map text-slate-600 dark:text-slate-300 pr-1"></i>
                    2. Map .csv headings to table columns
                </b>
            </div>

            @if(count($headersMappedToColumns) > 0)
                {{-- Map headings to table columns --}}
                <div class="grid grid-cols-5 gap-4">
                    @foreach($data['headers'] as $headerIndex => $header)
                        <div wire:key="mapped-header-{{ $headerIndex }}">
                            {!! view($WRLAHelper::getViewPath('components.forms.input-select'), [
                                'label' => $header,
                                'items' => $data['tableColumns'],
                                'options' => [
                                    
                                ],
                                'attributes' => new \Illuminate\View\ComponentAttributeBag([
                                    'wire:model.live' => "headersMappedToColumns.$headerIndex",
                                ])
                            ])->render() !!}
                        </div>
                    @endforeach
                </div>

                <div class="text-lg font-thin border-b border-slate-400 pb-1">
                    <b>Check data is aligned with correct columns</b>
                </div>

                {{-- Example data (first 3 rows) --}}
                <div class="w-full overflow-x-auto">
                    <table class="w-full mt-4 border border-gray-300">
                        <thead>
                            <tr>
                                @foreach($headersMappedToColumns as $columnIndex => $tableColumn)
                                    <th class="px-2 py-1 border border-gray-300 bg-gray-100 dark:bg-slate-800 dark:border-slate-600 whitespace-nowrap">
                                        {{ $tableColumn }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(array_slice($data['rows'], 0, 5) as $rowIndex => $row)
                                <tr>
                                    @foreach($row as $columnIndex => $cell)
                                        <td class="px-2 py-1 border border-gray-300 dark:border-slate-600 whitespace-nowrap">
                                            {{ $cell }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-sm text-gray-500 text-center">
                    <i class="fas fa-info-circle"></i>
                    No headers found in the .csv file.
                </div>
            @endif

        @endif

    </div>

    <br />
</x-wrla-modal-layout>