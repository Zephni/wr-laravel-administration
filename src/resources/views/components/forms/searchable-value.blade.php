<div
    x-data="{showSearchField: true}"
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
            'options' => [],
            'attributes' => new \Illuminate\View\ComponentAttributeBag(array_merge($searchAttributes->getAttributes(), [
                'x-ref' => "searchable_value_{$attributes->get('name')}_input",
                'class' => '!bg-slate-100 !placeholder-slate-400',
                'autocomplete' => 'off',
            ])),
        ])
    </div>

    {{-- Display selectable filtered items list --}}
    @if(count($filteredItems) > 0)
        <div class="mt-1 w-full bg-slate-200 border border-slate-300 px-1 max-h-72 overflow-y-auto" x-show="showSearchField" x-cloak>
            @foreach($filteredItems as $key => $value)
                <button
                    type="button"
                    wire:click="setFieldValues({
                        '{{ $attributes->get('name') }}': '{{ $key }}',
                        'searchable_value_{{ $attributes->get('name') }}': ''
                    })"
                    x-on:click="showSearchField = false"
                    class="block odd:bg-slate-100 hover:bg-slate-50  hover:border-l-4 border-primary-500 w-full text-left px-2 py-1.5">
                    {{ $value }}
                </button>
            @endforeach
        </div>
    @endif

    {{-- Display current value --}}
    <div x-on:click="
        showSearchField = true;
        setTimeout(() => $refs.searchable_value_{{ $attributes->get('name') }}_input.focus(), 100);
    ">
        @if(empty($attributes->get('value')))
            <div class="mt-1 px-2 py-1 text-slate-800">
                - None selected -
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