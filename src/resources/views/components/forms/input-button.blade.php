@props(['id' => '', 'name' => '', 'text' => 'Submit', 'type' => 'button', 'error' => null])

@php
    $name = empty($name) ? 'wrinput-'.rand(1000, 9999) : $name;

    // Set id from name if unset
    $id = empty($id) ? 'wrinput-'.$name : $id;
@endphp

<button {{ $attributes->merge([
    'id' => $id,
    'type' => $type,
    'name' => $name,
    'class' => 'block w-full mt-2 px-3 py-2 font-bold text-white dark:text-slate-900 border border-slate-400
        dark:border-slate-500 bg-primary-500 dark:bg-primary-500 rounded-md shadow-sm'
]) }}>
    {{ $text }}
</button>

@if(!empty($error))
    <p class="text-sm text-red-500 mt-2">{{ $error }}</p>
@endif
