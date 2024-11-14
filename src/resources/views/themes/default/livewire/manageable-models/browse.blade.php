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

            @foreach ($manageableModelClass::getBrowseFilters() as $filter)
                {!! $filter->render($filters) !!}
            @endforeach

        </div>
    </div>

    {{-- Main data table --}}
    @php
        $columns = $manageableModelClass::make()->getBrowseColumnsFinal();
        $columnWidths = collect($columns)
            ->map(function ($browseColumn) {
                if ($browseColumn === null) {
                    return 'auto';
                }

                if($browseColumn->getOption('width') !== null) {
                    $width = $browseColumn->getOption('width');
                    return is_numeric($width) ? $width . 'px' : $width;
                }

                $minWidth = '0px';
                if($browseColumn->getOption('minWidth') !== null) {
                    $minWidth = $browseColumn->getOption('minWidth');
                    $minWidth = is_int($minWidth) ? "{$minWidth}px" : $minWidth;
                }

                $maxWidth = '1fr';
                if($browseColumn->getOption('maxWidth') !== null) {
                    $maxWidth = $browseColumn->getOption('maxWidth');
                    $maxWidth = is_int($maxWidth) ? "{$maxWidth}px" : $maxWidth;
                }

                return "minmax($minWidth, $maxWidth)";
            })
            ->toArray();
        // Append 'auto' for the actions column
        $columnWidths[] = 'auto';
        $gridTemplateColumns = implode(' ', $columnWidths);
    @endphp
    <div class="rounded-md overflow-hidden shadow-lg shadow-slate-300 dark:shadow-slate-850">
        <div class="grid" style="grid-template-columns: {{ $gridTemplateColumns }};">
            <!-- Header -->
            <div class="contents">
                @foreach ($columns as $column => $browseColumn)
                    @continue($browseColumn === null)
                    <div @if ($browseColumn->getOption('allowOrdering')) title="Order by {{ $column }} {{ $orderDirection == 'asc' ? 'descending' : 'ascending' }}" @endif
                        class="text-left whitespace-nowrap px-3 py-2 bg-slate-700 dark:bg-slate-700 text-slate-100 dark:text-slate-300 border-b border-slate-400 dark:border-slate-600 @if ($browseColumn->getOption('allowOrdering')) group hover:text-primary-500 @endif @if ($orderBy == $column) text-primary-500 @endif"
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
                    </div>
                @endforeach
                <!-- Actions Header Placeholder -->
                <div class="px-3 py-2 bg-slate-700 dark:bg-slate-700 border-b border-slate-400 dark:border-slate-600">
                </div>
            </div>

            <!-- Body -->
            @foreach ($models as $k => $model)
                @php
                    $rowLoop = $loop;
                    $manageableModel = $manageableModelClass::make($model);
                @endphp
                {{-- odd:bg-slate-200 dark:odd:bg-slate-900 --}}
                <div class="contents odd:bg-slate-100 dark:odd:bg-slate-800">
                    @foreach ($manageableModel->getBrowseColumnsFinal() as $column => $browseColumn)
                        @continue($browseColumn === null)
                        <div class="grid grid-cols-1 items-center w-full h-full px-3 py-2 whitespace-nowrap bg-inherit">
                            <div class="text-ellipsis truncate text-sm">
                                {!! $browseColumn->renderValue($model, $column) !!}
                            </div>
                        </div>
                    @endforeach
                    <!-- Actions Column -->
                    <div class="grid grid-cols-1 items-center w-full h-full px-3 py-2 bg-inherit">
                        <div class="flex justify-end gap-2 text-sm">
                            @foreach ($manageableModel->getInstanceActionsFinal() as $browseAction)
                                {!! $browseAction->render() !!}
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
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

    @if ($WRLAUser->getSetting('debug') == true)
        <div class="border border-slate-300 rounded-md p-2 mt-10 text-slate-500">
            <p class=" text-sm font-semibold">Debug Information:</p>
            <hr class="my-1 border-slate-300">
            {{ $debugMessage }}
        </div>
    @endif

</div>

@push('appendBody')
@endpush
