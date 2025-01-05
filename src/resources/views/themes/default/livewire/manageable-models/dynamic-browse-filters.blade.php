<div class="flex flex-col gap-1 w-full pt-0.5">
    @themeComponent('forms.label', [
        'label' => 'Filters',
    ])

    @if(count($browseFilters))
        <div class="flex flex-col gap-2 w-full">
            @foreach ($browseFilters as $key => $browseFilter)
                <div class="flex gap-2 w-full">
                    {{-- Column selector --}}
                    @themeComponent('forms.input-select', [
                        'name' => $browseFilter->field->getAttribute('name'),
                        'label' => '',
                        'items' => $tableColumns,
                        'options' => [
                            'containerClass' => '!w-44',
                        ],
                        'attributes' => new \Illuminate\View\ComponentAttributeBag([
                            'wire:model.live.debounce.400ms' =>  "browseFilterInputs.$key.field",
                        ]),
                    ])

                    {{-- Operator selector --}}
                    @themeComponent('forms.input-select', [
                        'name' => $browseFilter->field->getAttribute('name'),
                        'label' => '',
                        'items' => [
                            'like' => 'Contains',
                            'not like' => 'Does not contain',
                            '=' => 'Equal to',
                            '!=' => 'Not equal to',
                            '>' => 'Greater than',
                            '<' => 'Less than',
                            '>=' => 'Greater or equal to',
                            '<=' => 'Less or equal to',
                        ],
                        'options' => [
                            'containerClass' => '!w-44',
                        ],
                        'attributes' => new \Illuminate\View\ComponentAttributeBag([
                            'wire:model.live.debounce.400ms' =>  "browseFilterInputs.$key.operator",
                        ]),
                    ])

                    {{-- Value input --}}
                    {!! $browseFilter->render() !!}

                    {{-- Remove filter button --}}
                    <button type="button" wire:click="removeFilterAction({{ $key }})" class="flex items-center justify-center w-8 h-8 border border-slate-500 text-slate-500 hover:border-rose-500 hover:text-rose-500 rounded-full">
                        <i class="fa fa-times" wire:loading.remove wire:target="removeFilterAction({{ $key }})"></i>
                        <i class="fa fa-spinner fa-spin" wire:loading wire:target="removeFilterAction({{ $key }})"></i>
                    </button>
                </div>
            @endforeach
        </div>
    @endif

    {{-- <div>
        @foreach($browseFilterInputs as $key => $browseFilterInput)
            <div>
                @dump($key, $browseFilterInput)
            </div>
        @endforeach
    </div> --}}

    <div class="mt-2">
        @themeComponent('forms.button', [
            'text' => 'Add filter',
            'icon' => 'fa fa-plus',
            'size' => 'small',
            'type' => 'button',
            'color' => 'primary',
            'attributes' => new \Illuminate\View\ComponentAttributeBag([
                'wire:click' => 'addFilterAction',
            ]),
        ])
    </div>
</div>
