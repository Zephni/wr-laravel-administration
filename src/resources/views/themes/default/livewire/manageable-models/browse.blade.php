{{-- Livewire browse models, a very modern style browse system, includes a search filter and a table with the models data. --}}
<div>

    <div class="w-full rounded-lg p-4 bg-slate-100 shadow-md dark:bg-slate-800">
        <div class="flex justify-between items-center">
            <div class="w-full md:w-2/3">
                @themeComponent('forms.input-text', [
                    'attr' => [
                        'wire:model.live.debounce.400ms' => 'search',
                        'placeholder' => 'Search filter...'
                    ],
                    'label' => 'Search',
                    'type' => 'text',
                    'name' => 'search',
                    'value' => old('search'),
                    'error' => $errors->first('search'),
                    'autofocus' => true
                ])
            </div>
        </div>
    </div>

    <div class="rounded-md overflow-hidden mt-6 shadow-lg shadow-slate-300 dark:shadow-slate-950">
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
                            <div class="flex justify-end">
                                @themeComponent('forms.anchor', [
                                    'size' => 'small',
                                    'type' => 'button',
                                    'text' => 'Edit',
                                    'icon' => 'fa fa-edit !mr-0 py-1',
                                    'attr' => [
                                        'href' => route('wrla.manageable-model.edit', ['modelUrlAlias' => $manageableModelClass::getUrlAlias(), 'id' => $model->id]),
                                        'title' => 'Edit',
                                    ]
                                ])
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- If empty, show message and link to create new model --}}
    @if($models->isEmpty())
        <div class="flex flex-row gap-4 justify-center items-center mt-10 text-slate-700 dark:text-slate-300">
            @if(empty($search))
                <span>No records exist in this table</span>
            @else
                <span>No records found, try expanding your search or </span>
            @endif
            @themeComponent('forms.anchor', [
                'size' => 'normal',
                'type' => 'button',
                'text' => 'Create a new ' . $manageableModelClass::getDisplayName() .' record',
                'icon' => 'fa fa-plus py-2',
                'class' => 'px-4',
                'attr' => [
                    'href' => route('wrla.manageable-model.create', ['modelUrlAlias' => $manageableModelClass::getUrlAlias()]),
                ]
            ])
        </div>
    @else
        {{-- Pagination --}}
        <div class="mx-auto p-8 text-center">
            {{ $models->links($WRLAHelper::getViewPath('livewire.pagination.tailwind')) }}
        </div>
    @endif

</div>