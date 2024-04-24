@props(['attr' => [], 'id' => '', 'name' => '', 'text' => 'Submit', 'type' => 'button', 'href' => null, 'icon' => '', 'size' => 'small', 'color' => 'primary', 'error' => null])

@php
    $name = empty($name) ? 'wrinput-'.rand(1000, 9999) : $name;

    // Set id from name if unset
    $id = empty($id) ? 'wrinput-'.$name : $id;

    // Set size classes
    if($size == 'large') $sizeClasses = 'w-full px-4 py-2';
    else if($size == 'medium') $sizeClasses = 'px-4 py-1';
    else if($size == 'small') $sizeClasses = 'px-2';

    // Set colour classes
    if($color == 'primary') $colorClasses = 'bg-primary-500 hover:bg-primary-600 border-slate-400 dark:border-slate-400';
    else if($color == 'danger') $colorClasses = 'bg-rose-500 hover:bg-rose-600 border-rose-700'
@endphp

@if(empty($href))
<button
@else
<a href="{{ $href }}"
@endif
    {{ $attributes->merge([
        'id' => $id,
        'type' => $type,
        'name' => $name,
        'class' =>
            "block $sizeClasses font-semibold text-white dark:text-slate-900 border $colorClasses rounded-md shadow-sm"
    ])->merge($attr) }}>
    @if(!empty($icon))
        <i class="{{ $icon }} text-white dark:text-slate-900 mr-1"></i>
    @endif
    {!! $text !!}
@if(empty($href))
</button>
@else
</a>
@endif

@if(!empty($error))
    <p class="text-sm text-red-500 mt-2">{{ $error }}</p>
@endif
