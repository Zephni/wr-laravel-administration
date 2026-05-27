@props(['options' => [], 'label' => null, 'fieldName' => 'images', 'maxImages' => 5, 'validation' => 'image|mimes:jpeg,png,jpg|max:10240'])

<div class="{{ $options['containerClass'] ?? 'w-full' }}">

    @if(!empty($label))
        @themeComponent('forms.label', [
            'label' => $label,
            'attributes' => Arr::toAttributeBag([
                'class' => $options['labelClass'] ?? ''
            ])
        ])
    @endif

    @livewire('wrla.multi-upload-fields.multi-image-uploads', [
        'fieldName' => $fieldName,
        'maxImages' => $maxImages,
        'validation' => $validation,
    ])

</div>
