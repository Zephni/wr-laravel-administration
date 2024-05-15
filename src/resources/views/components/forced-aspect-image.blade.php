@props([
    'src' => 'https://via.placeholder.com/150',
    'class' => '',
    'imageClass' => '',
    'width' => 'w-full',
    'rounded' => 'none',
    'aspect' => '1:1'
])

@php
    $height = '0px';

    if($aspect != null) {
        // Use aspect ratio to calculate padding-bottom
        $aspect = explode(':', $aspect);
        $paddingBottom = ($aspect[1] / $aspect[0]) * 100;
    } else {
        $height = 'auto';
        $paddingBottom = '0px';
    }

    if($rounded == false || empty($rounded)) {
        $rounded = 'none';
    } else if ($rounded == true) {
        $rounded = 'full';
    }
@endphp

<div
    class="relative overflow-hidden {{ $width }} {{ $height }} rounded-{{ $rounded }} {{ $class }}"
    style="padding-bottom: {{ $paddingBottom }}%;">
    <img src="{{ $src }}" alt="Image" class="object-cover w-full h-full absolute top-0 left-0 {{ $imageClass }}" />
</div>