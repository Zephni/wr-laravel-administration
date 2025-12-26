@props(['options' => [], 'label' => null])

@php
    // Set id from name if unset
    $id = empty($attributes->get('id')) ? 'wrinput-'.$attributes->get('name') : $attributes->get('id');
@endphp

{{-- We add an extra wrapper div here as Quill seems to not contain properly here --}}
<div class="w-full">
    <div wire:ignore class="{{ $options['containerClass'] ?? 'w-full flex-1 md:flex-auto' }}">

        @if(!empty($label))
            @themeComponent('forms.label', [
                'label' => $label,
                'attributes' => Arr::toAttributeBag([
                    'for' => $id.'-label',
                    'class' => ($options['labelClass'] ?? ''),
                    'style' => 'margin-bottom: 5px;'
                ])
            ])
        @endif

        <{{ $WRLAHelper::getWysiwygEditorHTMLElement() }} {{ $attributes->merge([
            'id' => $id,
            'class' => 'wrla_wysiwyg '.$WRLAHelper::getWysiwygHandler()->getCurrentConfiguration('class', ''),
            'style' => 'white-space: pre-wrap; word-wrap: break-word;'
        ])->except('value')  }}>@if($WRLAHelper::getWysiwygHandler()->shouldEscapeContent()){{ $attributes->get('value') }}@else{!! $attributes->get('value') !!}@endif
        </{{ $WRLAHelper::getWysiwygEditorHTMLElement() }}>

        {{-- Field notes (if options has notes key) --}}
        @if(!empty($options['notes']))
            @themeComponent('forms.field-notes', ['notes' => $options['notes']])
        @endif

        @error($attributes->get('name'))
            @themeComponent('alert', ['type' => 'error', 'message' => $message, 'class' => 'mt-2'])
        @enderror

        @once
        <style>
            
        </style>
        @endonce

    </div>
</div>