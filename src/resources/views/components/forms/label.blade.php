@props(['attr' => [], 'id' => '', 'for' => '', 'label' => 'No label set'])

@php
    // Set id from name if unset
    $id = empty($id) ? 'wrinput-'.$for : $id;
@endphp

<label
    for="{{ $id }}"
    {{ $attributes->merge([
        'class' => 'block text-sm font-bold text-slate-800 dark:text-slate-300'
    ])->merge($attr) }}
>

    {{ $label }}
</label>
