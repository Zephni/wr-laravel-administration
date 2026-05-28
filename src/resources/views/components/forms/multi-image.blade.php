@props(['options' => [], 'label' => null, 'fieldName' => 'images', 'maxImages' => 5, 'validation' => 'image|mimes:jpeg,png,jpg|max:10240', 'existingImages' => [], 'parseError' => null])

<div class="{{ $options['containerClass'] ?? 'w-full' }}">

    @if(!empty($label))
        @themeComponent('forms.label', [
            'label' => $label,
            'attributes' => Arr::toAttributeBag([
                'class' => $options['labelClass'] ?? ''
            ])
        ])
    @endif

    @if(!empty($parseError))
        <div class="mt-1 rounded border border-red-400 bg-red-50 px-3 py-2 text-sm text-red-700">
            {{ $parseError }}
        </div>
    @endif

    @livewire('wrla.multi-upload-fields.multi-image-uploads', [
        'fieldName'      => $fieldName,
        'maxImages'      => $maxImages,
        'validation'     => $validation,
        'existingImages' => $existingImages,
    ])

</div>
