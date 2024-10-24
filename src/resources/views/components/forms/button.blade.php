@props(['text' => 'Submit', 'icon' => '', 'size' => 'small', 'color' => 'primary', 'error' => null])

@php
    // Set id from name if unset
    $id = empty($attributes->get('id')) ? 'wrinput-'.$attributes->get('name') : $attributes->get('id');
    $name = empty($attributes->get('name')) ? 'wrinput-'.rand(1000, 9999) : $attributes->get('name');

    // Set size classes
    if($size == 'large') $sizeClasses = 'w-full px-4 py-2';
    else if($size == 'medium') $sizeClasses = 'w-fit px-4 py-1';
    else if($size == 'small') $sizeClasses = 'w-fit px-2 text-[14px]';

    // Set colour classes
    if($color == 'primary') $colorClasses = 'bg-teal-600 dark:bg-teal-800 dark:text-slate-200 hover:brightness-110 border-teal-500 dark:border-teal-600 shadow-lg shadow-slate-400 dark:shadow-slate-700 text-white dark:text-gray-800';
    else if($color == 'muted') $colorClasses = 'bg-slate-500 dark:bg-slate-600 dark:text-slate-200 hover:brightness-110 border-slate-700 dark:border-slate-500 shadow-lg shadow-slate-400 dark:shadow-slate-700 text-white dark:text-gray-800';
    else if($color == 'danger') $colorClasses = 'bg-rose-500 dark:bg-rose-700 dark:text-rose-100 hover:brightness-110 border-rose-500 shadow-lg shadow-slate-400 dark:shadow-slate-700 text-white dark:text-gray-800';
@endphp

@if(empty($href))
<button
@else
<a href="{{ $href }}"
@endif
    {{ $attributes->merge([
        'class' => "flex justify-center items-center gap-1 whitespace-nowrap $sizeClasses font-semibold border $colorClasses rounded-md shadow-sm whitespace-nowrap"
    ]) }}>
    @if(!empty($icon))
        <i class="{{ $icon }} text-[13px] mr-1"></i>
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