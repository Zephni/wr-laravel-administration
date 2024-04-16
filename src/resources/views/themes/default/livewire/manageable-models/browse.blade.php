{{-- Livewire browse models, a very modern style browse system, includes a search filter and a table with the models data. --}}
<div>

    <div class="w-full rounded-lg p-4 bg-slate-100 shadow-md dark:bg-slate-800">
        <div class="flex justify-between items-center">
            <div class="w-full md:w-2/3">
                @themeComponent('forms.input-text', [
                    'attr' => [
                        'wire:model.live.debounce.400ms' => 'search',
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

    <div class="overflow-x-auto mt-6 text-slate-900 dark:text-slate-100">
        <table class="table w-full">
            <thead class="text-sm border-b border-slate-400 dark:border-slate-600">
                <tr>
                    @foreach($columns as $column => $label)
                        <th class="text-left">{{ $label }}</th>
                    @endforeach
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($models as $model)
                    <tr>
                        @foreach($columns as $column => $label)
                            <td class="py-1">{{ $model->{$column} }}</td>
                        @endforeach
                        <td class="py-1">
                            <div class="flex justify-end">
                                <a href="{{ route('wrla.manageable-model.edit', ['modelUrlAlias' => $manageableModelClass::getUrlAlias(), 'id' => $model->id]) }}"
                                    class="text-primary-500 dark:text-primary-400 hover:text-primary-600 dark:hover:text-primary-500">Edit</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

</div>
