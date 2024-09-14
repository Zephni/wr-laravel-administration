{{-- Livewire browse models, a very modern style browse system, includes a search filter and a table with the models data. --}}
<div class="flex flex-col gap-4">

    <div class="flex justify-between items-center">
        <div class="text-xl font-semibold mb-2">
            <i class="{{ $manageableModelClass::getIcon() }} mr-2"></i>
            {{ $manageableModelClass::getDisplayName(true) }}
        </div>
        <div class="text-sm text-slate-500">
            Total: {{ 
                $models instanceof \Illuminate\Pagination\LengthAwarePaginator
                    ? $models?->total()
                    : $models->count()
            }} records
        </div>
    </div>

    @if($successMessage)
        @themeComponent('alert', ['type' => 'success', 'message' => $successMessage])
    @elseif($errorMessage)
        @themeComponent('alert', ['type' => 'error', 'message' => $errorMessage])
    @endif

    {{-- Browse actions --}}
    <div class="flex flex-row gap-3">
        @foreach($manageableModelClass::getBrowseActions() as $browseAction)
            {!! !is_string($browseAction) ? $browseAction->render() : $browseAction !!}
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="w-full rounded-lg px-3 pt-2 pb-3 mb-1 bg-slate-100 shadow-md dark:bg-slate-800">
        <div class="flex justify-start items-stretch gap-4">
            
            @foreach($manageableModelClass::getBrowseFilters() as $filter)
                {!! $filter->render($filters) !!}
            @endforeach

        </div>
    </div>

    <div class="rounded-md overflow-hidden shadow-lg shadow-slate-300 dark:shadow-slate-850">
        <table class="table w-full text-sm bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-300">
            <thead class="border-b bg-slate-700 dark:bg-slate-400 text-slate-100 dark:text-slate-800 border-slate-400 dark:border-slate-600">
                <tr>
                    @foreach($manageableModelClass::make()->withInstanceSetup()->getFinalBrowseColumns() as $column => $browseColumn)
                        @continue($browseColumn === null)
                        <th
                            @if($browseColumn->getOption('allowOrdering'))
                                title="Order by {{ $column }} {{ $orderDirection == 'asc' ? 'descending' : 'ascending' }}"
                            @endif
                            class="text-left px-3 py-2 @if($browseColumn->getOption('allowOrdering')) group hover:text-primary-500 dark:hover:text-slate-700 @endif @if($orderBy == $column) text-primary-500 dark:text-slate-800 @endif" @if($browseColumn->width != null) style="width: {{ is_numeric($browseColumn->width) ? $browseColumn->width.'px' : $browseColumn->width }}" @endif>
                            @if($browseColumn->getOption('allowOrdering'))
                                <button class="flex items-center gap-3 w-full text-left" wire:click="reOrderAction('{{ $column }}', '{{ $orderDirection == 'asc' ? 'desc' : 'asc' }}')">
                                    {{ $browseColumn->renderDisplayName() }}
                                    @if($orderBy == $column)
                                        <i class="relative fas fa-sort-{{ $orderDirection == 'asc' ? 'up' : 'down' }} text-primary-500 dark:text-slate-800"
                                            style="{{ $orderDirection == 'asc' ? 'top: 3px;' : 'top: -3px;' }}"
                                            ></i>
                                    @else
                                        <i class="fas fa-sort text-slate-400 group-hover:text-primary-500 dark:group-hover:text-slate-700" title="Order ascending"></i>
                                    @endif
                                </button>
                            @else
                                <div class="flex items-center gap-3">
                                    {{ $browseColumn->renderDisplayName() }}
                                </div>
                            @endif
                        </th>
                    @endforeach
                    <th></th>
                </tr>                
            </thead>
            <tbody>
                @foreach($models as $model)
                    @php
                        $manageableModel = $manageableModelClass::make($model)->withInstanceSetup();
                    @endphp
                    <tr class="odd:bg-slate-100 dark:odd:bg-slate-700 even:bg-slate-200 dark:even:bg-slate-800">
                        @foreach($manageableModel->getFinalBrowseColumns() as $column => $browseColumn)
                            @continue($browseColumn === null)
                            <td class="px-3 py-2 whitespace-nowrap" @if($browseColumn->width != null) style="width: {{ is_numeric($browseColumn->width) ? $browseColumn->width.'px' : $browseColumn->width }}" @endif>
                                {!! $browseColumn->renderValue($model, $column) !!}
                            </td>
                        @endforeach
                        <td class="px-3 py-2">
                            <div class="flex justify-end gap-2">

                                @foreach($manageableModel->getItemActions() as $browseAction)
                                    {!! $browseAction->render() !!}
                                @endforeach

                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- If empty, show message and link to create new model --}}
    @if($models->isEmpty())
        <div class="flex flex-row gap-4 justify-center items-center mt-6 text-slate-700 dark:text-slate-300">
            @if(!$hasFilters)
                <span>No records exist in this table</span>
            @else
                <span>No records found with the current filters</span>
            @endif

            {{-- Check has create permissions --}}
            @if($manageableModelClass::getPermission(\WebRegulate\LaravelAdministration\Enums\ManageableModelPermissions::CREATE->getString()))
                @themeComponent('forms.button', [
                    'href' => route('wrla.manageable-models.create', ['modelUrlAlias' => $manageableModelClass::getUrlAlias()]),
                    'size' => 'small',
                    'type' => 'button',
                    'text' => 'Create a new ' . $manageableModelClass::getDisplayName(),
                    'icon' => 'fa fa-plus py-2',
                    'class' => 'px-4'
                ])
            @endif
        </div>
    @else
        {{-- Pagination --}}
        <div class="mx-auto p-8 text-center">
            {{ $models->links($WRLAHelper::getViewPath('livewire.pagination.tailwind')) }}
        </div>
    @endif

    @if($WRLAUser->getSetting('debug') == true)
        <div class="border border-slate-300 rounded-md p-2 mt-10 text-slate-500">
            <p class=" text-sm font-semibold">Debug Information:</p>
            <hr class="my-1 border-slate-300">
            {{ $debugMessage }}
        </div>
    @endif

</div>

@push('appendBody')
    
@endpush
