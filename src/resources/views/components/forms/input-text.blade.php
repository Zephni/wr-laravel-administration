@props(['label' => null, 'id' => '', 'name', 'value' => '', 'type' => 'text', 'placeholder' => '', 'required' => false, 'autofocus' => false, 'readonly' => false])

@php
    // Set id from name if unset
    $id = empty($id) ? 'wrinput-'.$name : $id;
@endphp

@if(!empty($label))
    <label for="{{ $id }}" class="block text-sm font-medium text-slate-800 dark:text-slate-400">
        {{ $label }}
    </label>
@endif

<input {{ $attributes->merge([
    'id' => $id,
    'type' => $type,
    'name' => $name,
    'value' => old($name, $value),
    'placeholder' => $placeholder,
    'required' => $required,
    'autofocus' => $autofocus,
    'readonly' => $readonly,
    'class' => 'block w-full mt-2 px-3 py-1 border border-slate-400 dark:border-slate-600 bg-slate-200 dark:bg-slate-900
        focus:outline-none focus:ring-1 focus:ring-primary-500 dark:focus:ring-primary-500 rounded-md shadow-sm placeholder-slate-400 dark:placeholder-slate-600'
]) }} />

@error($name)
    <p class="text-sm text-red-500 mt-2">{{ $message }}</p>
@enderror
