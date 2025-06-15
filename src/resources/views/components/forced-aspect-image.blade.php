@props([
    'src' => $WRLAHelper::getCurrentThemeData('no_image_src'),
    'aspect' => 'auto',
    'class' => '',
    'style' => '',
    'originalSrc' => null,
])

<img
    src="{{ $src }}"
    @if(isset($originalSrc)) ogimage="{{ $originalSrc }}" @endif
    alt="Image"
    style="width: {{ $attributes->get('width', '100%') }}; aspect-ratio: {{ $aspect }};"
    {{ $attributes->merge([
        'class' => "object-cover border border-slate-400 $class",
        'style' => $style
    ]) }}
/>
