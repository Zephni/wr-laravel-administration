@props(['options' => [], 'label' => null, 'fieldName' => 'multi_field', 'items' => [], 'maxFormGroups' => 0, 'addFormGroupLabel' => 'Add row', 'emptyText' => 'No rows yet.', 'layout' => 'row', 'formGroups' => [], 'existingImages' => []])

<div class="{{ $options['containerClass'] ?? 'w-full' }}">

    @if(!empty($label))
        @themeComponent('forms.label', [
            'label' => $label,
            'attributes' => Arr::toAttributeBag([
                'class' => $options['labelClass'] ?? ''
            ])
        ])
    @endif

    @livewire('wrla.multi-upload-fields.multi-form-groups', [
        'fieldName'         => $fieldName,
        'items'             => $items,
        'maxFormGroups'     => $maxFormGroups,
        'addFormGroupLabel' => $addFormGroupLabel,
        'emptyText'         => $emptyText,
        'layout'            => $layout,
        'formGroups'        => $formGroups,
        'existingImages'    => $existingImages,
    ])

</div>
