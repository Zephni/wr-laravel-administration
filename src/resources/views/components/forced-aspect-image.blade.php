@props([
    'src' => $WRLAHelper::getCurrentThemeData('no_image_src'),
    'aspect' => 'auto',
    'class' => '',
    'style' => '',
    'originalSrc' => null,
    'hideIfEmpty' => false,
    'objectFit' => 'cover',
])

@php
    // Map friendly alias 'fit' to the CSS object-fit value 'contain'.
    $resolvedObjectFit = $objectFit === 'fit' ? 'contain' : $objectFit;
@endphp

@if(!$hideIfEmpty || !empty($src))
    <img
        src="{{ $src }}"
        @if(isset($originalSrc)) ogimage="{{ $originalSrc }}" @endif
        alt="Image"
        style="width: {{ $attributes->get('width', '100%') }}; aspect-ratio: {{ $aspect }};"
        {{ $attributes->merge([
            'class' => "object-{$resolvedObjectFit} border border-slate-400 $class",
            'style' => $style
        ]) }}
    />
@endif
