<div
    x-data="{showSearchField: {{ $valueIsSet ? 'false' : 'true' }} }"
    class="{{ $options['containerClass'] ?? 'w-full flex-1 md:flex-auto' }}"
>
    <div class="flex justify-between">
        {{-- Label --}}
        @if(!empty($label))
            {!! view($WRLAHelper::getViewPath('components.forms.label'), [
                'label' => $label,
                'attributes' => new \Illuminate\View\ComponentAttributeBag([
                    'class' => 'mb-2 '.($options['labelClass'] ?? '')
                ])
            ])->render() !!}
        @endif

        {{-- Cancel button --}}
        @if($attributes->get('required') != true && $valueIsSet)
            <button
                type="button"
                class="relative top-[-2px] flex items-center gap-2 text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-slate-300"
                wire:click.prevent="setFieldValue('{{ $attributes->get('name') }}', null)"
            >
                <i class="fas fa-times-circle text-xs"></i>
                <span class="text-sm">Cancel</span>
            </button>
        @endif
    </div>

    {{-- Search input --}}
    <div class="w-full" x-show="showSearchField" x-cloak>
        @themeComponent('forms.input-text', [
            'options' => [
                'showError' => false,
            ],
            'attributes' => new \Illuminate\View\ComponentAttributeBag(array_merge($searchAttributes->getAttributes(), [
                'x-ref' => "{$attributes->get('name')}_searchable_value_input",
                'class' => '!mt-0 !bg-slate-100 dark:!bg-slate-900 !placeholder-slate-400',
                'autocomplete' => 'off',
                'spellcheck' => 'false',
                'aria-autocomplete' => 'none'
            ])),
        ])
    </div>

    {{-- Display selectable filtered items list --}}
    @if($searchFieldValue != '')
        <div
            x-ref="{{ $attributes->get('name') }}_searchable_value_list"
            class="mt-1 w-full bg-slate-200 dark:bg-slate-800 border border-slate-300 px-1 max-h-72 overflow-y-auto">
            @forelse($filteredItems as $key => $value)
                <button
                    type="button"
                    wire:click="setFieldValues({
                        '{{ $attributes->get('name') }}': '{{ $key }}',
                        '{{ $attributes->get('name') }}_searchable_value': '{{ str_replace("'", "\'", $value) }}',
                        '{{ $attributes->get('name') }}_searchable_value': ''
                    })"
                    x-on:click="
                        showSearchField = false;
                        $refs.{{ $attributes->get('name') }}_searchable_value_list.style.display = 'none';
                    "
                    class="block odd:bg-slate-100 dark:odd:bg-slate-900 hover:border-l-4 border-primary-500 w-full text-left px-2 py-1.5">
                    {{ $value }}
                </button>
            @empty
                <div class="flex gap-2 items-center text-slate-700 px-2 py-1">
                    <i class="fas fa-info-circle text-slate-400"></i>
                    @if(strlen($searchFieldValue) < $options['minChars'])
                        Type at least {{ $options['minChars'] }} characters to search
                    @else
                        No items found, please expand your search.
                    @endif
                </div>
            @endforelse
        </div>
    @endif
    {{-- @dump($searchFieldValue, $attributes->get('value'), $items, $filteredItems) --}}

    {{-- Display current value --}}
    <div x-on:click="
            showSearchField = true;
            {{-- Standard mode --}}
            @if(!$searchModeHas_SHOW_ALL)
                setTimeout(() => $refs.{{ $attributes->get('name') }}_searchable_value_input.focus(), 100);
            {{-- Show all mode --}}
            @else
                // Set searchable value to space (This will trigger the search to show all items)
                $wire.set('livewireData.{{ $attributes->get('name') }}_searchable_value', ' ');
                // After wire set above, select the input text
                setTimeout(() => $refs.{{ $attributes->get('name') }}_searchable_value_input.select(), 200);
            @endif
        "
        class="select-none cursor-pointer"
    >
        @if($attributes->get('required') == true && !$valueIsSet)
            <div class="flex gap-2 items-center mt-1 px-2 py-1 text-slate-800">
                {{-- Negative icon --}}
                <i class="fas fa-exclamation-triangle text-slate-400"></i>
                None selected
            </div>
        @else
            <div
                class="flex items-center gap-2 px-2 py-1 text-slate-800 dark:text-slate-100 bg-slate-200 dark:bg-slate-900 border border-slate-400 rounded-md">
                <i class="fas fa-check-circle text-primary-600"></i>
                <b class="text-medium">{{ $selectedValueText }}</b>
            </div>
            @endif
        {{-- Actual value here to submit to form below --}}
        <input {{ $attributes->merge() }}>
    </div>

    {{-- Field notes (if options has notes key) --}}
    @if(!empty($options['notes']))
        @themeComponent('forms.field-notes', ['notes' => $options['notes']])
    @endif

    @error($attributes->get('name'))
        @themeComponent('alert', ['type' => 'error', 'message' => $message, 'class' => 'mt-2'])
    @enderror
</div>