<div
    x-data="{showSearchField: {{ $valueIsSet ? 'false' : 'true' }} }"
    class="{{ $options['containerClass'] ?? 'w-full flex-1 md:flex-auto' }}"
>
    {{-- Label --}}
    @if(!empty($label))
        {!! view($WRLAHelper::getViewPath('components.forms.label'), [
            'label' => $label,
            'attributes' => new \Illuminate\View\ComponentAttributeBag([
                'class' => 'mb-2 '.($options['labelClass'] ?? '')
            ])
        ])->render() !!}
    @endif

    {{-- Search input --}}
    <div class="w-full" x-show="showSearchField" x-cloak>
        @themeComponent('forms.input-text', [
            'options' => [
                'showError' => false,
            ],
            'attributes' => new \Illuminate\View\ComponentAttributeBag(array_merge($searchAttributes->getAttributes(), [
                'x-ref' => "searchable_value_{$attributes->get('name')}_input",
                'class' => '!bg-slate-100 !placeholder-slate-400',
                'autocomplete' => 'off',
            ])),
        ])
    </div>

    {{-- Display selectable filtered items list --}}
    @if($searchFieldValue != '')
        <div
            x-ref="searchable_value_{{ $attributes->get('name') }}_list"
            class="mt-1 w-full bg-slate-200 border border-slate-300 px-1 max-h-72 overflow-y-auto">
            @forelse($filteredItems as $key => $value)
                <button
                    type="button"
                    wire:click="setFieldValues({
                        '{{ $attributes->get('name') }}': '{{ $key }}',
                        'searchable_value_{{ $attributes->get('name') }}': ''
                    })"
                    x-on:click="
                        showSearchField = false;
                        $refs.searchable_value_{{ $attributes->get('name') }}_list.style.display = 'none';
                    "
                    class="block odd:bg-slate-100 hover:bg-slate-50  hover:border-l-4 border-primary-500 w-full text-left px-2 py-1.5">
                    {{ $value }}
                </button>
            @empty
                <div class="flex gap-2 items-center text-slate-700 px-2 py-1">
                    <i class="fas fa-info-circle text-slate-400"></i>
                    No items found, please expand your search.
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
                setTimeout(() => $refs.searchable_value_{{ $attributes->get('name') }}_input.focus(), 100);
            {{-- Show all mode --}}
            @else
                // Set searchable value to space (This will trigger the search to show all items)
                $wire.set('livewireData.searchable_value_{{ $attributes->get('name') }}', ' ');
                // After wire set above, select the input text
                setTimeout(() => $refs.searchable_value_{{ $attributes->get('name') }}_input.select(), 200);
            @endif
        "
        class="select-none cursor-pointer"
    >
        @if(!$valueIsSet)
            <div class="flex gap-2 items-center mt-1 px-2 py-1 text-slate-800">
                {{-- Negative icon --}}
                <i class="fas fa-exclamation-triangle text-slate-400"></i>
                None selected
            </div>
        @else
            <div
                class="flex items-center gap-2 px-2 py-1 text-slate-800 bg-slate-200 border border-slate-400 rounded-md">
                <i class="fas fa-check-circle text-primary-600"></i>
                <b class="text-medium">{{ $items[$attributes->get('value')] }}</b>
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