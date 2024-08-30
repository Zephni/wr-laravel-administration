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
    if($color == 'primary') $colorClasses = 'bg-teal-600 hover:bg-teal-700 border-teal-500 shadow-lg shadow-slate-400 dark:shadow-slate-700 text-white dark:text-gray-800';
    else if($color == 'secondary') $colorClasses = 'bg-slate-600 dark:bg-slate-500 hover:bg-slate-700 dark:hover:bg-slate-600 border-slate-700 shadow-lg shadow-slate-400 dark:shadow-slate-700 text-white dark:text-gray-800';
    else if($color == 'cyan') $colorClasses = 'bg-cyan-500 hover:bg-cyan-600 border-cyan-500 shadow-lg shadow-slate-400 dark:shadow-slate-700 text-white dark:text-gray-800';
    else if($color == 'indigo') $colorClasses = 'bg-indigo-500 hover:bg-indigo-600 border-indigo-500 shadow-lg shadow-slate-400 dark:shadow-slate-700 text-white dark:text-gray-800';
    else if($color == 'teal') $colorClasses = 'bg-teal-600 hover:bg-teal-700 border-teal-600 shadow-lg shadow-slate-400 dark:shadow-slate-700 text-white dark:text-gray-800';
    else if($color == 'danger') $colorClasses = 'bg-rose-500 hover:bg-rose-600 border-rose-500 shadow-lg shadow-slate-400 dark:shadow-slate-700 text-white dark:text-gray-800';
    else if($color == 'grey') $colorClasses = 'bg-gray-500 hover:bg-gray-600 border-gray-500 text-gray-300 shadow-lg shadow-slate-400 dark:shadow-slate-700 text-white dark:text-gray-800';
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