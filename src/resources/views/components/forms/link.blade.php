@props(['text' => 'Not set', 'href' => '#', 'icon' => ''])

<a href="{{ $href }}"
    {{ $attributes->merge([
        'class' => "no-underline"
    ]) }}>
    @if(!empty($icon))
        <i class="{{ $icon }} mr-1"></i>
    @endif
    <span class="inline underline">{!! $text !!}</span>
</a>
