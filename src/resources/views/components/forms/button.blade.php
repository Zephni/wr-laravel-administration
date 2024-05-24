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
    if($color == 'primary') $colorClasses = 'bg-primary-500 hover:bg-primary-600 border-primary-500 shadow-lg shadow-slate-400';
    else if($color == 'cyan') $colorClasses = 'bg-cyan-500 hover:bg-cyan-600 border-cyan-500 shadow-lg shadow-slate-400';
    else if($color == 'green') $colorClasses = 'bg-green-500 hover:bg-green-600 border-green-500 shadow-lg shadow-slate-400';
    else if($color == 'blue') $colorClasses = 'bg-blue-500 hover:bg-blue-600 border-blue-500 shadow-lg shadow-slate-400';
    else if($color == 'indigo') $colorClasses = 'bg-indigo-500 hover:bg-indigo-600 border-indigo-500 shadow-lg shadow-slate-400';
    else if($color == 'purple') $colorClasses = 'bg-purple-500 hover:bg-purple-600 border-purple-500 shadow-lg shadow-slate-400';
    else if($color == 'pink') $colorClasses = 'bg-pink-500 hover:bg-pink-600 border-pink-500 shadow-lg shadow-slate-400'; 
    else if($color == 'danger') $colorClasses = 'bg-rose-500 hover:bg-rose-600 border-rose-500 shadow-lg shadow-slate-400';
    else if($color == 'grey') $colorClasses = 'bg-gray-500 hover:bg-gray-600 border-gray-500 text-gray-300 shadow-lg shadow-slate-400';
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