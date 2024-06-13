@props([
    'src' => $WRLAHelper::getCurrentThemeData('no_image_src'),
    'class' => '',
    'imageClass' => '',
    'width' => 'w-full',
    'rounded' => 'none',
    'aspect' => null
])

@php
    $height = '0px';

    if($aspect !== null) {
        // Use aspect ratio to calculate padding-bottom
        $aspect = explode(':', $aspect);
        $paddingBottom = ($aspect[1] / $aspect[0]) * 100;
    } else {
        $height = 'auto';
        $paddingBottom = '0px';
    }

    if(empty($rounded) || $rounded == false) {
        $rounded = 'none';
    } else if ($rounded == true) {
        $rounded = 'full';
    }
@endphp

<div
    class="relative overflow-hidden {{ $width }} {{ $height }} rounded-{{ $rounded }} {{ $class }}"
    style="padding-bottom: {{ $paddingBottom }}%;">
    <img src="{{ $src }}" alt="Image" class="w-full h-full absolute top-0 left-0 {{ $imageClass ?? 'object-cover' }}" />
</div>