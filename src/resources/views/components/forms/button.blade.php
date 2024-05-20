@props(['text' => 'Submit', 'icon' => '', 'size' => 'small', 'color' => 'primary', 'error' => null])

@php
    // Set id from name if unset
    $id = empty($attributes->get('id')) ? 'wrinput-'.$attributes->get('name') : $attributes->get('id');
    $name = empty($attributes->get('name')) ? 'wrinput-'.rand(1000, 9999) : $attributes->get('name');

    // Set size classes
    if($size == 'large') $sizeClasses = 'w-full px-4 py-2';
    else if($size == 'medium') $sizeClasses = 'w-fit px-4 py-1';
    else if($size == 'small') $sizeClasses = 'w-fit px-2';

    // Set colour classes
    if($color == 'primary') $colorClasses = 'bg-primary-500 hover:bg-primary-600 border-slate-400 dark:border-slate-400';
    else if($color == 'danger') $colorClasses = 'bg-rose-500 hover:bg-rose-600 border-rose-700';
@endphp

@if(empty($href))
<button
@else
<a href="{{ $href }}"
@endif
    {{ $attributes->merge([
        'class' => "flex justify-center items-center gap-1 whitespace-nowrap $sizeClasses font-semibold text-white dark:text-slate-900 border $colorClasses rounded-md shadow-sm whitespace-nowrap"
    ]) }}>
    @if(!empty($icon))
        <i class="{{ $icon }} text-white dark:text-slate-900 mr-1"></i>
    @endif
    <div class="inline">{!! $text !!}</div>
@if(empty($href))
</button>
@else
</a>
@endif

@if(!empty($error))
    <p class="text-sm text-red-500 mt-2">{{ $error }}</p>
@endif