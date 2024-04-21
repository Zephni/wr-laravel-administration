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
                @php
                    // Temporary models array for testing purposes
                    $models = [
                        $models[0], $models[0], $models[0], $models[0], $models[0],
                        $models[0], $models[0], $models[0], $models[0], $models[0],
                        $models[0], $models[0], $models[0], $models[0], $models[0]
                    ];
                @endphp
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
