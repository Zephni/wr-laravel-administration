<x-wrla-modal-layout>
    {{-- Header --}}
    <div class="flex justify-start items-center gap-3 text-xl">
        <i class="{{ $manageableModelClass::getIcon() }}"></i>
        <span class="relative">Importing into {{ $manageableModelClass::getDisplayName(true) }}</span>
    </div>
    <hr class="border border-gray-300 mt-[6px] mb-3">

    {{-- Form --}}
    <div class="flex flex-col gap-2">
        {{-- Back button to go to previous step --}}
        @if(is_int($data['currentStep']) && $data['currentStep'] > 1)
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

            <div class="text-lg border-b border-slate-400 pb-1 mb-1">
                <b>
                    <i class="fas fa-file-import text-slate-600 dark:text-slate-300 pr-1"></i>
                    1. Import a .csv file
                </b>
            </div>
            <div>
                @themeComponent('forms.input-file', [
                    'options' => [
                        'notes' => '<b>NOTE:</b> The first row of the file MUST be a list of headers.',
                        'chooseFileText' => 'Select a .csv file to import...',
                    ],
                    'attributes' => Arr::toAttributeBag([
                        'name' => 'file',
                        'wire:model.live' => 'file',
                        'class' => ''
                    ])
                ])
            </div>

        {{-- STEP 2 - Map .csv headings to table columns --}}
        @elseif($data['currentStep'] == 2)

            <div class="text-lg font-thin border-b border-slate-400 pb-1 mt-2 mb-2">
                <b>
                    <i class="fas fa-map text-slate-600 dark:text-slate-300 pr-1"></i>
                    2. Map .csv headings to table columns
                </b>
            </div>

            @if(count($headersMappedToColumns) > 0)
                {{-- Map headings to table columns --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-4">
                    @foreach($data['origionalHeaders'] as $headerIndex => $header)
                        <div wire:key="mapped-header-{{ $headerIndex }}">
                            @themeComponent('forms.input-select', [
                                'label' => $header,
                                'items' => $data['tableColumns'],
                                'options' => [

                                ],
                                'attributes' => Arr::toAttributeBag([
                                    'wire:model.live' => "headersMappedToColumns.$headerIndex",
                                    'class' => 'px-1 py-0.5 '.($headersMappedToColumns[$headerIndex] == null ? '!border-rose-500' : '!border-emerald-500')
                                ])
                            ])
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-between text-lg font-thin border-b border-slate-400 pb-1 mt-4 mb-2">
                    <b>
                        <i class="fas fa-eye text-slate-600 dark:text-slate-300 pr-1"></i>
                        Check data is aligned with correct columns.
                    </b>

                    <div class="flex gap-4 text-base">
                        <span>Columns: <b>{{ count($data['headers']) }}</b></span>
                        <span>Rows: <b>{{ $data['totalRows'] }}</b></span>
                    </div>
                </div>

                {{-- Example data (first 3 rows) --}}
                <div class="w-full max-h-96 overflow-auto">
                    <table class="w-full border border-gray-300 text-xs">
                        <thead>
                            <tr>
                                @foreach($headersMappedToColumns as $columnIndex => $tableColumn)
                                    @continue(empty($headersMappedToColumns[$columnIndex]))
                                    <th class="px-2 py-1 border border-gray-300 bg-gray-100 dark:bg-slate-800 dark:border-slate-600 whitespace-nowrap">
                                        {{ $tableColumn }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($previewRows as $rowIndex => $row)
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

                @if((int)$data['totalRows'] > $data['previewRowsMax'])
                    <div class="text-sm text-slate-600 text-center mt-2">
                        <i class="fas fa-info-circle "></i>
                        Showing first {{ $data['previewRowsMax'] }} rows of data.
                    </div>
                @endif
            @else
                <div class="text-sm text-gray-500 text-center">
                    <i class="fas fa-info-circle"></i>
                    No headers found in the .csv file.
                </div>
            @endif

            {{-- Import button --}}
            <div class="flex justify-end mt-4">
                @themeComponent('forms.button', [
                    'text' => '
                        <span wire:loading.remove wire:target="importData">Import '.$data['totalRows'].' rows into '.$manageableModelClass::getDisplayName(true).'</span>
                        <span wire:loading wire:target="importData">Importing '.$data['totalRows'].', please wait...</span>
                    ',
                    'icon' => 'fas fa-file-import',
                    'size' => 'medium',
                    'attributes' => Arr::toAttributeBag([
                        'wire:loading.attr' => 'disabled',
                        'wire:loading.class' => 'opacity-70 cursor-not-allowed',
                        'wire:click' => 'importData',
                    ])
                ])
            </div>

        @elseif($data['currentStep'] == 'processing')

            <div class="text-lg font-thin border-b border-slate-400 pb-1 mt-2 mb-2">
                <b>
                    <i class="fas fa-spinner fa-spin text-slate-600 pr-1"></i>
                    Processing import data
                </b>
            </div>

            {{-- Successfull imports --}}
            <div class="text-base text-slate-600 text-center">
                <i class="fas fa-info-circle text-slate-500 pr-1"></i>
                <b class="text-emerald-500 text-lg">{{ $data['successfullImports'] }}</b> rows of data have been imported into {{ $manageableModelClass::getDisplayName(true) }}.
            </div>

            {{-- Failed imports --}}
            <div class="text-base text-slate-600 text-center mt-2">
                <i class="fas fa-info-circle text-slate-500 pr-1"></i>
                <b class="text-rose-500 text-lg">{{ $data['failedImports'] }}</b> rows of data failed to import.

                @if(count($data['failedReasons']) > 0)
                    @foreach($data['failedReasons'] as $reason)
                        <div class="text-sm text-rose-500">
                            <i class="fas fa-exclamation-triangle text-rose-500 pr-1"></i>
                            {{ $reason }}
                        </div>
                    @endforeach
                @endif
            </div>

        @elseif($data['currentStep'] == 'completed')

            <div class="text-lg font-thin border-b border-slate-400 pb-1 mt-2 mb-2">
                <b>
                    <i class="fas fa-check text-emerald-500 pr-1"></i>
                    Import completed
                </b>
            </div>

            {{-- Successfull imports --}}
            <div class="text-base text-slate-600 text-center">
                <i class="fas fa-info-circle text-slate-500 pr-1"></i>
                <b class="text-emerald-500 text-lg">{{ $data['successfullImports'] }}</b> rows of data have been imported into {{ $manageableModelClass::getDisplayName(true) }}.
            </div>

            {{-- Failed imports --}}
            <div class="text-base text-slate-600 text-center mt-2">
                <i class="fas fa-info-circle text-slate-500 pr-1"></i>
                <b class="text-rose-500 text-lg">{{ $data['failedImports'] }}</b> rows of data failed to import.

                @if(count($data['failedReasons']) > 0)
                    @foreach($data['failedReasons'] as $reason)
                        <div class="text-sm text-rose-500">
                            <i class="fas fa-exclamation-triangle text-rose-500 pr-1"></i>
                            {{ $reason }}
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Close and refresh button --}}
            <div class="flex justify-end mt-4">
                @themeComponent('forms.button', [
                    'text' => 'Close and refresh',
                    'icon' => 'fas fa-times',
                    'size' => 'medium',
                    'color' => 'secondary',
                    'attributes' => Arr::toAttributeBag([
                        'wire:click' => 'closeAndRefresh',
                        'wire:loading.attr' => 'disabled',
                        'wire:loading.class' => 'opacity-70 cursor-not-allowed',
                    ])
                ])
            </div>

        @endif

    </div>

    <br />
</x-wrla-modal-layout>
