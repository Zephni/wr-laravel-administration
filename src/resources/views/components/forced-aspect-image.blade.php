@props([
    'src' => 'https://via.placeholder.com/150',
    'class' => '',
    'imageClass' => '',
    'width' => 'w-full',
    'rounded' => 'none',
    'aspect' => '1:1'
])

@php
    // Use aspect ratio to calculate padding-bottom
    $aspect = explode(':', $aspect);
    $paddingBottom = ($aspect[1] / $aspect[0]) * 100;
@endphp

<div
    class="h-0 relative overflow-hidden {{ $width }} rounded-{{ $rounded }} {{ $class }}"
    style="padding-bottom: {{ $paddingBottom }}%;">
    <img src="{{ $src }}" alt="Image" class="object-cover w-full h-full absolute top-0 left-0 {{ $imageClass }}" />
</div>