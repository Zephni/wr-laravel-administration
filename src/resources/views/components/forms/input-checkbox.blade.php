@props(['attr' => [], 'label' => null, 'id' => '', 'name', 'value' => '1', 'checked' => false, 'error' => null])

@php
    // Set id from name if unset
    $id = empty($id) ? 'wrinput-'.$name : $id;
@endphp

<div class="flex gap-3 items-center">
    <input
        @if($checked)
            checked
        @endif
        {{ $attributes->merge([
            'id' => $id,
            'type' => 'checkbox',
            'name' => $name,
            'value' => $value,
            'class' => 'appearance-none checked:appearance-auto w-4 h-4 text-slate-900 border-2 border-slate-300
                dark:border-slate-600 accent-slate-200 dark:accent-slate-900 bg-slate-200 dark:bg-slate-900 focus:outline-none
                focus:ring-1 focus:ring-primary-500 dark:focus:ring-primary-500 rounded-md shadow-sm'
        ])->merge($attr) }} />

    @if(!empty($label))
        <label for="{{ $id }}" class="text-sm font-semibold text-slate-800 dark:text-slate-300">
            {{ $label }}
        </label>
    @endif
</div>

@if(!empty($error))
    <p class="text-sm text-red-500 mt-2">{{ $error }}</p>
@endif
