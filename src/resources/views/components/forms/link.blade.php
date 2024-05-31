@props(['text' => 'Not set', 'href' => '#', 'icon' => ''])

<a href="{{ $href }}"
    {{ $attributes->merge([
        'class' => ""
    ]) }}>
    @if(!empty($icon))
        <i class="{{ $icon }} mr-1"></i>
    @endif
    <div class="inline">{!! $text !!}</div>
</a>