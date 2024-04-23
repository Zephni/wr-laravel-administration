@props(['attr' => [], 'id' => '', 'name' => '', 'text' => 'Submit', 'type' => 'button', 'icon' => '', 'size' => 'large', 'error' => null])

@php
    $name = empty($name) ? 'wrinput-'.rand(1000, 9999) : $name;

    // Set id from name if unset
    $id = empty($id) ? 'wrinput-'.$name : $id;
@endphp

<a {{ $attributes->merge([
    'id' => $id,
    'type' => $type,
    'name' => $name,
    'class' => 'block '.($size == 'large' ? 'w-full px-3 py-2' : 'px-2').' font-semibold text-white dark:text-slate-900
        border border-slate-400 dark:border-slate-500 bg-primary-500 dark:bg-primary-500 rounded-md shadow-sm'
])->merge($attr) }}>
    @if(!empty($icon))
        <i class="{{ $icon }} text-white dark:text-slate-900 mr-1"></i>
    @endif
    <span style="position: relative; {{ $size == 'small' ? 'top: 1px;' : '' }}">{!! $text !!}</span>
</a>

@if(!empty($error))
    <p class="text-sm text-red-500 mt-2">{{ $error }}</p>
@endif
