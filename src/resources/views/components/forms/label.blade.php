@props(['for' => '', 'label' => 'No label set'])

<label
    for="{{ $for }}"
    {{ $attributes->merge([
        'class' => 'block text-sm font-bold text-slate-800 dark:text-slate-300'
    ]) }}
>

    {!! $label !!}
</label>
