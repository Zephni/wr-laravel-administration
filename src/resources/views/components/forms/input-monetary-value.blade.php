@props(['options' => [], 'label' => null])

@php
    // Set id from name if unset, and value
    $id = empty($attributes->get('id')) ? 'wrinput-'.$attributes->get('name') : $attributes->get('id');
@endphp

<div class="{{ $options['containerClass'] ?? 'w-full flex-1 md:flex-auto' }}">

@if(!empty($label))
    {!! view($WRLAHelper::getViewPath('components.forms.label'), [
        'label' => $label,
        'attributes' => new \Illuminate\View\ComponentAttributeBag([
            'id' => $id.'-label',
            'class' => $options['labelClass'] ?? ''
        ])
    ])->render() !!}
@endif

<div class="flex gap-0 items-stretch overflow-hidden w-full mt-2 py-0 border border-slate-400 dark:border-slate-500 bg-slate-200 dark:bg-slate-900
focus:outline-none focus:ring-1 focus:ring-primary-500 dark:focus:ring-primary-500 rounded-md shadow-sm placeholder-slate-400 dark:placeholder-slate-600">
    <div class="flex items-center pl-2 pr-2 bg-notes-200 dark:bg-notes-800 border-r border-slate-300 dark:border-slate-600">
        <span class="text-slate-700 dark:text-slate-300">{{ $options['currencySymbol'] }}</span>
    </div>
    <input {{ $attributes->merge([
        'step' => ".01",
        'class' => 'w-full px-1 py-1 bg-transparent focus:outline-none ring-inset focus:ring-1 focus:ring-primary-500 dark:focus:ring-primary-500 rounded-md shadow-sm placeholder-slate-400 dark:placeholder-slate-600',
        'onchange' => 'this.value = parseFloat(this.value).toFixed('.$options['decimalPlaces'].')',
    ]) }} />
</div>

{{-- Field notes (if options has notes key) --}}
@if(!empty($options['notes']))
    @themeComponent('forms.field-notes', ['notes' => $options['notes']])
@endif

@error($attributes->get('name'))
    @themeComponent('alert', ['type' => 'error', 'message' => $message, 'class' => 'mt-2'])
@enderror

</div>