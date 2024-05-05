{{-- Livewire browse models, a very modern style browse system, includes a search filter and a table with the models data. --}}
<div class="flex flex-col gap-4">

    <div class="text-xl font-semibold mb-2">
        Browsing {{ $manageableModelClass::getDisplayName()->plural() }}
    </div>

    @if($successMessage)
        @themeComponent('alert', ['type' => 'success', 'message' => $successMessage])
    @elseif($errorMessage)
        @themeComponent('alert', ['type' => 'error', 'message' => $errorMessage])
    @endif

    <div class="flex justify-start gap-4">
        @foreach($manageableModelClass::getBrowseActions() as $browseAction)
            {!! $browseAction->render() !!}
        @endforeach
    </div>

    <div class="w-full rounded-lg p-4 pt-3 mb-1 bg-slate-100 shadow-md dark:bg-slate-800">
        <div class="flex items-stretch gap-6">
            <div class="w-full md:w-7/12">
                {{-- Search input --}}
                @themeComponent('forms.input-text', [
                    'attr' => [
                        'wire:model.live.debounce.400ms' => 'filters.search',
                        'placeholder' => 'Search filter...'
                    ],
                    // 'label' => 'Filters',
                    'type' => 'text',
                    'name' => 'search',
                    'value' => old('search'),
                    'error' => $errors->first('search'),
                    'autofocus' => true
                ])
            </div>
            <div class="flex-1 flex justify-end items-end gap-6 pb-2">

                {{-- Show admin only checkbox --}}
                @themeComponent('forms.input-checkbox', [
                    'attr' => [
                        'wire:model.live' => 'filters.showAdminOnly'
                    ],
                    'label' => 'Show admins only',
                    'name' => 'showAdminOnly',
                    'checked' => $filters['showAdminOnly'],
                    'error' => $errors->first('showAdminOnly'),
                ])

                {{-- Show soft deleted checkbox --}}
                @if($manageableModelClass::isSoftDeletable())
                    @themeComponent('forms.input-checkbox', [
                        'attr' => [
                            'wire:model.live' => 'filters.showSoftDeleted'
                        ],
                        'label' => 'Show soft deleted',
                        'name' => 'showSoftDeleted',
                        'checked' => $filters['showSoftDeleted'],
                        'error' => $errors->first('showSoftDeleted'),
                    ])
                @endif

            </div>
        </div>
    </div>

    <div class="rounded-md overflow-hidden shadow-lg shadow-slate-300 dark:shadow-slate-850">
        <table class="table w-full text-sm bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-300">
            <thead class="border-b bg-slate-700 dark:bg-slate-400 text-slate-100 dark:text-slate-800 border-slate-400 dark:border-slate-600">
                <tr>
                    @foreach($columns as $column => $label)
                        <th class="text-left px-3 py-2">{{ $label }}</th>
                    @endforeach
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($models as $model)
                    <tr class="odd:bg-slate-100 dark:odd:bg-slate-700 even:bg-slate-200 dark:even:bg-slate-800">
                        @foreach($columns as $column => $label)
                            <td class="px-3 py-2">{{ $model->{$column} }}</td>
                        @endforeach
                        <td class="px-3 py-2">
                            <div class="flex justify-end gap-2">

                                @foreach($manageableModelClass::getBrowseItemActions($model) as $browseAction)
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

            @if($manageableModelClass->hasPehasPermission($WRLAPermissions::CREATE))
                @themeComponent('forms.button', [
                    'size' => 'small',
                    'type' => 'button',
                    'text' => 'Create a new ' . $manageableModelClass::getDisplayName(),
                    'icon' => 'fa fa-plus py-2',
                    'class' => 'px-4',
                    'attr' => [
                        'href' => route('wrla.manageable-model.create', ['modelUrlAlias' => $manageableModelClass::getUrlAlias()]),
                    ]
                ])
            @endif
        </div>
    @else
        {{-- Pagination --}}
        <div class="mx-auto p-8 text-center">
            {{ $models->links($WRLAHelper::getViewPath('livewire.pagination.tailwind')) }}
        </div>
    @endif

</div>
