<div class="flex flex-col gap-2 w-full pt-0.5">
    @if(count($browseFilters))
        <div class="flex flex-col gap-2 w-full">
            @foreach ($browseFilters as $key => $browseFilter)
                <div class="flex gap-2 w-full">
                    {{-- Column selector --}}
                    @themeComponent('forms.input-select', [
                        'name' => $browseFilter->field->getAttribute('name'),
                        'label' => $browseFilter->field::getLabelFromFieldName($browseFilter->field->getAttribute('name')),
                        'items' => $tableColumns,
                        'options' => [
                            'containerClass' => '!w-48',
                        ],
                        'attributes' => new \Illuminate\View\ComponentAttributeBag([
                            'wire:model.live.debounce.400ms' =>  "browseFilterInputs.$key.field",
                        ]),
                    ])

                    {{-- Operator selector --}}
                    @themeComponent('forms.input-select', [
                        'name' => $browseFilter->field->getAttribute('name'),
                        'label' => '&nbsp;',
                        'items' => [
                            'like' => 'Contains',
                            'not like' => 'Does not contain',
                            '=' => 'Exactly equal to',
                            '!=' => 'Not equal to',
                            '>' => 'Greater than',
                            '<' => 'Less than',
                        ],
                        'options' => [
                            'containerClass' => '!w-48',
                        ],
                        'attributes' => new \Illuminate\View\ComponentAttributeBag([
                            'wire:model.live.debounce.400ms' =>  "browseFilterInputs.$key.operator",
                        ]),
                    ])

                    {{-- Value input --}}
                    {{-- NOTE: We may want to pass the existing filters through here by just getting the values of all the browseFilters --}}
                    {{-- {!! $browseFilter->render($filters) !!} --}}
                    {!! $browseFilter->render([]) !!}
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

    <div class="mt-3">
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