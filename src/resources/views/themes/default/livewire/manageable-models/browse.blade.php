{{-- Livewire browse models, a very modern style browse system, includes a search filter and a table with the models data. --}}
<div class="flex flex-col gap-4">

    <div class="flex justify-between items-center">
        <div class="text-xl font-semibold mb-2">
            <i class="{{ $manageableModelClass::getIcon() }} mr-2"></i>
            {{ $manageableModelClass::getDisplayName(true) }}
        </div>
        <div class="text-sm text-slate-500">
            Total:
            {{ $models instanceof \Illuminate\Pagination\LengthAwarePaginator ? $models?->total() : $models->count() }}
            records
        </div>
    </div>

    @if ($successMessage)
        @themeComponent('alert', ['type' => 'success', 'message' => $successMessage])
    @elseif($errorMessage)
        @themeComponent('alert', ['type' => 'error', 'message' => $errorMessage])
    @endif

    {{-- Browse actions --}}
    <div class="flex flex-row gap-3">
        @foreach ($manageableModelClass::getBrowseActions() as $browseAction)
            {!! !is_string($browseAction) ? $browseAction->render() : $browseAction !!}
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="w-full rounded-lg px-3 pt-2 pb-3 mb-1 bg-slate-100 shadow-md dark:bg-slate-800">
        <div class="flex flex-wrap justify-start items-stretch gap-x-4 gap-y-2">

            @if(!$manageableModelClass::getStaticOption($manageableModelClass, 'browse.useDynamicFilters'))
                @foreach ($manageableModelClass::getBrowseFilters() as $filter)
                    {!! $filter->render($filters) !!}
                @endforeach
            @else
                @livewire(
                    'wrla.manageable-models.dynamic-browse-filters',
                    [
                        'manageableModelClass' => $manageableModelClass,
                    ]
                )
            @endif

        </div>
    </div>

    @php
        $browseColumns = $manageableModelClass::make()->getBrowseColumnsFinal();
    @endphp

    {{-- Main data table --}}
    <div class="w-full block overflow-x-auto rounded-md shadow-lg shadow-slate-300 dark:shadow-slate-850">
        <table class="w-full table-auto text-left border-collapse" style="table-layout: auto /* fixed */;">
            <colgroup>
                @foreach ($browseColumns as $column => $browseColumn)
                    @if($browseColumn === null)
                        <col style="width: auto;" />
                    @else
                        @php
                            $width = $browseColumn->getOption('width') ?? 'auto';
                            $width = (is_int($width) ? "{$width}px" : $width) ?? 'auto';
                            $minWidth = $browseColumn->getOption('minWidth') ?? 0;
                            $minWidth = (is_int($minWidth) ? "{$minWidth}px" : $minWidth) ?? 'auto';
                            $maxWidth = $browseColumn->getOption('maxWidth');
                            $maxWidth = (is_int($maxWidth) ? "{$maxWidth}px" : $maxWidth) ?? 'none';
                        @endphp
                        <col style="width: {{ $width }}; min-width: {{ $minWidth }}; max-width: {{ $maxWidth }};" />
                    @endif
                @endforeach
                <col style="width: auto;" />
            </colgroup>
            <thead>
                <tr>
                    @foreach ($browseColumns as $column => $browseColumn)
                        @continue($browseColumn === null)
                        <th @if ($browseColumn->getOption('allowOrdering'))
                                title="Order by {{ $column }} {{ $orderDirection == 'asc' ? 'descending' : 'ascending' }}"
                            @endif
                            class="px-3 py-2 bg-slate-700 dark:bg-slate-700 text-slate-100 dark:text-slate-300 border-b border-slate-400 dark:border-slate-600 @if ($browseColumn->getOption('allowOrdering')) group hover:text-primary-500 @endif @if ($orderBy == $column) text-primary-500 @endif"
                            scope="col"
                        >
                            <div class="w-full text-ellipsis truncate text-sm font-bold">
                                @if ($browseColumn->getOption('allowOrdering'))
                                    <button class="flex items-center gap-3 w-full text-left text-ellipsis truncate"
                                        wire:click="reOrderAction('{{ $column }}', '{{ $orderDirection == 'asc' ? 'desc' : 'asc' }}')">
                                        {{ $browseColumn->renderDisplayName() }}
                                        @if ($orderBy == $column)
                                            <i class="relative fas fa-sort-{{ $orderDirection == 'asc' ? 'up' : 'down' }} text-primary-500"
                                                style="{{ $orderDirection == 'asc' ? 'top: 3px;' : 'top: -3px;' }}"></i>
                                        @else
                                            <i class="fas fa-sort text-slate-400 group-hover:text-primary-500 dark:group-hover:text-slate-700"
                                                title="Order ascending"></i>
                                        @endif
                                    </button>
                                @else
                                    <div class="flex items-center gap-3">
                                        {{ $browseColumn->renderDisplayName() }}
                                    </div>
                                @endif
                            </div>
                        </th>
                    @endforeach
                    <th class="px-3 py-2 bg-slate-700 dark:bg-slate-700 border-b border-slate-400 dark:border-slate-600"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($models as $k => $model)
                    @php
                        $manageableModel = $manageableModelClass::make($model);
                    @endphp
                    <tr class="odd:bg-slate-100 dark:odd:bg-slate-800">
                        @foreach ($manageableModel->getBrowseColumnsFinal() as $column => $browseColumn)
                            @continue($browseColumn === null)
                            @php
                                $isHTML = $browseColumn->getOption('renderHtml') ?? false;
                                $value = $browseColumn->renderValue($model, $column);
                                $value = !$isHTML ? str($value)->limit(300) : $value;
                            @endphp
                            <td class="px-3 py-2 bg-inherit text-sm">
                                <div class="relative flex w-full @if(!$isHTML) h-[22px] @endif items-center overflow-hidden">
                                    @if(!$isHTML)
                                        <span style="color: transparent;">{!! $value !!}</span>
                                        <div class="absolute top-0 left-0 w-full h-full whitespace-nowrap overflow-ellipsis truncate">
                                            {!! $value !!}
                                        </div>
                                    @else
                                        {!! $value !!}
                                    @endif
                                </div>
                            </td>
                        @endforeach
                        <td class="px-3 py-2 bg-inherit">
                            <div class="flex justify-end gap-2 text-sm">
                                @foreach ($manageableModel->getInstanceActionsFinal() as $browseAction)
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
    @if ($models->isEmpty())
        <div class="flex flex-row gap-4 justify-center items-center mt-6 text-slate-700 dark:text-slate-300">
            @if (!$hasFilters)
                <span>No records exist in this table</span>
            @else
                <span>No records found with the current filters</span>
            @endif

            {{-- Check has create permissions --}}
            @if ($manageableModelClass::getPermission(\WebRegulate\LaravelAdministration\Enums\ManageableModelPermissions::CREATE))
                @themeComponent('forms.button', [
                    'href' => route('wrla.manageable-models.create', ['modelUrlAlias' => $manageableModelClass::getUrlAlias()]),
                    'size' => 'small',
                    'type' => 'button',
                    'text' => 'Create a new ' . $manageableModelClass::getDisplayName(),
                    'icon' => 'fa fa-plus py-2',
                    'class' => 'px-4',
                ])
            @endif
        </div>
    @else
        {{-- Pagination --}}
        <div class="mx-auto p-8 text-center">
            {{ $models->links($WRLAHelper::getViewPath('livewire.pagination.tailwind')) }}
        </div>
    @endif

    @if ($WRLAUserData->getSetting('debug') == true)
        <div class="flex-1 border border-slate-300 rounded-md p-2 mt-10 text-slate-500 overflow-auto">
            <p class=" text-sm font-semibold">Debug Information:</p>
            <hr class="my-1 border-slate-300">
            {{ $debugMessage }}
        </div>
        {{-- @foreach($dynamicFilterInputs as $key => $browseFilterInput)
            <div>
                @dump($key, $browseFilterInput)
            </div>
        @endforeach --}}
    @endif

</div>

@push('appendBody')
@endpush
